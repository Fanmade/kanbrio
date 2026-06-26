<?php

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->project = Project::factory()->create();
    $this->task = Task::factory()->for($this->project)->create();
});

it('lets a member comment but not a viewer', function () {
    $member = userWithRole($this->project, 'member');
    $viewer = userWithRole($this->project, 'viewer');

    // Comment creation is authorized against the project (resolved from the
    // commentable), so the scope is always the project.
    expect($member->can('create-comment', $this->project))->toBeTrue()
        ->and($viewer->can('create-comment', $this->project))->toBeFalse();
});

it('lets the author edit and delete their own comment', function () {
    $author = userWithRole($this->project, 'member');
    $comment = $this->task->comments()->create(['user_id' => $author->id, 'body' => 'mine']);

    expect($author->can('update', $comment))->toBeTrue()
        ->and($author->can('delete', $comment))->toBeTrue();
});

it("only a moderator may edit or delete someone else's comment", function () {
    $author = userWithRole($this->project, 'member');
    $comment = $this->task->comments()->create(['user_id' => $author->id, 'body' => 'theirs']);

    $member = userWithRole($this->project, 'member');          // can comment, cannot moderate
    $moderator = userWithPermissions($this->project, ['moderate-comments']);

    expect($member->can('update', $comment))->toBeFalse()
        ->and($member->can('delete', $comment))->toBeFalse()
        ->and($moderator->can('update', $comment))->toBeTrue()
        ->and($moderator->can('delete', $comment))->toBeTrue();
});
