<?php

use App\Models\User;

it('logs a user in through the login form', function () {
    $user = User::factory()->create();

    $page = visit('/login');

    $page->fill('email', $user->email)
        ->fill('password', 'password')
        ->press('Log in')
        ->assertPathIs('/dashboard')
        ->assertNoJavascriptErrors();

    $this->assertAuthenticatedAs($user);
});
