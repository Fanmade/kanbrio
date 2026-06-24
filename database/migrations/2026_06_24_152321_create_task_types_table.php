<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_types', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('zinc');
            $table->string('icon')->default('tag');
            // Drives the git branch prefix (e.g. "feat", "bugfix"); see KAN-257.
            $table->string('branch_prefix')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Names are unique per project, case-insensitively (so "Bug" and "bug"
        // can't coexist) — a functional index supported by SQLite and PostgreSQL,
        // mirroring the tags table.
        DB::statement('CREATE UNIQUE INDEX task_types_project_id_lower_name_unique ON task_types (project_id, lower(name))');

        Schema::table('tasks', static function (Blueprint $table): void {
            // Optional type; clearing the type (or deleting it) leaves the task untyped.
            $table->foreignId('task_type_id')->nullable()->after('priority')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('task_type_id');
        });

        Schema::dropIfExists('task_types');
    }
};
