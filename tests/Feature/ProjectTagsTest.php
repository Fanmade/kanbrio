<?php

use App\Livewire\Projects\ProjectTags;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * A signed-in project member at the given role, the project, and a tag on it.
 *
 * @return array{0: User, 1: Project, 2: Tag}
 */
function memberProjectTag(string $role = 'member'): array
{
    $user = User::factory()->create();
    $project = Project::factory()->create();
    joinProject($project, $user, $role);
    $tag = Tag::factory()->for($project)->create(['name' => 'Bug', 'color' => 'sky']);

    return [$user, $project, $tag];
}

it('lists the project tags with their usage counts', function () {
    [$user, $project, $tag] = memberProjectTag();
    Task::factory()->for($project)->create()->tags()->attach($tag);
    Tag::factory()->for($project)->create(['name' => 'Chore']);

    Livewire::actingAs($user)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->assertOk()
        ->assertSeeText('Bug')
        ->assertSeeText('Chore')
        ->assertSeeText('1 task');
});

it('forbids a non-member from opening the tag page', function () {
    $stranger = User::factory()->create();
    $project = Project::factory()->create();

    Livewire::actingAs($stranger)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->assertStatus(403);
});

it('renames a tag, logging the change', function () {
    [$user, $project, $tag] = memberProjectTag();

    Livewire::actingAs($user)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->call('startEdit', $tag->id)
        ->set('editName', 'Defect')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($tag->fresh()->name)->toBe('Defect')
        ->and($project->activities()->where('action', 'tag_renamed')->count())->toBe(1);
});

it('recolors a tag, logging the change', function () {
    [$user, $project, $tag] = memberProjectTag();

    Livewire::actingAs($user)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->call('startEdit', $tag->id)
        ->set('editColor', 'rose')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($tag->fresh()->color)->toBe('rose')
        ->and($project->activities()->where('action', 'tag_recolored')->count())->toBe(1);
});

it('rejects a blank name and an unknown color', function () {
    [$user, $project, $tag] = memberProjectTag();

    Livewire::actingAs($user)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->call('startEdit', $tag->id)
        ->set('editName', '   ')
        ->set('editColor', 'not-a-color')
        ->call('saveEdit')
        ->assertHasErrors(['editName', 'editColor']);

    expect($tag->fresh()->name)->toBe('Bug');
});

it('merges into the existing tag when a rename collides, re-pointing tasks', function () {
    [$user, $project, $bug] = memberProjectTag();
    $defect = Tag::factory()->for($project)->create(['name' => 'Defect', 'color' => 'rose']);
    $task = Task::factory()->for($project)->create();
    $task->tags()->attach($bug);

    Livewire::actingAs($user)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->call('startEdit', $bug->id)
        ->set('editName', 'defect') // case-insensitive collision with "Defect"
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect(Tag::find($bug->id))->toBeNull()
        ->and($task->fresh()->tags()->pluck('tags.id')->all())->toBe([$defect->id])
        ->and($project->activities()->where('action', 'tag_merged')->count())->toBe(1);
});

it('merges the selected tags into the chosen surviving tag, re-pointing tasks', function () {
    [$admin, $project] = [User::factory()->create(), Project::factory()->create()];
    joinProject($project, $admin, 'admin');
    $docs = Tag::factory()->for($project)->create(['name' => 'Docs']);
    $documentation = Tag::factory()->for($project)->create(['name' => 'Documentation']);

    $onlyDocs = Task::factory()->for($project)->create();
    $onlyDocs->tags()->attach($docs);
    $both = Task::factory()->for($project)->create();
    $both->tags()->attach([$docs->id, $documentation->id]);

    Livewire::actingAs($admin)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->set('selected', [$docs->id, $documentation->id])
        ->set('mergeTargetId', $documentation->id)
        ->call('mergeTags')
        ->assertSet('merging', false)
        ->assertSet('selected', []);

    expect(Tag::find($docs->id))->toBeNull()
        ->and(Tag::find($documentation->id))->not->toBeNull()
        ->and($onlyDocs->fresh()->tags()->pluck('tags.id')->all())->toBe([$documentation->id])
        ->and($both->fresh()->tags()->pluck('tags.id')->all())->toBe([$documentation->id])
        ->and($project->activities()->where('action', 'tag_merged')->count())->toBe(1);
});

it('defaults to the most-used tag and shows the names when opening the merge dialog', function () {
    [$admin, $project] = [User::factory()->create(), Project::factory()->create()];
    joinProject($project, $admin, 'admin');
    $small = Tag::factory()->for($project)->create(['name' => 'Spike']);
    $big = Tag::factory()->for($project)->create(['name' => 'Research']);

    Task::factory()->for($project)->create()->tags()->attach($small);
    Task::factory()->for($project)->create()->tags()->attach($big);
    Task::factory()->for($project)->create()->tags()->attach($big);

    Livewire::actingAs($admin)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->set('selected', [$small->id, $big->id])
        ->call('startMerge')
        ->assertSet('merging', true)
        ->assertSet('mergeTargetId', $big->id)
        ->assertSeeText('Spike')
        ->assertSeeText('Research');
});

it('does not merge when fewer than two tags are selected', function () {
    [$admin, $project, $tag] = memberProjectTag('admin');

    Livewire::actingAs($admin)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->set('selected', [$tag->id])
        ->set('mergeTargetId', $tag->id)
        ->call('mergeTags');

    expect(Tag::find($tag->id))->not->toBeNull();
});

it('forbids a plain member from merging tags', function () {
    [$member, $project, $tag] = memberProjectTag('member');
    $other = Tag::factory()->for($project)->create(['name' => 'Chore']);

    Livewire::actingAs($member)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->set('selected', [$tag->id, $other->id])
        ->set('mergeTargetId', $tag->id)
        ->call('mergeTags')
        ->assertStatus(403);

    expect(Tag::find($tag->id))->not->toBeNull()
        ->and(Tag::find($other->id))->not->toBeNull();
});

it('lets an admin delete a tag, detaching it from tasks', function () {
    [$admin, $project, $tag] = memberProjectTag('admin');
    $task = Task::factory()->for($project)->create();
    $task->tags()->attach($tag);

    Livewire::actingAs($admin)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->call('deleteTag', $tag->id);

    expect(Tag::find($tag->id))->toBeNull()
        ->and($task->fresh()->tags()->count())->toBe(0)
        ->and($project->activities()->where('action', 'tag_deleted')->count())->toBe(1);
});

it('forbids a plain member from deleting a tag', function () {
    [$member, $project, $tag] = memberProjectTag('member');

    Livewire::actingAs($member)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->call('deleteTag', $tag->id)
        ->assertStatus(403);

    expect(Tag::find($tag->id))->not->toBeNull();
});

it('sets and clears a tag icon through the edit modal', function () {
    [$user, $project, $tag] = memberProjectTag();

    $component = Livewire::actingAs($user)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->call('startEdit', $tag->id)
        ->set('editIcon', 'beaker')
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($tag->refresh()->icon)->toBe('beaker');

    $component->call('startEdit', $tag->id)
        ->call('clearIcon')
        ->assertSet('editIcon', null)
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($tag->refresh()->icon)->toBeNull();
});

it('rejects a tag icon outside the allowed set', function () {
    [$user, $project, $tag] = memberProjectTag();

    Livewire::actingAs($user)
        ->test(ProjectTags::class, ['short_name' => $project->short_name])
        ->call('startEdit', $tag->id)
        ->set('editIcon', 'not-a-real-icon')
        ->call('saveEdit')
        ->assertHasErrors('editIcon');
});
