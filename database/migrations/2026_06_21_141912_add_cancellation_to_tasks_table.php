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
        Schema::table('tasks', static function (Blueprint $table): void {
            $table->timestamp('canceled_at')->nullable()->after('archived_at');
            $table->string('cancel_reason')->nullable()->after('canceled_at');
            $table->text('cancel_message')->nullable()->after('cancel_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', static function (Blueprint $table): void {
            $table->dropColumn(['canceled_at', 'cancel_reason', 'cancel_message']);
        });
    }
};
