<?php

use App\Mcp\Servers\KanbrioServer;
use App\Mcp\Tools\CreateStoryTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\UpdateTaskTool;
use App\Models\Project;
use App\Models\Story;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['read', 'write']);
    $this->project = Project::factory()->withMembers([$this->user])->create(['short_name' => 'ABC']);
    $this->story = Story::factory()->for($this->project)->create();
});

it('creates a story with a due date', function () {
    KanbrioServer::tool(CreateStoryTool::class, [
        'short_name' => 'ABC',
        'title' => 'Milestone',
        'due_date' => '2026-07-04',
    ])
        ->assertOk()
        ->assertSee('2026-07-04');

    expect(Story::firstWhere('title', 'Milestone')->due_date->format('Y-m-d'))->toBe('2026-07-04');
});

it('creates a task with a due date and reads it back', function () {
    KanbrioServer::tool(CreateTaskTool::class, [
        'reference' => $this->story->reference,
        'title' => 'Deliver',
        'due_date' => '2026-07-10',
    ])->assertOk();

    $task = Task::firstWhere('title', 'Deliver');

    KanbrioServer::tool(GetTaskTool::class, ['reference' => $task->reference])
        ->assertOk()
        ->assertSee('2026-07-10');
});

it('updates and clears a task due date', function () {
    $task = Task::factory()->for($this->story)->dueOn('2026-07-10')->create();

    KanbrioServer::tool(UpdateTaskTool::class, [
        'reference' => $task->reference,
        'due_date' => '2026-12-25',
    ])->assertOk()->assertSee('2026-12-25');

    expect($task->fresh()->due_date->format('Y-m-d'))->toBe('2026-12-25');

    KanbrioServer::tool(UpdateTaskTool::class, [
        'reference' => $task->reference,
        'due_date' => null,
    ])->assertOk();

    expect($task->fresh()->due_date)->toBeNull();
});

it('rejects a malformed due date', function () {
    KanbrioServer::tool(CreateTaskTool::class, [
        'reference' => $this->story->reference,
        'title' => 'Bad date',
        'due_date' => '07/04/2026',
    ])->assertHasErrors();
});
