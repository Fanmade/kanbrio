<?php

namespace App\Livewire\Tasks;

use App\Actions\CreateTask;
use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The single, globally-mounted dialog for creating a task. It is opened from any
 * page (board cards, the project overview, a parent task, the command palette) by
 * dispatching the `open-create-task` event with optional project/parent context.
 * Creation funnels through the shared {@see CreateTask} action; on success it
 * dispatches `task-created` so the originating page can refresh.
 */
class CreateTaskModal extends Component
{
    public bool $show = false;

    /**
     * Project/parent inferred from the page the component was mounted on, used to
     * preselect the dialog when it is opened without explicit context (e.g. from
     * the command palette). Captured at mount because a later Livewire request
     * runs on the update endpoint, where the page route is no longer available.
     */
    #[Locked]
    public ?int $contextProjectId = null;

    #[Locked]
    public ?int $contextParentId = null;

    public ?int $projectId = null;

    public ?int $parentId = null;

    public string $title = '';

    public string $description = '';

    public int $priority;

    public string $status = '';

    public string $dueDate = '';

    public function mount(): void
    {
        [$this->contextProjectId, $this->contextParentId] = $this->routeContext();

        $this->resetForm();
    }

    /**
     * Open the dialog, preselecting the project and parent from the given context
     * or, when omitted, falling back to the page the component was mounted on.
     */
    #[On('open-create-task')]
    public function open(?int $projectId = null, ?int $parentId = null): void
    {
        $this->resetForm();

        $projectId ??= $this->contextProjectId;

        if ($parentId === null && $projectId === $this->contextProjectId) {
            $parentId = $this->contextParentId;
        }

        $this->projectId = $projectId;
        $this->parentId = $parentId;
        $this->show = true;
    }

    /**
     * The projects the authenticated user may create tasks in.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->projects()->orderBy('title')->get();
    }

    /**
     * Tasks in the selected project that may still take a child, as
     * `[id => "REF — title"]` options for the parent select.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function parentOptions(): array
    {
        $project = $this->selectedProject();

        if ($project === null) {
            return [];
        }

        $maxDepth = (int) config('kanbrio.tasks.max_depth');

        $tasks = $project->tasks()
            ->select(['id', 'parent_id', 'task_number', 'title', 'status', 'archived_at'])
            ->get();

        $depthOf = $this->depthResolver($tasks->keyBy('id'));

        return $tasks
            ->reject(static fn (Task $task): bool => $task->isArchived())
            ->reject(static fn (Task $task): bool => $task->status->isTerminal())
            ->filter(static fn (Task $task): bool => $depthOf($task->id) < $maxDepth)
            ->mapWithKeys(fn (Task $task): array => [
                $task->id => $project->short_name.'-'.$task->task_number.' — '.$task->title,
            ])
            ->all();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'projectId' => ['required', 'integer'],
            'parentId' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', new Enum(Priority::class)],
            'status' => ['required', 'string', 'in:'.collect(Status::columns())->map->value->implode(',')],
            'dueDate' => ['nullable', 'date'],
        ]);

        $project = $this->projects()->firstWhere('id', $validated['projectId']);

        abort_if($project === null, 404);

        $this->authorize('update', $project);

        $parent = $this->resolveParent($project, $validated['parentId'] ?? null);

        app(CreateTask::class)->handle(
            $project,
            $validated['title'],
            $validated['description'] ?: null,
            Priority::from($validated['priority']),
            Status::from($validated['status']),
            $validated['dueDate'] ?: null,
            $parent,
        );

        $this->resetForm();
        $this->show = false;

        $this->dispatch('task-created');

        Flux::toast(variant: 'success', text: __('Task created.'));
    }

    /**
     * Re-scope the parent options whenever the chosen project changes, dropping a
     * now-invalid parent selection.
     */
    public function updatedProjectId(): void
    {
        unset($this->parentOptions);
        $this->parentId = null;
    }

    /**
     * Reset the form to its defaults, ready for the next open.
     */
    protected function resetForm(): void
    {
        $this->reset('projectId', 'parentId', 'title', 'description', 'dueDate');
        $this->priority = Priority::default()->value;
        $this->status = Status::Planned->value;
        unset($this->parentOptions);
    }

    /**
     * Infer project/parent context from the current page route: a project page
     * yields its project, a task page yields that task as the parent. Returns
     * nulls when the route carries no project the user can access.
     *
     * @return array{0: ?int, 1: ?int}
     */
    protected function routeContext(): array
    {
        $shortName = request()->route('short_name');

        if (! is_string($shortName)) {
            return [null, null];
        }

        $project = $this->projects()->firstWhere('short_name', $shortName);

        if ($project === null) {
            return [null, null];
        }

        $taskNumber = request()->route('task_number');

        if (! is_string($taskNumber)) {
            return [$project->id, null];
        }

        $task = $project->tasks()->where('task_number', $taskNumber)->first();

        $parentId = $task !== null && $this->canParent($task) ? $task->id : null;

        return [$project->id, $parentId];
    }

    /**
     * Resolve and validate the chosen parent against the selected project.
     */
    protected function resolveParent(Project $project, ?int $parentId): ?Task
    {
        if ($parentId === null) {
            return null;
        }

        $parent = $project->tasks()->whereKey($parentId)->first();

        if ($parent === null || ! $this->canParent($parent)) {
            $this->addError('parentId', __('The selected parent task is not valid.'));
            abort(422);
        }

        return $parent;
    }

    /**
     * Whether the task may take another child within the depth limit.
     */
    protected function canParent(Task $task): bool
    {
        return $task->nestingDepth() < (int) config('kanbrio.tasks.max_depth');
    }

    protected function selectedProject(): ?Project
    {
        if ($this->projectId === null) {
            return null;
        }

        return $this->projects()->firstWhere('id', $this->projectId);
    }

    /**
     * Build a memoized depth resolver over a keyed task collection, counting the
     * root as level 1 by walking the in-memory parent chain (no extra queries).
     *
     * @param  Collection<int, Task>  $byId
     * @return callable(int): int
     */
    protected function depthResolver(Collection $byId): callable
    {
        $cache = [];

        $resolve = function (int $id) use (&$resolve, &$cache, $byId): int {
            if (isset($cache[$id])) {
                return $cache[$id];
            }

            $task = $byId->get($id);
            $parentId = $task?->parent_id;

            return $cache[$id] = $parentId === null ? 1 : $resolve($parentId) + 1;
        };

        return $resolve;
    }
}
