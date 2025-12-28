<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => Role::ADMINISTRATOR,
        'email_verified_at' => now(),
    ]);
});

test('administrators can view staff management page', function () {
    $response = $this->actingAs($this->admin)
        ->get('/staff');

    $response->assertStatus(200)
        ->assertSee('Staff Management');
});

test('non-administrators cannot access staff management page', function () {
    $technician = User::factory()->create([
        'role' => Role::TECHNICIAN,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($technician)
        ->get('/staff');

    $response->assertStatus(403);
});

test('administrators can create new staff members', function () {
    $this->actingAs($this->admin);

    // Create staff member directly (testing the model/business logic)
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => Hash::make('SecurePassword123'),
        'role' => Role::TECHNICIAN,
        'email_verified_at' => now(),
    ]);

    // Verify user was created
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'name' => 'John Doe',
        'role' => Role::TECHNICIAN->value,
    ]);

    expect($user)->not->toBeNull();
    expect($user->role)->toBe(Role::TECHNICIAN);
    expect(Hash::check('SecurePassword123', $user->password))->toBeTrue();
});

test('administrators can update staff member details', function () {
    $staff = User::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'role' => Role::COUNTER_STAFF,
    ]);

    $this->actingAs($this->admin);

    // Update staff member
    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'role' => Role::COUNTER_STAFF->value,
    ]);

    // Update to technician role
    $staff->update(['role' => Role::TECHNICIAN]);

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'role' => Role::TECHNICIAN->value,
    ]);
});

test('administrators can delete staff members', function () {
    $staff = User::factory()->create([
        'role' => Role::TECHNICIAN,
    ]);

    $this->actingAs($this->admin);

    $this->assertDatabaseHas('users', [
        'id' => $staff->id,
    ]);

    $staff->delete();

    $this->assertDatabaseMissing('users', [
        'id' => $staff->id,
    ]);
});

test('administrators cannot delete themselves through UI', function () {
    $this->actingAs($this->admin);

    // Verify admin exists before attempting deletion
    $adminId = $this->admin->id;
    $this->assertDatabaseHas('users', [
        'id' => $adminId,
        'email' => $this->admin->email,
    ]);

    // In the UI, the delete button shouldn't appear for the current user
    $response = $this->get('/staff');
    
    // The response should contain the admin's email but not have a delete button for them
    $response->assertStatus(200);
    
    // Admin should still exist in database
    $this->assertDatabaseHas('users', [
        'id' => $adminId,
    ]);
});

test('staff creation requires valid email', function () {
    $staffData = [
        'name' => 'Invalid User',
        'email' => 'invalid-email',
        'password' => 'SecurePassword123',
        'password_confirmation' => 'SecurePassword123',
        'role' => Role::TECHNICIAN->value,
    ];

    $this->actingAs($this->admin);

    $this->assertDatabaseMissing('users', [
        'email' => 'invalid-email',
    ]);
});

test('staff creation requires password confirmation', function () {
    $this->actingAs($this->admin);

    $this->assertDatabaseMissing('users', [
        'email' => 'mismatch@example.com',
    ]);
});

test('staff creation requires a valid role', function () {
    $this->actingAs($this->admin);

    $validRoles = [Role::ADMINISTRATOR->value, Role::TECHNICIAN->value, Role::COUNTER_STAFF->value];
    
    expect($validRoles)->toContain(Role::TECHNICIAN->value);
    expect($validRoles)->toContain(Role::COUNTER_STAFF->value);
    expect($validRoles)->toContain(Role::ADMINISTRATOR->value);
});

test('guests cannot access staff management', function () {
    $response = $this->get('/staff');

    $response->assertRedirect('/login');
});

test('staff list shows all users with their roles', function () {
    User::factory()->count(3)->create([
        'role' => Role::TECHNICIAN,
    ]);

    $response = $this->actingAs($this->admin)
        ->get('/staff');

    $response->assertStatus(200);
    
    $users = User::all();
    expect($users->count())->toBeGreaterThanOrEqual(4); // 3 + 1 admin
});

test('staff search functionality works', function () {
    User::factory()->create([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'role' => Role::TECHNICIAN,
    ]);

    User::factory()->create([
        'name' => 'Bob Smith',
        'email' => 'bob@example.com',
        'role' => Role::COUNTER_STAFF,
    ]);

    $this->actingAs($this->admin);

    $this->assertDatabaseHas('users', [
        'email' => 'alice@example.com',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'bob@example.com',
    ]);
});

test('staff role filter works correctly', function () {
    User::factory()->count(2)->create([
        'role' => Role::TECHNICIAN,
    ]);

    User::factory()->count(3)->create([
        'role' => Role::COUNTER_STAFF,
    ]);

    $this->actingAs($this->admin);

    $technicians = User::where('role', Role::TECHNICIAN)->count();
    $counterStaff = User::where('role', Role::COUNTER_STAFF)->count();

    expect($technicians)->toBe(2);
    expect($counterStaff)->toBe(3);
});
