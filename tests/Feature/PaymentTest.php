<?php

use App\Enums\JobOrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Exceptions\UnpaidBalance;
use App\Models\JobOrder;
use App\Models\JobOrderEvent;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\JobOrders\JobOrderLines;
use App\Services\JobOrders\JobOrderWorkflow;
use App\Services\Payments\PaymentService;
use Livewire\Volt\Volt;

function payUser(Role $role = Role::COUNTER_STAFF): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

function payJobOrder(float $estimate = 1000.00, JobOrderStatus $status = JobOrderStatus::COMPLETED): JobOrder
{
    return JobOrder::create([
        'customer_name' => 'Juan Dela Cruz',
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'issue_description' => 'Cracked screen',
        'estimated_cost' => $estimate,
        'status' => $status,
        'received_by' => payUser()->id,
    ]);
}

function pay(JobOrder $jobOrder, float $amount, PaymentMethod $method = PaymentMethod::CASH): Payment
{
    return app(PaymentService::class)->take($jobOrder, $amount, $method);
}

// -- Taking payment --------------------------------------------------------

test('a payment is recorded with a receipt number', function () {
    $this->actingAs($staff = payUser());
    $jobOrder = payJobOrder(1000);

    $payment = pay($jobOrder, 1000);

    expect($payment->receipt_number)->toStartWith('OR-')
        ->and($payment->received_by)->toBe($staff->id)
        ->and($jobOrder->fresh()->amountPaid())->toBe(1000.00)
        ->and($jobOrder->fresh()->balance())->toBe(0.0);
});

test('receipt numbers run in sequence within a day', function () {
    $this->actingAs(payUser());
    $jobOrder = payJobOrder(3000);

    $first = pay($jobOrder, 1000);
    $second = pay($jobOrder, 1000);

    expect($second->receipt_number)->toBe(
        substr($first->receipt_number, 0, -4).str_pad((string) ((int) substr($first->receipt_number, -4) + 1), 4, '0', STR_PAD_LEFT)
    );
});

test('a deposit leaves the repair partially paid', function () {
    $this->actingAs(payUser());
    $jobOrder = payJobOrder(1000);

    pay($jobOrder, 400);
    $jobOrder->refresh();

    expect($jobOrder->amountPaid())->toBe(400.00)
        ->and($jobOrder->balance())->toBe(600.00)
        ->and($jobOrder->paymentStatus())->toBe(PaymentStatus::PARTIAL);
});

test('a deposit plus the balance settles the repair', function () {
    $this->actingAs(payUser());
    $jobOrder = payJobOrder(1000);

    pay($jobOrder, 400);
    pay($jobOrder, 600, PaymentMethod::GCASH);
    $jobOrder->refresh();

    expect($jobOrder->balance())->toBe(0.0)
        ->and($jobOrder->paymentStatus())->toBe(PaymentStatus::PAID)
        ->and($jobOrder->isFullyPaid())->toBeTrue();
});

test('an unpaid repair reports as unpaid, not partial', function () {
    expect(payJobOrder(1000)->paymentStatus())->toBe(PaymentStatus::UNPAID);
});

test('taking more than is owed is flagged rather than hidden', function () {
    $this->actingAs(payUser());
    $jobOrder = payJobOrder(1000);

    pay($jobOrder, 1200);
    $jobOrder->refresh();

    expect($jobOrder->paymentStatus())->toBe(PaymentStatus::OVERPAID)
        // An overpayment is money owed back, not a negative debt.
        ->and($jobOrder->balance())->toBe(0.0);
});

test('a payment of zero is refused', function () {
    $this->actingAs(payUser());

    expect(fn () => pay(payJobOrder(1000), 0))
        ->toThrow(RuntimeException::class, 'more than zero');
});

test('taking a payment records it in the repair history for the customer', function () {
    $this->actingAs(payUser());
    $jobOrder = payJobOrder(1000);

    pay($jobOrder, 400);

    $event = JobOrderEvent::where('job_order_id', $jobOrder->id)
        ->where('type', JobOrderEvent::TYPE_PAYMENT_RECEIVED)
        ->firstOrFail();

    expect($event->is_customer_visible)->toBeTrue()
        ->and($event->description)->toContain('400.00')
        ->and($event->description)->toContain('Balance due');
});

// -- Voiding ---------------------------------------------------------------

test('a voided payment stops counting but is not deleted', function () {
    $this->actingAs(payUser());
    $jobOrder = payJobOrder(1000);

    $payment = pay($jobOrder, 1000);
    expect($jobOrder->fresh()->balance())->toBe(0.0);

    app(PaymentService::class)->void($payment, 'Taken against the wrong job order');

    expect($jobOrder->fresh()->balance())->toBe(1000.00)
        ->and($jobOrder->fresh()->amountPaid())->toBe(0.0)
        // The customer holds a receipt with this number on it.
        ->and(Payment::find($payment->id))->not->toBeNull()
        ->and(Payment::find($payment->id)->isVoided())->toBeTrue();
});

test('voiding twice is harmless', function () {
    $this->actingAs(payUser());
    $payment = pay(payJobOrder(1000), 1000);
    $service = app(PaymentService::class);

    $service->void($payment, 'first');
    $voidedAt = $payment->fresh()->voided_at;
    $service->void($payment->fresh(), 'second');

    expect($payment->fresh()->voided_at->toString())->toBe($voidedAt->toString());
});

// -- final_cost, which was dead until now ----------------------------------

test('finishing the work freezes the invoice total from the lines', function () {
    $this->actingAs(payUser());

    $part = Part::create([
        'name' => 'Screen', 'sku' => 'SCR-1', 'in_stock' => 5, 'reorder_point' => 1,
        'unit_cost_price' => 400, 'unit_sale_price' => 800, 'is_active' => true,
    ]);
    Service::create(['name' => 'Screen Repair', 'category' => 'General', 'labor_price' => 500, 'is_active' => true]);

    $jobOrder = payJobOrder(0, JobOrderStatus::ASSIGNED);
    app(JobOrderLines::class)->sync(
        $jobOrder,
        [['type' => 'Screen Repair']],
        [['part_id' => $part->id, 'quantity' => 1]],
    );

    $workflow = app(JobOrderWorkflow::class);
    $workflow->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    $workflow->transitionTo($jobOrder, JobOrderStatus::IN_PROGRESS);
    $workflow->transitionTo($jobOrder, JobOrderStatus::DONE);

    expect((float) $jobOrder->fresh()->final_cost)->toBe(1300.00);
});

test('a frozen invoice total survives a later catalogue price rise', function () {
    $this->actingAs(payUser());

    $part = Part::create([
        'name' => 'Screen', 'sku' => 'SCR-2', 'in_stock' => 5, 'reorder_point' => 1,
        'unit_cost_price' => 400, 'unit_sale_price' => 800, 'is_active' => true,
    ]);

    $jobOrder = payJobOrder(0, JobOrderStatus::ASSIGNED);
    app(JobOrderLines::class)->sync($jobOrder, [], [['part_id' => $part->id, 'quantity' => 1]]);

    $workflow = app(JobOrderWorkflow::class);
    $workflow->transitionTo($jobOrder, JobOrderStatus::APPROVED);
    $workflow->transitionTo($jobOrder, JobOrderStatus::IN_PROGRESS);
    $workflow->transitionTo($jobOrder, JobOrderStatus::DONE);

    $part->update(['unit_sale_price' => 5000]);

    expect((float) $jobOrder->fresh()->final_cost)->toBe(800.00);
});

test('reopening and re-finishing keeps the total the customer was invoiced', function () {
    $this->actingAs(payUser());

    $jobOrder = payJobOrder(0, JobOrderStatus::IN_PROGRESS);
    $workflow = app(JobOrderWorkflow::class);

    $workflow->transitionTo($jobOrder, JobOrderStatus::DONE);
    $jobOrder->forceFill(['final_cost' => 1500])->save();

    $workflow->transitionTo($jobOrder->fresh(), JobOrderStatus::IN_PROGRESS);
    $workflow->transitionTo($jobOrder->fresh(), JobOrderStatus::DONE);

    expect((float) $jobOrder->fresh()->final_cost)->toBe(1500.00);
});

// -- Not letting the device leave unpaid -----------------------------------

test('a device with a balance cannot be delivered by counter staff', function () {
    $this->actingAs(payUser(Role::COUNTER_STAFF));
    $jobOrder = payJobOrder(1000, JobOrderStatus::COMPLETED);

    expect(fn () => app(JobOrderWorkflow::class)->transitionTo($jobOrder, JobOrderStatus::DELIVERED))
        ->toThrow(UnpaidBalance::class);

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::COMPLETED);
});

test('a paid device is delivered normally', function () {
    $this->actingAs(payUser());
    $jobOrder = payJobOrder(1000, JobOrderStatus::COMPLETED);

    pay($jobOrder, 1000);
    app(JobOrderWorkflow::class)->transitionTo($jobOrder->fresh(), JobOrderStatus::DELIVERED);

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::DELIVERED);
});

test('an administrator can release an unpaid device deliberately', function () {
    $this->actingAs(payUser(Role::ADMINISTRATOR));
    $jobOrder = payJobOrder(1000, JobOrderStatus::COMPLETED);

    app(JobOrderWorkflow::class)->transitionTo(
        $jobOrder,
        JobOrderStatus::DELIVERED,
        allowUnpaidDelivery: true,
    );

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::DELIVERED)
        // The debt does not disappear because the device did.
        ->and($jobOrder->fresh()->balance())->toBe(1000.00);
});

// -- The screen ------------------------------------------------------------

test('counter staff can take a payment from the job orders screen', function () {
    $this->actingAs(payUser(Role::COUNTER_STAFF));
    $jobOrder = payJobOrder(1000);

    Volt::test('job-orders.index')
        ->call('openPaymentModal', $jobOrder->id)
        ->assertSet('paymentAmount', 1000.00)
        ->set('paymentAmount', 600)
        ->set('paymentMethod', 'gcash')
        ->set('paymentReference', 'GC-12345')
        ->call('takePayment')
        ->assertDispatched('success');

    $payment = $jobOrder->fresh()->payments()->firstOrFail();

    expect((float) $payment->amount)->toBe(600.00)
        ->and($payment->method)->toBe(PaymentMethod::GCASH)
        ->and($payment->reference_no)->toBe('GC-12345')
        ->and($jobOrder->fresh()->balance())->toBe(400.00);
});

test('the payment form rejects a zero amount', function () {
    $this->actingAs(payUser());
    $jobOrder = payJobOrder(1000);

    Volt::test('job-orders.index')
        ->call('openPaymentModal', $jobOrder->id)
        ->set('paymentAmount', 0)
        ->call('takePayment')
        ->assertHasErrors(['paymentAmount']);

    expect($jobOrder->fresh()->payments()->count())->toBe(0);
});

test('delivering an unpaid device from the screen is refused with a reason', function () {
    $this->actingAs(payUser(Role::COUNTER_STAFF));
    $jobOrder = payJobOrder(1000, JobOrderStatus::COMPLETED);

    Volt::test('job-orders.index')
        ->call('markDelivered', $jobOrder->id)
        ->assertDispatched('error');

    expect($jobOrder->fresh()->status)->toBe(JobOrderStatus::COMPLETED);
});

// -- The dashboard figure that was wrong ------------------------------------

test('dashboard revenue counts collected cash, not estimates', function () {
    $this->actingAs($admin = payUser(Role::ADMINISTRATOR));

    $paid = payJobOrder(1000);
    pay($paid, 1000);

    // Finished but never paid: it must not appear as revenue.
    payJobOrder(5000);

    $stats = Volt::test('admin.dashboard')->viewData('stats');

    expect((float) $stats['total_revenue'])->toBe(1000.00)
        ->and($stats['outstanding_balance'])->toBe(5000.00);
});

test('a voided payment is not counted as revenue', function () {
    $this->actingAs(payUser(Role::ADMINISTRATOR));

    $jobOrder = payJobOrder(1000);
    $payment = pay($jobOrder, 1000);
    app(PaymentService::class)->void($payment, 'Mistake');

    $stats = Volt::test('admin.dashboard')->viewData('stats');

    expect((float) $stats['total_revenue'])->toBe(0.0);
});
