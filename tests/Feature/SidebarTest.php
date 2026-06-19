<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the app sidebar renders as collapsible with a collapse toggle', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('collapsible="true"', escape: false)
        ->assertSee('data-flux-sidebar-collapse', escape: false);
});
