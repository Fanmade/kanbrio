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
        Schema::create('tag_synonyms', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // Case-insensitive uniqueness of a synonym within its tag — the same
        // functional-index approach the tags table uses for (project_id, name),
        // so "Eval" and "eval" can't both be added to one tag on SQLite or pgsql.
        DB::statement('CREATE UNIQUE INDEX tag_synonyms_tag_id_lower_name_unique ON tag_synonyms (tag_id, lower(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_synonyms');
    }
};
