<?php

use App\Enums\CancelReason;
use App\Enums\Status;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ItemActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actor = User::factory()->create();
    $this->project = Project::factory()->withMembers([$this->actor])->create(['short_name' => 'ABC']);
    $this->task = Task::factory()->for($this->project)->status(Status::ToDo)->create();
    $this->actingAs($this->actor);
});

it('cancels a task with a reason and message, recording a canceled activity', function () {
    $activity = $this->task->cancel(CancelReason::Duplicate, '  Same as ABC-1  ');

    $fresh = $this->task->fresh();

    expect($fresh->status)->toBe(Status::Canceled)
        ->and($fresh->isCanceled())->toBeTrue()
        ->and($fresh->cancel_reason)->toBe(CancelReason::Duplicate)
        ->and($fresh->cancel_message)->toBe('Same as ABC-1') // trimmed
        ->and($activity->action)->toBe('canceled');

    expect(json_decode((string) $activity->new_value, true))
        ->toBe(['reason' => 'duplicate', 'message' => 'Same as ABC-1']);
});

it('treats a blank cancel message as no message', function () {
    $this->task->cancel(CancelReason::WontFix, '   ');

    expect($this->task->fresh()->cancel_message)->toBeNull();
});

it('does not cancel an already-canceled task again', function () {
    $task = Task::factory()->for($this->project)->canceled(CancelReason::Deprecated)->create();

    expect($task->cancel(CancelReason::WontFix))->toBeNull()
        ->and($task->fresh()->cancel_reason)->toBe(CancelReason::Deprecated);
});

it('reopens a canceled task back to Planned, clearing the cancellation', function () {
    $task = Task::factory()->for($this->project)->canceled(CancelReason::Duplicate, 'note')->create();

    $activity = $task->reopen();

    $fresh = $task->fresh();

    expect($fresh->status)->toBe(Status::Planned)
        ->and($fresh->isCanceled())->toBeFalse()
        ->and($fresh->canceled_at)->toBeNull()
        ->and($fresh->cancel_reason)->toBeNull()
        ->and($fresh->cancel_message)->toBeNull()
        ->and($activity->action)->toBe('reopened');
});

it('does not reopen a task that is not canceled', function () {
    expect($this->task->reopen())->toBeNull();
});

it('notifies subscribers, but not the actor, when a task is canceled', function () {
    Notification::fake();

    $watcher = User::factory()->create();
    $this->project->members()->attach($watcher);
    $this->task->subscribe($watcher);
    $this->task->subscribe($this->actor);

    $this->task->cancel(CancelReason::WontFix);

    Notification::assertSentTo($watcher, ItemActivity::class);
    Notification::assertNotSentTo($this->actor, ItemActivity::class);
});

it('forbids a non-member from updating (and thus cancelling) a task', function () {
    $outsider = User::factory()->create();

    expect($outsider->can('update', $this->task))->toBeFalse()
        ->and($this->actor->can('update', $this->task))->toBeTrue();
});
