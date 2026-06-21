<?php

use App\Enums\CancelReason;
use App\Enums\Status;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes the cancel reasons by case name', function () {
    expect(CancelReason::names())->toBe(['WontFix', 'Duplicate', 'Deprecated'])
        ->and(CancelReason::fromName('Duplicate'))->toBe(CancelReason::Duplicate)
        ->and(CancelReason::fromName('nope'))->toBeNull();
});

it('gives every cancel reason a label, color and icon', function (CancelReason $reason) {
    expect($reason->label())->toBeString()->not->toBe('')
        ->and($reason->color())->toBeString()->not->toBe('')
        ->and($reason->icon())->toBeString()->not->toBe('');
})->with(CancelReason::cases());

it('persists and casts the cancellation columns on a task', function () {
    $task = Task::factory()->canceled(CancelReason::Duplicate, 'Same as ABC-1')->create();

    $fresh = $task->fresh();

    expect($fresh->status)->toBe(Status::Canceled)
        ->and($fresh->canceled_at)->not->toBeNull()
        ->and($fresh->cancel_reason)->toBe(CancelReason::Duplicate)
        ->and($fresh->cancel_message)->toBe('Same as ABC-1')
        ->and($fresh->isCanceled())->toBeTrue();
});

it('reports a non-canceled task as not canceled', function () {
    $task = Task::factory()->status(Status::ToDo)->create();

    expect($task->isCanceled())->toBeFalse()
        ->and($task->cancel_reason)->toBeNull()
        ->and($task->cancel_message)->toBeNull();
});
