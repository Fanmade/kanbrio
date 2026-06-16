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
        Schema::table('stories', static function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('description');
        });

        Schema::table('tasks', static function (Blueprint $table): void {
            $table->date('due_date')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', static function (Blueprint $table): void {
            $table->dropColumn('due_date');
        });

        Schema::table('tasks', static function (Blueprint $table): void {
            $table->dropColumn('due_date');
        });
    }
};
