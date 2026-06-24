<?php

use App\Livewire\Projects\ProjectList;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('belongs to a project and has many tasks', function () {
    $type = TaskType::factory()->create(['name' => 'Feature']);
    $task = Task::factory()->for($type->project)->create(['task_type_id' => $type->id]);

    expect($type->project)->toBeInstanceOf(Project::class)
        ->and($type->tasks->pluck('id'))->toContain($task->id)
        ->and($task->taskType->is($type))->toBeTrue();
});

it('leaves a task untyped when its type is deleted', function () {
    $type = TaskType::factory()->create();
    $task = Task::factory()->for($type->project)->create(['task_type_id' => $type->id]);

    $type->delete();

    expect($task->refresh()->task_type_id)->toBeNull()
        ->and(Task::query()->whereKey($task->getKey())->exists())->toBeTrue();
});

it('enforces case-insensitive unique names within a project', function () {
    $project = Project::factory()->create();
    TaskType::factory()->for($project)->create(['name' => 'Bug']);

    TaskType::factory()->for($project)->create(['name' => 'bug']);
})->throws(QueryException::class);

it('allows the same type name in different projects', function () {
    TaskType::factory()->for(Project::factory())->create(['name' => 'Bug']);
    TaskType::factory()->for(Project::factory())->create(['name' => 'Bug']);

    expect(TaskType::query()->where('name', 'Bug')->count())->toBe(2);
});

it('seeds the default types into a project, in order', function () {
    $project = Project::factory()->create();

    TaskType::provisionDefaults($project);

    $types = $project->taskTypes()->get();

    expect($types)->toHaveCount(3)
        ->and($types->pluck('name')->all())->toBe(['Feature', 'Bug', 'Chore'])
        ->and($types->firstWhere('name', 'Bug')->color)->toBe('red')
        ->and($types->firstWhere('name', 'Bug')->branch_prefix)->toBe('bugfix')
        ->and($types->firstWhere('name', 'Feature')->icon)->toBe('sparkles');
});

it('provisions defaults idempotently', function () {
    $project = Project::factory()->create();

    TaskType::provisionDefaults($project);
    TaskType::provisionDefaults($project);

    expect($project->taskTypes()->count())->toBe(3);
});

it('seeds default types when a project is created through the UI', function () {
    $user = User::factory()->canCreateProjects()->create();

    Livewire::actingAs($user)
        ->test(ProjectList::class)
        ->set('title', 'Apollo')
        ->set('short_name', 'APO')
        ->call('createProject');

    $project = Project::query()->where('short_name', 'APO')->sole();

    expect($project->taskTypes()->pluck('name')->all())->toBe(['Feature', 'Bug', 'Chore']);
});
