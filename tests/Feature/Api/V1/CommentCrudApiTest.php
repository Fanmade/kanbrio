<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['short_name' => 'ABC']);
    joinProject($this->project, $this->user);
    $this->task = Task::factory()->for($this->project)->create();
});

it('lists a task comments with replies', function () {
    $root = $this->task->comments()->create(['user_id' => $this->user->id, 'body' => '<p>Root</p>']);
    $this->task->comments()->create(['user_id' => $this->user->id, 'body' => '<p>Reply</p>', 'parent_id' => $root->id]);

    Sanctum::actingAs($this->user, ['read']);

    $this->getJson("/api/v1/tasks/{$this->task->reference}/comments")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', '<p>Root</p>')
        ->assertJsonPath('data.0.replies.0.body', '<p>Reply</p>')
        ->assertJsonStructure(['data' => [['id', 'parent_id', 'body', 'is_deleted', 'author', 'replies']], 'links', 'meta']);
});

it('creates a reply with a write token', function () {
    $root = $this->task->comments()->create(['user_id' => $this->user->id, 'body' => '<p>Root</p>']);
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->postJson("/api/v1/tasks/{$this->task->reference}/comments", [
        'body' => '<p>A reply</p>',
        'parent_id' => $root->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.parent_id', $root->id);
});

it('edits the caller own comment', function () {
    $comment = $this->task->comments()->create(['user_id' => $this->user->id, 'body' => '<p>Old</p>']);
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->patchJson("/api/v1/comments/{$comment->id}", ['body' => '<p>New</p>'])
        ->assertOk()
        ->assertJsonPath('data.body', '<p>New</p>');
});

it('forbids editing another user comment', function () {
    $author = User::factory()->create();
    joinProject($this->project, $author);
    $comment = $this->task->comments()->create(['user_id' => $author->id, 'body' => '<p>Theirs</p>']);

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->patchJson("/api/v1/comments/{$comment->id}", ['body' => '<p>Hijack</p>'])
        ->assertForbidden();
});

it('hard-deletes a comment without replies', function () {
    $comment = $this->task->comments()->create(['user_id' => $this->user->id, 'body' => '<p>Bye</p>']);
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->deleteJson("/api/v1/comments/{$comment->id}")->assertNoContent();

    assertDatabaseMissing('comments', ['id' => $comment->id]);
});

it('tombstones a comment that has replies', function () {
    $comment = $this->task->comments()->create(['user_id' => $this->user->id, 'body' => '<p>Parent</p>']);
    $this->task->comments()->create(['user_id' => $this->user->id, 'body' => '<p>Reply</p>', 'parent_id' => $comment->id]);

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->deleteJson("/api/v1/comments/{$comment->id}")->assertNoContent();

    assertDatabaseHas('comments', ['id' => $comment->id, 'is_deleted' => true, 'body' => '']);
});

it('forbids deleting a comment with a read-only token', function () {
    $comment = $this->task->comments()->create(['user_id' => $this->user->id, 'body' => '<p>Keep</p>']);
    Sanctum::actingAs($this->user, ['read']);

    $this->deleteJson("/api/v1/comments/{$comment->id}")->assertForbidden();
    assertDatabaseHas('comments', ['id' => $comment->id]);
});
