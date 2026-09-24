<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Models\JobOrder;
use App\Models\JobOrderPart;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use App\Services\JobOrders\JobOrderLines;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

function lineUser(Role $role): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

function linePart(array $attributes = []): Part
{
    static $n = 0;
    $n++;

    return Part::create(array_merge([
        'name' => "Part {$n}",
        'sku' => "SKU-{$n}",
        'in_stock' => 10,
        'reorder_point' => 2,
        'unit_cost_price' => 100.00,
        'unit_sale_price' => 250.00,
        'is_active' => true,
    ], $attributes));
}

function lineService(string $name, float $price): Service
{
    return Service::create([
        'name' => $name,
        'category' => 'General',
        'labor_price' => $price,
        'is_active' => true,
    ]);
}

function lineJobOrder(): JobOrder
{
    return JobOrder::create([
        'customer_name' => 'Juan Dela Cruz',
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'issue_description' => 'Cracked screen',
        'status' => JobOrderStatus::PENDING,
        'received_by' => lineUser(Role::COUNTER_STAFF)->id,
    ]);
}

// -- Writing lines ---------------------------------------------------------

test('creating a job order writes its parts and services as rows', function () {
    $screen = linePart(['unit_sale_price' => 2600.00, 'unit_cost_price' => 1400.00]);
    lineService('Screen Repair', 500.00);

    $this->actingAs(lineUser(Role::ADMINISTRATOR));

    Volt::test('job-orders.create')
        ->set('customer_name', 'Juan Dela Cruz')
        ->set('customer_phone', '09171234567')
        ->set('device_brand', 'Samsung')
        ->set('device_model', 'Galaxy A54')
        ->set('issue_description', 'Cracked screen')
        ->set('services', [['type' => 'Screen Repair', 'diagnosis' => 'Digitizer damaged']])
        // Through the picker, as the screen itself does — it fills in the
        // name and price the live quote panel renders.
        ->call('addPartToJob', $screen->id)
        ->call('addPartToJob', $screen->id)
        ->call('save')
        ->assertHasNoErrors();

    $jobOrder = JobOrder::firstOrFail();

    expect($jobOrder->parts)->toHaveCount(1)
        ->and($jobOrder->services)->toHaveCount(1)
        ->and($jobOrder->partsTotal())->toBe(5200.00)
        ->and($jobOrder->laborTotal())->toBe(500.00)
        ->and($jobOrder->lineTotal())->toBe(5700.00)
        // Cost is snapshotted too, for margin reporting later.
        ->and($jobOrder->partsCost())->toBe(2800.00);
});

test('the line carries the part name, sku and diagnosis', function () {
    $part = linePart(['name' => 'Screen Assembly', 'sku' => 'SAM-A54-SCR']);
    lineService('Screen Repair', 500.00);

    $jobOrder = lineJobOrder();

    app(JobOrderLines::class)->sync(
        $jobOrder,
        [['type' => 'Screen Repair', 'diagnosis' => 'Digitizer damaged']],
        [['part_id' => $part->id, 'quantity' => 1]],
    );

    expect($jobOrder->parts->first()->part_name)->toBe('Screen Assembly')
        ->and($jobOrder->parts->first()->sku)->toBe('SAM-A54-SCR')
        ->and($jobOrder->services->first()->diagnosis)->toBe('Digitizer damaged');
});

// -- The bugs this phase fixes --------------------------------------------

test('a later catalogue price rise does not rewrite an agreed quote', function () {
    $part = linePart(['unit_sale_price' => 1000.00]);
    $jobOrder = lineJobOrder();

    app(JobOrderLines::class)->sync($jobOrder, [], [['part_id' => $part->id, 'quantity' => 1]]);

    $part->update(['unit_sale_price' => 9999.00]);

    expect($jobOrder->fresh()->partsTotal())->toBe(1000.00);
});

test('renaming a service no longer zeroes the labour on past job orders', function () {
    // The old behaviour joined issues[].type to services.name at render time,
    // so a rename made the lookup miss and the labour silently became 0.
    $service = lineService('Screen Repair', 500.00);
    $jobOrder = lineJobOrder();

    app(JobOrderLines::class)->sync($jobOrder, [['type' => 'Screen Repair']], []);

    $service->update(['name' => 'Display Replacement']);

    expect($jobOrder->fresh()->laborTotal())->toBe(500.00)
        ->and($jobOrder->fresh()->services->first()->service_name)->toBe('Screen Repair');
});

test('a deleted part leaves its line intact', function () {
    $part = linePart(['unit_sale_price' => 1000.00]);
    $jobOrder = lineJobOrder();

    app(JobOrderLines::class)->sync($jobOrder, [], [['part_id' => $part->id, 'quantity' => 2]]);

    $part->delete();

    $line = $jobOrder->fresh()->parts->first();

    expect($line)->not->toBeNull()
        ->and($line->part_id)->toBeNull()
        ->and($line->lineTotal())->toBe(2000.00);
});

// -- Editing ---------------------------------------------------------------

test('changing a quantity keeps the same line row', function () {
    $part = linePart();
    $jobOrder = lineJobOrder();
    $lines = app(JobOrderLines::class);

    $lines->sync($jobOrder, [], [['part_id' => $part->id, 'quantity' => 1]]);
    $originalId = $jobOrder->parts->first()->id;

    // A line holds stock state, so an edit must not delete and recreate it.
    $lines->sync($jobOrder, [], [['part_id' => $part->id, 'quantity' => 5]]);
    $line = $jobOrder->fresh()->parts->first();

    expect($line->id)->toBe($originalId)
        ->and($line->quantity)->toBe(5);
});

test('a part dropped from the form is removed from the job order', function () {
    $kept = linePart();
    $dropped = linePart();
    $jobOrder = lineJobOrder();
    $lines = app(JobOrderLines::class);

    $lines->sync($jobOrder, [], [
        ['part_id' => $kept->id, 'quantity' => 1],
        ['part_id' => $dropped->id, 'quantity' => 1],
    ]);

    expect($jobOrder->parts)->toHaveCount(2);

    $lines->sync($jobOrder, [], [['part_id' => $kept->id, 'quantity' => 1]]);

    expect($jobOrder->fresh()->parts->pluck('part_id')->all())->toBe([$kept->id]);
});

test('clearing every part empties the job order', function () {
    $part = linePart();
    $jobOrder = lineJobOrder();
    $lines = app(JobOrderLines::class);

    $lines->sync($jobOrder, [], [['part_id' => $part->id, 'quantity' => 1]]);
    $lines->sync($jobOrder, [], []);

    expect($jobOrder->fresh()->parts)->toHaveCount(0);
});

test('new lines start pending, holding no stock', function () {
    $part = linePart();
    $jobOrder = lineJobOrder();

    app(JobOrderLines::class)->sync($jobOrder, [], [['part_id' => $part->id, 'quantity' => 1]]);

    expect($jobOrder->parts->first()->status)->toBe(JobOrderPart::STATUS_PENDING)
        ->and($part->fresh()->in_stock)->toBe(10);
});

// -- The intake preview ----------------------------------------------------

test('the estimate preview matches what the saved lines come to', function () {
    $part = linePart(['unit_sale_price' => 250.00]);
    lineService('Screen Repair', 500.00);

    $services = [['type' => 'Screen Repair', 'diagnosis' => '']];
    $parts = [['part_id' => $part->id, 'quantity' => 3]];

    $preview = app(JobOrderLines::class)->previewTotal($services, $parts);

    $jobOrder = lineJobOrder();
    app(JobOrderLines::class)->sync($jobOrder, $services, $parts);

    expect($preview)->toBe(1250.00)
        ->and($jobOrder->lineTotal())->toBe($preview);
});

test('booking the same service twice is charged twice', function () {
    lineService('Diagnostics', 150.00);

    $preview = app(JobOrderLines::class)->previewTotal([
        ['type' => 'Diagnostics'],
        ['type' => 'Diagnostics'],
    ], []);

    expect($preview)->toBe(300.00);
});

test('an empty selection previews as zero', function () {
    expect(app(JobOrderLines::class)->previewTotal([], []))->toBe(0.0);
});

// -- The data migration ----------------------------------------------------

test('the backfill migration converts the old JSON columns into rows', function () {
    $part = linePart(['name' => 'Screen Assembly', 'unit_sale_price' => 2600.00, 'unit_cost_price' => 1400.00]);
    $service = lineService('Screen Repair', 500.00);
    $jobOrder = lineJobOrder();

    // Write the legacy shape straight to the columns — the model no longer
    // casts or fills them, which is the point.
    DB::table('job_orders')->where('id', $jobOrder->id)->update([
        'parts_needed' => json_encode([
            ['part_id' => $part->id, 'part_name' => 'Screen Assembly', 'quantity' => 2, 'unit_sale_price' => 2600.00],
        ]),
        'issues' => json_encode([
            ['type' => 'Screen Repair', 'diagnosis' => 'Digitizer damaged'],
        ]),
    ]);

    DB::table('job_order_parts')->where('job_order_id', $jobOrder->id)->delete();
    DB::table('job_order_services')->where('job_order_id', $jobOrder->id)->delete();

    $migration = require database_path('migrations/2026_09_24_000007_backfill_job_order_lines.php');
    $migration->up();

    $jobOrder->refresh();

    expect($jobOrder->parts)->toHaveCount(1)
        ->and($jobOrder->parts->first()->quantity)->toBe(2)
        ->and($jobOrder->parts->first()->unit_sale_price)->toBe('2600.00')
        // Cost was never in the JSON; it is read once from the catalogue.
        ->and($jobOrder->parts->first()->unit_cost_price)->toBe('1400.00')
        ->and($jobOrder->services->first()->service_id)->toBe($service->id)
        ->and($jobOrder->services->first()->labor_price)->toBe('500.00')
        ->and($jobOrder->lineTotal())->toBe(5700.00);
});

test('the backfill is idempotent', function () {
    $part = linePart();
    $jobOrder = lineJobOrder();

    DB::table('job_orders')->where('id', $jobOrder->id)->update([
        'parts_needed' => json_encode([['part_id' => $part->id, 'quantity' => 1]]),
    ]);
    DB::table('job_order_parts')->where('job_order_id', $jobOrder->id)->delete();

    $migration = require database_path('migrations/2026_09_24_000007_backfill_job_order_lines.php');
    $migration->up();
    $migration->up();

    expect($jobOrder->fresh()->parts)->toHaveCount(1);
});

test('the backfill leaves a job order with no lines alone', function () {
    $jobOrder = lineJobOrder();

    $migration = require database_path('migrations/2026_09_24_000007_backfill_job_order_lines.php');
    $migration->up();

    expect($jobOrder->fresh()->parts)->toHaveCount(0)
        ->and($jobOrder->fresh()->services)->toHaveCount(0);
});
