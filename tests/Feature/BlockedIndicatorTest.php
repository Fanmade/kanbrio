<?php

use App\Enums\Status;
use App\Livewire\Board;
use App\Livewire\Projects\ProjectBoard;
use App\Models\Project;
use App\Models\Story;
use App\Models\Task;
use App\Models\User;
use App\Support\BlockedTasks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->member = User::factory()->create();
    $this->project = Project::factory()->create(['short_name' => 'ABC']);
    $this->project->members()->attach($this->member);
    $this->story = Story::factory()->for($this->project)->create();
});

test('a task blocked by an unfinished task is flagged', function () {
    $task = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $blocker = Task::factory()->for($this->story)->status(Status::InProgress)->create();
    $task->addBlocker($blocker);

    expect(BlockedTasks::ids([$task->id, $blocker->id]))->toBe([$task->id]);
});

test('a task is no longer flagged once its blocker is done', function () {
    $task = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $blocker = Task::factory()->for($this->story)->status(Status::Done)->create();
    $task->addBlocker($blocker);

    expect(BlockedTasks::ids([$task->id]))->toBe([]);
});

test('a task blocked by an incomplete story is flagged', function () {
    $task = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $blockingStory = Story::factory()->for($this->project)->create();
    Task::factory()->for($blockingStory)->status(Status::ToDo)->create();
    $task->addBlocker($blockingStory);

    expect(BlockedTasks::ids([$task->id]))->toBe([$task->id]);

    $blockingStory->tasks()->update(['status' => Status::Done]);

    expect(BlockedTasks::ids([$task->id]))->toBe([]);
});

test('the project board renders a blocked indicator', function () {
    $task = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $blocker = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $task->addBlocker($blocker);

    Livewire::actingAs($this->member)
        ->test(ProjectBoard::class, ['short_name' => 'ABC'])
        ->assertSeeHtml('data-test="blocked-'.$task->id.'"')
        ->assertDontSeeHtml('data-test="blocked-'.$blocker->id.'"');
});

test('the global board renders a blocked indicator', function () {
    $task = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $blocker = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $task->addBlocker($blocker);

    Livewire::actingAs($this->member)
        ->test(Board::class)
        ->assertSeeHtml('data-test="blocked-'.$task->id.'"');
});

test('moving a blocker to done clears the dependent indicator on the board', function () {
    $task = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $blocker = Task::factory()->for($this->story)->status(Status::ToDo)->create();
    $task->addBlocker($blocker);

    Livewire::actingAs($this->member)
        ->test(ProjectBoard::class, ['short_name' => 'ABC'])
        ->assertSeeHtml('data-test="blocked-'.$task->id.'"')
        ->call('moveTask', $blocker->id, Status::Done->value)
        ->assertDontSeeHtml('data-test="blocked-'.$task->id.'"');
});
