<?php

use App\Livewire\Notes\NoteList;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('requires authentication to reach the notes page', function () {
    $this->get(route('notes.index'))->assertRedirect(route('login'));
});

it('renders the notes page for an authenticated user', function () {
    $this->actingAs($this->user)->get(route('notes.index'))->assertOk();
});

it('lists the user\'s own notes, hiding others and empty drafts', function () {
    $mine = Note::factory()->for($this->user)->create(['title' => 'Grocery list']);
    Note::factory()->for($this->user)->create(['title' => '']);   // abandoned draft
    Note::factory()->create(['title' => 'Someone else note']);    // another user's

    $component = Livewire::actingAs($this->user)->test(NoteList::class);

    expect($component->instance()->notes()->pluck('id'))
        ->toContain($mine->id)
        ->toHaveCount(1);

    $component->assertSee('Grocery list')->assertDontSee('Someone else note');
});

it('orders notes by most recently updated first', function () {
    $older = Note::factory()->for($this->user)->create(['title' => 'Older', 'updated_at' => now()->subDay()]);
    $newer = Note::factory()->for($this->user)->create(['title' => 'Newer', 'updated_at' => now()]);

    $ids = Livewire::actingAs($this->user)->test(NoteList::class)->instance()->notes()->pluck('id');

    expect($ids->first())->toBe($newer->id)
        ->and($ids->last())->toBe($older->id);
});

it('deletes one of the user\'s own notes from the page', function () {
    $note = Note::factory()->for($this->user)->create(['title' => 'Throwaway']);

    Livewire::actingAs($this->user)
        ->test(NoteList::class)
        ->call('deleteNote', $note->id)
        ->assertHasNoErrors();

    expect(Note::query()->whereKey($note->id)->exists())->toBeFalse();
});

it('refreshes the list when a note is saved elsewhere', function () {
    $component = Livewire::actingAs($this->user)->test(NoteList::class);

    expect($component->instance()->notes())->toHaveCount(0);

    Note::factory()->for($this->user)->create(['title' => 'Fresh idea']);

    $component->dispatch('note-saved')->assertSee('Fresh idea');
});
