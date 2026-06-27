<?php

use App\Enums\Permission;
use Fanmade\DelegatedPermissions\Models\Permission as PackagePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Register the new access-all-projects account permission (KAN-314) in the
     * package's global catalog so it resolves through the one engine and the
     * system role grants it like any other (mirrors KAN-242). Looping every case
     * keeps this idempotent and self-healing should the catalog drift.
     */
    public function up(): void
    {
        foreach (Permission::cases() as $permission) {
            PackagePermission::query()->firstOrCreate(['name' => $permission->value]);
        }
    }

    /**
     * No-op: the catalog permission is left in place.
     */
    public function down(): void
    {
        //
    }
};
