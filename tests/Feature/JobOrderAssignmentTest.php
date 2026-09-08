<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Volt;

function actor(Role $role): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

function fillJobOrderForm($component, ?int $assignedTo): mixed
{
    return $component
        ->set('customer_name', 'Juan Dela Cruz')
        ->set('customer_phone', '09171234567')
        ->set('device_brand', 'Samsung')
        ->set('device_model', 'Galaxy A54')
        ->set('issue_description', 'Cracked screen, touch not responding')
        ->set('services', [['type' => 'Screen Repair', 'diagnosis' => 'Digitizer damaged']])
        ->set('assigned_to', $assignedTo);
}

test('an administrator can create a job order assigned to a technician', function () {
    $technician = actor(Role::TECHNICIAN);

    $this->actingAs(actor(Role::ADMINISTRATOR));

    fillJobOrderForm(Volt::test('job-orders.create'), $technician->id)
        ->call('save')
        ->assertHasNoErrors();

    $jobOrder = JobOrder::firstOrFail();

    expect($jobOrder->assigned_to)->toBe($technician->id);
    // Assigning up front must be reflected in the status, not left on Pending
    expect($jobOrder->status)->toBe(JobOrderStatus::ASSIGNED);
});

test('a job order created without a technician stays pending', function () {
    $this->actingAs(actor(Role::ADMINISTRATOR));

    fillJobOrderForm(Volt::test('job-orders.create'), null)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobOrder::firstOrFail()->status)->toBe(JobOrderStatus::PENDING);
});

test('a job order cannot be assigned to a user who is not a technician', function () {
    $counterStaff = actor(Role::COUNTER_STAFF);

    $this->actingAs(actor(Role::ADMINISTRATOR));

    fillJobOrderForm(Volt::test('job-orders.create'), $counterStaff->id)
        ->call('save')
        ->assertHasErrors('assigned_to');

    expect(JobOrder::count())->toBe(0);
});

test('counter staff can also create an assigned job order', function () {
    $technician = actor(Role::TECHNICIAN);

    $this->actingAs(actor(Role::COUNTER_STAFF));

    fillJobOrderForm(Volt::test('job-orders.create'), $technician->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobOrder::firstOrFail()->status)->toBe(JobOrderStatus::ASSIGNED);
});

test('a technician can only view job orders assigned to them', function () {
    $technician = actor(Role::TECHNICIAN);
    $otherTechnician = actor(Role::TECHNICIAN);

    $jobOrder = JobOrder::create([
        'customer_name' => 'Maria Santos',
        'customer_phone' => '09181234567',
        'device_brand' => 'Apple',
        'device_model' => 'iPhone 13',
        'issue_description' => 'Battery drains fast',
        'status' => JobOrderStatus::ASSIGNED,
        'assigned_to' => $otherTechnician->id,
        'received_by' => actor(Role::COUNTER_STAFF)->id,
    ]);

    // The technician it is NOT assigned to cannot open it
    $this->actingAs($technician);

    expect(fn () => Volt::test('technician.dashboard')->call('viewJobOrder', $jobOrder->id))
        ->toThrow(ModelNotFoundException::class);

    // The assigned technician can
    $this->actingAs($otherTechnician);

    Volt::test('technician.dashboard')
        ->call('viewJobOrder', $jobOrder->id)
        ->assertSet('showViewModal', true);
});
