<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Exceptions\InvalidStatusTransition;
use App\Models\JobOrder;
use App\Models\JobOrderEvent;
use App\Models\User;
use App\Services\JobOrders\JobOrderWorkflow;
use Livewire\Volt\Volt;

function transitionUser(Role $role): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

function jobOrderInStatus(JobOrderStatus $status, ?User $technician = null): JobOrder
{
    return JobOrder::create([
        'customer_name' => 'Juan Dela Cruz',
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'issue_description' => 'Cracked screen',
        'status' => $status,
        'received_by' => transitionUser(Role::COUNTER_STAFF)->id,
        'assigned_to' => $technician?->id,
    ]);
}

// -- The transition map ----------------------------------------------------

test('a legal transition is applied', function () {
    $jobOrder = jobOrderInStatus(JobOrderStatus::IN_PROGRESS);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::DONE);

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::DONE);
});

test('a transition that skips completion is refused', function () {
    $jobOrder = jobOrderInStatus(JobOrderStatus::IN_PROGRESS);

    expect(fn () => app(JobOrderWorkflow::class)
        ->transitionTo($jobOrder, JobOrderStatus::DELIVERED))
        ->toThrow(InvalidStatusTransition::class);

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::IN_PROGRESS);
});

test('a delivered job order is terminal', function () {
    $jobOrder = jobOrderInStatus(JobOrderStatus::DELIVERED);

    expect(JobOrderStatus::DELIVERED->isTerminal())->toBeTrue();

    expect(fn () => app(JobOrderWorkflow::class)
        ->transitionTo($jobOrder, JobOrderStatus::IN_PROGRESS))
        ->toThrow(InvalidStatusTransition::class);
});

test('re-saving the same status is not treated as a transition', function () {
    $jobOrder = jobOrderInStatus(JobOrderStatus::APPROVED);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::APPROVED);
});

test('completing and delivering stamp their timestamps once', function () {
    $jobOrder = jobOrderInStatus(JobOrderStatus::DONE);
    $workflow = app(JobOrderWorkflow::class);

    $workflow->transitionTo($jobOrder, JobOrderStatus::COMPLETED);
    $completedAt = $jobOrder->fresh()->completed_at;

    expect($completedAt)->not->toBeNull();

    // Reopened, then finished again: the original completion date is what the
    // customer's receipt says, so it must not move.
    $workflow->transitionTo($jobOrder, JobOrderStatus::IN_PROGRESS);
    $workflow->transitionTo($jobOrder, JobOrderStatus::DONE);
    $workflow->transitionTo($jobOrder, JobOrderStatus::COMPLETED);

    expect($jobOrder->fresh()->completed_at->toString())->toBe($completedAt->toString());
});

// -- The technician dashboard, which is the exploitable path ---------------

test('a technician cannot jump their job order straight to delivered', function () {
    $technician = transitionUser(Role::TECHNICIAN);
    $jobOrder = jobOrderInStatus(JobOrderStatus::IN_PROGRESS, $technician);

    $this->actingAs($technician);

    Volt::test('technician.dashboard')
        ->call('updateStatus', $jobOrder->id, 'delivered');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::IN_PROGRESS);
});

test('a technician sending a junk status gets an error rather than a crash', function () {
    $technician = transitionUser(Role::TECHNICIAN);
    $jobOrder = jobOrderInStatus(JobOrderStatus::IN_PROGRESS, $technician);

    $this->actingAs($technician);

    Volt::test('technician.dashboard')
        ->call('updateStatus', $jobOrder->id, 'not-a-status')
        ->assertDispatched('error');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::IN_PROGRESS);
});

test('a technician can still move their job order through the normal steps', function () {
    $technician = transitionUser(Role::TECHNICIAN);
    $jobOrder = jobOrderInStatus(JobOrderStatus::ASSIGNED, $technician);

    $this->actingAs($technician);

    Volt::test('technician.dashboard')
        ->call('updateStatus', $jobOrder->id, 'in_progress')
        ->assertDispatched('success');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::IN_PROGRESS);

    Volt::test('technician.dashboard')
        ->call('updateStatus', $jobOrder->id, 'done')
        ->assertDispatched('success');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::DONE);
});

// -- History ---------------------------------------------------------------

test('creating a job order records a customer-visible event', function () {
    $jobOrder = jobOrderInStatus(JobOrderStatus::PENDING);

    $event = JobOrderEvent::where('job_order_id', $jobOrder->id)->firstOrFail();

    expect($event->type)->toBe(JobOrderEvent::TYPE_CREATED)
        ->and($event->is_customer_visible)->toBeTrue();
});

test('every status change is recorded, including ones made by a bare update', function () {
    $jobOrder = jobOrderInStatus(JobOrderStatus::IN_PROGRESS);

    // Deliberately bypassing the workflow: the observer is the safety net for
    // the call sites that have not been routed through it.
    $jobOrder->update(['status' => JobOrderStatus::DONE]);

    $event = JobOrderEvent::where('job_order_id', $jobOrder->id)
        ->where('type', JobOrderEvent::TYPE_STATUS_CHANGED)
        ->firstOrFail();

    expect($event->from_status)->toBe(JobOrderStatus::IN_PROGRESS)
        ->and($event->to_status)->toBe(JobOrderStatus::DONE);
});

test('technician assignment is recorded but hidden from the customer', function () {
    $technician = transitionUser(Role::TECHNICIAN);
    $jobOrder = jobOrderInStatus(JobOrderStatus::PENDING);

    $jobOrder->update(['assigned_to' => $technician->id]);

    $event = JobOrderEvent::where('job_order_id', $jobOrder->id)
        ->where('type', JobOrderEvent::TYPE_ASSIGNED)
        ->firstOrFail();

    expect($event->is_customer_visible)->toBeFalse()
        ->and($event->description)->toContain($technician->name);
});

test('a note can be attached without changing the status', function () {
    $jobOrder = jobOrderInStatus(JobOrderStatus::IN_PROGRESS);

    app(JobOrderWorkflow::class)->note($jobOrder, 'Waiting on the customer to call back.');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::IN_PROGRESS);

    $event = JobOrderEvent::where('job_order_id', $jobOrder->id)
        ->where('type', JobOrderEvent::TYPE_NOTE)
        ->firstOrFail();

    expect($event->is_customer_visible)->toBeFalse();
});
