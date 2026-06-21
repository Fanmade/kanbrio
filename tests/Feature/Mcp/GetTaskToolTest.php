<?php

use App\Enums\Status;
use App\Mcp\Servers\KanbrioServer;
use App\Mcp\Tools\GetTaskTool;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a task in a project the user is a member of', function () {
    $user = User::factory()->create();
    $project = Project::factory()->withMembers([$user])->create(['short_name' => 'ABC']);
    $task = Task::factory()->for($project)->create();

    KanbrioServer::actingAs($user)->tool(GetTaskTool::class, ['reference' => $task->reference])
        ->assertOk()
        ->assertSee($task->reference)
        ->assertSee($task->status->value);
});

it('denies access to a task in a project the user is not a member of', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['short_name' => 'ABC']);
    $task = Task::factory()->for($project)->create();

    KanbrioServer::actingAs($user)->tool(GetTaskTool::class, ['reference' => $task->reference])
        ->assertHasErrors();
});

it('returns an error for a malformed task reference', function () {
    $user = User::factory()->create();

    KanbrioServer::actingAs($user)->tool(GetTaskTool::class, ['reference' => 'ABC1'])
        ->assertHasErrors();
});

it('includes the task tags', function () {
    $user = User::factory()->create();
    $project = Project::factory()->withMembers([$user])->create(['short_name' => 'ABC']);
    $task = Task::factory()->for($project)->create();
    $task->syncTags('design');

    KanbrioServer::actingAs($user)->tool(GetTaskTool::class, ['reference' => $task->reference])
        ->assertOk()
        ->assertSee('design');
});

it('exposes the task hierarchy: parent, ancestors, children and rolled-up progress', function () {
    $user = User::factory()->create();
    $project = Project::factory()->withMembers([$user])->create(['short_name' => 'ABC']);
    $root = Task::factory()->for($project)->create();
    $middle = Task::factory()->for($project)->childOf($root)->create();
    $leaf = Task::factory()->for($project)->childOf($middle)->status(Status::Done)->create();

    KanbrioServer::actingAs($user)->tool(GetTaskTool::class, ['reference' => $middle->reference])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($root, $middle, $leaf) {
            $json->where('reference', $middle->reference)
                ->where('parent', $root->reference)
                ->where('ancestors', [$root->reference])
                ->where('children.0.reference', $leaf->reference)
                ->where('children.0.status', Status::Done->value)
                ->where('progress.done', 1)   // the one Done descendant
                ->where('progress.total', 1)  // the leaf is the only descendant
                ->etc();
        });
});

it('reports a top-level task as having no parent or ancestors', function () {
    $user = User::factory()->create();
    $project = Project::factory()->withMembers([$user])->create(['short_name' => 'ABC']);
    $task = Task::factory()->for($project)->create();

    KanbrioServer::actingAs($user)->tool(GetTaskTool::class, ['reference' => $task->reference])
        ->assertOk()
        ->assertStructuredContent(function ($json) {
            $json->where('parent', null)
                ->where('ancestors', [])
                ->where('children', [])
                ->etc();
        });
});

it('lists the task attachments with their ids', function () {
    $user = User::factory()->create();
    $project = Project::factory()->withMembers([$user])->create(['short_name' => 'ABC']);
    $task = Task::factory()->for($project)->create();
    $attachment = Attachment::factory()->create([
        'attachable_id' => $task->id,
        'attachable_type' => $task->getMorphClass(),
        'name' => 'diagram.png',
        'is_inline' => true,
    ]);

    KanbrioServer::actingAs($user)->tool(GetTaskTool::class, ['reference' => $task->reference])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($attachment) {
            $json->where('attachments.0.id', $attachment->id)
                ->where('attachments.0.name', 'diagram.png')
                ->where('attachments.0.is_inline', true)
                ->etc();
        });
});
