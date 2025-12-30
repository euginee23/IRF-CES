<?php

use App\Enums\Role;
use App\Models\Service;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => Role::ADMINISTRATOR,
        'email_verified_at' => now(),
    ]);
});

test('authenticated users can view services page', function () {
    $response = $this->actingAs($this->admin)
        ->get('/admin/services');

    $response->assertStatus(200)
        ->assertSee('Services Management');
});

test('unauthenticated users cannot access services page', function () {
    $response = $this->get('/admin/services');

    $response->assertStatus(302)
        ->assertRedirect('/login');
});

test('administrator can create a new service', function () {
    Volt::actingAs($this->admin)
        ->test('admin.services')
        ->set('name', 'Screen Replacement')
        ->set('category', 'Display & Input')
        ->set('description', 'Replace cracked screen')
        ->set('labor_price', 1200.00)
        ->set('estimated_duration', 45)
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('success');

    $this->assertDatabaseHas('services', [
        'name' => 'Screen Replacement',
        'category' => 'Display & Input',
        'labor_price' => 1200.00,
    ]);
});

test('service creation requires name, category, and labor_price', function () {
    Volt::actingAs($this->admin)
        ->test('admin.services')
        ->set('name', '')
        ->set('category', '')
        ->set('labor_price', '')
        ->call('save')
        ->assertHasErrors(['name', 'category', 'labor_price']);
});

test('administrator can edit existing service', function () {
    $service = Service::create([
        'name' => 'Old Service',
        'category' => 'Diagnostics & Testing',
        'description' => 'Old desc',
        'labor_price' => 500.00,
        'estimated_duration' => 30,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.services')
        ->call('openEditModal', $service->id)
        ->assertSet('name', 'Old Service')
        ->set('name', 'Updated Service')
        ->set('labor_price', 750.00)
        ->call('save')
        ->assertDispatched('success');

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => 'Updated Service',
        'labor_price' => 750.00,
    ]);
});

test('administrator can delete service', function () {
    $service = Service::create([
        'name' => 'Service To Delete',
        'category' => 'Refurbishing & Resale',
        'labor_price' => 300.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.services')
        ->call('delete', $service->id)
        ->assertDispatched('success');

    $this->assertDatabaseMissing('services', [
        'id' => $service->id,
    ]);
});
