<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates a project, seeding default types and making the caller owner', function () {
    $user = User::factory()->canCreateProjects()->create();
    Sanctum::actingAs($user, ['read', 'write']);

    $this->postJson('/api/v1/projects', [
        'title' => 'Apollo',
        'short_name' => 'apo',
        'description' => 'Moon shot',
    ])
        ->assertCreated()
        ->assertJsonPath('data.short_name', 'APO')
        ->assertJsonPath('data.title', 'Apollo');

    $project = Project::where('short_name', 'APO')->sole();

    expect($project->members()->whereKey($user->id)->exists())->toBeTrue()
        ->and($project->isOwner($user))->toBeTrue()
        ->and($project->taskTypes()->pluck('name')->all())->toBe(['Feature', 'Bug', 'Chore']);
});

it('forbids creating a project with a read-only token', function () {
    $user = User::factory()->canCreateProjects()->create();
    Sanctum::actingAs($user, ['read']);

    $this->postJson('/api/v1/projects', ['title' => 'Nope', 'short_name' => 'NOP'])
        ->assertForbidden();
});

it('forbids creating a project without the create-projects permission', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['read', 'write']);

    $this->postJson('/api/v1/projects', ['title' => 'Nope', 'short_name' => 'NOP'])
        ->assertForbidden();
});

it('validates the project short name', function () {
    $user = User::factory()->canCreateProjects()->create();
    Sanctum::actingAs($user, ['read', 'write']);

    $this->postJson('/api/v1/projects', ['title' => 'Bad', 'short_name' => 'TOOLONG'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('short_name');
});

it('updates a project as an admin', function () {
    $admin = User::factory()->create();
    $project = Project::factory()->create(['short_name' => 'ABC', 'title' => 'Old']);
    joinProject($project, $admin, 'admin');
    Sanctum::actingAs($admin, ['read', 'write']);

    $this->patchJson('/api/v1/projects/ABC', ['title' => 'New', 'short_name' => 'NEW'])
        ->assertOk()
        ->assertJsonPath('data.title', 'New')
        ->assertJsonPath('data.short_name', 'NEW');

    expect($project->fresh()->short_name)->toBe('NEW');
});

it('forbids a plain member from updating a project', function () {
    $member = User::factory()->create();
    $project = Project::factory()->create(['short_name' => 'ABC']);
    joinProject($project, $member);
    Sanctum::actingAs($member, ['read', 'write']);

    $this->patchJson('/api/v1/projects/ABC', ['title' => 'Hijack'])
        ->assertForbidden();
});

it('404s updating a project the user is not a member of', function () {
    $user = User::factory()->create();
    Project::factory()->create(['short_name' => 'XYZ']);
    Sanctum::actingAs($user, ['read', 'write']);

    $this->patchJson('/api/v1/projects/XYZ', ['title' => 'Hijack'])
        ->assertNotFound();
});
