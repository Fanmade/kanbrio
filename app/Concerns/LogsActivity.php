<?php

namespace App\Concerns;

use App\Contracts\Subscribable;
use App\Models\Activity;
use App\Models\User;
use App\Notifications\ItemActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(
            static function (Model $model): void {
                /** @var Model&self $model */
                $model->recordActivity('created');
            });
    }

    /**
     * The audit-trail entries recorded for this model.
     *
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    /**
     * Record an audit-trail entry for this model.
     */
    public function recordActivity(string $action, ?string $field = null, ?string $oldValue = null, ?string $newValue = null): Activity
    {
        $activity = $this->activities()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);

        $this->notifySubscribers($activity);

        return $activity;
    }

    /**
     * Record an assignee change, capturing the names of the users added and
     * removed as a JSON snapshot. Names are stored (rather than IDs) so the
     * trail reflects who was assigned at the time, even after a later rename or
     * deletion. Returns null when nothing actually changed.
     *
     * @param  array<int, int|string>  $attachedIds  user IDs newly assigned
     * @param  array<int, int|string>  $detachedIds  user IDs unassigned
     */
    public function recordAssigneeChange(array $attachedIds, array $detachedIds): ?Activity
    {
        if ($attachedIds === [] && $detachedIds === []) {
            return null;
        }

        $names = User::query()
            ->whereIn('id', array_merge($attachedIds, $detachedIds))
            ->pluck('name', 'id');

        $resolve = static fn (array $ids): array => collect($ids)
            ->map(static fn ($id) => $names[(int) $id] ?? null)
            ->filter()
            ->values()
            ->all();

        $added = $resolve($attachedIds);
        $removed = $resolve($detachedIds);

        return $this->recordActivity(
            'assignee_changed',
            'assignees',
            $removed === [] ? null : json_encode($removed, JSON_THROW_ON_ERROR),
            $added === [] ? null : json_encode($added, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Notify the item's subscribers (excluding the actor) about an update.
     */
    protected function notifySubscribers(Activity $activity): void
    {
        if (! $this instanceof Subscribable) {
            return;
        }

        $actorId = Auth::id();

        $this->notificationAudience()
            ->unique('id')
            ->reject(static fn (User $user) => $user->id === $actorId)
            ->each(static fn (User $user) => $user->notify(new ItemActivity($activity)));
    }
}
