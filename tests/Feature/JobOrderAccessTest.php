<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Models\JobOrder;
use App\Models\User;

function staffMember(Role $role): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

test('administrators can view the job orders list', function () {
    $this->actingAs(staffMember(Role::ADMINISTRATOR))
        ->get('/job-orders')
        ->assertStatus(200);
});

test('counter staff can view the job orders list', function () {
    $this->actingAs(staffMember(Role::COUNTER_STAFF))
        ->get('/job-orders')
        ->assertStatus(200);
});

test('technicians cannot view the job orders list', function () {
    $this->actingAs(staffMember(Role::TECHNICIAN))
        ->get('/job-orders')
        ->assertStatus(403);
});

test('administrators can open the job order create page', function () {
    $this->actingAs(staffMember(Role::ADMINISTRATOR))
        ->get('/job-orders/create')
        ->assertStatus(200);
});

test('counter staff can open the job order create page', function () {
    $this->actingAs(staffMember(Role::COUNTER_STAFF))
        ->get('/job-orders/create')
        ->assertStatus(200);
});

test('technicians cannot open the job order create page', function () {
    $this->actingAs(staffMember(Role::TECHNICIAN))
        ->get('/job-orders/create')
        ->assertStatus(403);
});

test('guests are redirected to login from job orders', function () {
    $this->get('/job-orders')->assertRedirect('/login');
});

test('administrators can open the job order edit page', function () {
    $jobOrder = JobOrder::create([
        'customer_name' => 'Juan Dela Cruz',
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'A54',
        'issue_description' => 'Cracked screen',
        'status' => JobOrderStatus::PENDING,
        'received_by' => staffMember(Role::COUNTER_STAFF)->id,
    ]);

    $this->actingAs(staffMember(Role::ADMINISTRATOR))
        ->get("/job-orders/{$jobOrder->id}/edit")
        ->assertStatus(200);
});
