<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

it('lets an admin add a user to a project from user administration', function () {
    $admin = User::factory()->canManageUsers()->create(['name' => 'Ada Admin']);
    $user = User::factory()->create(['name' => 'Casey User']);
    $project = Project::factory()->create(['title' => 'Apollo', 'short_name' => 'APO']);

    $this->actingAs($admin);

    $page = visit('/admin/users');
    $page->click('@manage-projects-'.$user->id)
        ->assertSee('Projects for Casey User')
        ->click('@mp-add-'.$project->id)
        ->waitForText('Member added.')
        ->assertNoJavascriptErrors();

    expect($project->roleFor($user))->toBe(ProjectRole::Member);
});
