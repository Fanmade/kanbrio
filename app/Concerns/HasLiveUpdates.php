<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Shared "live updates" gating for auto-refreshing views (boards, task page).
 * Backed by a persisted per-user preference (default on) so a `wire:poll` can be
 * rendered only while the viewer wants it — letting users opt out if it ever
 * feels heavy. The poll wiring itself lives in each consuming view; this trait
 * provides the switch state, its persistence, and the interval to poll at.
 */
trait HasLiveUpdates
{
    /**
     * The per-user preference key controlling whether views auto-refresh.
     */
    public const string LIVE_UPDATES_PREFERENCE_KEY = 'live_updates';

    /**
     * How often a live view polls for changes while enabled.
     */
    public const string LIVE_UPDATES_INTERVAL = '15s';

    /**
     * Whether this view auto-refreshes, mirroring the viewer's saved preference.
     */
    public bool $liveUpdates = true;

    /**
     * Seed the toggle from the saved preference (default on) when the view mounts.
     */
    public function mountHasLiveUpdates(): void
    {
        $this->liveUpdates = (bool) Auth::user()?->preference(self::LIVE_UPDATES_PREFERENCE_KEY, true);
    }

    /**
     * Persist the choice whenever the toggle is flipped.
     */
    public function updatedLiveUpdates(bool $value): void
    {
        Auth::user()?->setPreference(self::LIVE_UPDATES_PREFERENCE_KEY, $value);
    }

    /**
     * The `wire:poll` interval for this view, or null when live updates are off so
     * the view can omit the poll entirely.
     */
    public function livePollInterval(): ?string
    {
        return $this->liveUpdates ? self::LIVE_UPDATES_INTERVAL : null;
    }
}
