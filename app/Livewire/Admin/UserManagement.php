<?php

namespace App\Livewire\Admin;

use App\Enums\Permission;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('User administration')]
class UserManagement extends Component
{
    #[Url]
    public string $search = '';

    /**
     * Whether the removal confirmation modal is open.
     */
    public bool $confirmingRemoval = false;

    /**
     * The id of the user currently pending removal confirmation.
     */
    public ?int $confirmingRemovalId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    /**
     * The permission cases an administrator may grant or revoke.
     *
     * @return array<int, Permission>
     */
    #[Computed]
    public function permissions(): array
    {
        return Permission::cases();
    }

    /**
     * All user accounts with their granted permissions, optionally filtered.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->with('permissions')
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(static function ($query) use ($term): void {
                    $query->where('name', 'like', $term)->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Pending (unaccepted, unexpired) invitations awaiting a response.
     *
     * @return Collection<int, Invitation>
     */
    #[Computed]
    public function pendingInvitations(): Collection
    {
        return Invitation::query()->valid()->with('inviter')->latest()->get();
    }

    /**
     * Whether toggling the given permission would let an administrator revoke
     * their own management access and lock themselves out of this area.
     */
    public function locksSelfOutOfManagement(User $user, Permission $permission): bool
    {
        return $permission === Permission::ManageUsers && $user->is(Auth::user());
    }

    /**
     * Grant or revoke a single permission for the given user.
     */
    public function togglePermission(int $userId, string $permission): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        $case = Permission::from($permission);
        $granted = $user->hasPermission($case);

        if ($granted && $this->locksSelfOutOfManagement($user, $case)) {
            Flux::toast(text: __('You cannot revoke your own user management permission.'), variant: 'warning');

            return;
        }

        $values = $user->permissions
            ->map(static fn ($userPermission): Permission => $userPermission->permission)
            ->reject(static fn (Permission $existing): bool => $granted && $existing === $case)
            ->all();

        if (! $granted) {
            $values[] = $case;
        }

        $user->syncPermissions($values);

        unset($this->users);

        Flux::toast(text: __('Permissions updated.'), variant: 'success');
    }

    /**
     * Deactivate a user account, preventing them from signing in.
     */
    public function deactivate(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('deactivate', $user);

        $user->deactivate();

        unset($this->users);

        Flux::toast(text: __('Account deactivated.'), variant: 'success');
    }

    /**
     * Restore a previously deactivated account.
     */
    public function reactivate(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('deactivate', $user);

        $user->reactivate();

        unset($this->users);

        Flux::toast(text: __('Account reactivated.'), variant: 'success');
    }

    /**
     * Ask the user to confirm removing an account.
     */
    public function confirmRemoval(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('delete', $user);

        $this->confirmingRemovalId = $userId;
        $this->confirmingRemoval = true;
    }

    /**
     * Dismiss the removal confirmation.
     */
    public function cancelRemoval(): void
    {
        $this->confirmingRemovalId = null;
        $this->confirmingRemoval = false;
    }

    /**
     * The user currently pending removal confirmation, if any.
     */
    #[Computed]
    public function removalTarget(): ?User
    {
        return $this->confirmingRemovalId !== null
            ? User::find($this->confirmingRemovalId)
            : null;
    }

    /**
     * Remove a user account. The account is soft-deleted and its project access,
     * task assignments and subscriptions are detached; authored comments survive
     * as the work of a removed user.
     */
    public function removeUser(): void
    {
        $user = User::findOrFail($this->confirmingRemovalId);

        $this->authorize('delete', $user);

        $user->delete();

        $this->confirmingRemovalId = null;
        $this->confirmingRemoval = false;

        unset($this->users);

        Flux::toast(text: __('Account removed.'), variant: 'success');
    }

    /**
     * Re-send a pending invitation, refreshing its expiry.
     */
    public function resendInvitation(int $invitationId): void
    {
        $this->authorize('manage-users');

        $invitation = Invitation::query()->valid()->findOrFail($invitationId);

        $invitation->forceFill(['expires_at' => now()->addDays(7)])->save();

        Mail::to($invitation->email)->send(new InvitationMail($invitation, $invitation->token));

        unset($this->pendingInvitations);

        Flux::toast(text: __('Invitation resent.'), variant: 'success');
    }

    /**
     * Revoke a pending invitation so it can no longer be accepted.
     */
    public function revokeInvitation(int $invitationId): void
    {
        $this->authorize('manage-users');

        Invitation::query()->valid()->findOrFail($invitationId)->delete();

        unset($this->pendingInvitations);

        Flux::toast(text: __('Invitation revoked.'), variant: 'success');
    }
}
