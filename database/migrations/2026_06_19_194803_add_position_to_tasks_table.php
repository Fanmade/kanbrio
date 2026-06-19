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
        Schema::table('tasks', static function (Blueprint $table): void {
            $table->double('position')->default(0)->after('status');
            $table->index(['status', 'position']);
        });

        // Seed positions from the current board order (priority, then id) so the
        // existing visual order is preserved as the initial manual order, and
        // every task gets a distinct value to drag between. Uses the query
        // builder so it is not blocked by the model's $fillable allow-list.
        $position = 1;

        DB::table('tasks')->orderByDesc('priority')->orderBy('id')->select('id')->cursor()
            ->each(static function (object $task) use (&$position): void {
                DB::table('tasks')->where('id', $task->id)->update(['position' => $position++]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', static function (Blueprint $table): void {
            $table->dropIndex(['status', 'position']);
            $table->dropColumn('position');
        });
    }
};
