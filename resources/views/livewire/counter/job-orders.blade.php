<?php

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

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
                                            <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-zinc-600 hover:bg-zinc-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer">
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
</div>
