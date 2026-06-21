<?php

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sanitizes a task description on save', function () {
    $task = Task::factory()->for(Project::factory())->create([
        'description' => '<p>Hi</p><a href="/x" onclick="y()">link</a><script>alert(1)</script>',
    ]);

    expect($task->fresh()->description)
        ->toContain('<p>Hi</p>')
        ->toContain('href="/x"')
        ->not->toContain('<script')
        ->not->toContain('onclick');
});

it('sanitizes a project description on save', function () {
    $project = Project::factory()->create([
        'description' => '<p>Ok</p><script>alert(1)</script>',
    ]);

    expect($project->fresh()->description)
        ->toContain('<p>Ok</p>')
        ->not->toContain('<script');
});

it('leaves a null description untouched', function () {
    $task = Task::factory()->for(Project::factory())->create(['description' => null]);

    expect($task->fresh()->description)->toBeNull();
});
