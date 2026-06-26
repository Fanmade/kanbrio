<?php

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->project = Project::factory()->create();
    $this->task = Task::factory()->for($this->project)->create();
});

/**
 * An attachment on the test task.
 */
function taskAttachment(Task $task): Attachment
{
    return Attachment::factory()->create([
        'attachable_id' => $task->id,
        'attachable_type' => $task->getMorphClass(),
    ]);
}

it('lets a member upload and delete task attachments', function () {
    $member = userWithRole($this->project, 'member');

    expect($member->can('create', [Attachment::class, $this->task]))->toBeTrue()
        ->and($member->can('delete', taskAttachment($this->task)))->toBeTrue();
});

it('keeps a viewer from uploading or deleting attachments', function () {
    $viewer = userWithRole($this->project, 'viewer');

    expect($viewer->can('create', [Attachment::class, $this->task]))->toBeFalse()
        ->and($viewer->can('delete', taskAttachment($this->task)))->toBeFalse();
});

it('gates upload on manage-attachments and delete on delete-attachment', function () {
    $uploader = userWithPermissions($this->project, ['manage-attachments']);
    $deleter = userWithPermissions($this->project, ['delete-attachment']);
    $attachment = taskAttachment($this->task);

    expect($uploader->can('create', [Attachment::class, $this->task]))->toBeTrue()
        ->and($uploader->can('delete', $attachment))->toBeFalse()
        ->and($deleter->can('delete', $attachment))->toBeTrue()
        ->and($deleter->can('create', [Attachment::class, $this->task]))->toBeFalse();
});
