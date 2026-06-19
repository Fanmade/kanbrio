<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\HasComments;
use App\Concerns\HasKeywords;
use App\Concerns\HasScopedNumber;
use App\Concerns\HasSubscribers;
use App\Concerns\LogsActivity;
use App\Contracts\Subscribable;
use App\Enums\Priority;
use App\Enums\Status;
use App\Support\StoryProgress;
use Database\Factories\StoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $project_id
 * @property int $story_number
 * @property string $title
 * @property string|null $description
 * @property Priority $priority
 * @property Carbon|null $due_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $reference
 * @property-read Project $project
 */
#[Fillable(['title', 'description', 'priority', 'due_date'])]
class Story extends Model implements Subscribable
{
    /** @use HasFactory<StoryFactory> */
    use HasAttachments, HasComments, HasFactory, HasKeywords, HasScopedNumber, HasSubscribers, LogsActivity;

    protected string $scopedNumberColumn = 'story_number';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'due_date' => 'date',
        ];
    }

    /**
     * @return Builder<static>
     */
    public function scopedNumberQuery(): Builder
    {
        return static::query()->where('project_id', $this->project_id);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * A story update also reaches the project's subscribers.
     *
     * @return Collection<int, User>
     */
    public function notificationAudience(): Collection
    {
        return $this->subscribers()->get()
            ->merge($this->project->subscribers()->get())
            ->unique('id');
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('task_number');
    }

    /**
     * Eager-load the per-story task counts that {@see progress()} reads, so a list
     * of stories can report completeness from a single query instead of an N+1.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function withProgressCounts(Builder $query): Builder
    {
        return $query->withCount([
            'tasks as tasks_total',
            'tasks as tasks_done' => static fn (Builder $tasks): Builder => $tasks->where('status', Status::Done),
        ]);
    }

    /**
     * Completeness derived from the story's tasks. Prefers counts aggregated by
     * the {@see withProgressCounts()} scope, then an already-loaded tasks
     * relation, falling back to two count queries.
     */
    public function progress(): StoryProgress
    {
        if (array_key_exists('tasks_total', $this->attributes) && array_key_exists('tasks_done', $this->attributes)) {
            return new StoryProgress(
                done: (int) $this->attributes['tasks_done'],
                total: (int) $this->attributes['tasks_total'],
            );
        }

        if ($this->relationLoaded('tasks')) {
            return new StoryProgress(
                done: $this->tasks->where('status', Status::Done)->count(),
                total: $this->tasks->count(),
            );
        }

        return new StoryProgress(
            done: $this->tasks()->where('status', Status::Done)->count(),
            total: $this->tasks()->count(),
        );
    }

    /**
     * The users assigned to this story.
     *
     * @return BelongsToMany<User, $this>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * The public reference, e.g. "ABC1".
     *
     * @return Attribute<non-empty-string, never>
     */
    protected function reference(): Attribute
    {
        return Attribute::get(fn (): string => $this->project->short_name.$this->story_number);
    }
}
