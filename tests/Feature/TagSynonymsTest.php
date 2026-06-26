<?php

use App\Livewire\Tasks\CreateTaskModal;
use App\Models\Project;
use App\Models\Tag;
use App\Models\TagSynonym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->project = Project::factory()->create();
});

it('suggests a tag in the create dialog when the query matches a synonym', function () {
    $member = User::factory()->create();
    joinProject($this->project, $member);
    $tag = Tag::factory()->for($this->project)->create(['name' => 'Research']);
    $tag->syncSynonyms(['Evaluation']);

    $suggestions = Livewire::actingAs($member)
        ->test(CreateTaskModal::class)
        ->call('open', $this->project->id)
        ->set('tagQuery', 'eval')
        ->instance()
        ->tagSuggestions();

    expect($suggestions->pluck('name')->all())->toContain('Research');
});

it('syncs synonyms, trimming, de-duping case-insensitively and dropping the tag name', function () {
    $tag = Tag::factory()->for($this->project)->create(['name' => 'Research']);

    $tag->syncSynonyms(['Evaluation', '  Spike  ', 'evaluation', 'research', '']);

    expect($tag->synonyms()->pluck('name')->sort()->values()->all())
        ->toBe(['Evaluation', 'Spike']);
});

it('keeps existing synonym rows when syncing rather than recreating them', function () {
    $tag = Tag::factory()->for($this->project)->create(['name' => 'Feature']);
    $tag->syncSynonyms(['Enhancement', 'Improvement']);
    $keptId = $tag->synonyms()->where('name', 'Enhancement')->value('id');

    // Drop one, keep the other, add a new one.
    $tag->syncSynonyms(['Enhancement', 'Upgrade']);

    expect($tag->synonyms()->pluck('name')->sort()->values()->all())->toBe(['Enhancement', 'Upgrade'])
        ->and($tag->synonyms()->where('name', 'Enhancement')->value('id'))->toBe($keptId);
});

it('appends synonyms while skipping existing names and the tag name', function () {
    $tag = Tag::factory()->for($this->project)->create(['name' => 'Bug']);
    $tag->syncSynonyms(['Defect']);

    $tag->addSynonyms(['defect', 'BUG', 'Glitch']);

    expect($tag->synonyms()->pluck('name')->sort()->values()->all())
        ->toBe(['Defect', 'Glitch']);
});

it('adopts a folded-in tag and its synonyms as synonyms of the survivor on merge', function () {
    $survivor = Tag::factory()->for($this->project)->create(['name' => 'Research']);
    $survivor->syncSynonyms(['Spike']);
    $loser = Tag::factory()->for($this->project)->create(['name' => 'Evaluation']);
    $loser->syncSynonyms(['Assessment', 'spike']); // "spike" already on survivor

    $loser->mergeInto($survivor, adoptAsSynonym: true);

    expect($survivor->synonyms()->pluck('name')->sort()->values()->all())
        ->toBe(['Assessment', 'Evaluation', 'Spike'])
        ->and(Tag::find($loser->id))->toBeNull();
});

it('does not adopt synonyms on a plain merge', function () {
    $survivor = Tag::factory()->for($this->project)->create(['name' => 'Research']);
    $loser = Tag::factory()->for($this->project)->create(['name' => 'Evaluation']);

    $loser->mergeInto($survivor);

    expect($survivor->synonyms()->count())->toBe(0);
});

it('deletes a tag\'s synonyms when the tag is deleted', function () {
    $tag = Tag::factory()->for($this->project)->create(['name' => 'Research']);
    $tag->syncSynonyms(['Evaluation', 'Spike']);

    $tag->delete();

    expect(TagSynonym::count())->toBe(0);
});
