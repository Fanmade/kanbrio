<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an optional Heroicon to tags (null = no icon, identified by colour
     * alone). A plain column add — no `->change()` — so it does not rebuild the
     * table or disturb the case-insensitive `(project_id, lower(name))` index.
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->string('icon')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table): void {
            $table->dropColumn('icon');
        });
    }
};
