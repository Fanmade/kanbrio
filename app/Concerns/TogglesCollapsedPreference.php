<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Shared collapse-and-remember behaviour for the Livewire sections that can be
 * folded away (the comments list, the activity feed). The using component keeps
 * its own `public bool $collapsed` (so it can default open or closed) and names
 * the preference key; this concern loads and persists that state per user.
 *
 * @property bool $collapsed
 */
trait TogglesCollapsedPreference
{
    /**
     * The user-preference key under which this section's collapsed state persists.
     */
    abstract protected function collapsedPreferenceKey(): string;

    /**
     * Load the persisted collapsed state for the current user, falling back to the
     * given default. Call from the component's mount().
     */
    protected function initCollapsed(bool $default = false): void
    {
        $this->collapsed = (bool) Auth::user()->preference($this->collapsedPreferenceKey(), $default);
    }

    /**
     * Toggle the section and persist the new state as a user preference.
     */
    public function toggleCollapsed(): void
    {
        $this->collapsed = ! $this->collapsed;

        Auth::user()->setPreference($this->collapsedPreferenceKey(), $this->collapsed);
    }
}
