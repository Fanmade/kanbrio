<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The notifications payload was created as `text`, but the notifications page
     * groups by JSON keys (`data->subject_type`). PostgreSQL rejects the `->>`
     * operator on a text column ("operator does not exist: text ->> unknown"),
     * 500ing the page (KAN-329). Promote the column to `jsonb` so JSON extraction
     * is valid. SQLite and MySQL/MariaDB already apply JSON operators to a text
     * column, so this only needs to run on PostgreSQL.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE jsonb USING data::jsonb');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
    }
};
