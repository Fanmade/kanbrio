<?php

use App\Authorization\AccountPermissionProvisioner;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move account permissions off the legacy `user_permissions` pivot and onto
     * the delegated-permissions package (KAN-385): provision a global role per
     * permission, reassign each existing grant as a global role assignment, then
     * drop the pivot. The package global scope becomes the single store.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_permissions')) {
            return;
        }

        $provisioner = app(AccountPermissionProvisioner::class);
        $provisioner->provision();

        $byUser = [];

        foreach (DB::table('user_permissions')->get() as $grant) {
            $byUser[(int) $grant->user_id][] = Permission::from((string) $grant->permission);
        }

        foreach ($byUser as $userId => $permissions) {
            $user = User::find($userId);

            if ($user !== null) {
                $provisioner->sync($user, $permissions);
            }
        }

        Schema::dropIfExists('user_permissions');
    }

    /**
     * Irreversible: rebuilding the pivot would mean reconstructing per-user grants
     * from the package's global role assignments. The package global scope is the
     * single source of truth going forward.
     */
    public function down(): void
    {
        //
    }
};
