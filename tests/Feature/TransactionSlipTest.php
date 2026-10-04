<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Models\JobOrder;
use App\Models\User;

function slipUser(Role $role): User
{
    return User::factory()->create(['role' => $role, 'email_verified_at' => now()]);
}

function slipJobOrder(): JobOrder
{
    return JobOrder::create([
        'customer_name' => 'Ana Lim',
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy S24',
        'issue_description' => 'No display',
        'expected_completion_date' => '2026-10-09',
        'status' => JobOrderStatus::PENDING,
        'received_by' => slipUser(Role::COUNTER_STAFF)->id,
    ]);
}

test('counter staff and administrators can print the transaction slip', function (Role $role) {
    $jobOrder = slipJobOrder();

    $this->actingAs(slipUser($role))
        ->get(route('job-orders.slip', $jobOrder))
        ->assertOk()
        ->assertSee('TRANSACTION SLIP')
        ->assertSee($jobOrder->job_order_number)
        ->assertSee($jobOrder->tracking_code)
        ->assertSee('Oct 09, 2026')
        ->assertSee('Ana Lim');
})->with([Role::COUNTER_STAFF, Role::ADMINISTRATOR]);

test('a technician cannot print the transaction slip', function () {
    $this->actingAs(slipUser(Role::TECHNICIAN))
        ->get(route('job-orders.slip', slipJobOrder()))
        ->assertForbidden();
});

test('a guest cannot print the transaction slip', function () {
    $this->get(route('job-orders.slip', slipJobOrder()))->assertRedirect(route('login'));
});
