<?php

use App\Livewire\Tasks\TaskView;
use App\Models\Project;
use App\Models\Story;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('attaches comma-separated tags, trimming and de-duplicating', function () {
    $task = Task::factory()->create();

    $task->syncTags('bug,  urgent , Bug');

    expect($task->tags()->count())->toBe(2)
        ->and(Tag::count())->toBe(2);
});

it('reuses the same tag across stories and tasks', function () {
    $task = Task::factory()->create();
    $story = Story::factory()->create();

    $task->syncTags('shared');
    $story->syncTags('shared');

    expect(Tag::where('name', 'shared')->count())->toBe(1)
        ->and($task->tags()->count())->toBe(1)
        ->and($story->tags()->count())->toBe(1);
});

it('detaches tags removed from the list', function () {
    $task = Task::factory()->create();

    $task->syncTags('a, b, c');
    $task->syncTags('a');

    expect($task->tags()->pluck('name')->all())->toBe(['a']);
});

it('saves tags through the task view and logs the change', function () {
    $member = User::factory()->create();
    $project = Project::factory()->create(['short_name' => 'ABC']);
    $project->members()->attach($member);
    $story = Story::factory()->for($project)->create();
    $task = Task::factory()->for($story)->create();

    Livewire::actingAs($member)
        ->test(TaskView::class, [
            'short_name' => 'ABC',
            'story_number' => $story->story_number,
            'task_number' => $task->task_number,
        ])
        ->call('edit')
        ->set('tags', 'frontend, ux')
        ->call('save');

    expect($task->fresh()->tags()->pluck('name')->all())->toBe(['frontend', 'ux'])
        ->and($task->activities()->where('action', 'tags_changed')->count())->toBe(1);
});
