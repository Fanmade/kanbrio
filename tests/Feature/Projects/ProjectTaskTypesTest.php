<?php

use App\Livewire\Projects\ProjectTaskTypes;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * A signed-in project member at the given role and the project.
 *
 * @return array{0: User, 1: Project}
 */
function adminProject(string $role = 'admin'): array
{
    $user = User::factory()->create();
    $project = Project::factory()->create(['short_name' => 'ABC']);
    joinProject($project, $user, $role);

    return [$user, $project];
}

it('lists the project task types with usage counts', function () {
    [$admin, $project] = adminProject();
    $bug = TaskType::factory()->for($project)->create(['name' => 'Bug']);
    Task::factory()->for($project)->create()->taskType()->associate($bug)->save();

    Livewire::actingAs($admin)
        ->test(ProjectTaskTypes::class, ['short_name' => 'ABC'])
        ->assertOk()
        ->assertSeeText('Bug')
        ->assertSeeText('1 task');
});

it('forbids a plain member from managing task types', function () {
    [$member, $project] = adminProject('member');

    Livewire::actingAs($member)
        ->test(ProjectTaskTypes::class, ['short_name' => 'ABC'])
        ->assertForbidden();
});

it('creates a task type', function () {
    [$admin, $project] = adminProject();

    Livewire::actingAs($admin)
        ->test(ProjectTaskTypes::class, ['short_name' => 'ABC'])
        ->call('startCreate')
        ->set('editName', 'Spike')
        ->set('editColor', 'violet')
        ->set('editIcon', 'beaker')
        ->set('editBranchPrefix', 'spike')
        ->call('save')
        ->assertHasNoErrors();

    $type = $project->taskTypes()->where('name', 'Spike')->first();

    expect($type)->not->toBeNull()
        ->and($type->color)->toBe('violet')
        ->and($type->icon)->toBe('beaker')
        ->and($type->branch_prefix)->toBe('spike');
});

it('rejects a duplicate type name, case-insensitively', function () {
    [$admin, $project] = adminProject();
    TaskType::factory()->for($project)->create(['name' => 'Bug']);

    Livewire::actingAs($admin)
        ->test(ProjectTaskTypes::class, ['short_name' => 'ABC'])
        ->call('startCreate')
        ->set('editName', 'bug')
        ->call('save')
        ->assertHasErrors('editName');

    expect($project->taskTypes()->count())->toBe(1);
});

it('edits a task type', function () {
    [$admin, $project] = adminProject();
    $type = TaskType::factory()->for($project)->create(['name' => 'Bug', 'color' => 'red', 'icon' => 'bug-ant']);

    Livewire::actingAs($admin)
        ->test(ProjectTaskTypes::class, ['short_name' => 'ABC'])
        ->call('startEdit', $type->id)
        ->set('editName', 'Defect')
        ->set('editColor', 'rose')
        ->call('save')
        ->assertHasNoErrors();

    $type->refresh();
    expect($type->name)->toBe('Defect')
        ->and($type->color)->toBe('rose');
});

it('rejects an icon outside the allowed set', function () {
    [$admin, $project] = adminProject();

    Livewire::actingAs($admin)
        ->test(ProjectTaskTypes::class, ['short_name' => 'ABC'])
        ->call('startCreate')
        ->set('editName', 'Spike')
        ->set('editIcon', 'not-a-real-icon')
        ->call('save')
        ->assertHasErrors('editIcon');
});

it('deletes a task type, leaving its tasks untyped', function () {
    [$admin, $project] = adminProject();
    $type = TaskType::factory()->for($project)->create();
    $task = Task::factory()->for($project)->create();
    $task->taskType()->associate($type)->save();

    Livewire::actingAs($admin)
        ->test(ProjectTaskTypes::class, ['short_name' => 'ABC'])
        ->call('deleteType', $type->id);

    expect($project->taskTypes()->whereKey($type->id)->exists())->toBeFalse()
        ->and($task->fresh()->task_type_id)->toBeNull();
});

it('reorders task types', function () {
    [$admin, $project] = adminProject();
    TaskType::provisionDefaults($project); // Feature, Bug, Chore (positions 0,1,2)
    $feature = $project->taskTypes()->where('name', 'Feature')->first();

    Livewire::actingAs($admin)
        ->test(ProjectTaskTypes::class, ['short_name' => 'ABC'])
        ->call('moveDown', $feature->id);

    expect($project->taskTypes()->pluck('name')->all())->toBe(['Bug', 'Feature', 'Chore']);
});
