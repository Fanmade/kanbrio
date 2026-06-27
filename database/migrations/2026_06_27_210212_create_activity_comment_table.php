<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Links a comment to the activity-log entries it references. A comment may
     * reference several entries, possibly on other tasks, so this is a plain
     * many-to-many pivot rather than a column on either table.
     */
    public function up(): void
    {
        Schema::create('activity_comment', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'comment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_comment');
    }
};
