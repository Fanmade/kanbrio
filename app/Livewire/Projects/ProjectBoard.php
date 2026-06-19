<?php

namespace App\Livewire\Projects;

use App\Actions\CreateStory;
use App\Actions\CreateTask;
use App\Concerns\BuildsKanbanColumns;
use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Project;
use App\Models\Story;
use App\Models\Task;
use App\Support\BlockedTasks;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectBoard extends Component
{
    use BuildsKanbanColumns;

    #[Locked]
    public string $shortName;

    // Board filters.
    public ?int $priorityFilter = null;

    public bool $showArchived = false;

    // Create-story modal state.
    public bool $showStoryModal = false;

    public string $storyTitle = '';

    public string $storyDescription = '';

    public string $storyDueDate = '';

    public int $storyPriority;

    // Create-task modal state.
    public bool $showTaskModal = false;

    public ?int $taskStoryId = null;

    public string $taskTitle = '';

    public string $taskDescription = '';

    public string $taskDueDate = '';

    public string $taskStatus = Status::Planned->value;

    public int $taskPriority;

    public function mount(string $short_name): void
    {
        $this->shortName = $short_name;
        $this->storyPriority = Priority::default()->value;
        $this->taskPriority = Priority::default()->value;

        $this->authorize('view', $this->project());
    }

    #[Computed]
    public function project(): Project
    {
        $project = Project::where('short_name', $this->shortName)->firstOrFail();

        $this->authorize('view', $project);

        return $project;
    }

    /**
     * @return Collection<int, Story>
     */
    #[Computed]
    public function stories(): Collection
    {
        return $this->project()->stories()->with(['tasks.assignees', 'tasks.tags'])->get();
    }

    /**
     * The board columns for this project's tasks. Archived tasks, and tasks of
     * an archived story, are hidden unless {@see $showArchived} is on.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function columns(): array
    {
        $project = $this->project();

        $tasks = $this->stories()->flatMap(static function ($story) use ($project) {
            $story->setRelation('project', $project);

            return $story->tasks->each(static fn (Task $task) => $task->setRelation('story', $story));
        });

        if (! $this->showArchived) {
            $tasks = $tasks->reject(static fn (Task $task): bool => $task->isArchived() || $task->story->isArchived());
        }

        if ($this->priorityFilter) {
            $tasks = $tasks->filter(fn (Task $task): bool => $task->priority->value === $this->priorityFilter);
        }

        return $this->buildColumns($tasks);
    }

    /**
     * The ids of this project's tasks that are blocked by an unfinished dependency.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function blockedTaskIds(): array
    {
        $taskIds = $this->stories()
            ->flatMap(static fn (Story $story) => $story->tasks)
            ->pluck('id')
            ->all();

        return BlockedTasks::ids($taskIds);
    }

    /**
     * Move a task to a new status column (drag-and-drop drop handler).
     */
    public function moveTask(int $taskId, string $status): void
    {
        $this->applyTaskMove($this->resolveProjectTask($taskId), $status);

        unset($this->stories, $this->columns, $this->blockedTaskIds);
    }

    /**
     * Drag-and-drop placement: move and reorder a task within this project's board.
     */
    public function reorderTask(int $taskId, string $status, ?int $beforeId, ?int $afterId): void
    {
        $this->applyTaskReorder($this->resolveProjectTask($taskId), $status, $beforeId, $afterId);

        unset($this->stories, $this->columns, $this->blockedTaskIds);
    }

    /**
     * Archive a task, removing it from this project's board.
     */
    public function archiveTask(int $taskId): void
    {
        $this->applyTaskArchive($this->resolveProjectTask($taskId));

        unset($this->stories, $this->columns, $this->blockedTaskIds);
    }

    /**
     * Restore a task from the archive.
     */
    public function unarchiveTask(int $taskId): void
    {
        $this->applyTaskUnarchive($this->resolveProjectTask($taskId));

        unset($this->stories, $this->columns, $this->blockedTaskIds);
    }

    public function createStory(): void
    {
        $this->authorize('update', $this->project());

        $validated = $this->validate([
            'storyTitle' => ['required', 'string', 'max:255'],
            'storyDescription' => ['nullable', 'string'],
            'storyPriority' => ['required', new Enum(Priority::class)],
            'storyDueDate' => ['nullable', 'date'],
        ]);

        app(CreateStory::class)->handle(
            $this->project(),
            $validated['storyTitle'],
            $validated['storyDescription'] ?? null,
            Priority::from($validated['storyPriority']),
            $validated['storyDueDate'],
        );

        $this->reset('storyTitle', 'storyDescription', 'storyDueDate', 'showStoryModal');
        unset($this->stories, $this->columns);

        Flux::toast(variant: 'success', text: __('Story created.'));
    }

    public function openTaskModal(?int $storyId = null, ?string $status = null): void
    {
        $this->reset('taskTitle', 'taskDescription', 'taskDueDate');
        $this->taskStoryId = $storyId ?? $this->stories()->reject(static fn (Story $story): bool => $story->isArchived())->first()?->id;
        $this->taskStatus = $status ?? Status::Planned->value;
        $this->taskPriority = $this->priorityForStory($this->taskStoryId);
        $this->showTaskModal = true;
    }

    /**
     * Keep the task priority in sync with the selected story — tasks inherit it.
     */
    public function updatedTaskStoryId(mixed $value): void
    {
        $this->taskPriority = $this->priorityForStory((int) $value);
    }

    /**
     * The priority a new task should default to, inherited from its story.
     */
    protected function priorityForStory(?int $storyId): int
    {
        return $this->stories()->firstWhere('id', $storyId)?->priority->value
            ?? Priority::default()->value;
    }

    public function createTask(): void
    {
        $this->authorize('update', $this->project());

        $validated = $this->validate([
            'taskStoryId' => ['required', 'integer'],
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskDescription' => ['nullable', 'string'],
            'taskPriority' => ['required', new Enum(Priority::class)],
            'taskDueDate' => ['nullable', 'date'],
            'taskStatus' => ['required', 'string', 'in:'.collect(Status::cases())->map->value->implode(',')],
        ]);

        $story = $this->project()->stories()->whereKey($validated['taskStoryId'])->firstOrFail();

        app(CreateTask::class)->handle(
            $story,
            $validated['taskTitle'],
            $validated['taskDescription'] ?? null,
            Priority::from($validated['taskPriority']),
            Status::from($validated['taskStatus']),
            $validated['taskDueDate'],
        );

        $this->reset('taskTitle', 'taskDescription', 'taskDueDate', 'showTaskModal');
        unset($this->stories, $this->columns);

        Flux::toast(variant: 'success', text: __('Task created.'));
    }

    /**
     * Resolve a task that belongs to this board's project, or 404.
     */
    protected function resolveProjectTask(int $taskId): Task
    {
        $task = Task::with('story')->findOrFail($taskId);

        abort_unless($task->story->project_id === $this->project()->id, 404);

        return $task;
    }
}
