<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ranks roles by privilege through the permissions they hold', function () {
    $project = Project::factory()->create();
    $owner = userWithRole($project, 'owner');
    $admin = userWithRole($project, 'admin');
    $member = userWithRole($project, 'member');

    // Owner outranks admin outranks member: each higher role holds a strict
    // superset of the permissions below it.
    expect($owner->can('manageMembers', $project))->toBeTrue()
        ->and($admin->can('manageMembers', $project))->toBeFalse()
        ->and($admin->can('manageSettings', $project))->toBeTrue()
        ->and($member->can('manageSettings', $project))->toBeFalse()
        ->and($member->can('view', $project))->toBeTrue();
});

it('reports each member\'s role name, and null for a stranger', function () {
    $project = Project::factory()->create();
    $owner = userWithRole($project, 'owner');
    $admin = userWithRole($project, 'admin');
    $member = userWithRole($project, 'member');
    $stranger = User::factory()->create();

    expect($project->roleNameFor($owner))->toBe('owner')
        ->and($project->roleNameFor($admin))->toBe('admin')
        ->and($project->roleNameFor($member))->toBe('member')
        ->and($project->roleNameFor($stranger))->toBeNull();
});

it('treats only the owner as owner', function () {
    $project = Project::factory()->create();
    $owner = userWithRole($project, 'owner');
    $admin = userWithRole($project, 'admin');

    expect($project->isOwner($owner))->toBeTrue()
        ->and($project->isOwner($admin))->toBeFalse();
});

it('grants a factory member the member role', function () {
    $user = User::factory()->create();

    $project = Project::factory()->withMember($user)->create();

    expect($project->roleNameFor($user))->toBe('member')
        ->and($project->isOwner($user))->toBeFalse();
});

it('creates a project owned by a user through the factory', function () {
    $owner = User::factory()->create();

    $project = Project::factory()->withOwner($owner)->create();

    expect($project->isOwner($owner))->toBeTrue()
        ->and($project->roleNameFor($owner))->toBe('owner');
});
