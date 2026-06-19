<?php

use App\Enums\Permission;
use App\Livewire\Admin\UserManagement;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('forbids users without the manage-users permission from reaching the area', function () {
    actingAs(User::factory()->create())
        ->get(route('admin.users'))
        ->assertForbidden();
});

it('allows administrators with the manage-users permission to reach the area', function () {
    actingAs(User::factory()->canManageUsers()->create())
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSeeLivewire(UserManagement::class);
});

it('shows the navigation entry only to administrators who can manage users', function () {
    actingAs(User::factory()->canManageUsers()->create())
        ->get(route('dashboard'))
        ->assertSee('User administration');

    actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertDontSee('User administration');
});

it('lists all users with their status', function () {
    $admin = User::factory()->canManageUsers()->create(['name' => 'Ada Admin']);
    $member = User::factory()->create(['name' => 'Bob Member']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->assertSee('Ada Admin')
        ->assertSee('Bob Member');
});

it('grants a permission to a user', function () {
    $admin = User::factory()->canManageUsers()->create();
    $member = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('togglePermission', $member->id, Permission::CreateProjects->value);

    expect($member->fresh()->hasPermission(Permission::CreateProjects))->toBeTrue();
});

it('revokes a permission the user already has', function () {
    $admin = User::factory()->canManageUsers()->create();
    $member = User::factory()->canCreateProjects()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('togglePermission', $member->id, Permission::CreateProjects->value);

    expect($member->fresh()->hasPermission(Permission::CreateProjects))->toBeFalse();
});

it('prevents an administrator from revoking their own manage-users permission', function () {
    $admin = User::factory()->canManageUsers()->create();

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('togglePermission', $admin->id, Permission::ManageUsers->value);

    expect($admin->fresh()->hasPermission(Permission::ManageUsers))->toBeTrue();
});

it('filters users by name or email', function () {
    $admin = User::factory()->canManageUsers()->create(['name' => 'Ada Admin', 'email' => 'ada@example.com']);
    User::factory()->create(['name' => 'Zoe Zebra', 'email' => 'zoe@example.com']);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->set('search', 'Zoe')
        ->assertSee('Zoe Zebra')
        ->assertDontSee('Ada Admin');
});

it('resends a pending invitation and refreshes its expiry', function () {
    Mail::fake();

    $admin = User::factory()->canManageUsers()->create();
    $invitation = Invitation::create([
        'email' => 'invitee@example.com',
        'token' => 'a-token',
        'invited_by' => $admin->id,
        'project_ids' => [],
        'expires_at' => now()->addDay(),
    ]);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('resendInvitation', $invitation->id);

    Mail::assertSent(InvitationMail::class, static fn (InvitationMail $mail): bool => $mail->hasTo('invitee@example.com'));
    expect($invitation->fresh()->expires_at->isAfter(now()->addDays(6)))->toBeTrue();
});

it('revokes a pending invitation', function () {
    $admin = User::factory()->canManageUsers()->create();
    $invitation = Invitation::create([
        'email' => 'invitee@example.com',
        'token' => 'a-token',
        'invited_by' => $admin->id,
        'project_ids' => [],
        'expires_at' => now()->addDay(),
    ]);

    Livewire::actingAs($admin)
        ->test(UserManagement::class)
        ->call('revokeInvitation', $invitation->id);

    $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
});

it('forbids a non-administrator from mounting the management component', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(UserManagement::class)
        ->assertForbidden();
});
