<?php

use App\Enums\Priority;
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
            $table->unsignedTinyInteger('priority')->default(Priority::default()->value)->after('description');
        });

        Schema::table('tasks', static function (Blueprint $table): void {
            $table->unsignedTinyInteger('priority')->default(Priority::default()->value)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', static function (Blueprint $table): void {
            $table->dropColumn('priority');
        });

        Schema::table('tasks', static function (Blueprint $table): void {
            $table->dropColumn('priority');
        });
    }
};
