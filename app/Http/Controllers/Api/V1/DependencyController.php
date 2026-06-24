<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dependency;
use App\Models\Task;
use App\Support\ReferenceResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DependencyController extends Controller
{
    /**
     * Link a dependency between the task at {reference} and a related task. With
     * direction "blocked_by" the task is blocked by the related one; with "blocks"
     * it blocks the related one. Self-dependencies and cycles are rejected.
     */
    public function store(Request $request, string $reference): JsonResponse
    {
        $validated = $request->validate([
            'related' => ['required', 'string'],
            'direction' => ['required', Rule::in(['blocked_by', 'blocks'])],
        ]);

        [$item, $related] = $this->resolvePair($reference, $validated['related']);

        // "blocked_by": the item depends on the related one. "blocks": the related
        // item depends on the item.
        [$dependent, $blocker] = $validated['direction'] === 'blocks'
            ? [$related, $item]
            : [$item, $related];

        try {
            $dependent->addBlocker($blocker);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'related' => __('That would make an item depend on itself or create a cycle.'),
            ]);
        }

        $item->recordDependencyChange(true, $validated['direction'], $related->reference);

        return response()->json(['data' => $this->payload($item)], 201);
    }

    /**
     * Remove the dependency between two tasks, in whichever direction it exists.
     */
    public function destroy(string $reference, string $related): JsonResponse
    {
        [$item, $relatedTask] = $this->resolvePair($reference, $related);

        $dependency = Dependency::query()
            ->where(static fn (Builder $query): Builder => $query
                ->where('dependent_type', $item->getMorphClass())->where('dependent_id', $item->getKey())
                ->where('blocker_type', $relatedTask->getMorphClass())->where('blocker_id', $relatedTask->getKey()))
            ->orWhere(static fn (Builder $query): Builder => $query
                ->where('dependent_type', $relatedTask->getMorphClass())->where('dependent_id', $relatedTask->getKey())
                ->where('blocker_type', $item->getMorphClass())->where('blocker_id', $item->getKey()))
            ->first();

        abort_if($dependency === null, 404);

        // Direction from the item's perspective: as the dependent it is
        // "blocked_by" the related item, otherwise it "blocks" it.
        $direction = $dependency->dependent_type === $item->getMorphClass() && $dependency->dependent_id === $item->getKey()
            ? 'blocked_by'
            : 'blocks';

        $dependency->delete();

        $item->unsetRelation('dependencyLinks');
        $item->recordDependencyChange(false, $direction, $relatedTask->reference);

        return response()->json(['data' => $this->payload($item)]);
    }

    /**
     * Resolve the changed task (which the user must be able to update) and the
     * related task (which they must at least be able to view). Either being
     * missing or inaccessible is a 404 — only tasks take part in a dependency.
     *
     * @return array{0: Task, 1: Task}
     */
    private function resolvePair(string $reference, string $relatedReference): array
    {
        $item = ReferenceResolver::task($reference);
        abort_if(! $item instanceof Task || Auth::user()->cannot('update', $item), 404);

        $related = ReferenceResolver::task($relatedReference);
        abort_if(! $related instanceof Task || Auth::user()->cannot('view', $related), 404);

        return [$item, $related];
    }

    /**
     * Build the dependency payload for a task — the references of what blocks it,
     * what it blocks, and whether it is currently blocked — eager-loading the
     * linked items in one pass to keep reference resolution N+1-free.
     *
     * @return array{reference: string, blocked_by: array<int, string>, blocks: array<int, string>, is_blocked: bool}
     */
    private function payload(Task $item): array
    {
        $item->loadMissing([
            'dependencyLinks.blocker' => static fn (MorphTo $morphTo) => $morphTo->morphWith([
                Task::class => ['project'],
            ]),
            'dependentLinks.dependent' => static fn (MorphTo $morphTo) => $morphTo->morphWith([
                Task::class => ['project'],
            ]),
        ]);

        return [
            'reference' => $item->reference,
            'blocked_by' => $item->blockers()->map(static fn (Task $blocker): string => $blocker->reference)->values()->all(),
            'blocks' => $item->blocking()->map(static fn (Task $blocked): string => $blocked->reference)->values()->all(),
            'is_blocked' => $item->isBlocked(),
        ];
    }
}
