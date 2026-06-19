<?php

use App\Livewire\Settings\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('two factor authentication disabled when confirmation abandoned between requests', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    Livewire::test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ]);
});

test('enabling two factor shows the setup modal and requires confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(TwoFactor::class)
        ->call('enable')
        ->assertSet('showModal', true)
        ->assertSet('twoFactorEnabled', false);

    expect($user->refresh()->two_factor_secret)->not->toBeNull();
});

test('two factor is confirmed with a valid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(TwoFactor::class)
        ->call('enable')
        ->call('showVerificationIfNecessary')
        ->assertSet('showVerificationStep', true);

    $code = app(Google2FA::class)
        ->getCurrentOtp(decrypt($user->refresh()->two_factor_secret));

    $component->set('code', $code)
        ->call('confirmTwoFactor')
        ->assertHasNoErrors()
        ->assertSet('twoFactorEnabled', true)
        ->assertSet('showModal', false);

    expect($user->refresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('two factor can be disabled', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($user);

    Livewire::test(TwoFactor::class)
        ->assertSet('twoFactorEnabled', true)
        ->call('disable')
        ->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => null,
    ]);
});
