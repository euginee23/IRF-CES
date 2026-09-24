<?php

use App\Enums\Role;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => Role::ADMINISTRATOR,
        'email_verified_at' => now(),
    ]);
});

test('an administrator can reach the part categories screen', function () {
    $this->actingAs($this->admin)->get(route('admin.part-categories'))->assertOk();
});

test('a technician cannot reach the part categories screen', function () {
    $technician = User::factory()->create([
        'role' => Role::TECHNICIAN,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($technician)->get(route('admin.part-categories'))->assertForbidden();
});

test('a category can be created and is given a slug', function () {
    Volt::actingAs($this->admin)
        ->test('admin.part-categories')
        ->set('name', 'Display & Input Components')
        ->set('sort_order', 1)
        ->call('save')
        ->assertDispatched('success');

    $category = PartCategory::firstOrFail();

    expect($category->name)->toBe('Display & Input Components')
        ->and($category->slug)->toBe('display-input-components');
});

test('two categories cannot share a name', function () {
    PartCategory::create(['name' => 'Battery']);

    Volt::actingAs($this->admin)
        ->test('admin.part-categories')
        ->set('name', 'Battery')
        ->set('sort_order', 0)
        ->call('save')
        ->assertHasErrors(['name']);
});

test('two categories whose names slug alike still both save', function () {
    // "Display & Input" and "Display Input" both slug to display-input, and
    // slug carries a unique index.
    PartCategory::create(['name' => 'Display & Input']);
    $second = PartCategory::create(['name' => 'Display Input']);

    expect($second->slug)->not->toBe('display-input')
        ->and(PartCategory::count())->toBe(2);
});

test('renaming a category updates its slug', function () {
    $category = PartCategory::create(['name' => 'Battery']);

    $category->update(['name' => 'Power & Charging']);

    expect($category->fresh()->slug)->toBe('power-charging');
});

test('a category still holding parts cannot be deleted', function () {
    $category = PartCategory::create(['name' => 'Battery']);

    Part::create([
        'name' => 'Battery - Galaxy A32',
        'sku' => 'BAT-A32',
        'part_category_id' => $category->id,
        'in_stock' => 5,
        'reorder_point' => 2,
        'unit_cost_price' => 400,
        'unit_sale_price' => 800,
        'is_active' => true,
    ]);

    Volt::actingAs($this->admin)
        ->test('admin.part-categories')
        ->call('confirmDelete', $category->id)
        ->call('delete')
        ->assertDispatched('error');

    expect(PartCategory::find($category->id))->not->toBeNull();
});

test('an empty category can be deleted', function () {
    $category = PartCategory::create(['name' => 'Obsolete']);

    Volt::actingAs($this->admin)
        ->test('admin.part-categories')
        ->call('confirmDelete', $category->id)
        ->call('delete')
        ->assertDispatched('success');

    expect(PartCategory::find($category->id))->toBeNull();
});

test('an inactive category is hidden from the pickers but keeps its parts', function () {
    $active = PartCategory::create(['name' => 'Battery', 'is_active' => true]);
    $retired = PartCategory::create(['name' => 'Obsolete', 'is_active' => false]);

    $offered = PartCategory::active()->ordered()->pluck('name');

    expect($offered)->toContain('Battery')
        ->and($offered)->not->toContain('Obsolete')
        ->and(PartCategory::find($retired->id))->not->toBeNull();
});

test('a part reports its category name, falling back to the legacy string', function () {
    $category = PartCategory::create(['name' => 'Battery']);

    $filed = Part::create([
        'name' => 'Battery A', 'sku' => 'A1', 'part_category_id' => $category->id,
        'in_stock' => 1, 'reorder_point' => 1, 'unit_cost_price' => 1, 'unit_sale_price' => 2,
    ]);

    // A part that predates the backfill still reads as something.
    $legacy = Part::create([
        'name' => 'Battery B', 'sku' => 'B1', 'category' => 'Old Name',
        'in_stock' => 1, 'reorder_point' => 1, 'unit_cost_price' => 1, 'unit_sale_price' => 2,
    ]);

    expect($filed->category_name)->toBe('Battery')
        ->and($legacy->category_name)->toBe('Old Name');
});
