<?php

use App\Enums\Status;
use App\Livewire\Projects\ProjectBoard;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->project = Project::factory()->create();
    $this->task = Task::factory()->for($this->project)->status(Status::ToDo)->create();
});

it('grants every task ability to a member', function () {
    $member = userWithRole($this->project, 'member');

    expect($member->can('view', $this->task))->toBeTrue()
        ->and($member->can('update', $this->task))->toBeTrue()
        ->and($member->can('delete', $this->task))->toBeTrue()
        ->and($member->can('updateStatus', $this->task))->toBeTrue()
        ->and($member->can('close', $this->task))->toBeTrue()
        ->and($member->can('cancel', $this->task))->toBeTrue()
        ->and($member->can('archive', $this->task))->toBeTrue()
        ->and($member->can('manageDependencies', $this->task))->toBeTrue()
        ->and($member->can('tag', $this->task))->toBeTrue()
        ->and($member->can('create-task', $this->project))->toBeTrue();
});

it('limits a viewer to read-only', function () {
    $viewer = userWithRole($this->project, 'viewer');

    expect($viewer->can('view', $this->task))->toBeTrue()
        ->and($viewer->can('update', $this->task))->toBeFalse()
        ->and($viewer->can('delete', $this->task))->toBeFalse()
        ->and($viewer->can('close', $this->task))->toBeFalse()
        ->and($viewer->can('cancel', $this->task))->toBeFalse()
        ->and($viewer->can('archive', $this->task))->toBeFalse()
        ->and($viewer->can('manageDependencies', $this->task))->toBeFalse()
        ->and($viewer->can('tag', $this->task))->toBeFalse()
        ->and($viewer->can('create-task', $this->project))->toBeFalse();
});

it('gates each ability on its own permission', function (string $ability, string $permission) {
    $holder = userWithPermissions($this->project, [$permission]);
    $other = userWithPermissions($this->project, ['edit-task']); // a perm that is never the one under test

    expect($holder->can($ability, $this->task))->toBeTrue()
        ->and($other->can($ability, $this->task))->toBeFalse();
})->with([
    'delete' => ['delete', 'delete-task'],
    'close' => ['close', 'close-task'],
    'cancel' => ['cancel', 'cancel-task'],
    'archive' => ['archive', 'archive-task'],
    'manageDependencies' => ['manageDependencies', 'manage-dependencies'],
    'tag' => ['tag', 'tag-tasks'],
]);

it('requires close-task to move a card to Done, but only edit-task to move elsewhere', function () {
    // A role that can edit/move tasks but cannot complete them.
    $mover = userWithPermissions($this->project, ['edit-task']);

    Livewire::actingAs($mover)
        ->test(ProjectBoard::class, ['short_name' => $this->project->short_name])
        ->call('moveTask', $this->task->id, Status::InProgress->value)
        ->assertOk();

    expect($this->task->fresh()->status)->toBe(Status::InProgress);

    Livewire::actingAs($mover)
        ->test(ProjectBoard::class, ['short_name' => $this->project->short_name])
        ->call('moveTask', $this->task->id, Status::Done->value)
        ->assertForbidden();

    expect($this->task->fresh()->status)->toBe(Status::InProgress);
});
