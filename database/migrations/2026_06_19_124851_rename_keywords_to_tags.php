<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the "keywords" feature to "tags": the table, its pivot and the
     * polymorphic columns. Existing data is preserved; only the identifiers change.
     */
    public function up(): void
    {
        Schema::rename('keywords', 'tags');
        Schema::rename('keywordables', 'taggables');

        Schema::table('taggables', static function (Blueprint $table): void {
            $table->renameColumn('keyword_id', 'tag_id');
            $table->renameColumn('keywordable_id', 'taggable_id');
            $table->renameColumn('keywordable_type', 'taggable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taggables', static function (Blueprint $table): void {
            $table->renameColumn('tag_id', 'keyword_id');
            $table->renameColumn('taggable_id', 'keywordable_id');
            $table->renameColumn('taggable_type', 'keywordable_type');
        });

        Schema::rename('taggables', 'keywordables');
        Schema::rename('tags', 'keywords');
    }
};
