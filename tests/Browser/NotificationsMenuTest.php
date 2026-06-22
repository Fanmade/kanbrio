<?php

use App\Models\User;

it('keeps the notifications menu working across SPA navigation', function () {
    $this->actingAs(User::factory()->create());

    $page = visit(route('dashboard'));

    $page->assertVisible('@notifications-trigger')
        ->click('@nav-projects') // wire:navigate transition, no full page reload
        ->assertPathIs('/projects')
        ->assertVisible('@notifications-trigger')
        ->click('@notifications-trigger')
        ->assertVisible('@notifications-panel')
        ->assertNoJavascriptErrors();
});
