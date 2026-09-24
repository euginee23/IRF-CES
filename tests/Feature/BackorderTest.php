<?php

use App\Enums\JobOrderStatus;
use App\Enums\Role;
use App\Models\JobOrder;
use App\Models\JobOrderEvent;
use App\Models\JobOrderPart;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\JobOrders\JobOrderLines;
use App\Services\JobOrders\JobOrderWorkflow;

function stockUser(Role $role = Role::COUNTER_STAFF): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

function stockPart(int $inStock, array $attributes = []): Part
{
    static $n = 0;
    $n++;

    return Part::create(array_merge([
        'name' => "Part {$n}",
        'sku' => "SKU-{$n}",
        'in_stock' => $inStock,
        'reorder_point' => 1,
        'unit_cost_price' => 100.00,
        'unit_sale_price' => 250.00,
        'is_active' => true,
    ], $attributes));
}

function stockJobOrder(Part $part, int $quantity, JobOrderStatus $status = JobOrderStatus::ASSIGNED): JobOrder
{
    $jobOrder = JobOrder::create([
        'customer_name' => 'Juan Dela Cruz',
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'issue_description' => 'Cracked screen',
        'status' => $status,
        'received_by' => stockUser()->id,
    ]);

    app(JobOrderLines::class)->sync($jobOrder, [], [['part_id' => $part->id, 'quantity' => $quantity]]);

    return $jobOrder;
}

/** The invariant the ledger exists to guarantee. */
function assertLedgerBalances(Part $part): void
{
    $part->refresh();

    expect((int) StockMovement::where('part_id', $part->id)->sum('quantity'))
        ->toBe($part->in_stock, "the ledger does not add up to {$part->name}'s stock");
}

// -- Reserving -------------------------------------------------------------

test('approving a repair reserves its parts without taking them off the shelf', function () {
    $part = stockPart(5);
    $jobOrder = stockJobOrder($part, 2);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    $part->refresh();

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::APPROVED)
        ->and($part->in_stock)->toBe(5)
        ->and($part->reserved_stock)->toBe(2)
        ->and($part->availableStock())->toBe(3)
        ->and($jobOrder->fresh()->parts->first()->status)->toBe(JobOrderPart::STATUS_RESERVED);

    assertLedgerBalances($part);
});

test('a reservation is not a stock movement', function () {
    $part = stockPart(5);
    $jobOrder = stockJobOrder($part, 2);

    $before = StockMovement::where('part_id', $part->id)->count();

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    // Nothing physical happened, so nothing is in the ledger — which is what
    // keeps SUM(quantity) == in_stock true.
    expect(StockMovement::where('part_id', $part->id)->count())->toBe($before);
});

test('two repairs cannot reserve the same last part', function () {
    $part = stockPart(1);
    $first = stockJobOrder($part, 1);
    $second = stockJobOrder($part, 1);

    $workflow = app(JobOrderWorkflow::class);
    $workflow->transitionTo($first, JobOrderStatus::APPROVED);
    $workflow->transitionTo($second, JobOrderStatus::APPROVED);

    expect($first->fresh()->status)->toBe(JobOrderStatus::APPROVED)
        ->and($second->fresh()->status)->toBe(JobOrderStatus::AWAITING_PARTS)
        ->and($part->fresh()->reserved_stock)->toBe(1);
});

// -- Backordering: the feature ---------------------------------------------

test('a repair can be approved for a part the shop does not have', function () {
    // This is the client request: book the phone in, order the part later.
    $part = stockPart(0);
    $jobOrder = stockJobOrder($part, 1);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::AWAITING_PARTS)
        ->and($jobOrder->fresh()->parts->first()->status)->toBe(JobOrderPart::STATUS_BACKORDERED)
        ->and($part->fresh()->reserved_stock)->toBe(0);
});

test('the customer is told their repair is waiting on a part', function () {
    $part = stockPart(0, ['name' => 'Screen Assembly']);
    $jobOrder = stockJobOrder($part, 1);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    $event = JobOrderEvent::where('job_order_id', $jobOrder->id)
        ->where('type', JobOrderEvent::TYPE_PART_BACKORDERED)
        ->firstOrFail();

    expect($event->is_customer_visible)->toBeTrue()
        ->and($event->description)->toContain('Screen Assembly');
});

test('a partly-available repair reserves what it can and backorders the rest', function () {
    $available = stockPart(5);
    $missing = stockPart(0);

    $jobOrder = stockJobOrder($available, 1);
    app(JobOrderLines::class)->sync($jobOrder, [], [
        ['part_id' => $available->id, 'quantity' => 1],
        ['part_id' => $missing->id, 'quantity' => 1],
    ]);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::AWAITING_PARTS)
        ->and($available->fresh()->reserved_stock)->toBe(1)
        ->and($jobOrder->fresh()->parts()->backordered()->count())->toBe(1);
});

// -- Receiving stock -------------------------------------------------------

test('receiving stock clears a backorder and frees the repair', function () {
    $part = stockPart(0);
    $jobOrder = stockJobOrder($part, 1);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::AWAITING_PARTS);

    $released = app(InventoryService::class)->receive($part, 3, note: 'Delivery');

    expect($released)->toHaveCount(1)
        ->and($jobOrder->fresh()->parts->first()->status)->toBe(JobOrderPart::STATUS_RESERVED)
        ->and($part->fresh()->reserved_stock)->toBe(1);

    app(JobOrderWorkflow::class)->partsArrived($jobOrder->fresh());

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::APPROVED);

    assertLedgerBalances($part->fresh());
});

test('backorders are cleared oldest first', function () {
    $part = stockPart(0);

    $first = stockJobOrder($part, 1);
    $second = stockJobOrder($part, 1);

    $workflow = app(JobOrderWorkflow::class);
    $workflow->transitionTo($first, JobOrderStatus::APPROVED);
    $workflow->transitionTo($second, JobOrderStatus::APPROVED);

    // Only enough for one: the one that has waited longest gets it.
    app(InventoryService::class)->receive($part, 1);

    expect($first->fresh()->parts->first()->status)->toBe(JobOrderPart::STATUS_RESERVED)
        ->and($second->fresh()->parts->first()->status)->toBe(JobOrderPart::STATUS_BACKORDERED);
});

test('a delivery too small for the waiting order leaves it waiting', function () {
    $part = stockPart(0);
    $jobOrder = stockJobOrder($part, 5);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    $released = app(InventoryService::class)->receive($part, 2);

    expect($released)->toBeEmpty()
        ->and($jobOrder->fresh()->parts->first()->status)->toBe(JobOrderPart::STATUS_BACKORDERED);
});

test('a receipt is recorded in the ledger', function () {
    $part = stockPart(2);

    app(InventoryService::class)->receive($part, 8, note: 'Delivery from supplier');

    expect($part->fresh()->in_stock)->toBe(10);
    assertLedgerBalances($part->fresh());
});

// -- Consuming -------------------------------------------------------------

test('finishing the work takes the parts off the shelf', function () {
    $part = stockPart(5);
    $jobOrder = stockJobOrder($part, 2);
    $workflow = app(JobOrderWorkflow::class);

    $workflow->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    $workflow->transitionTo($jobOrder, JobOrderStatus::IN_PROGRESS);
    $workflow->transitionTo($jobOrder, JobOrderStatus::DONE);

    $part->refresh();

    expect($part->in_stock)->toBe(3)
        ->and($part->reserved_stock)->toBe(0)
        ->and($jobOrder->fresh()->parts->first()->status)->toBe(JobOrderPart::STATUS_CONSUMED);

    assertLedgerBalances($part);
});

test('consumption records the cost agreed at intake, not today\'s', function () {
    $part = stockPart(5, ['unit_cost_price' => 100.00]);
    $jobOrder = stockJobOrder($part, 1);
    $workflow = app(JobOrderWorkflow::class);

    $workflow->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    // The catalogue moves after the quote was agreed.
    $part->update(['unit_cost_price' => 999.00]);

    $workflow->transitionTo($jobOrder, JobOrderStatus::IN_PROGRESS);
    $workflow->transitionTo($jobOrder, JobOrderStatus::DONE);

    $movement = StockMovement::where('part_id', $part->id)
        ->where('type', \App\Enums\StockMovementType::CONSUME)
        ->firstOrFail();

    expect($movement->unit_cost_price)->toBe('100.00');
});

// -- Releasing -------------------------------------------------------------

test('cancelling a repair gives back what it was holding', function () {
    $part = stockPart(5);
    $jobOrder = stockJobOrder($part, 2);
    $workflow = app(JobOrderWorkflow::class);

    $workflow->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    expect($part->fresh()->reserved_stock)->toBe(2);

    $workflow->transitionTo($jobOrder, JobOrderStatus::CANCELLED);

    $part->refresh();

    expect($part->reserved_stock)->toBe(0)
        ->and($part->in_stock)->toBe(5);

    assertLedgerBalances($part);
});

test('cancelling after the parts were fitted puts them back on the shelf', function () {
    $part = stockPart(5);
    $jobOrder = stockJobOrder($part, 2);
    $workflow = app(JobOrderWorkflow::class);

    $workflow->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    $workflow->transitionTo($jobOrder, JobOrderStatus::IN_PROGRESS);
    $workflow->transitionTo($jobOrder, JobOrderStatus::DONE);
    expect($part->fresh()->in_stock)->toBe(3);

    $workflow->transitionTo($jobOrder, JobOrderStatus::CANCELLED);

    expect($part->fresh()->in_stock)->toBe(5);
    assertLedgerBalances($part->fresh());
});

// -- The purchasing list ---------------------------------------------------

test('the shortfall list totals what is owed per part', function () {
    $part = stockPart(0, ['name' => 'Screen Assembly']);

    $first = stockJobOrder($part, 2);
    $second = stockJobOrder($part, 3);

    $workflow = app(JobOrderWorkflow::class);
    $workflow->transitionTo($first, JobOrderStatus::APPROVED);
    $workflow->transitionTo($second, JobOrderStatus::APPROVED);

    $shortfall = app(InventoryService::class)->shortfalls()->firstOrFail();

    expect((int) $shortfall->needed)->toBe(5)
        ->and((int) $shortfall->job_order_count)->toBe(2)
        ->and($shortfall->part->name)->toBe('Screen Assembly');
});

test('nothing is owed when every repair has its parts', function () {
    $part = stockPart(10);
    $jobOrder = stockJobOrder($part, 1);

    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);

    expect(app(InventoryService::class)->shortfalls())->toBeEmpty();
});

// -- Manual adjustment -----------------------------------------------------

test('a manual adjustment is recorded and cannot drive the shelf negative', function () {
    $part = stockPart(1);
    $inventory = app(InventoryService::class);

    $inventory->adjust($part, -1, note: 'Stocktake');
    expect($part->fresh()->in_stock)->toBe(0);

    $inventory->adjust($part, -1, note: 'Stocktake');
    expect($part->fresh()->in_stock)->toBe(0);

    assertLedgerBalances($part->fresh());
});

// -- The inventory screen must not bypass the ledger ------------------------

test('editing a part\'s stock on the inventory screen goes through the ledger', function () {
    $admin = stockUser(Role::ADMINISTRATOR);
    $part = stockPart(5, ['manufacturer' => 'Samsung', 'model' => 'A54']);
    $category = \App\Models\PartCategory::firstOrCreate(['name' => 'Display']);
    $part->update(['part_category_id' => $category->id]);

    \Livewire\Volt\Volt::actingAs($admin)
        ->test('admin.parts-inventory')
        ->call('openEditModal', $part->id)
        ->set('in_stock', 12)
        ->call('save')
        ->assertDispatched('success');

    expect($part->fresh()->in_stock)->toBe(12);

    // Writing in_stock straight to the column would leave the ledger behind
    // for good, and nothing else in the app would notice.
    assertLedgerBalances($part->fresh());
});

test('correcting stock upwards can clear a backorder', function () {
    $admin = stockUser(Role::ADMINISTRATOR);
    $part = stockPart(0, ['manufacturer' => 'Samsung', 'model' => 'A54']);
    $category = \App\Models\PartCategory::firstOrCreate(['name' => 'Display']);
    $part->update(['part_category_id' => $category->id]);

    $jobOrder = stockJobOrder($part, 1);
    app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::AWAITING_PARTS);

    \Livewire\Volt\Volt::actingAs($admin)
        ->test('admin.parts-inventory')
        ->call('openEditModal', $part->id)
        ->set('in_stock', 4)
        ->call('save');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::APPROVED);
    assertLedgerBalances($part->fresh());
});
