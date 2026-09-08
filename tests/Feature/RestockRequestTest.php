<?php

use App\Enums\Role;
use App\Mail\RestockRequestMail;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

beforeEach(function () {
    Mail::fake();

    $this->admin = User::factory()->create([
        'role' => Role::ADMINISTRATOR,
        'email_verified_at' => now(),
    ]);

    $this->supplier = Supplier::create([
        'name' => 'ACME Parts',
        'contact_person' => 'Ana Reyes',
        'email' => 'orders@acme-parts.test',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

function makePart(array $attributes = []): Part
{
    static $sequence = 0;
    $sequence++;

    return Part::create(array_merge([
        'name' => 'Part ' . $sequence,
        'sku' => 'SKU-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
        'category' => 'Display & Input Components',
        'in_stock' => 1,
        'reorder_point' => 5,
        'unit_cost_price' => 100,
        'unit_sale_price' => 150,
        'supplier' => 'ACME Parts',
        'is_active' => true,
    ], $attributes));
}

test('the suggested restock quantity never exceeds the cap', function () {
    // Formula would give (50 * 2) - 0 = 100
    $part = makePart(['reorder_point' => 50, 'in_stock' => 0]);

    expect($part->suggestedRestockQuantity())->toBe(Part::MAX_RESTOCK_QUANTITY);

    // A modest shortfall is left untouched
    expect(makePart(['reorder_point' => 5, 'in_stock' => 2])->suggestedRestockQuantity())->toBe(8);
});

test('the low stock scope matches the isLowStock check', function () {
    $low = makePart(['in_stock' => 2, 'reorder_point' => 5]);
    $atThreshold = makePart(['in_stock' => 5, 'reorder_point' => 5]);
    $healthy = makePart(['in_stock' => 9, 'reorder_point' => 5]);

    $ids = Part::lowStock()->pluck('id');

    expect($ids)->toContain($low->id)
        ->and($ids)->toContain($atThreshold->id)
        ->and($ids)->not->toContain($healthy->id)
        ->and($healthy->isLowStock())->toBeFalse();
});

test('a quantity above the cap is rejected and no mail is sent', function () {
    $part = makePart();

    Volt::test('admin.parts-inventory')
        ->call('openRestockModal', $part->id)
        ->set("restockItems.{$part->id}.quantity", Part::MAX_RESTOCK_QUANTITY + 1)
        ->call('confirmRestock')
        ->assertHasErrors("restockItems.{$part->id}.quantity");

    Mail::assertNothingSent();
});

test('a quantity exactly at the cap is accepted', function () {
    $part = makePart();

    Volt::test('admin.parts-inventory')
        ->call('openRestockModal', $part->id)
        ->set("restockItems.{$part->id}.quantity", Part::MAX_RESTOCK_QUANTITY)
        ->call('confirmRestock')
        ->assertHasNoErrors();

    Mail::assertSent(RestockRequestMail::class, fn ($mail) => $mail->items->count() === 1
        && $mail->items->first()['quantity'] === Part::MAX_RESTOCK_QUANTITY);
});

test('a quantity below one is rejected', function () {
    $part = makePart();

    Volt::test('admin.parts-inventory')
        ->call('openRestockModal', $part->id)
        ->set("restockItems.{$part->id}.quantity", 0)
        ->call('confirmRestock')
        ->assertHasErrors("restockItems.{$part->id}.quantity");

    Mail::assertNothingSent();
});

test('several parts are sent in a single email with their own quantities', function () {
    $first = makePart(['name' => 'Battery A54', 'in_stock' => 1, 'reorder_point' => 5]);
    $second = makePart(['name' => 'Charging Port', 'in_stock' => 0, 'reorder_point' => 4]);

    Volt::test('admin.parts-inventory')
        ->call('openRestockModal', $first->id)
        ->set("restockItems.{$second->id}.selected", true)
        ->set("restockItems.{$first->id}.quantity", 7)
        ->set("restockItems.{$second->id}.quantity", 12)
        ->call('confirmRestock')
        ->assertHasNoErrors();

    Mail::assertSentCount(1);
    Mail::assertSent(RestockRequestMail::class, function ($mail) use ($first, $second) {
        $quantities = $mail->items->mapWithKeys(fn ($item) => [$item['part']->id => $item['quantity']]);

        return $mail->items->count() === 2
            && $quantities[$first->id] === 7
            && $quantities[$second->id] === 12;
    });
});

test('only parts needing restock from the same supplier are offered', function () {
    $low = makePart(['in_stock' => 1, 'reorder_point' => 5]);
    $healthy = makePart(['in_stock' => 20, 'reorder_point' => 5]);
    $otherSupplier = makePart(['in_stock' => 0, 'reorder_point' => 5, 'supplier' => 'Other Supply Co']);

    $component = Volt::test('admin.parts-inventory')->call('openRestockModal', $low->id);

    $offered = array_keys($component->get('restockItems'));

    expect($offered)->toContain($low->id)
        ->and($offered)->not->toContain($healthy->id)
        ->and($offered)->not->toContain($otherSupplier->id);
});

test('a part that does not need restocking cannot open the modal', function () {
    $healthy = makePart(['in_stock' => 20, 'reorder_point' => 5]);

    Volt::test('admin.parts-inventory')
        ->call('openRestockModal', $healthy->id)
        ->assertSet('showRestockModal', false);

    Mail::assertNothingSent();
});

test('a healthy part injected into the request is not restocked', function () {
    $low = makePart(['in_stock' => 1, 'reorder_point' => 5]);
    $healthy = makePart(['in_stock' => 20, 'reorder_point' => 5]);

    // Forge a selection for a part the UI never offered
    Volt::test('admin.parts-inventory')
        ->call('openRestockModal', $low->id)
        ->set("restockItems.{$healthy->id}", ['selected' => true, 'quantity' => 5])
        ->call('confirmRestock')
        ->assertHasNoErrors();

    Mail::assertSent(RestockRequestMail::class, function ($mail) use ($low, $healthy) {
        $ids = $mail->items->pluck('part.id');

        return $ids->contains($low->id) && ! $ids->contains($healthy->id);
    });
});

test('sending with nothing selected errors and sends no mail', function () {
    $part = makePart();

    Volt::test('admin.parts-inventory')
        ->call('openRestockModal', $part->id)
        ->set("restockItems.{$part->id}.selected", false)
        ->call('confirmRestock')
        ->assertHasErrors('restockItems');

    Mail::assertNothingSent();
});

test('the test:restock-email command still sends with the new mailable shape', function () {
    $part = makePart(['reorder_point' => 50, 'in_stock' => 0]);

    $this->artisan('test:restock-email', ['partId' => $part->id])
        ->assertExitCode(0);

    Mail::assertSent(RestockRequestMail::class, fn ($mail) => $mail->items->count() === 1
        // capped, not the raw (50 * 2) - 0 = 100
        && $mail->items->first()['quantity'] === Part::MAX_RESTOCK_QUANTITY);
});

test('a part with no supplier cannot be restocked', function () {
    $orphan = makePart(['supplier' => null]);

    Volt::test('admin.parts-inventory')
        ->call('openRestockModal', $orphan->id)
        ->assertSet('showRestockModal', false);

    Mail::assertNothingSent();
});

test('a low stock part with no supplier explains itself in the row', function () {
    makePart(['name' => 'Orphan Part', 'supplier' => null, 'in_stock' => 0, 'reorder_point' => 5]);

    Volt::test('admin.parts-inventory')
        ->assertSee('No supplier yet')
        ->assertSee('Open Suppliers panel')
        ->assertDontSee('Request Restock');
});

test('a supplier missing from the panel is named in the row and on click', function () {
    $part = makePart(['supplier' => 'Ghost Supply Co', 'in_stock' => 0, 'reorder_point' => 5]);

    Volt::test('admin.parts-inventory')
        ->assertSee('Supplier not in panel')
        ->assertDontSee('Request Restock')
        ->call('openRestockModal', $part->id)
        ->assertSet('showRestockModal', false)
        ->assertDispatched('error', function (string $event, array $params) {
            return str_contains($params['message'], 'Ghost Supply Co')
                && str_contains($params['message'], 'Suppliers panel');
        });

    Mail::assertNothingSent();
});

test('a supplier with no email address is called out in the row', function () {
    Supplier::create(['name' => 'Silent Supply', 'is_active' => true]);
    $part = makePart(['supplier' => 'Silent Supply', 'in_stock' => 0, 'reorder_point' => 5]);

    Volt::test('admin.parts-inventory')
        ->assertSee('Supplier has no email')
        ->call('openRestockModal', $part->id)
        ->assertSet('showRestockModal', false)
        ->assertDispatched('error', fn (string $event, array $params) => str_contains($params['message'], 'no email address'));

    Mail::assertNothingSent();
});

test('a restockable part still shows the request button', function () {
    makePart(['in_stock' => 0, 'reorder_point' => 5]);

    Volt::test('admin.parts-inventory')
        ->assertSee('Request Restock')
        ->assertDontSee('No supplier yet')
        ->assertDontSee('Supplier not in panel');
});
