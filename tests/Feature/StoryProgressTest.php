<?php

use App\Enums\Status;
use App\Models\Story;
use App\Models\Task;
use App\Support\StoryProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

test('a story with no tasks reports empty progress', function () {
    $progress = Story::factory()->create()->progress();

    expect($progress->done)->toBe(0)
        ->and($progress->total)->toBe(0)
        ->and($progress->percent())->toBe(0)
        ->and($progress->hasTasks())->toBeFalse();
});

test('a story with every task done is fully complete', function () {
    $story = Story::factory()->create();
    Task::factory()->for($story)->status(Status::Done)->count(3)->create();

    $progress = $story->progress();

    expect($progress->done)->toBe(3)
        ->and($progress->total)->toBe(3)
        ->and($progress->percent())->toBe(100)
        ->and($progress->hasTasks())->toBeTrue();
});

test('a story with no task done is zero percent complete', function () {
    $story = Story::factory()->create();
    Task::factory()->for($story)->status(Status::Planned)->count(2)->create();
    Task::factory()->for($story)->status(Status::InProgress)->create();

    $progress = $story->progress();

    expect($progress->done)->toBe(0)
        ->and($progress->total)->toBe(3)
        ->and($progress->percent())->toBe(0);
});

test('only done tasks count toward completeness', function () {
    $story = Story::factory()->create();
    Task::factory()->for($story)->status(Status::Done)->create();
    Task::factory()->for($story)->status(Status::InProgress)->create();
    Task::factory()->for($story)->status(Status::ToDo)->create();
    Task::factory()->for($story)->status(Status::Planned)->create();

    $progress = $story->progress();

    expect($progress->done)->toBe(1)
        ->and($progress->total)->toBe(4)
        ->and($progress->percent())->toBe(25);
});

test('the percentage is rounded to a whole number', function () {
    $story = Story::factory()->create();
    Task::factory()->for($story)->status(Status::Done)->create();
    Task::factory()->for($story)->status(Status::ToDo)->count(2)->create();

    expect($story->progress()->percent())->toBe(33);
});

test('progress is computed without extra queries when tasks are preloaded', function () {
    $story = Story::factory()->create();
    Task::factory()->for($story)->status(Status::Done)->create();
    Task::factory()->for($story)->status(Status::ToDo)->create();

    $story->load('tasks');

    DB::enableQueryLog();
    $progress = $story->progress();
    DB::disableQueryLog();

    expect(DB::getQueryLog())->toHaveCount(0)
        ->and($progress->done)->toBe(1)
        ->and($progress->total)->toBe(2);
});

test('the story-progress component renders a bar and a done-of-total label', function () {
    $html = Blade::render('<x-story-progress :progress="$progress" />', [
        'progress' => new StoryProgress(done: 2, total: 5),
    ]);

    expect($html)
        ->toContain('2 / 5')
        ->toContain('done');
});

test('the story-progress component renders nothing for a story with no tasks', function () {
    $html = Blade::render('<x-story-progress :progress="$progress" />', [
        'progress' => new StoryProgress(done: 0, total: 0),
    ]);

    expect(trim($html))->toBe('');
});
