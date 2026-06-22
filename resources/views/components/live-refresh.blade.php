@props(['intervalMs'])

{{-- Drives auto-refresh for a comment-style page (task page, project overview):
     every interval, while "Live updates" is on, it dispatches the `live-refresh`
     Livewire event that the page header, comments and activity feed listen for.
     Skips a tick whenever the user is focused in a field or rich-text editor, so
     an in-progress comment/description draft is never morphed away (the editor
     syncs deferred, so unsaved keystrokes would be lost by a refresh).
     wire:ignore keeps the timer alive across re-renders. --}}
<div
    wire:ignore
    data-test="live-refresh"
    x-data="{
        timer: null,
        init() {
            this.timer = setInterval(() => {
                if (! this.$wire.liveUpdates) return;
                const el = document.activeElement;
                if (el && (el.isContentEditable || el.matches('input, textarea, select'))) return;
                this.$wire.dispatch('live-refresh');
            }, {{ (int) $intervalMs }});
        },
        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
            }
        },
    }"
></div>
