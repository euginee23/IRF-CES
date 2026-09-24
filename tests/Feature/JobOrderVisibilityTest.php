<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Models\JobOrder;
use App\Models\User;
use Livewire\Volt\Volt;

function visibilityUser(Role $role, string $name = 'Staff'): User
{
    return User::factory()->create([
        'name' => $name,
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

function visibilityJobOrder(array $attributes = []): JobOrder
{
    static $counter = 0;
    $counter++;

    return JobOrder::create(array_merge([
        'customer_name' => "Customer {$counter}",
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'issue_description' => 'Cracked screen',
        'status' => JobOrderStatus::PENDING,
        'received_by' => visibilityUser(Role::COUNTER_STAFF)->id,
    ], $attributes));
}

// -- The scope -------------------------------------------------------------

test('an administrator sees every job order, including other technicians work', function () {
    $tech = visibilityUser(Role::TECHNICIAN, 'Tech One');
    visibilityJobOrder(['assigned_to' => $tech->id]);
    visibilityJobOrder();

    $admin = visibilityUser(Role::ADMINISTRATOR);

    expect(JobOrder::visibleTo($admin)->count())->toBe(2);
});

test('counter staff see every job order', function () {
    $tech = visibilityUser(Role::TECHNICIAN, 'Tech One');
    visibilityJobOrder(['assigned_to' => $tech->id]);
    visibilityJobOrder();

    expect(JobOrder::visibleTo(visibilityUser(Role::COUNTER_STAFF))->count())->toBe(2);
});

test('a technician still sees only their own bench', function () {
    $mine = visibilityUser(Role::TECHNICIAN, 'Tech One');
    $theirs = visibilityUser(Role::TECHNICIAN, 'Tech Two');

    visibilityJobOrder(['assigned_to' => $mine->id]);
    visibilityJobOrder(['assigned_to' => $theirs->id]);
    visibilityJobOrder();

    expect(JobOrder::visibleTo($mine)->count())->toBe(1);
});

// -- The index screen ------------------------------------------------------

test('the job orders index lists every job order for an administrator', function () {
    $tech = visibilityUser(Role::TECHNICIAN, 'Tech One');
    $assigned = visibilityJobOrder(['assigned_to' => $tech->id]);
    $unassigned = visibilityJobOrder();

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.index')
        ->assertSee($assigned->job_order_number)
        ->assertSee($unassigned->job_order_number);
});

test('the technician filter narrows the list to one bench', function () {
    $one = visibilityUser(Role::TECHNICIAN, 'Tech One');
    $two = visibilityUser(Role::TECHNICIAN, 'Tech Two');

    $theirs = visibilityJobOrder(['assigned_to' => $one->id]);
    $others = visibilityJobOrder(['assigned_to' => $two->id]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.index')
        ->set('technicianFilter', (string) $one->id)
        ->assertSee($theirs->job_order_number)
        ->assertDontSee($others->job_order_number);
});

test('the unassigned queue shows only job orders with no technician', function () {
    $tech = visibilityUser(Role::TECHNICIAN, 'Tech One');
    $assigned = visibilityJobOrder(['assigned_to' => $tech->id]);
    $unassigned = visibilityJobOrder();

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.index')
        ->call('showQueue', 'unassigned')
        ->assertSee($unassigned->job_order_number)
        ->assertDontSee($assigned->job_order_number);
});

test('the overdue queue ignores repairs that are already finished', function () {
    $late = visibilityJobOrder([
        'expected_completion_date' => today()->subDays(3),
        'status' => JobOrderStatus::IN_PROGRESS,
    ]);

    // Late, but the customer already has it back — chasing it would be noise.
    $lateButDelivered = visibilityJobOrder([
        'expected_completion_date' => today()->subDays(3),
        'status' => JobOrderStatus::DELIVERED,
    ]);

    $onTime = visibilityJobOrder([
        'expected_completion_date' => today()->addDays(3),
        'status' => JobOrderStatus::IN_PROGRESS,
    ]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.index')
        ->call('showQueue', 'overdue')
        ->assertSee($late->job_order_number)
        ->assertDontSee($lateButDelivered->job_order_number)
        ->assertDontSee($onTime->job_order_number);
});

test('clicking the same queue twice clears it', function () {
    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.index')
        ->call('showQueue', 'overdue')
        ->assertSet('assignmentFilter', 'overdue')
        ->call('showQueue', 'overdue')
        ->assertSet('assignmentFilter', '');
});

// -- The administrator's workload panel -------------------------------------

test('the workload panel counts what each technician is carrying', function () {
    $busy = visibilityUser(Role::TECHNICIAN, 'Busy Tech');
    $idle = visibilityUser(Role::TECHNICIAN, 'Idle Tech');

    visibilityJobOrder(['assigned_to' => $busy->id, 'status' => JobOrderStatus::IN_PROGRESS]);
    visibilityJobOrder(['assigned_to' => $busy->id, 'status' => JobOrderStatus::ASSIGNED]);
    // Delivered work is no longer being carried.
    visibilityJobOrder(['assigned_to' => $busy->id, 'status' => JobOrderStatus::DELIVERED]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    $workload = Volt::test('admin.dashboard')
        ->assertSee('Technician Workload')
        ->assertSee('Busy Tech')
        ->assertSee('Idle Tech')
        ->viewData('workload');

    $busyRow = $workload->firstWhere('technician.id', $busy->id);
    $idleRow = $workload->firstWhere('technician.id', $idle->id);

    expect($busyRow['open'])->toBe(2)
        ->and($busyRow['in_progress'])->toBe(1)
        ->and($idleRow['open'])->toBe(0)
        ->and($idleRow['oldest_open_number'])->toBeNull();
});

test('the workload panel flags a technician holding an overdue repair', function () {
    $tech = visibilityUser(Role::TECHNICIAN, 'Late Tech');

    visibilityJobOrder([
        'assigned_to' => $tech->id,
        'status' => JobOrderStatus::IN_PROGRESS,
        'expected_completion_date' => today()->subDays(5),
    ]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    $row = Volt::test('admin.dashboard')
        ->viewData('workload')
        ->firstWhere('technician.id', $tech->id);

    expect($row['overdue'])->toBe(1)
        ->and($row['oldest_open_number'])->not->toBeNull();
});

// -- The history timeline in the view modal ---------------------------------

test('the view modal shows the repair history', function () {
    $tech = visibilityUser(Role::TECHNICIAN, 'Tech One');
    $jobOrder = visibilityJobOrder(['status' => JobOrderStatus::IN_PROGRESS]);
    $jobOrder->update(['assigned_to' => $tech->id]);
    $jobOrder->update(['status' => JobOrderStatus::DONE]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.index')
        ->call('viewJobOrder', $jobOrder->id)
        ->assertSee('Repair History')
        ->assertSee('Repair booked in.')
        ->assertSee('Assigned to Tech One.')
        ->assertSee('The repair work is finished and being checked.')
        // Assignment is shop business, and the modal says so.
        ->assertSee('Internal');
});

test('the history is newest first', function () {
    $jobOrder = visibilityJobOrder(['status' => JobOrderStatus::IN_PROGRESS]);
    $jobOrder->update(['status' => JobOrderStatus::DONE]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    $events = Volt::test('job-orders.index')
        ->call('viewJobOrder', $jobOrder->id)
        ->instance()
        ->selectedJobOrderEvents();

    expect($events->first()->to_status)->toBe(JobOrderStatus::DONE)
        ->and($events->last()->type)->toBe(\App\Models\JobOrderEvent::TYPE_CREATED);
});

// -- Nullable lifecycle dates in the view modal ----------------------------

test('a completed job order that has not been collected yet can be viewed', function () {
    // The Delivered block used to sit inside the completed_at guard without a
    // test of its own, so every finished-but-uncollected repair formatted a
    // null delivered_at and took the modal down with a 500. On production
    // that was 5 of 8 job orders.
    $jobOrder = visibilityJobOrder([
        'status' => JobOrderStatus::COMPLETED,
        'completed_at' => now(),
        'delivered_at' => null,
    ]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    // Rendering at all is the regression guard; the delivered timestamp
    // itself must be absent. ("Delivered" as a word also appears in the
    // status filter, so the date is what distinguishes the block.)
    Volt::test('job-orders.index')
        ->call('viewJobOrder', $jobOrder->id)
        ->assertHasNoErrors()
        ->assertSee($jobOrder->completed_at->format('M d, Y h:i A'));
});

test('a delivered job order shows both dates', function () {
    $jobOrder = visibilityJobOrder([
        'status' => JobOrderStatus::DELIVERED,
        'completed_at' => now()->subDay(),
        'delivered_at' => now(),
    ]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.index')
        ->call('viewJobOrder', $jobOrder->id)
        ->assertHasNoErrors()
        ->assertSee($jobOrder->completed_at->format('M d, Y h:i A'))
        ->assertSee($jobOrder->delivered_at->format('M d, Y h:i A'));
});

test('a job order with none of the lifecycle dates set can be viewed', function () {
    $jobOrder = visibilityJobOrder([
        'status' => JobOrderStatus::PENDING,
        'expected_completion_date' => null,
    ]);

    $this->actingAs(visibilityUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.index')
        ->call('viewJobOrder', $jobOrder->id)
        ->assertHasNoErrors();
});
