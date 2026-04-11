<?php

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $statusFilter = '';
    public ?JobOrder $selectedJobOrder = null;
    public bool $showViewModal = false;

    public function viewJobOrder(int $id): void
    {
        $job = JobOrder::with(['receivedBy', 'assignedTo'])->findOrFail($id);

        // Normalize parts data
        $parts = $job->parts_needed ?? [];
        if (is_array($parts) && count($parts) > 0) {
            foreach ($parts as $idx => $p) {
                $parts[$idx]['quantity'] = isset($p['quantity']) ? (int) $p['quantity'] : 1;

                if (empty($p['part_name']) && !empty($p['part_id'])) {
                    $partModel = \App\Models\Part::find($p['part_id']);
                    if ($partModel) {
                        $parts[$idx]['part_name'] = $partModel->name;
                        $parts[$idx]['unit_sale_price'] = $partModel->unit_sale_price;
                    } else {
                        $parts[$idx]['part_name'] = $parts[$idx]['part_name'] ?? 'N/A';
                        $parts[$idx]['unit_sale_price'] = $parts[$idx]['unit_sale_price'] ?? 0;
                    }
                }
            }
            $job->parts_needed = $parts;
        }

        $this->selectedJobOrder = $job;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->selectedJobOrder = null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $jobOrder = JobOrder::where('assigned_to', auth()->id())->findOrFail($id);
        $newStatus = JobOrderStatus::from($status);
        $jobOrder->update([
            'status' => $newStatus,
        ]);
        $this->dispatch('success', message: 'Job order status updated successfully.');
    }

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function with(): array
    {
        $technicianId = auth()->id();

        $query = JobOrder::with(['receivedBy', 'assignedTo'])
            ->where('assigned_to', $technicianId);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $jobOrders = $query->latest()->paginate(15);

        return [
            'jobOrders' => $jobOrders,
            'stats' => [
                'assigned' => JobOrder::where('assigned_to', $technicianId)->whereIn('status', [JobOrderStatus::ASSIGNED, JobOrderStatus::PENDING, JobOrderStatus::APPROVED])->count(),
                'in_progress' => JobOrder::where('assigned_to', $technicianId)->where('status', JobOrderStatus::IN_PROGRESS)->count(),
                'completed' => JobOrder::where('assigned_to', $technicianId)->whereIn('status', [JobOrderStatus::DONE, JobOrderStatus::COMPLETED])->count(),
                'total' => JobOrder::where('assigned_to', $technicianId)->count(),
                'completed_this_week' => JobOrder::where('assigned_to', $technicianId)->whereIn('status', [JobOrderStatus::DONE, JobOrderStatus::COMPLETED])->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'completed_this_month' => JobOrder::where('assigned_to', $technicianId)->whereIn('status', [JobOrderStatus::DONE, JobOrderStatus::COMPLETED])->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
            ],
        ];
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-800 bg-clip-text text-transparent">Technician Dashboard</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Your assigned job orders and work progress</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
            <!-- Total Assigned -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Assigned</p>
                        <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $stats['total'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">all time</p>
                    </div>
                </div>
            </div>

            <!-- Pending / Assigned -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 rounded-full">Pending</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending Tasks</p>
                        <p class="text-4xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['assigned'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">awaiting work</p>
                    </div>
                </div>
            </div>

            <!-- In Progress -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30 rounded-full">Active</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">In Progress</p>
                        <p class="text-4xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['in_progress'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">being repaired</p>
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30 rounded-full">Done</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Completed</p>
                        <p class="text-4xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">finished repairs</p>
                    </div>
                </div>
            </div>

            <!-- Completed This Week -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-teal-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">This Week</p>
                        <p class="text-4xl font-bold text-teal-600 dark:text-teal-400">{{ $stats['completed_this_week'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">completed</p>
                    </div>
                </div>
            </div>

            <!-- Completed This Month -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">This Month</p>
                        <p class="text-4xl font-bold text-cyan-600 dark:text-cyan-400">{{ $stats['completed_this_month'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">completed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center gap-4">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Filter by Status:</label>
                <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-500">
                    <option value="">All</option>
                    @foreach(\App\Enums\JobOrderStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Job Orders List -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">My Job Orders</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Assigned repair work orders</p>
            </div>

            @if($jobOrders->isEmpty())
                <div class="p-12 text-center">
                    <svg class="mx-auto w-16 h-16 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-4 text-zinc-500 dark:text-zinc-400 font-medium">No job orders assigned to you yet.</p>
                </div>
            @else
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($jobOrders as $jobOrder)
                        <div class="flex items-center justify-between p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ $jobOrder->job_order_number }}</span>
                                    @php
                                        $statusColors = [
                                            'pending' => 'amber',
                                            'assigned' => 'blue',
                                            'awaiting_approval' => 'yellow',
                                            'approved' => 'emerald',
                                            'in_progress' => 'indigo',
                                            'done' => 'cyan',
                                            'completed' => 'green',
                                            'delivered' => 'teal',
                                            'cancelled' => 'red',
                                        ];
                                        $color = $statusColors[$jobOrder->status->value] ?? 'zinc';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-300">
                                        {{ $jobOrder->status->label() }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-zinc-900 dark:text-white font-medium">{{ $jobOrder->customer_name }}</div>
                                <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $jobOrder->device_brand }} {{ $jobOrder->device_model }}
                                    &middot; {{ $jobOrder->created_at->format('M d, Y') }}
                                </div>
                            </div>
                            <button wire:click="viewJobOrder({{ $jobOrder->id }})" class="ml-4 px-4 py-2 text-sm font-medium border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors cursor-pointer">
                                View Details
                            </button>
                        </div>
                    @endforeach
                </div>

                @if($jobOrders->hasPages())
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                        {{ $jobOrders->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- View Job Order Modal -->
    <div
        x-data="{ show: @entangle('showViewModal').live }"
        x-show="show"
        x-cloak
        x-on:keydown.escape.window="show = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">

        <!-- Backdrop -->
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
            x-on:click="$wire.closeViewModal()"></div>

        <!-- Modal Container -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden"
                x-on:click.stop>

                @if($selectedJobOrder)
                <!-- Modal Header -->
                <div class="sticky top-0 z-10 bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 border-b border-indigo-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Job Order Details</h3>
                                <p class="text-sm text-indigo-100">{{ $selectedJobOrder->job_order_number }}</p>
                            </div>
                        </div>
                        <button wire:click="closeViewModal" class="p-2 hover:bg-white/10 rounded-lg transition-colors cursor-pointer">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 pb-24">
                    <div class="flex flex-col lg:flex-row gap-6">

                        <!-- Left Column: Summary -->
                        <div class="w-full lg:w-80 flex-none space-y-6">
                            <!-- Status Card -->
                            <div class="bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
                                <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-4">Status</h4>
                                @php
                                    $statusColors = [
                                        'pending' => 'amber',
                                        'assigned' => 'blue',
                                        'awaiting_approval' => 'yellow',
                                        'approved' => 'emerald',
                                        'in_progress' => 'indigo',
                                        'done' => 'cyan',
                                        'completed' => 'green',
                                        'delivered' => 'teal',
                                        'cancelled' => 'red',
                                    ];
                                    $color = $statusColors[$selectedJobOrder->status->value] ?? 'zinc';
                                @endphp
                                <div class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-{{ $color }}-700 bg-{{ $color }}-100 dark:text-{{ $color }}-300 dark:bg-{{ $color }}-900/30 rounded-full">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3"/>
                                    </svg>
                                    {{ $selectedJobOrder->status->label() }}
                                </div>
                            </div>

                            <!-- Cost Summary -->
                            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-800">
                                <h4 class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wide mb-4">Cost Summary</h4>
                                @php
                                    $partsTotal = 0.0;
                                    foreach($selectedJobOrder->parts_needed ?? [] as $p) {
                                        $qty = isset($p['quantity']) ? (int)$p['quantity'] : 1;
                                        $price = isset($p['unit_sale_price']) ? (float)$p['unit_sale_price'] : 0.0;
                                        $partsTotal += $qty * $price;
                                    }
                                    $laborTotal = 0.0;
                                    if(!empty($selectedJobOrder->issues) && is_array($selectedJobOrder->issues)) {
                                        foreach($selectedJobOrder->issues as $issue) {
                                            if (!empty($issue['type'])) {
                                                $svc = \App\Models\Service::where('name', $issue['type'])->first();
                                                if ($svc) $laborTotal += (float)$svc->labor_price;
                                            }
                                        }
                                    }
                                    $displayTotal = $selectedJobOrder->final_cost ?? $selectedJobOrder->estimated_cost ?? ($partsTotal + $laborTotal);
                                @endphp

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Parts</span>
                                        <span class="text-lg font-bold text-zinc-900 dark:text-white">₱{{ number_format($partsTotal, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">Labor</span>
                                        <span class="text-lg font-bold text-zinc-900 dark:text-white">₱{{ number_format($laborTotal, 2) }}</span>
                                    </div>
                                    <div class="pt-3 border-t border-emerald-200 dark:border-emerald-800">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Estimated Total</span>
                                            <span class="text-xl font-bold text-emerald-700 dark:text-emerald-400">₱{{ number_format($displayTotal, 2) }}</span>
                                        </div>
                                    </div>
                                    @if($selectedJobOrder->final_cost)
                                        <div class="pt-3 border-t border-emerald-200 dark:border-emerald-800">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Final Cost</span>
                                                <span class="text-xl font-bold text-emerald-700 dark:text-emerald-400">₱{{ number_format($selectedJobOrder->final_cost, 2) }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Quick Status Update -->
                            @if(!in_array($selectedJobOrder->status->value, ['done', 'completed', 'delivered', 'cancelled']))
                                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-6 border border-indigo-200 dark:border-indigo-800">
                                    <h4 class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wide mb-4">Update Status</h4>
                                    <div class="space-y-2">
                                        @if(in_array($selectedJobOrder->status->value, ['assigned', 'pending', 'approved']))
                                            <button wire:click="updateStatus({{ $selectedJobOrder->id }}, 'in_progress')" class="w-full px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all cursor-pointer">
                                                Start Working
                                            </button>
                                        @endif
                                        @if($selectedJobOrder->status->value === 'in_progress')
                                            <button wire:click="updateStatus({{ $selectedJobOrder->id }}, 'done')" class="w-full px-4 py-2.5 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all cursor-pointer">
                                                Mark as Done
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Divider -->
                        <div class="hidden lg:block w-px bg-zinc-100 dark:bg-zinc-800"></div>

                        <!-- Right Column: Details -->
                        <div class="flex-1 overflow-y-auto max-h-[70vh] pr-4 pb-10">
                            <div class="space-y-6">

                                <!-- Customer Information -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 px-5 py-3 border-b border-blue-100 dark:border-blue-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            Customer Information
                                        </h4>
                                    </div>
                                    <div class="p-5 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Name</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->customer_name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Phone</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->customer_phone }}</p>
                                        </div>
                                        @if($selectedJobOrder->customer_email)
                                            <div>
                                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Email</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->customer_email }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Device Information -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 px-5 py-3 border-b border-purple-100 dark:border-purple-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            Device Information
                                        </h4>
                                    </div>
                                    <div class="p-5 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Brand</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->device_brand }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Model</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->device_model }}</p>
                                        </div>
                                        @if($selectedJobOrder->serial_number)
                                            <div class="col-span-2">
                                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Serial / IMEI</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white font-mono">{{ $selectedJobOrder->serial_number }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Issue Description -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 px-5 py-3 border-b border-red-100 dark:border-red-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            Issue Description
                                        </h4>
                                    </div>
                                    <div class="p-5">
                                        <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $selectedJobOrder->issue_description }}</p>
                                    </div>
                                </div>

                                <!-- Services Required -->
                                @if($selectedJobOrder->issues && count($selectedJobOrder->issues) > 0)
                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 px-5 py-3 border-b border-indigo-100 dark:border-indigo-800">
                                            <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                                Services Required
                                            </h4>
                                        </div>
                                        <div class="p-5">
                                            <div class="space-y-3">
                                                @foreach($selectedJobOrder->issues as $issue)
                                                    @php
                                                        $dbService = null;
                                                        if (!empty($issue['type'])) {
                                                            $dbService = \App\Models\Service::where('name', $issue['type'])->first();
                                                        }
                                                    @endphp
                                                    <div class="flex items-start gap-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                                        <div class="mt-0.5">
                                                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <div class="flex items-center justify-between">
                                                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $issue['type'] ?? 'N/A' }}</p>
                                                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">@if($dbService)Labor: ₱{{ number_format($dbService->labor_price, 2) }} @else — @endif</div>
                                                            </div>
                                                            @if(!empty($issue['diagnosis']))
                                                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $issue['diagnosis'] }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Parts Needed -->
                                @if($selectedJobOrder->parts_needed && count($selectedJobOrder->parts_needed) > 0)
                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 px-5 py-3 border-b border-emerald-100 dark:border-emerald-800">
                                            <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                                Parts Needed
                                            </h4>
                                        </div>
                                        <div class="p-5">
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                                            <th class="text-left py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Part</th>
                                                            <th class="text-center py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Qty</th>
                                                            <th class="text-right py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Unit Price</th>
                                                            <th class="text-right py-2 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                                        @foreach($selectedJobOrder->parts_needed as $part)
                                                            @php
                                                                $partName = $part['part_name'] ?? null;
                                                                $unitPrice = isset($part['unit_sale_price']) ? (float)$part['unit_sale_price'] : null;
                                                                $qty = isset($part['quantity']) ? (int)$part['quantity'] : 1;

                                                                if (empty($partName) && !empty($part['part_id'])) {
                                                                    $pm = \App\Models\Part::find($part['part_id']);
                                                                    if ($pm) {
                                                                        $partName = $pm->name;
                                                                        $unitPrice = $unitPrice ?? $pm->unit_sale_price;
                                                                    }
                                                                }

                                                                $partName = $partName ?? 'N/A';
                                                                $unitPrice = $unitPrice ?? 0;
                                                            @endphp
                                                            <tr>
                                                                <td class="py-2 font-medium text-zinc-900 dark:text-white">{{ $partName }}</td>
                                                                <td class="py-2 text-center text-zinc-700 dark:text-zinc-300">{{ $qty }}</td>
                                                                <td class="py-2 text-right text-zinc-700 dark:text-zinc-300">₱{{ number_format($unitPrice, 2) }}</td>
                                                                <td class="py-2 text-right font-semibold text-zinc-900 dark:text-white">₱{{ number_format($unitPrice * $qty, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Work Performed -->
                                @if($selectedJobOrder->work_performed)
                                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 px-5 py-3 border-b border-green-100 dark:border-green-800">
                                            <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Work Performed
                                            </h4>
                                        </div>
                                        <div class="p-5">
                                            <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $selectedJobOrder->work_performed }}</p>
                                        </div>
                                    </div>
                                @endif

                                <!-- Assignment Information -->
                                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                                    <div class="bg-gradient-to-r from-slate-50 to-zinc-50 dark:from-slate-900/20 dark:to-zinc-900/20 px-5 py-3 border-b border-slate-100 dark:border-slate-800">
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            Assignment Information
                                        </h4>
                                    </div>
                                    <div class="p-5 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Received By</p>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                                                    {{ strtoupper(substr($selectedJobOrder->receivedBy->name ?? 'N', 0, 1)) }}
                                                </div>
                                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->receivedBy->name ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Assigned To</p>
                                            @if($selectedJobOrder->assignedTo)
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                                                        {{ strtoupper(substr($selectedJobOrder->assignedTo->name, 0, 1)) }}
                                                    </div>
                                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->assignedTo->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-sm text-zinc-400 italic">Unassigned</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="sticky bottom-0 z-50 bg-zinc-50 dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-end gap-2">
                        <button
                            wire:click="closeViewModal"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-zinc-600 hover:bg-zinc-700 text-white text-xs font-semibold rounded-lg shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Close
                        </button>
                    </div>
                </div>

                @endif
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <x-notification-toast />
</div>
