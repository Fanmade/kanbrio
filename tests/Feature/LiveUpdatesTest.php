<?php

use App\Livewire\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('defaults live updates on for a user with no saved preference', function () {
    Livewire::actingAs($this->user)
        ->test(Board::class)
        ->assertSet('liveUpdates', true);
});

it('reads the persisted preference on mount', function () {
    $this->user->setPreference(Board::LIVE_UPDATES_PREFERENCE_KEY, false);

    Livewire::actingAs($this->user)
        ->test(Board::class)
        ->assertSet('liveUpdates', false);
});

it('persists the choice when the toggle is flipped', function () {
    Livewire::actingAs($this->user)
        ->test(Board::class)
        ->set('liveUpdates', false);

    expect($this->user->fresh()->preference(Board::LIVE_UPDATES_PREFERENCE_KEY))->toBeFalse();

    Livewire::actingAs($this->user)
        ->test(Board::class)
        ->set('liveUpdates', true);

    expect($this->user->fresh()->preference(Board::LIVE_UPDATES_PREFERENCE_KEY))->toBeTrue();
});

it('exposes the poll interval only while enabled', function () {
    $component = Livewire::actingAs($this->user)->test(Board::class);

    expect($component->instance()->livePollInterval())->toBe(Board::LIVE_UPDATES_INTERVAL);

    $component->set('liveUpdates', false);

    expect($component->instance()->livePollInterval())->toBeNull();
});

it('renders the live-updates toggle on the board', function () {
    Livewire::actingAs($this->user)
        ->test(Board::class)
        ->assertSeeHtml('data-test="live-updates-toggle"');
});
