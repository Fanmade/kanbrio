<?php

namespace App\Concerns;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use InvalidArgumentException;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Collection;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * Lets a task nest under another task to form a tree. Builds on the
 * adjacency-list package for the recursive `ancestors` / `descendants` /
 * `children` / `parent` relations and guards every parent assignment against
 * self-parenting, cycles and the configured maximum nesting depth.
 *
 * @phpstan-require-extends Model
 */
trait Nestable
{
    use HasRecursiveRelationships;

    /**
     * Validate the parent whenever it is set or changed, on create and on
     * re-parent. A null parent (a root task) is always allowed.
     */
    public static function bootNestable(): void
    {
        static::saving(static function (Task $task): void {
            if ($task->parent_id !== null && $task->isDirty('parent_id')) {
                $task->assertValidParent();
            }
        });
    }

    /**
     * The task's ancestors ordered from the root down to the immediate parent.
     * Reads the loaded `ancestors` relation, sorted by the depth the
     * adjacency-list package records under {@see getDepthName()} — the single
     * source of the breadcrumb ordering rendered by both the task header and the
     * {@see \resources\views\components\task-breadcrumb.blade.php} component.
     *
     * @return SupportCollection<int, static>
     */
    public function orderedAncestors(): SupportCollection
    {
        return $this->ancestors->sortBy($this->getDepthName())->values();
    }

    /**
     * This task's direct children, derived from the already-loaded `descendants`
     *  relation, so rendering a card does not trigger a `children` query per task.
     * Optionally drops archived children; ordered by task number.
     *
     * @return SupportCollection<int, static>
     */
    public function loadedChildren(bool $includeArchived = true): SupportCollection
    {
        return $this->descendants
            ->where('parent_id', $this->getKey())
            ->when(! $includeArchived, static fn (Collection $children): Collection => $children
                ->reject(static fn (Task $child): bool => $child->isArchived()))
            ->sortBy('task_number')
            ->values();
    }

    /**
     * The task's level in the tree, counting the root as level 1.
     */
    public function nestingDepth(): int
    {
        return $this->ancestors()->count() + 1;
    }

    /**
     * The height of the subtree rooted at this task, counting the task itself
     * as level 1 (a leaf task has a height of 1).
     */
    public function subtreeHeight(): int
    {
        if (! $this->exists) {
            return 1;
        }

        $deepest = $this->descendants()->get()->max($this->getDepthName());

        return $deepest === null ? 1 : (int) $deepest + 1;
    }

    /**
     * Ensure the pending `parent_id` is a real task that does not point at the
     * task itself, close a cycle, or push the subtree past the depth limit.
     *
     * @throws InvalidArgumentException
     */
    protected function assertValidParent(): void
    {
        if ($this->parent_id === $this->getKey()) {
            throw new InvalidArgumentException('A task cannot be its own parent.');
        }

        $parent = Task::find($this->parent_id);

        if ($parent === null) {
            throw new InvalidArgumentException('The parent task does not exist.');
        }

        // Moving a task under one of its own descendants would close a cycle.
        if ($this->exists && $this->descendants()->whereKey($parent->getKey())->exists()) {
            throw new InvalidArgumentException('A task cannot be nested under its own descendant.');
        }

        $maxDepth = (int) config('kanvigo.tasks.max_depth');

        if ($parent->nestingDepth() + $this->subtreeHeight() > $maxDepth) {
            throw new InvalidArgumentException(
                "A task cannot be nested deeper than {$maxDepth} levels."
            );
        }
    }
}
