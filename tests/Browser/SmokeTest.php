<?php

use App\Models\Project;
use App\Models\User;

it('renders the key pages without browser errors', function () {
    $user = User::factory()->create();
    Project::factory()->create()->members()->attach($user);

    $this->actingAs($user);

    $pages = visit([
        '/dashboard',
        '/projects',
        '/board',
        '/settings/profile',
        '/settings/appearance',
        '/settings/security',
        '/settings/api-tokens',
    ]);

    $pages->assertNoSmoke();
});
