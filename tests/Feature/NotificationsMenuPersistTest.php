<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('wraps the notifications menu in a persist boundary so it survives navigation', function () {
    // @persist('notifications-menu') compiles to <div x-persist="notifications-menu">.
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('x-persist="notifications-menu"', false)
        ->assertSee('data-test="notifications-trigger"', false);
});
