<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Models\JobOrder;
use App\Models\Service;
use App\Models\User;
use Livewire\Volt\Volt;

function dateStaff(): User
{
    return User::factory()->create(['role' => Role::COUNTER_STAFF, 'email_verified_at' => now()]);
}

function overdueJobOrder(): JobOrder
{
    $jobOrder = JobOrder::create([
        'customer_name' => 'Leo Cruz',
        'customer_phone' => '09171234567',
        'device_brand' => 'Oppo',
        'device_model' => 'Reno12',
        'issue_description' => 'Will not charge',
        'expected_completion_date' => today()->subDays(2),
        'status' => JobOrderStatus::IN_PROGRESS,
        'received_by' => dateStaff()->id,
    ]);
    $jobOrder->services()->create(['service_name' => 'Charging Port Replacement', 'labor_price' => 450]);

    return $jobOrder;
}

test('a new job order defaults to a date three days out', function () {
    $this->actingAs(dateStaff());

    Volt::test('job-orders.create')
        ->assertSet('expected_completion_date', today()->addDays(3)->toDateString());
});

test('a job order cannot be booked without an expected completion date', function () {
    $this->actingAs(dateStaff());
    Service::create(['name' => 'Screen Replacement', 'category' => 'Display & Input', 'labor_price' => 500, 'is_active' => true]);

    Volt::test('job-orders.create')
        ->set('customer_name', 'Juan Dela Cruz')
        ->set('customer_phone', '09171234567')
        ->set('device_brand', 'Samsung')
        ->set('device_model', 'Galaxy A54')
        ->set('issue_description', 'Cracked screen')
        ->set('services', [['type' => 'Screen Replacement', 'diagnosis' => '']])
        ->set('expected_completion_date', '')
        ->call('save')
        ->assertHasErrors(['expected_completion_date' => 'required']);

    expect(JobOrder::count())->toBe(0);
});

test('booking a job order offers the transaction slip', function () {
    $this->actingAs(dateStaff());
    Service::create(['name' => 'Screen Replacement', 'category' => 'Display & Input', 'labor_price' => 500, 'is_active' => true]);

    Volt::test('job-orders.create')
        ->set('customer_name', 'Juan Dela Cruz')
        ->set('customer_phone', '09171234567')
        ->set('device_brand', 'Samsung')
        ->set('device_model', 'Galaxy A54')
        ->set('issue_description', 'Cracked screen')
        ->set('services', [['type' => 'Screen Replacement', 'diagnosis' => '']])
        ->call('save')
        ->assertHasNoErrors();

    expect(session('print_slip'))->toBe(JobOrder::firstOrFail()->id);
});

test('an overdue job order can be saved without moving its date', function () {
    $this->actingAs(dateStaff());
    $jobOrder = overdueJobOrder();

    Volt::test('job-orders.edit', ['jobOrder' => $jobOrder])
        ->set('issue_description', 'Will not charge, port loose')
        ->call('save')
        ->assertHasNoErrors();

    expect($jobOrder->fresh()->issue_description)->toBe('Will not charge, port loose');
});

test('a changed date on edit cannot be in the past', function () {
    $this->actingAs(dateStaff());
    $jobOrder = overdueJobOrder();

    Volt::test('job-orders.edit', ['jobOrder' => $jobOrder])
        ->set('expected_completion_date', today()->subDay()->toDateString())
        ->call('save')
        ->assertHasErrors(['expected_completion_date']);
});

test('staff can move the date from the job order view and the customer sees it', function () {
    $this->actingAs(dateStaff());
    $jobOrder = overdueJobOrder();
    $newDate = today()->addDays(5);

    Volt::test('job-orders.index')
        ->call('viewJobOrder', $jobOrder->id)
        ->set('expectedDate', $newDate->toDateString())
        ->call('updateExpectedDate')
        ->assertHasNoErrors();

    expect($jobOrder->fresh()->expected_completion_date->isSameDay($newDate))->toBeTrue()
        ->and($jobOrder->events()->where('is_customer_visible', true)
            ->where('description', 'like', '%'.$newDate->format('F d, Y').'%')->exists())->toBeTrue();
});

test('the job order list flags an overdue repair', function () {
    $this->actingAs(dateStaff());
    overdueJobOrder();

    Volt::test('job-orders.index')->assertSee('Overdue');
});
