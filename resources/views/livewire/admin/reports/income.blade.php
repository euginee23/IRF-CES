<?php

use App\Enums\PaymentMethod;
use App\Services\Reports\IncomeReport;
use Carbon\CarbonImmutable;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    /** Income records: '' for every method, or a PaymentMethod value. */
    public string $ledgerMethod = '';

    /** Income records: receipt #, job order #, customer name or phone. */
    public string $ledgerSearch = '';

    /** today | week | month | quarter | year | custom */
    public string $period = 'month';

    public string $from = '';
    public string $to = '';

    protected $queryString = ['period', 'from', 'to'];

    public function mount(): void
    {
        [$from, $to] = $this->resolveRange();
        $this->from = $from->format('Y-m-d');
        $this->to = $to->format('Y-m-d');
    }

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Income Report');
    }

    public function updatedLedgerMethod(): void
    {
        $this->resetPage('recordsPage');
    }

    public function updatedLedgerSearch(): void
    {
        $this->resetPage('recordsPage');
    }

    public function updatedFrom(): void
    {
        $this->resetPage('recordsPage');
    }

    public function updatedTo(): void
    {
        $this->resetPage('recordsPage');
    }

    public function updatedPeriod(): void
    {
        $this->resetPage('recordsPage');

        if ($this->period === 'custom') {
            return;
        }

        [$from, $to] = $this->resolveRange();
        $this->from = $from->format('Y-m-d');
        $this->to = $to->format('Y-m-d');
    }

    /**
     * The window to report on.
     *
     * Ends at the end of the day so a payment taken this afternoon is
     * included — a plain date would cut the window at midnight and quietly
     * omit today's takings.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveRange(): array
    {
        $now = CarbonImmutable::now();

        return match ($this->period) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'week' => [$now->startOfWeek(), $now->endOfWeek()],
            'quarter' => [$now->startOfQuarter(), $now->endOfQuarter()],
            'year' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => [
                CarbonImmutable::parse($this->from ?: $now->startOfMonth())->startOfDay(),
                CarbonImmutable::parse($this->to ?: $now)->endOfDay(),
            ],
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };
    }

    private function report(): IncomeReport
    {
        [$from, $to] = $this->resolveRange();

        return new IncomeReport($from, $to);
    }

    /** The report as a CSV, for a spreadsheet or an accountant. */
    public function export()
    {
        $report = $this->report();
        [$from, $to] = $this->resolveRange();

        $rows = [
            ['IRF-CES Income Report'],
            ['From', $from->format('Y-m-d'), 'To', $to->format('Y-m-d')],
            [],
            ['Measure', 'Amount'],
        ];

        foreach ($report->summary() as $key => $value) {
            $rows[] = [ucwords(str_replace('_', ' ', $key)), $value];
        }

        $rows[] = [];
        $rows[] = ['Payment method', 'Collected'];
        foreach ($report->byMethod() as $row) {
            $rows[] = [$row['label'], $row['value']];
        }

        $rows[] = [];
        $rows[] = ['Service', 'Times', 'Revenue'];
        foreach ($report->topServices(50) as $row) {
            $rows[] = [$row->service_name, $row->times, $row->revenue];
        }

        $rows[] = [];
        $rows[] = ['Part', 'Units', 'Revenue', 'Margin'];
        foreach ($report->topParts(50) as $row) {
            $rows[] = [$row->part_name, $row->units, $row->revenue, $row->margin];
        }

        $filename = 'income-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Every payment in the period, one row each, as a CSV. */
    public function exportRecords()
    {
        [$from, $to] = $this->resolveRange();
        $method = $this->ledgerMethodValue();

        $rows = [[
            'Date', 'OR #', 'Job Order #', 'Customer', 'Device', 'Paid For',
            'Method', 'Reference #', 'Received By', 'Amount',
        ]];

        foreach ($this->report()->ledgerRows($method, $this->ledgerSearch) as $row) {
            $rows[] = array_values($row);
        }

        $filename = 'income-records-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** The method filter, ignoring anything that is not a real method. */
    private function ledgerMethodValue(): ?string
    {
        return PaymentMethod::tryFrom($this->ledgerMethod)?->value;
    }

    public function with(): array
    {
        $report = $this->report();
        $ledger = $report->ledger($this->ledgerMethodValue(), $this->ledgerSearch);
        [$from, $to] = $this->resolveRange();

        return [
            'summary' => $report->summary(),
            'trend' => $report->trend(),
            'byMethod' => $report->byMethod(),
            'topServices' => $report->topServices(),
            'topParts' => $report->topParts(),
            'byTechnician' => $report->byTechnician(),
            'records' => $ledger->paginate(25, pageName: 'recordsPage'),
            'paymentMethods' => PaymentMethod::cases(),
            'rangeLabel' => $from->format('d M Y') . ' — ' . $to->format('d M Y'),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-emerald-600 to-teal-700 bg-clip-text text-transparent">Income Report</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $rangeLabel }}</p>
            </div>
            <button type="button" wire:click="export"
                class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-100 text-sm font-semibold rounded-xl transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </button>
        </div>

        <!-- Period -->
        <div class="mt-6 flex flex-wrap items-end gap-4">
            <div>
                <label for="period" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Period</label>
                <select id="period" wire:model.live="period"
                    class="px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                    <option value="today">Today</option>
                    <option value="week">This week</option>
                    <option value="month">This month</option>
                    <option value="quarter">This quarter</option>
                    <option value="year">This year</option>
                    <option value="custom">Custom range</option>
                </select>
            </div>

            @if($period === 'custom')
                <div>
                    <label for="from" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">From</label>
                    <input type="date" id="from" wire:model.live="from"
                        class="px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                </div>
                <div>
                    <label for="to" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">To</label>
                    <input type="date" id="to" wire:model.live="to"
                        class="px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                </div>
            @endif
        </div>
    </div>

    <!-- Headline figures -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            $tiles = [
                ['Collected', $summary['collected'], 'Cash that arrived in this period', 'emerald'],
                ['Billed', $summary['billed'], 'Invoice value of repairs finished', 'blue'],
                ['Outstanding', $summary['outstanding'], 'Finished but not yet paid for', 'red'],
                ['Gross margin', $summary['gross_margin'], 'Parts margin plus labour', 'purple'],
            ];
        @endphp

        @foreach($tiles as [$label, $value, $hint, $colour])
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md p-6">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                <p @class([
                    'mt-2 text-3xl font-bold',
                    'text-emerald-600 dark:text-emerald-400' => $colour === 'emerald',
                    'text-blue-600 dark:text-blue-400' => $colour === 'blue',
                    'text-red-600 dark:text-red-400' => $colour === 'red',
                    'text-purple-600 dark:text-purple-400' => $colour === 'purple',
                ])>
                    ₱{{ number_format($value, 2) }}
                </p>
                <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <!-- Trend -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Collected over time</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Payments by the date they were taken</p>

        @php $peak = max(1, $trend->max('value')); @endphp

        {{-- A CSS-grid bar chart rather than a charting library: the project
             has no JS chart dependency, and one bar per bucket does not
             justify adding one. --}}
        <div class="mt-6 flex items-end gap-1 h-48" role="img" aria-label="Collected income per period">
            @foreach($trend as $bucket)
                <div class="flex-1 flex flex-col justify-end items-center group relative min-w-0">
                    <div class="absolute -top-1 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 whitespace-nowrap rounded bg-zinc-900 dark:bg-zinc-700 px-2 py-1 text-[10px] font-medium text-white">
                        {{ $bucket['label'] }} · ₱{{ number_format($bucket['value'], 2) }}
                    </div>
                    <div class="w-full rounded-t bg-emerald-500/80 hover:bg-emerald-500 transition-colors"
                        style="height: {{ max(2, ($bucket['value'] / $peak) * 100) }}%"></div>
                </div>
            @endforeach
        </div>

        <div class="mt-2 flex justify-between text-xs text-zinc-400 dark:text-zinc-500">
            <span>{{ $trend->first()['label'] ?? '' }}</span>
            <span>{{ $trend->last()['label'] ?? '' }}</span>
        </div>
    </div>

    <!-- Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Where the money came from</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-600 dark:text-zinc-400">Parts revenue</dt>
                    <dd class="font-semibold text-zinc-900 dark:text-white">₱{{ number_format($summary['parts_revenue'], 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-600 dark:text-zinc-400">Parts cost</dt>
                    <dd class="font-semibold text-red-600 dark:text-red-400">−₱{{ number_format($summary['parts_cost'], 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-3">
                    <dt class="text-zinc-600 dark:text-zinc-400">Labour <span class="text-xs text-zinc-400">(all margin)</span></dt>
                    <dd class="font-semibold text-zinc-900 dark:text-white">₱{{ number_format($summary['labor_revenue'], 2) }}</dd>
                </div>
                <div class="flex justify-between border-t-2 border-zinc-900 dark:border-zinc-600 pt-3">
                    <dt class="font-semibold text-zinc-900 dark:text-white">Gross margin</dt>
                    <dd class="text-lg font-bold text-purple-600 dark:text-purple-400">₱{{ number_format($summary['gross_margin'], 2) }}</dd>
                </div>
            </dl>

            <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-zinc-200 dark:border-zinc-700 pt-4 text-sm">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">Repairs finished</dt>
                    <dd class="mt-1 text-xl font-bold text-zinc-900 dark:text-white">{{ $summary['job_orders_finished'] }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">Average job value</dt>
                    <dd class="mt-1 text-xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($summary['average_job_value'], 2) }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">How customers paid</h2>

            @if($byMethod->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">No payments were taken in this period.</p>
            @else
                @php $methodPeak = max(1, $byMethod->max('value')); @endphp
                <div class="space-y-3">
                    @foreach($byMethod as $row)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-700 dark:text-zinc-300">{{ $row['label'] }}</span>
                                <span class="font-semibold text-zinc-900 dark:text-white">₱{{ number_format($row['value'], 2) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ ($row['value'] / $methodPeak) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Income records -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Income records</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Every payment taken in this period and the repair it came from.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" wire:model.live.debounce.300ms="ledgerSearch" placeholder="OR #, job order #, customer..."
                    class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white">
                <select wire:model.live="ledgerMethod"
                    class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white">
                    <option value="">All methods</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="exportRecords"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-100 text-sm font-semibold rounded-lg transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export records
                </button>
            </div>
        </div>

        @if($records->isEmpty())
            <p class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No payments match in this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">OR #</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Job Order</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Customer / Device</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Paid for</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Method</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Received by</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($records as $payment)
                            <tr wire:key="record-{{ $payment->id }}" class="align-top">
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                                    {{ $payment->paid_at->format('M d, Y') }}
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $payment->paid_at->format('h:i A') }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-zinc-900 dark:text-white">{{ $payment->receipt_number }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($payment->jobOrder)
                                        <a href="{{ route('job-orders.index', ['search' => $payment->jobOrder->job_order_number]) }}" wire:navigate class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ $payment->jobOrder->job_order_number }}</a>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $payment->jobOrder?->customer_name }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ trim("{$payment->jobOrder?->device_brand} {$payment->jobOrder?->device_model}") }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-700 dark:text-zinc-300 max-w-xs">{{ \App\Services\Reports\IncomeReport::paidFor($payment) ?: '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-700 dark:text-zinc-300">
                                    {{ $payment->method->label() }}
                                    @if($payment->reference_no)
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">Ref {{ $payment->reference_no }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $payment->receivedBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-right font-semibold text-zinc-900 dark:text-white">₱{{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($records->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $records->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Top earners -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Top services</h2>
            </div>
            @if($topServices->isEmpty())
                <p class="p-6 text-sm text-zinc-500 dark:text-zinc-400">Nothing finished in this period.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <th class="px-6 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Service</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Times</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($topServices as $row)
                            <tr>
                                <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">{{ $row->service_name }}</td>
                                <td class="px-6 py-3 text-center text-zinc-700 dark:text-zinc-300">{{ $row->times }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white">₱{{ number_format((float) $row->revenue, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Top parts</h2>
            </div>
            @if($topParts->isEmpty())
                <p class="p-6 text-sm text-zinc-500 dark:text-zinc-400">Nothing finished in this period.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <th class="px-6 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Part</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Units</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Revenue</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($topParts as $row)
                            <tr>
                                <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">{{ $row->part_name }}</td>
                                <td class="px-6 py-3 text-center text-zinc-700 dark:text-zinc-300">{{ $row->units }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white">₱{{ number_format((float) $row->revenue, 2) }}</td>
                                <td class="px-6 py-3 text-right text-purple-600 dark:text-purple-400">₱{{ number_format((float) $row->margin, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- By technician -->
    @if($byTechnician->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">By technician</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                        <th class="px-6 py-3 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Technician</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Repairs finished</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase">Billed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($byTechnician as $row)
                        <tr>
                            <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">{{ $row->technician }}</td>
                            <td class="px-6 py-3 text-center text-zinc-700 dark:text-zinc-300">{{ $row->jobs }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white">₱{{ number_format((float) $row->billed, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
