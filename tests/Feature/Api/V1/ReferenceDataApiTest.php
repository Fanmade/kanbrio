<?php

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['short_name' => 'ABC']);
    joinProject($this->project, $this->user);
});

it('lists a project task types', function () {
    TaskType::provisionDefaults($this->project);
    Sanctum::actingAs($this->user, ['read']);

    $this->getJson('/api/v1/projects/ABC/task-types')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.name', 'Feature')
        ->assertJsonPath('data.1.name', 'Bug')
        ->assertJsonStructure(['data' => [['name', 'color', 'icon', 'branch_prefix', 'position']]]);
});

it('lists a project tags with usage counts', function () {
    $tag = Tag::factory()->for($this->project)->create(['name' => 'urgent']);
    Task::factory()->for($this->project)->create()->tags()->attach($tag);
    Sanctum::actingAs($this->user, ['read']);

    $this->getJson('/api/v1/projects/ABC/tags')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'urgent')
        ->assertJsonPath('data.0.task_count', 1);
});

it('404s reference data for a project the user cannot access', function () {
    Project::factory()->create(['short_name' => 'XYZ']);
    Sanctum::actingAs($this->user, ['read']);

    $this->getJson('/api/v1/projects/XYZ/task-types')->assertNotFound();
    $this->getJson('/api/v1/projects/XYZ/tags')->assertNotFound();
});

it('requires authentication', function () {
    $this->getJson('/api/v1/projects/ABC/task-types')->assertUnauthorized();
});
