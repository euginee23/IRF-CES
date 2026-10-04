<?php

use App\Enums\Role;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use Livewire\Volt\Volt;

function searchPart(string $name, string $manufacturer = 'Apple', string $model = 'iPhone 15'): Part
{
    static $n = 0;
    $n++;

    return Part::create([
        'name' => $name, 'sku' => "SRCH-{$n}", 'manufacturer' => $manufacturer, 'model' => $model,
        'in_stock' => 1, 'reorder_point' => 1, 'unit_cost_price' => 100, 'unit_sale_price' => 200, 'is_active' => true,
    ]);
}

function searchService(string $name, string $category = 'Display & Input'): Service
{
    return Service::create(['name' => $name, 'category' => $category, 'labor_price' => 500, 'is_active' => true]);
}

test('parts whose name starts with the term come first', function () {
    searchPart('Adhesive for Screen - iPhone 15');
    searchPart('Battery - iPhone 15');
    searchPart('Screen Assembly - iPhone 15');
    searchPart('Back Glass - iPhone 15');
    searchPart('Screen Assembly - Samsung Galaxy S24', 'Samsung', 'Galaxy S24');

    $names = Part::search('screen')->pluck('name')->all();

    // Ties within a rank fall back to name order, which is collation
    // dependent (SQLite sorts case-sensitively), so only the ranks are
    // asserted here.
    expect($names)->toHaveCount(3)
        ->and(array_slice($names, 0, 2))->toEqualCanonicalizing([
            'Screen Assembly - iPhone 15',
            'Screen Assembly - Samsung Galaxy S24',
        ])
        ->and($names[2])->toBe('Adhesive for Screen - iPhone 15');
});

test('each word of a part search is matched on its own', function () {
    searchPart('Screen Assembly - iPhone 15');
    searchPart('Screen Assembly - iPhone 14', 'Apple', 'iPhone 14');
    searchPart('Battery - iPhone 15');

    expect(Part::search('screen iphone 15')->pluck('name')->all())
        ->toBe(['Screen Assembly - iPhone 15']);
});

test('a part is found by its brand and model columns', function () {
    searchPart('Battery', 'Samsung', 'Galaxy A54');

    expect(Part::search('galaxy a54')->count())->toBe(1);
});

test('services whose name starts with the term come first', function () {
    searchService('Battery Replacement', 'Power & Charging');
    searchService('Cracked Screen Assessment', 'Diagnostics');
    searchService('Screen Replacement');
    searchService('Screen Protector Installation');

    expect(Service::search('screen')->pluck('name')->all())->toBe([
        'Screen Protector Installation',
        'Screen Replacement',
        'Cracked Screen Assessment',
    ]);
});

test('the job order form searches services and adds them once', function () {
    $this->actingAs(User::factory()->create(['role' => Role::COUNTER_STAFF, 'email_verified_at' => now()]));
    $screen = searchService('Screen Replacement');
    searchService('Battery Replacement', 'Power & Charging');

    Volt::test('job-orders.create')
        ->set('serviceSearch', 'screen')
        ->assertSee('Screen Replacement')
        ->assertViewHas('serviceResults', fn ($results) => $results->pluck('name')->all() === ['Screen Replacement'])
        ->call('addServiceToJob', $screen->id)
        ->call('addServiceToJob', $screen->id)
        ->assertSet('services', [['type' => 'Screen Replacement', 'diagnosis' => '']])
        ->call('removeService', 0)
        ->assertSet('services', []);
});

test('the job order form ranks part matches and caps the list', function () {
    $this->actingAs(User::factory()->create(['role' => Role::COUNTER_STAFF, 'email_verified_at' => now()]));
    searchPart('Adhesive for Screen - iPhone 15');
    searchPart('Screen Assembly - iPhone 15');
    foreach (range(1, 55) as $i) {
        searchPart("Battery - Phone {$i}", 'Generic', "Phone {$i}");
    }

    Volt::test('job-orders.create')
        ->set('partSearch', 'screen')
        ->assertViewHas('availableParts', fn ($parts) => $parts->first()->name === 'Screen Assembly - iPhone 15')
        ->set('partSearch', '')
        ->assertViewHas('availableParts', fn ($parts) => $parts->count() === 50)
        ->assertViewHas('morePartsAvailable', true);
});
