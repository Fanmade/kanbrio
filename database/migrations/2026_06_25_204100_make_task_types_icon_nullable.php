<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'task_types_project_id_lower_name_unique';

    /**
     * Make a task type's icon optional: nullable, with no default, so a type can
     * be identified by colour alone.
     *
     * SQLite rebuilds the whole table on a column change and cannot reproduce the
     * functional unique index on (project_id, lower(name)) — leaving it would
     * corrupt the index into a bogus single-column one. Drop it first so the
     * rebuild runs on a clean table, then recreate it.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDEX);

        Schema::table('task_types', function (Blueprint $table): void {
            $table->string('icon')->nullable()->default(null)->change();
        });

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS '.self::INDEX.' ON task_types (project_id, lower(name))');
    }

    /**
     * Reverse the migrations, restoring the non-null `tag`-defaulted column.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDEX);

        DB::table('task_types')->whereNull('icon')->update(['icon' => 'tag']);

        Schema::table('task_types', function (Blueprint $table): void {
            $table->string('icon')->default('tag')->change();
        });

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS '.self::INDEX.' ON task_types (project_id, lower(name))');
    }
};
