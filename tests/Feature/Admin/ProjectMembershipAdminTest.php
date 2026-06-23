<?php

use App\Enums\ProjectRole;
use App\Livewire\Admin\UserManagement;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets an admin add a user to a project as a member', function () {
    $admin = User::factory()->canManageUsers()->create();
    $user = User::factory()->create();
    $project = Project::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('manageProjects', $user->id)
        ->call('addUserToProject', $project->id);

    expect($project->roleFor($user))->toBe(ProjectRole::Member);
});

it('lets an admin change a user\'s project role and remove them', function () {
    $admin = User::factory()->canManageUsers()->create();
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->attach($user, ['role' => ProjectRole::Member->value]);

    $component = Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('manageProjects', $user->id);

    $component->call('setUserProjectRole', $project->id, ProjectRole::Admin->value);
    expect($project->roleFor($user))->toBe(ProjectRole::Admin);

    $component->call('removeUserFromProject', $project->id);
    expect($project->members()->whereKey($user->id)->exists())->toBeFalse();
});

it('does not let an admin re-role or remove a project owner', function () {
    $admin = User::factory()->canManageUsers()->create();
    $owner = User::factory()->create();
    $project = Project::factory()->create();
    $project->members()->attach($owner, ['role' => ProjectRole::Owner->value]);

    $component = Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('manageProjects', $owner->id);

    $component->call('setUserProjectRole', $project->id, ProjectRole::Member->value);
    $component->call('removeUserFromProject', $project->id);

    expect($project->roleFor($owner))->toBe(ProjectRole::Owner)
        ->and($project->members()->whereKey($owner->id)->exists())->toBeTrue();
});

it('exposes the managed user\'s roles per project', function () {
    $admin = User::factory()->canManageUsers()->create();
    $user = User::factory()->create();
    $adminProject = Project::factory()->create();
    $memberProject = Project::factory()->create();
    Project::factory()->create(); // user is not in this one
    $adminProject->members()->attach($user, ['role' => ProjectRole::Admin->value]);
    $memberProject->members()->attach($user, ['role' => ProjectRole::Member->value]);

    $roles = Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('manageProjects', $user->id)
        ->instance()->managedUserRoles();

    expect($roles)->toBe([
        $adminProject->id => 'admin',
        $memberProject->id => 'member',
    ]);
});

it('forbids a non-admin from opening user administration', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(UserManagement::class)
        ->assertForbidden();
});
