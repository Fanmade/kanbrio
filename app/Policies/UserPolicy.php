<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can access the user administration area.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    /**
     * Determine whether the user can change another account's permissions.
     */
    public function update(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::ManageUsers);
    }

    /**
     * Determine whether the user can deactivate or reactivate an account.
     * Administrators may not deactivate their own account.
     */
    public function deactivate(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::ManageUsers) && ! $user->is($target);
    }

    /**
     * Determine whether the user can remove an account.
     * Administrators may not remove their own account here.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->hasPermission(Permission::ManageUsers) && ! $user->is($target);
    }
}
