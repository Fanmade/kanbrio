import Sortable from 'sortablejs';

/**
 * Walk the siblings of a card in the given direction and return the id of the
 * nearest neighbouring task card, or null if there is none.
 */
function adjacentTaskId(card, direction) {
    let sibling = card[direction];

    while (sibling && !sibling.hasAttribute('data-task-id')) {
        sibling = sibling[direction];
    }

    return sibling ? parseInt(sibling.getAttribute('data-task-id'), 10) : null;
}

/**
 * Board drag-and-drop.
 *
 * Each column's task list (and each empty column's drop zone) registers an
 * `x-data="kanbanList"` Alpine component backed by SortableJS. All lists share
 * the `kanban` group so cards can be dragged within and across columns with
 * smooth FLIP animation, touch support and clear drop affordances. On drop the
 * card's status and its new neighbours are sent to the server via
 * `$wire.reorderTask`, which persists the order; Livewire's morph then
 * reconciles the authoritative result.
 *
 * Keyboard moves are handled separately by the per-card "Move to" menu in the
 * Blade view, since SortableJS does not provide keyboard interaction.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('kanbanList', () => ({
        sortable: null,

        init() {
            this.sortable = Sortable.create(this.$el, {
                group: 'kanban',
                draggable: '[data-task-card]',
                // Let interactive controls (the "Move to" menu) be clicked, not dragged.
                filter: '[data-no-drag]',
                preventOnFilter: false,
                animation: 160,
                easing: 'cubic-bezier(0.2, 0, 0, 1)',
                ghostClass: 'kanban-ghost',
                chosenClass: 'kanban-chosen',
                dragClass: 'kanban-drag',
                fallbackOnBody: true,
                // Hold briefly before dragging on touch so the list can still scroll.
                delay: 120,
                delayOnTouchOnly: true,
                touchStartThreshold: 6,
                onStart: () => document.body.classList.add('kanban-dragging'),
                onEnd: (event) => {
                    document.body.classList.remove('kanban-dragging');

                    const card = event.item;
                    const taskId = parseInt(card.getAttribute('data-task-id'), 10);
                    const toStatus = event.to.getAttribute('data-status');

                    if (!taskId || !toStatus) {
                        return;
                    }

                    this.$wire.reorderTask(
                        taskId,
                        toStatus,
                        adjacentTaskId(card, 'previousElementSibling'),
                        adjacentTaskId(card, 'nextElementSibling'),
                    );
                },
            });
        },

        destroy() {
            this.sortable?.destroy();
            this.sortable = null;
        },
    }));
});
