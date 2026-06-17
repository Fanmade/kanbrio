<?php

use App\Enums\Priority;
use App\Enums\Status;
use App\Livewire\Stories\StoryView;
use App\Models\Project;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->member = User::factory()->create();
    $this->project = Project::factory()->create(['short_name' => 'ABC']);
    $this->project->members()->attach($this->member);
    $this->story = Story::factory()->for($this->project)->priority(Priority::High)->create();
});

it('creates a task from the story view, inheriting the story priority', function () {
    Livewire::actingAs($this->member)
        ->test(StoryView::class, ['short_name' => 'ABC', 'story_number' => $this->story->story_number])
        ->call('openTaskModal')
        ->assertSet('taskPriority', Priority::High->value)
        ->set('taskTitle', 'Write the docs')
        ->call('createTask')
        ->assertSet('showTaskModal', false);

    $task = $this->story->tasks()->where('title', 'Write the docs')->first();

    expect($task)->not->toBeNull()
        ->and($task->priority)->toBe(Priority::High)
        ->and($task->status)->toBe(Status::Planned);
});

it('lets the new task override the inherited priority and pick a status', function () {
    Livewire::actingAs($this->member)
        ->test(StoryView::class, ['short_name' => 'ABC', 'story_number' => $this->story->story_number])
        ->call('openTaskModal')
        ->set('taskTitle', 'Urgent fix')
        ->set('taskPriority', Priority::Highest->value)
        ->set('taskStatus', Status::ToDo->value)
        ->call('createTask');

    $task = $this->story->tasks()->where('title', 'Urgent fix')->first();

    expect($task->priority)->toBe(Priority::Highest)
        ->and($task->status)->toBe(Status::ToDo);
});

it('requires a title for the new task', function () {
    Livewire::actingAs($this->member)
        ->test(StoryView::class, ['short_name' => 'ABC', 'story_number' => $this->story->story_number])
        ->call('openTaskModal')
        ->set('taskTitle', '')
        ->call('createTask')
        ->assertHasErrors(['taskTitle' => 'required']);

    expect($this->story->tasks()->count())->toBe(0);
});

it('forbids a non-member from opening the story view', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(StoryView::class, ['short_name' => 'ABC', 'story_number' => $this->story->story_number])
        ->assertForbidden();
});
