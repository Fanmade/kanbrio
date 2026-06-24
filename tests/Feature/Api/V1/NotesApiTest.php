<?php

use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists only the caller own notes', function () {
    Note::factory()->for($this->user)->create(['title' => 'Mine']);
    Note::factory()->create(['title' => 'Theirs']);

    Sanctum::actingAs($this->user, ['read']);

    $this->getJson('/api/v1/notes')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Mine')
        ->assertJsonPath('data.0.owned', true);
});

it('shows a public project note to a member', function () {
    $project = Project::factory()->create(['short_name' => 'ABC']);
    $member = User::factory()->create();
    joinProject($project, $member);
    $note = Note::factory()->publicTo($project)->create(['title' => 'Shared']);

    Sanctum::actingAs($member, ['read']);

    $this->getJson("/api/v1/notes/{$note->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Shared')
        ->assertJsonPath('data.owned', false)
        ->assertJsonPath('data.project', 'ABC');
});

it('404s a private note of another user', function () {
    $note = Note::factory()->create();
    Sanctum::actingAs($this->user, ['read']);

    $this->getJson("/api/v1/notes/{$note->id}")->assertNotFound();
});

it('creates a note', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->postJson('/api/v1/notes', ['title' => 'Idea', 'body' => '<p>Spark</p>'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Idea')
        ->assertJsonPath('data.owned', true);

    assertDatabaseHas('notes', ['title' => 'Idea', 'user_id' => $this->user->id]);
});

it('forbids creating a note with a read-only token', function () {
    Sanctum::actingAs($this->user, ['read']);

    $this->postJson('/api/v1/notes', ['title' => 'Nope'])->assertForbidden();
});

it('updates the caller own note', function () {
    $note = Note::factory()->for($this->user)->create(['title' => 'Old']);
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->patchJson("/api/v1/notes/{$note->id}", ['title' => 'New'])
        ->assertOk()
        ->assertJsonPath('data.title', 'New');
});

it('forbids updating another user note', function () {
    $note = Note::factory()->create(['title' => 'Theirs']);
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->patchJson("/api/v1/notes/{$note->id}", ['title' => 'Hijack'])->assertForbidden();
});

it('deletes the caller own note', function () {
    $note = Note::factory()->for($this->user)->create();
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->deleteJson("/api/v1/notes/{$note->id}")->assertNoContent();
    assertDatabaseMissing('notes', ['id' => $note->id, 'deleted_at' => null]);
});

it('converts a note into a task', function () {
    $project = Project::factory()->create(['short_name' => 'ABC']);
    joinProject($project, $this->user);
    $note = Note::factory()->for($this->user)->create(['title' => 'Build it', 'body' => '<p>Details</p>']);

    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->postJson("/api/v1/notes/{$note->id}/convert", ['project' => 'ABC'])
        ->assertOk()
        ->assertJsonPath('data.id', $note->id);

    $task = $project->tasks()->where('title', 'Build it')->sole();

    expect($note->fresh()->converted_task_id)->toBe($task->id)
        ->and($response->json('data.converted_task'))->toBe($task->reference);
});
