<?php

namespace App\Concerns;

use App\Enums\Status;
use App\Models\Task;
use Illuminate\Support\Collection;

trait BuildsKanbanColumns
{
    /**
     * Split a set of tasks into board columns by status. Within each column the
     * tasks keep their manual board order (the `position` set by drag-and-drop),
     * with the id as a stable tie-breaker. Story context travels with each
     * task's reference, so the board is no longer grouped by story.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array{status: Status, tasks: Collection<int, Task>}>
     */
    protected function buildColumns(Collection $tasks): array
    {
        $columns = [];

        foreach (Status::columns() as $status) {
            $columns[] = [
                'status' => $status,
                'tasks' => $tasks->where('status', $status)
                    ->sortBy([
                        ['position', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->values(),
            ];
        }

        return $columns;
    }

    /**
     * Authorize and apply a status change to a task, recording the activity.
     * Moved tasks land at the bottom of the destination column.
     */
    protected function applyTaskMove(Task $task, string $status): void
    {
        $this->authorize('updateStatus', $task);

        $new = Status::tryFrom($status);

        if ($new === null || $task->status === $new) {
            return;
        }

        $old = $task->status;
        $task->status = $new;
        $task->position = (Task::where('status', $new)->max('position') ?? 0) + 1;
        $task->save();

        $task->recordActivity('status_changed', 'status', $old->value, $new->value);
    }

    /**
     * Authorize and archive a task, hiding it from the board.
     */
    protected function applyTaskArchive(Task $task): void
    {
        $this->authorize('updateStatus', $task);

        $task->archive();
    }

    /**
     * Authorize and restore a task from the archive.
     */
    protected function applyTaskUnarchive(Task $task): void
    {
        $this->authorize('updateStatus', $task);

        $task->unarchive();
    }

    /**
     * Authorize and apply a drag-and-drop placement: move the task to the given
     * status (if changed, recording the activity) and reposition it between its
     * new neighbours — the cards immediately above ($beforeId) and below
     * ($afterId) it in the destination column.
     */
    protected function applyTaskReorder(Task $task, string $status, ?int $beforeId, ?int $afterId): void
    {
        $this->authorize('updateStatus', $task);

        $new = Status::tryFrom($status);

        if ($new === null) {
            return;
        }

        $old = $task->status;
        $statusChanged = $old !== $new;

        $task->status = $new;
        $task->position = $this->positionBetween($beforeId, $afterId);
        $task->save();

        if ($statusChanged) {
            $task->recordActivity('status_changed', 'status', $old->value, $new->value);
        }
    }

    /**
     * The position value that places a card between the given neighbours. Uses
     * the midpoint of the two surrounding positions so only the moved card is
     * rewritten — this stays correct on both the project and the cross-project
     * global board, since the neighbours are concrete tasks shared by both.
     */
    protected function positionBetween(?int $beforeId, ?int $afterId): float
    {
        $before = $beforeId !== null ? Task::find($beforeId)?->position : null;
        $after = $afterId !== null ? Task::find($afterId)?->position : null;

        return match (true) {
            $before !== null && $after !== null => ($before + $after) / 2,
            $before !== null => $before + 1,
            $after !== null => $after - 1,
            default => 0.0,
        };
    }
}
