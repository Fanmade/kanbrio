<?php

use App\Livewire\Activity\ActivityFeed;
use App\Models\Project;
use App\Models\Story;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->member = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->members()->attach($this->member);
    $this->story = Story::factory()->for($this->project)->create();
    $this->task = Task::factory()->for($this->story)->create();
});

it('collapses the activity feed by default', function () {
    Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->assertSet('collapsed', true);
});

it('exposes the activity count for the badge', function () {
    $count = Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->instance()->activityCount();

    // The task factory logs a single "created" activity.
    expect($count)->toBe(1);
});

it('persists the collapsed state as a user preference when toggled', function () {
    Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->call('toggleCollapsed')
        ->assertSet('collapsed', false)
        ->call('toggleCollapsed')
        ->assertSet('collapsed', true);

    expect($this->member->fresh()->preference('activities_collapsed'))->toBeTrue();
});

it('restores the expanded state from the user preference on mount', function () {
    $this->member->setPreference('activities_collapsed', false);

    Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->assertSet('collapsed', false);
});

it('applies the collapsed preference across all subject types', function () {
    Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->call('toggleCollapsed')
        ->assertSet('collapsed', false);

    Livewire::actingAs($this->member->fresh())
        ->test(ActivityFeed::class, ['subject' => $this->project])
        ->assertSet('collapsed', false);

    Livewire::actingAs($this->member->fresh())
        ->test(ActivityFeed::class, ['subject' => $this->story])
        ->assertSet('collapsed', false);
});

it('shows which assignees were added and removed', function () {
    $this->member->setPreference('activities_collapsed', false);
    $this->task->recordActivity('assignee_changed', 'assignees', json_encode(['Carol']), json_encode(['Alice', 'Bob']));

    Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->assertSee('assigned Alice and Bob, unassigned Carol');
});

it('shows only added assignees when none were removed', function () {
    $this->member->setPreference('activities_collapsed', false);
    $this->task->recordActivity('assignee_changed', 'assignees', null, json_encode(['Alice']));

    Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->assertSee('assigned Alice')
        ->assertDontSee('unassigned');
});

it('localizes the assignee change in German', function () {
    app()->setLocale('de');
    $this->member->setPreference('activities_collapsed', false);
    $this->task->recordActivity('assignee_changed', 'assignees', json_encode(['Carol']), json_encode(['Alice', 'Bob']));

    Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->assertSee('wies Alice und Bob zu und entfernte Carol aus den Zuständigen');
});

it('falls back to a generic line for legacy assignee entries without detail', function () {
    $this->member->setPreference('activities_collapsed', false);
    $this->task->recordActivity('assignee_changed', 'assignees');

    Livewire::actingAs($this->member)
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->assertSee('updated the assignees');
});

it('forbids non-members from viewing the feed', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(ActivityFeed::class, ['subject' => $this->task])
        ->assertForbidden();
});
