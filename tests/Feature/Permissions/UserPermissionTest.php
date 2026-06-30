<?php

use App\Enums\Permission;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('backs each permission with its gate name and exposes a label', function () {
    expect(Permission::CreateProjects->value)->toBe('create-projects')
        ->and(Permission::InviteUsers->value)->toBe('invite-users');

    foreach (Permission::cases() as $permission) {
        expect($permission->label())->toBeString()->not->toBe('');
    }
});

it('denies a permission the user has not been granted', function () {
    $user = User::factory()->create();

    expect($user->hasPermission(Permission::CreateProjects))->toBeFalse();
});

it('grants a permission after it is synced and persists it', function () {
    $user = User::factory()->create();

    $user->syncPermissions([Permission::CreateProjects]);

    // Re-fetch to prove the grant persisted to the package, not just in memory.
    expect($user->fresh()->hasPermission(Permission::CreateProjects))->toBeTrue();
});

it('revokes permissions that are not part of the synced set', function () {
    $user = User::factory()->withPermission(Permission::CreateProjects, Permission::InviteUsers)->create();

    $user->syncPermissions([Permission::InviteUsers]);

    expect($user->hasPermission(Permission::InviteUsers))->toBeTrue()
        ->and($user->hasPermission(Permission::CreateProjects))->toBeFalse();
});

it('is idempotent and never duplicates a grant', function () {
    $user = User::factory()->create();

    $user->syncPermissions([Permission::CreateProjects]);
    $user->syncPermissions([Permission::CreateProjects]);

    expect($user->roles()->where('name', 'create-projects')->count())->toBe(1);
});

it('accepts string values as well as enum instances', function () {
    $user = User::factory()->create();

    $user->syncPermissions(['invite-users']);

    expect($user->hasPermission(Permission::InviteUsers))->toBeTrue();
});

it('filters users by a granted permission via the scope', function () {
    $granted = User::factory()->canInviteUsers()->create();
    User::factory()->create();

    $results = User::wherePermission(Permission::InviteUsers)->get();

    expect($results->pluck('id'))->toContain($granted->id)->toHaveCount(1);
});

it('registers a gate for every permission case', function () {
    $user = User::factory()->canCreateProjects()->create();

    expect(Gate::forUser($user)->allows('create-projects'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('invite-users'))->toBeFalse();
});

it('gates project creation behind the granted permission', function () {
    $allowed = User::factory()->canCreateProjects()->create();
    $denied = User::factory()->create();

    expect($allowed->can('create', Project::class))->toBeTrue()
        ->and($denied->can('create', Project::class))->toBeFalse();
});
