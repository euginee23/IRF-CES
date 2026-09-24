<?php

use App\Enums\JobOrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\JobOrder;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use App\Services\JobOrders\JobOrderLines;
use App\Services\Payments\PaymentService;
use App\Services\Reports\IncomeReport;
use Carbon\CarbonImmutable;
use Livewire\Volt\Volt;

function reportUser(Role $role = Role::ADMINISTRATOR): User
{
    return User::factory()->create([
        'role' => $role,
        'email_verified_at' => now(),
    ]);
}

/** A finished repair with known parts and labour, completed on a given date. */
function finishedJobOrder(
    CarbonImmutable $completedAt,
    float $partSale = 800,
    float $partCost = 400,
    float $labor = 500,
    ?User $technician = null,
): JobOrder {
    static $n = 0;
    $n++;

    $part = Part::create([
        'name' => "Part {$n}", 'sku' => "RPT-{$n}", 'in_stock' => 10, 'reorder_point' => 1,
        'unit_cost_price' => $partCost, 'unit_sale_price' => $partSale, 'is_active' => true,
    ]);

    $service = Service::firstOrCreate(
        ['name' => 'Screen Repair'],
        ['category' => 'General', 'labor_price' => $labor, 'is_active' => true],
    );

    $jobOrder = JobOrder::create([
        'customer_name' => "Customer {$n}",
        'customer_phone' => '09171234567',
        'device_brand' => 'Samsung',
        'device_model' => 'Galaxy A54',
        'issue_description' => 'Cracked screen',
        'status' => JobOrderStatus::COMPLETED,
        'received_by' => reportUser(Role::COUNTER_STAFF)->id,
        'assigned_to' => $technician?->id,
        'completed_at' => $completedAt,
    ]);

    app(JobOrderLines::class)->sync(
        $jobOrder,
        [['type' => $service->name]],
        [['part_id' => $part->id, 'quantity' => 1]],
    );

    $jobOrder->forceFill(['final_cost' => $jobOrder->lineTotal()])->save();

    return $jobOrder->fresh();
}

function reportFor(CarbonImmutable $from, CarbonImmutable $to): IncomeReport
{
    return new IncomeReport($from->startOfDay(), $to->endOfDay());
}

beforeEach(function () {
    $this->actingAs(reportUser());
    $this->today = CarbonImmutable::now();
});

// -- Collected vs billed ---------------------------------------------------

test('collected counts cash that arrived, billed counts work finished', function () {
    $jobOrder = finishedJobOrder($this->today, partSale: 800, labor: 500);

    // Only a deposit so far.
    app(PaymentService::class)->take($jobOrder, 500, PaymentMethod::CASH);

    $report = reportFor($this->today, $this->today);

    expect($report->billed())->toBe(1300.00)
        ->and($report->collected())->toBe(500.00)
        // The gap is the point: work done that is not yet paid for.
        ->and($report->outstanding())->toBe(800.00);
});

test('collected is keyed on when the cash arrived, not when the repair finished', function () {
    $jobOrder = finishedJobOrder($this->today->subMonths(2));

    // Paid on collection, long after the work was done — a customer settling
    // an old balance is this month's income, not that month's.
    $payment = app(PaymentService::class)->take($jobOrder, 1000, PaymentMethod::CASH);

    expect(reportFor($this->today, $this->today)->collected())->toBe(1000.00)
        ->and(reportFor($this->today->subMonths(2), $this->today->subMonths(2))->collected())->toBe(0.0);

    // And a payment genuinely taken back then belongs to back then.
    $payment->forceFill(['paid_at' => $this->today->subMonths(2)])->save();

    expect(reportFor($this->today, $this->today)->collected())->toBe(0.0)
        ->and(reportFor($this->today->subMonths(2), $this->today->subMonths(2))->collected())->toBe(1000.00);
});

test('a repair is counted in the period it was finished, not booked', function () {
    // Booked in December, finished in January: January's earnings.
    $jobOrder = finishedJobOrder($this->today);
    $jobOrder->forceFill(['created_at' => $this->today->subMonths(2)])->save();

    expect(reportFor($this->today, $this->today)->billed())->toBe(1300.00)
        ->and(reportFor($this->today->subMonths(2), $this->today->subMonths(2))->billed())->toBe(0.0);
});

test('a payment taken today is included, not cut off at midnight', function () {
    $jobOrder = finishedJobOrder($this->today);
    app(PaymentService::class)->take($jobOrder, 300, PaymentMethod::CASH);

    expect(reportFor($this->today, $this->today)->collected())->toBe(300.00);
});

// -- Margin ----------------------------------------------------------------

test('margin is parts markup plus all of the labour', function () {
    finishedJobOrder($this->today, partSale: 800, partCost: 400, labor: 500);

    $report = reportFor($this->today, $this->today);

    expect($report->partsRevenue())->toBe(800.00)
        ->and($report->partsCost())->toBe(400.00)
        ->and($report->laborRevenue())->toBe(500.00)
        // 400 of parts markup + 500 of labour, which costs the shop nothing
        // but its own time.
        ->and($report->grossMargin())->toBe(900.00);
});

test('margin uses the cost snapshotted at the time, not today\'s', function () {
    $jobOrder = finishedJobOrder($this->today, partSale: 800, partCost: 400);

    $jobOrder->parts->first()->part->update(['unit_cost_price' => 9999]);

    expect(reportFor($this->today, $this->today)->partsCost())->toBe(400.00);
});

// -- Averages and counts ---------------------------------------------------

test('the average job value divides billed by repairs finished', function () {
    finishedJobOrder($this->today, partSale: 800, labor: 500);
    finishedJobOrder($this->today, partSale: 1200, labor: 500);

    $report = reportFor($this->today, $this->today);

    expect($report->jobOrdersFinished())->toBe(2)
        ->and($report->billed())->toBe(3000.00)
        ->and($report->averageJobValue())->toBe(1500.00);
});

test('an empty period reports zeroes rather than dividing by zero', function () {
    $report = reportFor($this->today->subYear(), $this->today->subYear());

    expect($report->billed())->toBe(0.0)
        ->and($report->collected())->toBe(0.0)
        ->and($report->averageJobValue())->toBe(0.0)
        ->and($report->grossMargin())->toBe(0.0)
        ->and($report->jobOrdersFinished())->toBe(0);
});

// -- Breakdowns ------------------------------------------------------------

test('payments are split by method', function () {
    $jobOrder = finishedJobOrder($this->today);
    $service = app(PaymentService::class);

    $service->take($jobOrder, 300, PaymentMethod::CASH);
    $service->take($jobOrder, 700, PaymentMethod::GCASH);

    $byMethod = reportFor($this->today, $this->today)->byMethod();

    expect($byMethod->firstWhere('label', 'GCash')['value'])->toBe(700.00)
        ->and($byMethod->firstWhere('label', 'Cash')['value'])->toBe(300.00);
});

test('top parts report units, revenue and margin', function () {
    finishedJobOrder($this->today, partSale: 800, partCost: 400);

    $top = reportFor($this->today, $this->today)->topParts()->first();

    expect((int) $top->units)->toBe(1)
        ->and((float) $top->revenue)->toBe(800.00)
        ->and((float) $top->margin)->toBe(400.00);
});

test('technician throughput is attributed to whoever did the work', function () {
    $tech = reportUser(Role::TECHNICIAN);
    finishedJobOrder($this->today, technician: $tech);

    $row = reportFor($this->today, $this->today)->byTechnician()->first();

    expect($row->technician)->toBe($tech->name)
        ->and((int) $row->jobs)->toBe(1);
});

test('the trend buckets every day in the window, including empty ones', function () {
    $report = reportFor($this->today->subDays(6), $this->today);

    expect($report->trend())->toHaveCount(7);
});

test('a long window buckets by month instead of by day', function () {
    $report = reportFor($this->today->subMonths(6), $this->today);

    // Six months of daily bars would be unreadable.
    expect($report->trend()->count())->toBeLessThanOrEqual(8);
});

// -- The screen ------------------------------------------------------------

test('an administrator can open the income report', function () {
    $this->get(route('admin.reports.income'))->assertOk();
});

test('a counter staff member cannot open the income report', function () {
    $this->actingAs(reportUser(Role::COUNTER_STAFF));

    $this->get(route('admin.reports.income'))->assertForbidden();
});

test('changing the period changes the figures', function () {
    $jobOrder = finishedJobOrder($this->today);
    app(PaymentService::class)->take($jobOrder, 1000, PaymentMethod::CASH);

    $component = Volt::test('admin.reports.income')->set('period', 'today');
    expect($component->viewData('summary')['collected'])->toBe(1000.00);

    $component->set('period', 'custom')
        ->set('from', $this->today->subYear()->format('Y-m-d'))
        ->set('to', $this->today->subYear()->format('Y-m-d'));

    expect($component->viewData('summary')['collected'])->toBe(0.0);
});

test('the report exports as a csv', function () {
    $jobOrder = finishedJobOrder($this->today);
    app(PaymentService::class)->take($jobOrder, 1000, PaymentMethod::CASH);

    $response = Volt::test('admin.reports.income')->call('export');

    $response->assertFileDownloaded();
});
