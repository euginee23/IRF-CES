<?php

use App\Models\Part;
use App\Models\Service;
use App\Support\PhoneCatalogue;
use Database\Seeders\PartCategorySeeder;
use Database\Seeders\PartsTableSeeder;
use Database\Seeders\ServiceSeeder;

beforeEach(function () {
    $this->seed(PartCategorySeeder::class);
});

test('the catalogue covers 2020 to 2026 phones up to the iPhone 18', function () {
    $this->seed(PartsTableSeeder::class);

    foreach (['iPhone 12', 'iPhone 16 Pro Max', 'iPhone 17', 'iPhone 18', 'iPhone 18 Pro Max'] as $model) {
        expect(Part::where('manufacturer', 'Apple')->where('model', $model)->where('name', 'like', 'Screen Assembly%')->exists())
            ->toBeTrue("No screen for {$model}");
    }

    expect(Part::where('model', 'Galaxy S25 Ultra')->count())->toBe(count(PhoneCatalogue::PART_TYPES))
        ->and(Part::where('model', 'Galaxy Z Fold6')->where('name', 'like', 'Inner Screen Assembly%')->exists())->toBeTrue();

    foreach (['Xiaomi', 'Oppo', 'Vivo', 'Realme', 'Infinix', 'Tecno', 'Huawei', 'Honor', 'Google'] as $brand) {
        expect(Part::where('manufacturer', $brand)->count())->toBeGreaterThan(50, "Too few parts for {$brand}");
    }
});

test('re-running the seeder adds nothing and leaves stock alone', function () {
    $this->seed(PartsTableSeeder::class);
    $count = Part::count();

    $starter = Part::where('sku', 'SAM-S21-SCR')->firstOrFail();
    $starter->update(['in_stock' => 1, 'unit_sale_price' => 9999]);
    $catalogued = Part::where('sku', PhoneCatalogue::sku('Apple', 'iPhone 18', 'SCR'))->firstOrFail();
    $catalogued->update(['in_stock' => 4]);

    $this->seed(PartsTableSeeder::class);

    expect(Part::count())->toBe($count)
        ->and($starter->fresh()->in_stock)->toBe(1)
        ->and((float) $starter->fresh()->unit_sale_price)->toBe(9999.0)
        ->and($catalogued->fresh()->in_stock)->toBe(4);
});

test('a part already stocked under an older name is not added twice', function () {
    $this->seed(PartsTableSeeder::class);

    // The starter set has "Charging Port - Samsung Galaxy S21" and
    // "Lightning Connector - iPhone 13"; neither phone gets a second one.
    expect(Part::where('model', 'Galaxy S21')->where('name', 'like', 'Charging Port%')->count())->toBe(1)
        ->and(Part::where('model', 'iPhone 13')->where(fn ($q) => $q
            ->where('name', 'like', 'Charging Port%')
            ->orWhere('name', 'like', 'Lightning Connector%'))->count())->toBe(1);
});

test('catalogue parts are listed but not flagged as low stock', function () {
    $this->seed(PartsTableSeeder::class);

    $catalogued = Part::where('sku', PhoneCatalogue::sku('Apple', 'iPhone 18', 'SCR'))->firstOrFail();

    expect($catalogued->in_stock)->toBe(0)
        ->and($catalogued->isLowStock())->toBeFalse()
        ->and(Part::lowStock()->whereKey($catalogued->id)->exists())->toBeFalse();
});

test('the service seeder can be re-run and offers screen services', function () {
    $this->seed(ServiceSeeder::class);
    $this->seed(ServiceSeeder::class);

    expect(Service::where('name', 'Screen Replacement')->count())->toBe(1)
        ->and(Service::search('screen')->first()->name)->toStartWith('Screen');
});
