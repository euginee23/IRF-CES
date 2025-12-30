<?php

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?JobOrder $selectedJobOrder = null;
    public bool $showViewModal = false;

    public function viewJobOrder(int $id): void
    {
        $job = JobOrder::with(['receivedBy', 'assignedTo'])->findOrFail($id);

        // Normalize parts data: if parts_needed entries only have part_id, fetch part details
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

    public function downloadReceipt(int $id)
    {
        $jobOrder = JobOrder::with(['receivedBy', 'assignedTo'])->findOrFail($id);
        
        // Normalize parts data
        $parts = $jobOrder->parts_needed ?? [];
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
            $jobOrder->parts_needed = $parts;
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.job-order-receipt', [
            'jobOrder' => $jobOrder
        ]);
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'job-order-' . $jobOrder->job_order_number . '.pdf');
    }

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Job Orders');
    }

    public function with(): array
    {
        $query = JobOrder::with(['receivedBy', 'assignedTo']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('job_order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $this->search . '%')
                  ->orWhere('device_brand', 'like', '%' . $this->search . '%')
                  ->orWhere('device_model', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $jobOrders = $query->latest()->paginate(15);
        
        return [
            'jobOrders' => $jobOrders,
            'stats' => [
                'total' => JobOrder::count(),
                'pending' => JobOrder::where('status', JobOrderStatus::PENDING)->count(),
                'in_progress' => JobOrder::where('status', JobOrderStatus::IN_PROGRESS)->count(),
                'completed' => JobOrder::where('status', JobOrderStatus::COMPLETED)->count(),
            ],
        ];
    }

    public function delete(int $id): void
    {
        $jobOrder = JobOrder::findOrFail($id);
        
        if (!$jobOrder->canBeEdited()) {
            $this->dispatch('error', message: 'Cannot delete completed or delivered job orders.');
            return;
        }
        
        $jobOrder->delete();
        $this->dispatch('success', message: 'Job order deleted successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-800 bg-clip-text text-transparent">Job Orders</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Manage cellphone repair and service job orders</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Orders Card -->
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
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Orders</p>
                        <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $stats['total'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">all time orders</p>
                    </div>
                </div>
            </div>

            <!-- Pending Card -->
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
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending</p>
                        <p class="text-4xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">awaiting assignment</p>
                    </div>
                </div>
            </div>

            <!-- In Progress Card -->
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

            <!-- Completed Card -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-md">
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
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">Filters</h3>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label for="search" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            Search
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="search"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search by order#, customer, phone, device..."
                                class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="statusFilter" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            Status Filter
                        </label>
                        <select 
                            id="statusFilter"
                            wire:model.live="statusFilter"
                            class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">All Job Orders</h3>
                    <span class="px-2 py-0.5 text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-full">
                        {{ $jobOrders->total() }}
                    </span>
                </div>
                <a href="{{ route('counter.job-orders.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Job Order
                </a>
            </div>

            @if($jobOrders->isEmpty())
                <div class="p-12 text-center">
                    <div class="flex justify-center mb-4">
                        <div class="p-4 bg-zinc-100 dark:bg-zinc-800 rounded-full">
                            <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No Job Orders Found</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                        @if($search || $statusFilter)
                            Try adjusting your filters
                        @else
                            Get started by creating your first job order
                        @endif
                    </p>
                    @if(!$search && !$statusFilter)
                        <a href="{{ route('counter.job-orders.create') }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create Your First Job Order
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Job Order
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Device
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Technician
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach($jobOrders as $jobOrder)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $jobOrder->job_order_number }}</div>
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $jobOrder->created_at->format('M d, Y') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $jobOrder->customer_name }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $jobOrder->customer_phone }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-zinc-900 dark:text-white font-medium">{{ $jobOrder->device_brand }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $jobOrder->device_model }} • {{ $jobOrder->device_type }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'amber',
                                                'assigned' => 'blue',
                                                'in_progress' => 'indigo',
                                                'completed' => 'green',
                                                'delivered' => 'teal',
                                                'cancelled' => 'red',
                                            ];
                                            $color = $statusColors[$jobOrder->status->value] ?? 'zinc';
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-{{ $color }}-700 bg-{{ $color }}-100 dark:text-{{ $color }}-300 dark:bg-{{ $color }}-900/30 rounded-full">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 8 8">
                                                <circle cx="4" cy="4" r="3"/>
                                            </svg>
                                            {{ $jobOrder->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($jobOrder->assignedTo)
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                                                    {{ strtoupper(substr($jobOrder->assignedTo->name, 0, 1)) }}
                                                </div>
                                                <span class="text-sm text-zinc-900 dark:text-white">{{ $jobOrder->assignedTo->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-zinc-400 italic">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">{{ $jobOrder->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $jobOrder->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <button wire:click="viewJobOrder({{ $jobOrder->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 bg-zinc-600 hover:bg-zinc-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View
                                            </button>
                                            @if($jobOrder->canBeEdited())
                                                <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button
                                                    x-on:click="$dispatch('open-delete-dialog', {
                                                        title: 'Delete Job Order',
                                                        message: 'Are you sure you want to delete {{ addslashes($jobOrder->job_order_number) }}? This action cannot be undone.',
                                                        confirmText: 'Delete',
                                                        cancelText: 'Cancel',
                                                        callback: () => $wire.delete({{ $jobOrder->id }})
                                                    })"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($jobOrders->hasPages())
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800">
                        {{ $jobOrders->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Notification Toast -->
    <x-notification-toast />
    
    <!-- Delete Confirmation Dialog -->
    <x-delete-confirmation />

    <!-- View Job Order Modal -->
    @if($showViewModal && $selectedJobOrder)
        <div x-data="{ show: @entangle('showViewModal') }" 
             x-show="show" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeViewModal"></div>
            
            <!-- Modal Container -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.stop>
                    
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
                            <button wire:click="closeViewModal" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 pb-24">
                        <div class="flex flex-col lg:flex-row gap-6">

                            <!-- Left Column: Summary Card (static, non-scrolling) -->
                            <div class="w-full lg:w-80 flex-none space-y-6">
                                <!-- Status Card -->
                                <div class="bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
                                    <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-4">Status</h4>
                                    @php
                                        $statusColors = [
                                            'pending' => 'amber',
                                            'assigned' => 'blue',
                                            'in_progress' => 'indigo',
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
                                        // Calculate parts total from stored parts_needed
                                        $partsTotal = 0.0;
                                        foreach($selectedJobOrder->parts_needed ?? [] as $p) {
                                            $qty = isset($p['quantity']) ? (int)$p['quantity'] : 1;
                                            $price = isset($p['unit_sale_price']) ? (float)$p['unit_sale_price'] : 0.0;
                                            $partsTotal += $qty * $price;
                                        }

                                        // Calculate labor total from issues (lookup labor_price)
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

                                <!-- Timeline -->
                                <div class="bg-gradient-to-br from-zinc-50 to-zinc-100 dark:from-zinc-800 dark:to-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
                                    <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-4">Timeline</h4>
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Created</p>
                                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedJobOrder->created_at->format('M d, Y h:i A') }}</p>
                                            </div>
                                        </div>
                                        @if($selectedJobOrder->expected_completion_date)
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 p-1.5 bg-amber-100 dark:bg-amber-900/30 rounded-full">
                                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Expected Completion</p>
                                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedJobOrder->expected_completion_date->format('M d, Y') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        @if($selectedJobOrder->completed_at)
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 p-1.5 bg-green-100 dark:bg-green-900/30 rounded-full">
                                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                                <div>
                                            @if($selectedJobOrder->completed_at)
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-0.5 p-1.5 bg-green-100 dark:bg-green-900/30 rounded-full">
                                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Completed</p>
                                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedJobOrder->completed_at->format('M d, Y h:i A') }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Delivered</p>
                                    
                                        
                                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedJobOrder->delivered_at->format('M d, Y h:i A') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <!-- GET RECEIPT BUTTON (left column) -->
                                <div class="mt-4 lg:mt-0 lg:mb-6">
                                    <button wire:click="downloadReceipt({{ $selectedJobOrder->id }})" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span class="hidden lg:inline">GET RECEIPT</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="hidden lg:block w-px bg-zinc-100 dark:bg-zinc-800"></div>

                            <!-- Right Column: Details (scrollable) -->
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
                                        @if($selectedJobOrder->customer_address)
                                            <div class="col-span-2">
                                                <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Address</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $selectedJobOrder->customer_address }}</p>
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

                    <!-- Modal Footer -->
                    <div class="sticky bottom-0 z-50 bg-zinc-50 dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-end">
                            <button wire:click="closeViewModal" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all">
                                Close
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
