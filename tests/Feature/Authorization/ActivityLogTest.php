<?php

use App\Livewire\Activity\ActivityFeed;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('gates the activity feed behind view-activity-log', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->for($project)->create();

    $member = userWithRole($project, 'member');                 // holds view-activity-log
    $restricted = userWithPermissions($project, []);            // only view-project

    Livewire::actingAs($member)
        ->test(ActivityFeed::class, ['subject' => $task])
        ->assertOk();

    Livewire::actingAs($restricted)
        ->test(ActivityFeed::class, ['subject' => $task])
        ->assertForbidden();
});
