<?php

use App\Enums\Role;
use App\Models\Part;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => Role::ADMINISTRATOR,
        'email_verified_at' => now(),
    ]);

    $this->technician = User::factory()->create([
        'role' => Role::TECHNICIAN,
        'email_verified_at' => now(),
    ]);
});

test('authenticated users can view parts inventory page', function () {
    $response = $this->actingAs($this->admin)
        ->get('/admin/parts-inventory');

    $response->assertStatus(200)
        ->assertSee('Parts Inventory');
});

test('unauthenticated users cannot access parts inventory page', function () {
    $response = $this->get('/admin/parts-inventory');

    $response->assertStatus(302)
        ->assertRedirect('/login');
});

test('parts inventory page displays parts list', function () {
    Part::create([
        'name' => 'iPhone 13 LCD Screen',
        'sku' => 'LCD-IP13-001',
        'category' => 'Display & Input Components',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 2500.00,
        'manufacturer' => 'Apple',
        'model' => 'iPhone 13',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->get('/admin/parts-inventory');

    $response->assertStatus(200)
        ->assertSee('iPhone 13 LCD Screen')
        ->assertSee('LCD-IP13-001')
        ->assertSee('Apple');
});

test('administrator can create a new part', function () {
    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('name', 'Samsung Galaxy S21 Battery')
        ->set('sku', 'BAT-S21-001')
        ->set('category', 'Power & Charging Components')
        ->set('description', 'Original Samsung battery')
        ->set('in_stock', 15)
        ->set('reorder_point', 5)
        ->set('unit_price', 800.00)
        ->set('supplier', 'TechParts Inc.')
        ->set('manufacturer', 'Samsung')
        ->set('model', 'Galaxy S21')
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('success');

    $this->assertDatabaseHas('parts', [
        'name' => 'Samsung Galaxy S21 Battery',
        'sku' => 'BAT-S21-001',
        'category' => 'Power & Charging Components',
        'manufacturer' => 'Samsung',
        'model' => 'Galaxy S21',
    ]);
});

test('technician can create a new part', function () {
    Volt::actingAs($this->technician)
        ->test('admin.parts-inventory')
        ->set('name', 'Xiaomi Redmi Note 10 Screen')
        ->set('sku', 'LCD-RN10-001')
        ->set('category', 'Display & Input Components')
        ->set('in_stock', 8)
        ->set('reorder_point', 3)
        ->set('unit_price', 1200.00)
        ->set('manufacturer', 'Xiaomi')
        ->set('model', 'Redmi Note 10')
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('success');

    $this->assertDatabaseHas('parts', [
        'name' => 'Xiaomi Redmi Note 10 Screen',
        'sku' => 'LCD-RN10-001',
    ]);
});

test('part creation requires name and sku', function () {
    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('name', '')
        ->set('sku', '')
        ->set('in_stock', 10)
        ->set('reorder_point', 5)
        ->set('unit_price', 1000.00)
        ->call('save')
        ->assertHasErrors(['name', 'sku']);
});

test('sku must be unique when creating part', function () {
    Part::create([
        'name' => 'Existing Part',
        'sku' => 'UNIQUE-SKU-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 500.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('name', 'New Part')
        ->set('sku', 'UNIQUE-SKU-001')
        ->set('in_stock', 5)
        ->set('reorder_point', 2)
        ->set('unit_price', 600.00)
        ->call('save')
        ->assertHasErrors(['sku']);
});

test('stock and price fields require valid numeric values', function () {
    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('name', 'Test Part')
        ->set('sku', 'TEST-001')
        ->set('in_stock', -5)
        ->set('reorder_point', -2)
        ->set('unit_price', -100.00)
        ->call('save')
        ->assertHasErrors(['in_stock', 'reorder_point', 'unit_price']);
});

test('administrator can edit existing part', function () {
    $part = Part::create([
        'name' => 'Original Name',
        'sku' => 'ORIGINAL-001',
        'category' => 'Power & Charging Components',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'manufacturer' => 'Samsung',
        'model' => 'Galaxy S21',
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->call('openEditModal', $part->id)
        ->assertSet('name', 'Original Name')
        ->assertSet('sku', 'ORIGINAL-001')
        ->set('name', 'Updated Name')
        ->set('in_stock', 15)
        ->set('unit_price', 1200.00)
        ->call('save')
        ->assertDispatched('success');

    $this->assertDatabaseHas('parts', [
        'id' => $part->id,
        'name' => 'Updated Name',
        'sku' => 'ORIGINAL-001',
        'in_stock' => 15,
        'unit_price' => 1200.00,
    ]);
});

test('sku uniqueness is ignored when updating same part', function () {
    $part = Part::create([
        'name' => 'Test Part',
        'sku' => 'TEST-SKU-001',
        'category' => 'Display & Input Components',
        'manufacturer' => 'Apple',
        'model' => 'iPhone 13',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->call('openEditModal', $part->id)
        ->set('name', 'Updated Test Part')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('parts', [
        'id' => $part->id,
        'name' => 'Updated Test Part',
        'sku' => 'TEST-SKU-001',
    ]);
});

test('administrator can delete part', function () {
    $part = Part::create([
        'name' => 'Part to Delete',
        'sku' => 'DELETE-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 500.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->call('delete', $part->id)
        ->assertDispatched('success');

    $this->assertDatabaseMissing('parts', [
        'id' => $part->id,
    ]);
});

test('parts inventory can be searched by name', function () {
    Part::create([
        'name' => 'iPhone 13 Battery',
        'sku' => 'BAT-IP13-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1500.00,
        'is_active' => true,
    ]);

    Part::create([
        'name' => 'Samsung Galaxy Battery',
        'sku' => 'BAT-S21-001',
        'in_stock' => 8,
        'reorder_point' => 3,
        'unit_price' => 1200.00,
        'is_active' => true,
    ]);

    $component = Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('search', 'iPhone')
        ->assertSee('iPhone 13 Battery')
        ->assertDontSee('Samsung Galaxy Battery');
});

test('parts inventory can be searched by sku', function () {
    Part::create([
        'name' => 'Part One',
        'sku' => 'SKU-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    Part::create([
        'name' => 'Part Two',
        'sku' => 'SKU-002',
        'in_stock' => 8,
        'reorder_point' => 3,
        'unit_price' => 800.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('search', 'SKU-002')
        ->assertSee('Part Two')
        ->assertDontSee('Part One');
});

test('parts inventory can be searched by supplier', function () {
    Part::create([
        'name' => 'Part from TechParts',
        'sku' => 'TP-001',
        'supplier' => 'TechParts Inc.',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    Part::create([
        'name' => 'Part from OtherSupplier',
        'sku' => 'OS-001',
        'supplier' => 'Other Supplier Co.',
        'in_stock' => 8,
        'reorder_point' => 3,
        'unit_price' => 800.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('search', 'TechParts')
        ->assertSee('Part from TechParts')
        ->assertDontSee('Part from OtherSupplier');
});

test('parts inventory can be filtered by category', function () {
    Part::create([
        'name' => 'LCD Screen',
        'sku' => 'LCD-001',
        'category' => 'Display & Input Components',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 2000.00,
        'is_active' => true,
    ]);

    Part::create([
        'name' => 'Battery Pack',
        'sku' => 'BAT-001',
        'category' => 'Power & Charging Components',
        'in_stock' => 8,
        'reorder_point' => 3,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('categoryFilter', 'Display & Input Components')
        ->assertSee('LCD Screen')
        ->assertDontSee('Battery Pack');
});

test('parts inventory can show only low stock items', function () {
    Part::create([
        'name' => 'Low Stock Part',
        'sku' => 'LOW-001',
        'in_stock' => 3,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    Part::create([
        'name' => 'Normal Stock Part',
        'sku' => 'NORMAL-001',
        'in_stock' => 20,
        'reorder_point' => 5,
        'unit_price' => 800.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('showLowStock', true)
        ->assertSee('Low Stock Part')
        ->assertDontSee('Normal Stock Part');
});

test('part model correctly identifies low stock', function () {
    $lowStock = Part::create([
        'name' => 'Low Stock Part',
        'sku' => 'LOW-001',
        'in_stock' => 3,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    $normalStock = Part::create([
        'name' => 'Normal Stock Part',
        'sku' => 'NORMAL-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 800.00,
        'is_active' => true,
    ]);

    expect($lowStock->isLowStock())->toBeTrue();
    expect($normalStock->isLowStock())->toBeFalse();
});

test('part model can deduct stock successfully', function () {
    $part = Part::create([
        'name' => 'Test Part',
        'sku' => 'TEST-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    $result = $part->deductStock(3);

    expect($result)->toBeTrue();
    expect($part->fresh()->in_stock)->toBe(7);
});

test('part model cannot deduct more stock than available', function () {
    $part = Part::create([
        'name' => 'Test Part',
        'sku' => 'TEST-001',
        'in_stock' => 5,
        'reorder_point' => 2,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    $result = $part->deductStock(10);

    expect($result)->toBeFalse();
    expect($part->fresh()->in_stock)->toBe(5);
});

test('part model can add stock', function () {
    $part = Part::create([
        'name' => 'Test Part',
        'sku' => 'TEST-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    $part->addStock(5);

    expect($part->fresh()->in_stock)->toBe(15);
});

test('parts with manufacturer and model are displayed correctly', function () {
    Part::create([
        'name' => 'iPhone Screen',
        'sku' => 'IP-SCR-001',
        'manufacturer' => 'Apple',
        'model' => 'iPhone 13 Pro',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 3000.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->get('/admin/parts-inventory');

    $response->assertStatus(200)
        ->assertSee('Apple')
        ->assertSee('iPhone 13 Pro');
});

test('parts without manufacturer show N/A', function () {
    Part::create([
        'name' => 'Generic Part',
        'sku' => 'GEN-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 500.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->get('/admin/parts-inventory');

    $response->assertStatus(200)
        ->assertSee('N/A');
});

test('parts without supplier show N/A', function () {
    Part::create([
        'name' => 'Part Without Supplier',
        'sku' => 'NO-SUP-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 500.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->get('/admin/parts-inventory');

    $response->assertStatus(200)
        ->assertSee('N/A');
});

test('modal opens correctly for creating part', function () {
    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->call('openCreateModal')
        ->assertSet('showModal', true)
        ->assertSet('isEditing', false)
        ->assertSet('name', '')
        ->assertSet('sku', '');
});

test('modal opens correctly for editing part', function () {
    $part = Part::create([
        'name' => 'Test Part',
        'sku' => 'TEST-001',
        'category' => 'Display & Input Components',
        'manufacturer' => 'Samsung',
        'model' => 'Galaxy S21',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1500.00,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->call('openEditModal', $part->id)
        ->assertSet('showModal', true)
        ->assertSet('isEditing', true)
        ->assertSet('name', 'Test Part')
        ->assertSet('sku', 'TEST-001')
        ->assertSet('manufacturer', 'Samsung')
        ->assertSet('model', 'Galaxy S21');
});

test('modal closes and resets form', function () {
    Volt::actingAs($this->admin)
        ->test('admin.parts-inventory')
        ->set('name', 'Test Part')
        ->set('sku', 'TEST-001')
        ->call('openCreateModal')
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('name', '')
        ->assertSet('sku', '');
});

test('parts inventory displays correct statistics', function () {
    Part::create([
        'name' => 'Part 1',
        'sku' => 'P1-001',
        'in_stock' => 10,
        'reorder_point' => 5,
        'unit_price' => 1000.00,
        'is_active' => true,
    ]);

    Part::create([
        'name' => 'Part 2 - Low Stock',
        'sku' => 'P2-001',
        'in_stock' => 2,
        'reorder_point' => 5,
        'unit_price' => 500.00,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->get('/admin/parts-inventory');

    $response->assertStatus(200)
        ->assertSee('Total Parts')
        ->assertSee('Low Stock Items')
        ->assertSee('Total Value');
});
