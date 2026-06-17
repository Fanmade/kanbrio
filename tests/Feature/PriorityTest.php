<?php

use App\Enums\Priority;
use App\Models\Project;
use App\Models\Story;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the middle level as the default priority', function () {
    expect(Priority::default())->toBe(Priority::Medium)
        ->and(Priority::Medium->value)->toBe(3);
});

it('defaults a story without an explicit priority to medium', function () {
    $project = Project::factory()->create();

    $story = $project->stories()->create(['title' => 'No priority given']);

    expect($story->refresh()->priority)->toBe(Priority::Medium);
});

it('casts the priority column to the enum', function () {
    $story = Story::factory()->priority(Priority::High)->create();

    expect($story->refresh()->priority)->toBe(Priority::High);
});

it('inherits the parent story priority when a task is created without one', function () {
    $story = Story::factory()->priority(Priority::Highest)->create();

    $task = $story->tasks()->create(['title' => 'Inherits from story']);

    expect($task->refresh()->priority)->toBe(Priority::Highest);
});

it('lets a task override the inherited priority', function () {
    $story = Story::factory()->priority(Priority::Highest)->create();

    $task = $story->tasks()->create([
        'title' => 'Explicit priority',
        'priority' => Priority::Lowest,
    ]);

    expect($task->refresh()->priority)->toBe(Priority::Lowest);
});

it('sorts by the integer-backed priority column', function () {
    $story = Story::factory()->priority(Priority::Medium)->create();

    Task::factory()->for($story)->priority(Priority::High)->create();
    Task::factory()->for($story)->priority(Priority::Lowest)->create();
    Task::factory()->for($story)->priority(Priority::Highest)->create();

    $ordered = Task::query()->where('story_id', $story->id)->orderByDesc('priority')->get()
        ->map(static fn (Task $task): Priority => $task->priority)
        ->all();

    expect($ordered[0])->toBe(Priority::Highest)
        ->and($ordered[1])->toBe(Priority::High)
        ->and(end($ordered))->toBe(Priority::Lowest);
});
