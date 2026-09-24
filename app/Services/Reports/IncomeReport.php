<?php

namespace App\Services\Reports;

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use App\Models\JobOrderPart;
use App\Models\JobOrderService;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * What the shop earned over a period.
 *
 * Two different numbers are reported side by side, because they answer
 * different questions and the shop needs both:
 *
 *   Collected — cash that actually arrived, by the date it arrived. This is
 *               what the client means by "total income".
 *   Billed    — the invoice total of repairs finished in the period. Work
 *               done but not yet paid for shows up as the gap.
 *
 * Every figure is built from the snapshots taken when the work was booked,
 * so re-running a past month cannot change its answer.
 */
class IncomeReport
{
    public function __construct(
        private readonly CarbonImmutable $from,
        private readonly CarbonImmutable $to,
    ) {}

    /** Cash that arrived in the period. */
    public function collected(): float
    {
        return round((float) Payment::counted()
            ->whereBetween('paid_at', [$this->from, $this->to])
            ->sum('amount'), 2);
    }

    /** Invoice value of repairs finished in the period. */
    public function billed(): float
    {
        return round((float) $this->finishedInPeriod()
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(final_cost, 0), estimated_cost, 0)), 0) as total')
            ->value('total'), 2);
    }

    /** Revenue from parts on those repairs, at the price charged. */
    public function partsRevenue(): float
    {
        return round((float) $this->linesOfFinishedJobOrders(JobOrderPart::query())
            ->selectRaw('COALESCE(SUM(quantity * unit_sale_price), 0) as total')
            ->value('total'), 2);
    }

    /** What those parts cost the shop, at the price it paid. */
    public function partsCost(): float
    {
        return round((float) $this->linesOfFinishedJobOrders(JobOrderPart::query())
            ->selectRaw('COALESCE(SUM(quantity * unit_cost_price), 0) as total')
            ->value('total'), 2);
    }

    /** Labour on those repairs. Entirely margin — the shop's own time. */
    public function laborRevenue(): float
    {
        return round((float) $this->linesOfFinishedJobOrders(JobOrderService::query())
            ->selectRaw('COALESCE(SUM(labor_price), 0) as total')
            ->value('total'), 2);
    }

    /**
     * Parts margin plus labour.
     *
     * Labour carries no cost of goods, so it contributes in full.
     */
    public function grossMargin(): float
    {
        return round(($this->partsRevenue() - $this->partsCost()) + $this->laborRevenue(), 2);
    }

    public function jobOrdersFinished(): int
    {
        return $this->finishedInPeriod()->count();
    }

    /** Average invoice value, or zero when nothing was finished. */
    public function averageJobValue(): float
    {
        $count = $this->jobOrdersFinished();

        return $count === 0 ? 0.0 : round($this->billed() / $count, 2);
    }

    /** Still owed on repairs finished in the period. */
    public function outstanding(): float
    {
        $collectedAgainstPeriod = (float) Payment::counted()
            ->whereIn('job_order_id', $this->finishedInPeriod()->select('id'))
            ->sum('amount');

        return round(max(0, $this->billed() - $collectedAgainstPeriod), 2);
    }

    /**
     * Collected cash bucketed for the trend chart.
     *
     * @return Collection<int, array{label: string, value: float}>
     */
    public function trend(): Collection
    {
        $days = $this->from->diffInDays($this->to);

        // Days for anything up to a couple of months, months beyond that —
        // a year of daily bars is unreadable.
        [$format, $step, $label] = $days <= 62
            ? ['Y-m-d', 'day', 'd M']
            : ['Y-m', 'month', 'M Y'];

        $payments = Payment::counted()
            ->whereBetween('paid_at', [$this->from, $this->to])
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $p) => $p->paid_at->format($format))
            ->map(fn ($group) => round((float) $group->sum('amount'), 2));

        $buckets = collect();
        $cursor = $step === 'day' ? $this->from->startOfDay() : $this->from->startOfMonth();

        while ($cursor <= $this->to) {
            $key = $cursor->format($format);

            $buckets->push([
                'label' => $cursor->format($label),
                'value' => (float) ($payments[$key] ?? 0),
            ]);

            $cursor = $step === 'day' ? $cursor->addDay() : $cursor->addMonth();
        }

        return $buckets;
    }

    /**
     * Money taken, split by how it was paid — the end-of-day reconcile.
     *
     * @return Collection<int, array{label: string, value: float}>
     */
    public function byMethod(): Collection
    {
        return Payment::counted()
            ->whereBetween('paid_at', [$this->from, $this->to])
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->method->label(),
                'value' => round((float) $row->total, 2),
            ]);
    }

    /**
     * The services that earned most.
     *
     * @return Collection<int, object>
     */
    public function topServices(int $limit = 8): Collection
    {
        return $this->linesOfFinishedJobOrders(JobOrderService::query())
            ->selectRaw('service_name, COUNT(*) as times, SUM(labor_price) as revenue')
            ->groupBy('service_name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * The parts that earned most, with their margin.
     *
     * @return Collection<int, object>
     */
    public function topParts(int $limit = 8): Collection
    {
        return $this->linesOfFinishedJobOrders(JobOrderPart::query())
            ->selectRaw('part_name, SUM(quantity) as units, SUM(quantity * unit_sale_price) as revenue, SUM(quantity * (unit_sale_price - unit_cost_price)) as margin')
            ->groupBy('part_name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * What each technician finished.
     *
     * @return Collection<int, object>
     */
    public function byTechnician(): Collection
    {
        return $this->finishedInPeriod()
            ->join('users', 'users.id', '=', 'job_orders.assigned_to')
            ->selectRaw('users.name as technician, COUNT(*) as jobs, COALESCE(SUM(COALESCE(NULLIF(final_cost, 0), estimated_cost, 0)), 0) as billed')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('billed')
            ->get();
    }

    /**
     * Repairs whose work finished inside the window.
     *
     * Keyed on completed_at rather than created_at: a repair booked in
     * December and finished in January belongs to January's earnings.
     *
     * @return \Illuminate\Database\Eloquent\Builder<JobOrder>
     */
    private function finishedInPeriod()
    {
        return JobOrder::query()
            ->whereIn('status', [JobOrderStatus::COMPLETED, JobOrderStatus::DELIVERED])
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$this->from, $this->to]);
    }

    /**
     * Narrow a line-item query to the repairs finished in the period.
     *
     * @template TBuilder of \Illuminate\Database\Eloquent\Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    private function linesOfFinishedJobOrders($query)
    {
        return $query->whereIn('job_order_id', $this->finishedInPeriod()->select('id'));
    }

    /**
     * Everything as a flat array, for the CSV export and the tests.
     *
     * @return array<string, float|int>
     */
    public function summary(): array
    {
        return [
            'collected' => $this->collected(),
            'billed' => $this->billed(),
            'outstanding' => $this->outstanding(),
            'parts_revenue' => $this->partsRevenue(),
            'parts_cost' => $this->partsCost(),
            'labor_revenue' => $this->laborRevenue(),
            'gross_margin' => $this->grossMargin(),
            'job_orders_finished' => $this->jobOrdersFinished(),
            'average_job_value' => $this->averageJobValue(),
        ];
    }
}
