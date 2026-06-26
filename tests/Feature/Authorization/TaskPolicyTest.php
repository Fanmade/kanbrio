<?php

use App\Authorization\ProjectRoleProvisioner;
use App\Enums\Status;
use App\Livewire\Projects\ProjectBoard;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Fanmade\DelegatedPermissions\RoleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->project = Project::factory()->create();
    $this->task = Task::factory()->for($this->project)->status(Status::ToDo)->create();
});

/**
 * A user holding the named base role on the test project.
 */
function userWithRole(Project $project, string $role): User
{
    $user = User::factory()->create();
    joinProject($project, $user, $role);

    return $user;
}

/**
 * A user holding a fresh custom role granting exactly the given permissions
 * (always plus view-project, so they can reach the project at all).
 */
function userWithPermissions(Project $project, array $permissions): User
{
    $owner = app(ProjectRoleProvisioner::class)->roleFor($project, 'owner');
    $role = app(RoleManager::class)->createRole(
        'Custom '.fake()->unique()->word(),
        $owner,
        array_values(array_unique(['view-project', ...$permissions])),
        $project,
    );

    return User::factory()->create()->assignRole($role);
}

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
