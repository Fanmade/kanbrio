@props(['intervalMs'])

{{-- Auto-refreshes the board on a timer while "Live updates" is on, but never
     while a card is being dragged (SortableJS sets body.kanban-dragging) — a
     refresh mid-drag would morph the DOM out from under the drag. The guard is
     reactive: $wire.liveUpdates reflects the toggle without a remount. --}}
<div
    data-test="board-auto-refresh"
    x-data="{
        timer: null,
        init() {
            this.timer = setInterval(() => {
                if (! this.$wire.liveUpdates) return;
                if (document.body.classList.contains('kanban-dragging')) return;
                this.$wire.$refresh();
            }, {{ (int) $intervalMs }});
        },
        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
            }
        },
    }"
    {{ $attributes }}
>
    {{ $slot }}
</div>
