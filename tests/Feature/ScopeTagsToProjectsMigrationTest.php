<?php

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The migration runs DDL, which cannot live inside RefreshDatabase's wrapping
// transaction. Rebuild the schema fresh around each test instead.
beforeEach(fn () => Artisan::call('migrate:fresh'));
afterEach(fn () => Artisan::call('migrate:fresh'));

/**
 * Recreate the global `tags`/`taggables` schema as it was before this
 * migration: a single name-unique tags table and a morph pivot, no project_id.
 * The unique index keeps its original "keywords_*" name (the rename migration
 * renamed the table, not the index), matching what the migration drops.
 */
function rebuildGlobalTagsSchema(): void
{
    Schema::dropIfExists('taggables');
    Schema::dropIfExists('tags');

    Schema::create('tags', static function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('color')->default('zinc');
        $table->timestamps();
        $table->unique('name', 'keywords_name_unique');
    });

    Schema::create('taggables', static function (Blueprint $table): void {
        $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
        $table->morphs('taggable');
        $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
    });
}

function loadScopeTagsMigration(): object
{
    return require database_path('migrations/2026_06_23_111119_scope_tags_to_projects.php');
}

function insertGlobalTag(string $name, string $color = 'zinc'): int
{
    return DB::table('tags')->insertGetId([
        'name' => $name,
        'color' => $color,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function attachGlobalTag(int $tagId, Task $task): void
{
    DB::table('taggables')->insert([
        'tag_id' => $tagId,
        'taggable_id' => $task->id,
        'taggable_type' => $task->getMorphClass(),
    ]);
}

it('splits a tag shared across projects into one tag per project and repoints the pivot', function () {
    rebuildGlobalTagsSchema();

    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();
    $taskA1 = Task::factory()->for($projectA)->create();
    $taskA2 = Task::factory()->for($projectA)->create();
    $taskB = Task::factory()->for($projectB)->create();

    $tagId = insertGlobalTag('shared', 'sky');
    attachGlobalTag($tagId, $taskA1);
    attachGlobalTag($tagId, $taskA2);
    attachGlobalTag($tagId, $taskB);

    loadScopeTagsMigration()->up();

    $tags = DB::table('tags')->where('name', 'shared')->get();
    $byProject = $tags->keyBy('project_id');

    expect($tags)->toHaveCount(2)
        ->and($byProject->has($projectA->id))->toBeTrue()
        ->and($byProject->has($projectB->id))->toBeTrue()
        // The original row (lowest id) keeps the first project; the clone is new.
        ->and((int) $tags->min('id'))->toBe((int) $byProject[$projectA->id]->id)
        // Color carries over to the clone.
        ->and($byProject[$projectB->id]->color)->toBe('sky');

    $tagA = $byProject[$projectA->id]->id;
    $tagB = $byProject[$projectB->id]->id;

    // Each task now references its own project's tag.
    expect(DB::table('taggables')->where('tag_id', $tagA)->count())->toBe(2)
        ->and(DB::table('taggables')->where('tag_id', $tagB)->count())->toBe(1)
        ->and((int) DB::table('taggables')->where('taggable_id', $taskB->id)->value('tag_id'))->toBe((int) $tagB);
});

it('keeps a single-project tag as one row and assigns its project', function () {
    rebuildGlobalTagsSchema();

    $project = Project::factory()->create();
    $task = Task::factory()->for($project)->create();
    $tagId = insertGlobalTag('solo');
    attachGlobalTag($tagId, $task);

    loadScopeTagsMigration()->up();

    expect(DB::table('tags')->where('name', 'solo')->count())->toBe(1)
        ->and((int) DB::table('tags')->where('id', $tagId)->value('project_id'))->toBe($project->id);
});

it('drops tags not attached to any task', function () {
    rebuildGlobalTagsSchema();
    insertGlobalTag('orphan');

    loadScopeTagsMigration()->up();

    expect(DB::table('tags')->where('name', 'orphan')->exists())->toBeFalse();
});

it('enforces per-project name uniqueness after scoping', function () {
    rebuildGlobalTagsSchema();

    $project = Project::factory()->create();
    $task = Task::factory()->for($project)->create();
    attachGlobalTag(insertGlobalTag('dup'), $task);

    loadScopeTagsMigration()->up();

    $projectId = (int) DB::table('tags')->where('name', 'dup')->value('project_id');

    expect(fn () => DB::table('tags')->insert([
        'project_id' => $projectId,
        'name' => 'dup',
        'color' => 'zinc',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(UniqueConstraintViolationException::class);
});
