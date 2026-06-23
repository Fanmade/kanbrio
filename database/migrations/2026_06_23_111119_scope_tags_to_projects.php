<?php

use App\Models\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scope tags to a project. Tags were global (unique by name across the whole
     * app); they become owned by a project with a unique (project_id, name).
     *
     * Existing global tags are split per project: a tag used by tasks in N
     * projects becomes N per-project tags, with the `taggables` pivot rows
     * repointed to the right per-project tag. A tag attached to no task has no
     * project to live in and is dropped.
     */
    public function up(): void
    {
        Schema::table('tags', static function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Drop the global name-unique BEFORE splitting: cloning a shared tag per
        // project temporarily creates rows with duplicate names. The index was
        // created on the original "keywords" table and keeps that name through
        // the table rename.
        Schema::table('tags', static function (Blueprint $table): void {
            $table->dropUnique('keywords_name_unique');
        });

        $this->splitGlobalTagsPerProject();

        Schema::table('tags', static function (Blueprint $table): void {
            $table->unique(['project_id', 'name']);
        });
    }

    /**
     * Give every existing global tag a per-project identity.
     */
    private function splitGlobalTagsPerProject(): void
    {
        $taskType = (new Task)->getMorphClass();

        foreach (DB::table('tags')->orderBy('id')->get() as $tag) {
            $projectIds = $this->projectIdsForTag($tag->id, $taskType);

            if ($projectIds === []) {
                DB::table('tags')->where('id', $tag->id)->delete();

                continue;
            }

            // The first project keeps the original row; the rest get clones.
            $firstProjectId = array_shift($projectIds);
            DB::table('tags')->where('id', $tag->id)->update(['project_id' => $firstProjectId]);

            foreach ($projectIds as $projectId) {
                $cloneId = DB::table('tags')->insertGetId([
                    'project_id' => $projectId,
                    'name' => $tag->name,
                    'color' => $tag->color,
                    'created_at' => $tag->created_at,
                    'updated_at' => $tag->updated_at,
                ]);

                $this->repointTaggables($tag->id, $cloneId, $projectId, $taskType);
            }
        }
    }

    /**
     * The distinct project ids of the tasks a tag is attached to.
     *
     * @return array<int, int>
     */
    private function projectIdsForTag(int $tagId, string $taskType): array
    {
        return DB::table('taggables')
            ->join('tasks', 'tasks.id', '=', 'taggables.taggable_id')
            ->where('taggables.taggable_type', $taskType)
            ->where('taggables.tag_id', $tagId)
            ->distinct()
            ->orderBy('tasks.project_id')
            ->pluck('tasks.project_id')
            ->all();
    }

    /**
     * Move the pivot rows for one project's tasks from the original tag to its
     * per-project clone.
     */
    private function repointTaggables(int $fromTagId, int $toTagId, int $projectId, string $taskType): void
    {
        $taggableIds = DB::table('taggables')
            ->join('tasks', 'tasks.id', '=', 'taggables.taggable_id')
            ->where('taggables.taggable_type', $taskType)
            ->where('taggables.tag_id', $fromTagId)
            ->where('tasks.project_id', $projectId)
            ->pluck('taggables.taggable_id')
            ->all();

        DB::table('taggables')
            ->where('tag_id', $fromTagId)
            ->where('taggable_type', $taskType)
            ->whereIn('taggable_id', $taggableIds)
            ->update(['tag_id' => $toTagId]);
    }

    /**
     * Reverse the migrations. Best-effort: merging the per-project tags back
     * into globally-unique ones is not attempted, so this only restores the
     * shape and may fail if two projects share a tag name.
     */
    public function down(): void
    {
        Schema::table('tags', static function (Blueprint $table): void {
            $table->dropUnique(['project_id', 'name']);
            $table->dropConstrainedForeignId('project_id');
        });

        Schema::table('tags', static function (Blueprint $table): void {
            $table->unique('name', 'keywords_name_unique');
        });
    }
};
