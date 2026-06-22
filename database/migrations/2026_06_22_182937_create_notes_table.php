<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notes', static function (Blueprint $table) {
            $table->id();
            // The owner. Notes are the first user-owned, projectless entity.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Optional project attachment, separate from visibility.
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            // Visibility — only ever true when a project is attached (model invariant).
            $table->boolean('is_public')->default(false);
            $table->string('title');
            $table->text('body')->nullable();
            // Set once the note has been converted into a task.
            $table->foreignId('converted_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
