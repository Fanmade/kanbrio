<?php

namespace App\Concerns;

use App\Contracts\Dependable;
use App\Models\Dependency;
use App\Models\Story;
use App\Models\Task;
use App\Support\ReferenceResolver;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Computed;

/**
 * Adds dependency management to a Story or Task view component: listing an
 * item's blockers and the items it blocks, and adding or removing links by
 * reference (e.g. "ABC1-3").
 */
trait ManagesDependencies
{
    public string $dependencyReference = '';

    public string $dependencyDirection = 'blocked_by';

    /**
     * The story or task whose dependencies are being managed.
     */
    abstract protected function dependable(): Story|Task;

    /**
     * The dependency links where the viewed item is blocked, with the blocking
     * item (and the relations needed to render its reference) eager-loaded.
     *
     * @return Collection<int, Dependency>
     */
    #[Computed]
    public function blockerLinks(): Collection
    {
        return $this->dependable()->dependencyLinks()->with('blocker')->get();
    }

    /**
     * The dependency links where the viewed item is the blocker, with the blocked
     * item eager-loaded.
     *
     * @return Collection<int, Dependency>
     */
    #[Computed]
    public function blockingLinks(): Collection
    {
        return $this->dependable()->dependentLinks()->with('dependent')->get();
    }

    /**
     * The blocker links whose blocking item still exists, ready to render.
     *
     * @return Collection<int, Dependency>
     */
    #[Computed]
    public function presentBlockerLinks(): Collection
    {
        return $this->blockerLinks()->filter(static fn (Dependency $link): bool => $link->blocker !== null)->values();
    }

    /**
     * The blocking links whose dependent item still exists, ready to render.
     *
     * @return Collection<int, Dependency>
     */
    #[Computed]
    public function presentBlockingLinks(): Collection
    {
        return $this->blockingLinks()->filter(static fn (Dependency $link): bool => $link->dependent !== null)->values();
    }

    /**
     * Whether the viewed item has an unfinished blocker.
     */
    #[Computed]
    public function isBlocked(): bool
    {
        return $this->blockerLinks()->contains(
            static fn (Dependency $link): bool => $link->blocker instanceof Dependable && ! $link->blocker->isComplete()
        );
    }

    /**
     * Whether the current user may add or remove this item's dependencies.
     */
    #[Computed]
    public function canManageDependencies(): bool
    {
        return Gate::allows('update', $this->dependable());
    }

    /**
     * Link the referenced item as a blocker of, or blocked by, the viewed item.
     */
    public function addDependency(): void
    {
        $item = $this->dependable();
        $this->authorize('update', $item);

        $this->validate([
            'dependencyReference' => ['required', 'string'],
            'dependencyDirection' => ['required', 'in:blocked_by,blocks'],
        ]);

        $related = ReferenceResolver::commentable(trim($this->dependencyReference));

        if (! $related instanceof Story && ! $related instanceof Task) {
            $this->addError('dependencyReference', __('No story or task found for that reference.'));

            return;
        }

        if (Gate::denies('view', $related)) {
            $this->addError('dependencyReference', __('You do not have access to that item.'));

            return;
        }

        // "blocked_by": the viewed item depends on the related one. "blocks":
        // the related item depends on the viewed one.
        [$dependent, $blocker] = $this->dependencyDirection === 'blocks'
            ? [$related, $item]
            : [$item, $related];

        try {
            $dependent->addBlocker($blocker);
        } catch (InvalidArgumentException) {
            $this->addError('dependencyReference', __('That would make an item depend on itself or create a cycle.'));

            return;
        }

        $item->recordActivity('dependency_changed', 'dependencies');

        $this->reset('dependencyReference');
        unset($this->blockerLinks, $this->blockingLinks, $this->presentBlockerLinks, $this->presentBlockingLinks, $this->isBlocked);

        Flux::toast(variant: 'success', text: __('Dependency added.'));
    }

    /**
     * Remove a dependency link involving the viewed item.
     */
    public function removeDependency(int $dependencyId): void
    {
        $item = $this->dependable();
        $this->authorize('update', $item);

        $dependency = Dependency::findOrFail($dependencyId);

        $morph = $item->getMorphClass();
        $involvesItem = ($dependency->dependent_type === $morph && $dependency->dependent_id === $item->getKey())
            || ($dependency->blocker_type === $morph && $dependency->blocker_id === $item->getKey());

        abort_unless($involvesItem, 404);

        $dependency->delete();

        $item->recordActivity('dependency_changed', 'dependencies');

        unset($this->blockerLinks, $this->blockingLinks, $this->presentBlockerLinks, $this->presentBlockingLinks, $this->isBlocked);

        Flux::toast(variant: 'success', text: __('Dependency removed.'));
    }
}
