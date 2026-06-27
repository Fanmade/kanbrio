<?php

namespace App\Livewire\Activity;

use App\Concerns\ResolvesMorphSubject;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Task;
use App\Support\ActivityDescriber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The activity feed loads lazily where it is embedded (a `lazy` tag attribute):
 * it renders a lightweight {@see placeholder()} with the page and only fetches
 * its activities once it scrolls into view (Livewire's lazy loading uses an
 * intersection observer). It also stays collapsed by default, so even once
 * loaded the full activity list is fetched only when the user expands it.
 */
class ActivityFeed extends Component
{
    use ResolvesMorphSubject;

    public const string COLLAPSED_PREFERENCE_KEY = 'activities_collapsed';

    /**
     * How many activity entries are revealed per "show older" step (and the
     * initial window). Bounds the load — and the per-poll re-fetch — instead of
     * pulling the entire history every render.
     */
    public const int PER_PAGE = 15;

    public bool $collapsed = true;

    /**
     * The number of activity entries currently shown, grown by {@see showMore()}.
     */
    public int $visible = self::PER_PAGE;

    public function mount(Project|Task $subject): void
    {
        $this->initMorphSubject($subject);
        $this->authorize('view-activity-log', $subject instanceof Task ? $subject->project : $subject);

        $this->collapsed = (bool) Auth::user()->preference(self::COLLAPSED_PREFERENCE_KEY, true);
    }

    /**
     * Lightweight skeleton shown in place of the feed until it scrolls into view
     * and the real component loads. Mirrors the collapsed card's header so the
     * page layout doesn't shift when the feed resolves.
     */
    public function placeholder(): string
    {
        $label = e(__('Activity'));

        return <<<HTML
        <div
            class="rounded-xl border border-zinc-200/70 bg-white p-4 dark:border-white/10 dark:bg-zinc-800"
            data-test="activity-placeholder"
        >
            <div class="flex items-center gap-2">
                <div class="size-4 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{$label}</span>
            </div>
        </div>
        HTML;
    }

    /**
     * Live-updates tick: pull in activity recorded by others (read-only feed, so
     * always safe to refresh).
     */
    #[On('live-refresh')]
    public function liveRefresh(): void
    {
        unset($this->activities, $this->activityCount, $this->descriptions, $this->hasMoreActivities);
    }

    /**
     * Toggle the activity feed and persist the state as a user preference.
     */
    public function toggleCollapsed(): void
    {
        $this->collapsed = ! $this->collapsed;

        Auth::user()->setPreference(self::COLLAPSED_PREFERENCE_KEY, $this->collapsed);
    }

    /**
     * Resolve the model the activities belong to.
     */
    #[Computed]
    public function subject(): Project|Task
    {
        return $this->resolveMorphSubject();
    }

    /**
     * The subject's recorded activities (newest first) with their author.
     *
     * @return Collection<int, Activity>
     */
    #[Computed]
    public function activities(): Collection
    {
        return $this->subject()->activities()->with('user')->limit($this->visible)->get();
    }

    /**
     * Count of recorded activities, used for the collapsed-state badge.
     */
    #[Computed]
    public function activityCount(): int
    {
        return $this->subject()->activities()->count();
    }

    /**
     * Whether older activity remains beyond the current window.
     */
    #[Computed]
    public function hasMoreActivities(): bool
    {
        return $this->activityCount() > $this->visible;
    }

    /**
     * Reveal the next page of older activity.
     */
    public function showMore(): void
    {
        $this->visible += self::PER_PAGE;

        unset($this->activities, $this->descriptions, $this->hasMoreActivities);
    }

    /**
     * Human-readable description line for each activity, keyed by activity id.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function descriptions(): array
    {
        return $this->activities()
            ->mapWithKeys(static fn (Activity $activity): array => [$activity->id => ActivityDescriber::describe($activity)])
            ->all();
    }
}
