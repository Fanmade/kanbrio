<?php

namespace App\Models;

use App\Concerns\HasScopedNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $token_name
 * @property string $subject_type
 * @property int $subject_id
 * @property int $sequence
 * @property string $action
 * @property string|null $field
 * @property string|null $old_value
 * @property string|null $new_value
 * @property Carbon|null $created_at
 * @property-read User|null $user
 * @property-read string|null $reference
 */
#[Fillable(['user_id', 'token_name', 'action', 'field', 'old_value', 'new_value'])]
class Activity extends Model
{
    use HasScopedNumber;

    public const UPDATED_AT = null;

    /**
     * The per-subject ordinal column ("the Nth entry recorded for this subject").
     */
    protected string $scopedNumberColumn = 'sequence';

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Siblings sharing the same subject — the scope the {@see $sequence} numbers
     * within (see {@see HasScopedNumber}).
     *
     * @return Builder<static>
     */
    public function scopedNumberQuery(): Builder
    {
        return static::query()
            ->where('subject_type', $this->subject_type)
            ->where('subject_id', $this->subject_id);
    }

    /**
     * The portable, self-describing reference for this entry, e.g. "KAN-42-log-2"
     * for the 2nd activity recorded on task KAN-42. Null for subjects that don't
     * expose a reference (only task-subject activities do today).
     */
    public function getReferenceAttribute(): ?string
    {
        $subject = $this->subject;

        return $subject instanceof Task
            ? $subject->reference.'-log-'.$this->sequence
            : null;
    }

    /**
     * A deep link to this entry in its subject's activity feed: the "?log=N"
     * query forces the (lazy, collapsed) feed open and the "#log-N" fragment
     * scrolls to the row. Null for subjects without a per-entry reference.
     */
    public function deepLinkUrl(): ?string
    {
        $subject = $this->subject;

        if (! $subject instanceof Task) {
            return null;
        }

        return route('task.show', [
            'short_name' => $subject->project->short_name,
            'task_number' => $subject->task_number,
        ]).'?log='.$this->sequence.'#log-'.$this->sequence;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The comments that reference this entry.
     *
     * @return BelongsToMany<Comment, $this>
     */
    public function comments(): BelongsToMany
    {
        return $this->belongsToMany(Comment::class)->withTimestamps();
    }
}
