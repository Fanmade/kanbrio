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
        Schema::create('dependencies', static function (Blueprint $table): void {
            $table->id();
            // The blocked item; it depends on the blocker being completed first.
            $table->morphs('dependent');
            // The blocking item that must be completed first.
            $table->morphs('blocker');
            $table->timestamps();

            $table->unique(
                ['dependent_type', 'dependent_id', 'blocker_type', 'blocker_id'],
                'dependencies_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependencies');
    }
};
