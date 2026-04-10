<?php

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use App\Models\Part;
use App\Models\RepairQuoteRequest;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\User;
use function Livewire\Volt\{state, layout};

layout('components.layouts.app');

state([
    'stats' => fn() => [
        // Job Orders
        'total_job_orders' => JobOrder::count(),
        'pending_orders' => JobOrder::where('status', JobOrderStatus::PENDING)->count(),
        'assigned_orders' => JobOrder::where('status', JobOrderStatus::ASSIGNED)->count(),
        'awaiting_approval' => JobOrder::where('status', JobOrderStatus::AWAITING_APPROVAL)->count(),
        'approved_orders' => JobOrder::where('status', JobOrderStatus::APPROVED)->count(),
        'in_progress_orders' => JobOrder::where('status', JobOrderStatus::IN_PROGRESS)->count(),
        'completed_orders' => JobOrder::where('status', JobOrderStatus::COMPLETED)->count(),
        'delivered_orders' => JobOrder::where('status', JobOrderStatus::DELIVERED)->count(),
        'cancelled_orders' => JobOrder::where('status', JobOrderStatus::CANCELLED)->count(),
        'total_revenue' => JobOrder::whereIn('status', [JobOrderStatus::COMPLETED, JobOrderStatus::DELIVERED])->sum('final_cost') ?: JobOrder::whereIn('status', [JobOrderStatus::COMPLETED, JobOrderStatus::DELIVERED])->sum('estimated_cost'),
        'this_month_orders' => JobOrder::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),

        // Parts / Inventory
        'active_parts' => Part::where('is_active', true)->count(),
        'low_stock_parts' => Part::where('is_active', true)->whereColumn('in_stock', '<=', 'reorder_point')->count(),
        'inventory_value' => Part::where('is_active', true)->selectRaw('SUM(in_stock * unit_cost_price) as total')->value('total') ?? 0,

        // Quote Requests
        'total_quotes' => RepairQuoteRequest::count(),
        'pending_quotes' => RepairQuoteRequest::where('status', 'pending')->count(),

        // Services & Suppliers
        'active_services' => Service::where('is_active', true)->count(),
        'active_suppliers' => Supplier::where('is_active', true)->count(),

        // Users
        'total_users' => User::count(),
        'administrators' => User::where('role', 'administrator')->count(),
        'technicians' => User::where('role', 'technician')->count(),
        'counter_staff' => User::where('role', 'counter_staff')->count(),
    ],
    'recentOrders' => fn() => JobOrder::with(['receivedBy', 'assignedTo'])->latest()->take(5)->get(),
]);

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Administrator Dashboard</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">System overview and analytics &mdash; {{ now()->format('F d, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Row 1: Key Business Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Job Orders -->
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
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Job Orders</p>
                    <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $stats['total_job_orders'] }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $stats['this_month_orders'] }} this month</p>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-md">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Revenue</p>
                    <p class="text-4xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($stats['total_revenue'], 0) }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">from completed orders</p>
                </div>
            </div>
        </div>

        <!-- Active Inventory -->
        <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-md">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    @if($stats['low_stock_parts'] > 0)
                        <span class="px-3 py-1 text-xs font-semibold text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30 rounded-full">{{ $stats['low_stock_parts'] }} low</span>
                    @endif
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Inventory Items</p>
                    <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $stats['active_parts'] }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">₱{{ number_format($stats['inventory_value'], 0) }} total value</p>
                </div>
            </div>
        </div>

        <!-- Pending Quotes -->
        <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-md">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    @if($stats['pending_quotes'] > 0)
                        <span class="px-3 py-1 text-xs font-semibold text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 rounded-full">Needs review</span>
                    @endif
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Quote Requests</p>
                    <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $stats['pending_quotes'] }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $stats['total_quotes'] }} total requests</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Job Order Pipeline -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Job Order Pipeline</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Current status distribution across all orders</p>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
            @php
                $pipeline = [
                    ['label' => 'Pending', 'count' => $stats['pending_orders'], 'color' => 'amber'],
                    ['label' => 'Assigned', 'count' => $stats['assigned_orders'], 'color' => 'blue'],
                    ['label' => 'Awaiting', 'count' => $stats['awaiting_approval'], 'color' => 'yellow'],
                    ['label' => 'Approved', 'count' => $stats['approved_orders'], 'color' => 'emerald'],
                    ['label' => 'In Progress', 'count' => $stats['in_progress_orders'], 'color' => 'indigo'],
                    ['label' => 'Completed', 'count' => $stats['completed_orders'], 'color' => 'green'],
                    ['label' => 'Delivered', 'count' => $stats['delivered_orders'], 'color' => 'teal'],
                    ['label' => 'Cancelled', 'count' => $stats['cancelled_orders'], 'color' => 'red'],
                ];
            @endphp

            @foreach($pipeline as $stage)
                <div class="text-center p-4 bg-{{ $stage['color'] }}-50 dark:bg-{{ $stage['color'] }}-900/20 rounded-xl border border-{{ $stage['color'] }}-200 dark:border-{{ $stage['color'] }}-800/50">
                    <div class="text-2xl font-bold text-{{ $stage['color'] }}-700 dark:text-{{ $stage['color'] }}-400">{{ $stage['count'] }}</div>
                    <div class="text-xs font-medium text-{{ $stage['color'] }}-600 dark:text-{{ $stage['color'] }}-500 mt-1">{{ $stage['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Row 3: Two-column layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Inventory & System Stats -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">System Overview</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Inventory, services, and suppliers at a glance</p>

            <div class="mt-6 space-y-4">
                <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Low Stock Alerts</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Parts below reorder point</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold {{ $stats['low_stock_parts'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $stats['low_stock_parts'] }}</span>
                </div>

                <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Inventory Value</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Total cost value of parts in stock</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white">₱{{ number_format($stats['inventory_value'], 0) }}</span>
                </div>

                <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Active Suppliers</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Registered parts suppliers</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['active_suppliers'] }}</span>
                </div>

                <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">Active Services</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Repair service offerings</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['active_services'] }}</span>
                </div>
            </div>
        </div>

        <!-- Staff Overview -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Staff Overview</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $stats['total_users'] }} total users across all roles</p>

            <div class="mt-6 space-y-4">
                @php
                    $roles = [
                        ['label' => 'Administrators', 'count' => $stats['administrators'], 'color' => 'blue', 'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                        ['label' => 'Technicians', 'count' => $stats['technicians'], 'color' => 'indigo', 'icon' => 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z'],
                        ['label' => 'Counter Staff', 'count' => $stats['counter_staff'], 'color' => 'teal', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
                    ];
                    $maxCount = max($stats['administrators'], $stats['technicians'], $stats['counter_staff'], 1);
                @endphp

                @foreach($roles as $role)
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-{{ $role['color'] }}-100 dark:bg-{{ $role['color'] }}-900/30 rounded-lg">
                                    <svg class="w-5 h-5 text-{{ $role['color'] }}-600 dark:text-{{ $role['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $role['icon'] }}" />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $role['label'] }}</span>
                            </div>
                            <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $role['count'] }}</span>
                        </div>
                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                            <div class="bg-{{ $role['color'] }}-500 h-2 rounded-full transition-all" style="width: {{ $maxCount > 0 ? round(($role['count'] / $maxCount) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Quick Actions -->
            <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('staff.index') }}" wire:navigate class="flex items-center gap-2 px-4 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-lg text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Manage Staff
                    </a>
                    <a href="{{ route('admin.parts-inventory') }}" wire:navigate class="flex items-center gap-2 px-4 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        Parts Inventory
                    </a>
                    <a href="{{ route('admin.services') }}" wire:navigate class="flex items-center gap-2 px-4 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Services
                    </a>
                    <a href="{{ route('admin.suppliers') }}" wire:navigate class="flex items-center gap-2 px-4 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        Suppliers
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Recent Job Orders -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Job Orders</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Latest 5 job orders across the system</p>
        </div>

        @if($recentOrders->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto w-16 h-16 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-4 text-zinc-500 dark:text-zinc-400 font-medium">No job orders yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Job Order #</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Customer</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Device</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Status</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Technician</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($recentOrders as $order)
                            @php
                                $statusColors = [
                                    'pending' => 'amber',
                                    'assigned' => 'blue',
                                    'awaiting_approval' => 'yellow',
                                    'approved' => 'emerald',
                                    'in_progress' => 'indigo',
                                    'completed' => 'green',
                                    'delivered' => 'teal',
                                    'cancelled' => 'red',
                                ];
                                $color = $statusColors[$order->status->value] ?? 'zinc';
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-indigo-600 dark:text-indigo-400">{{ $order->job_order_number }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $order->customer_name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $order->customer_phone }}</p>
                                </td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ $order->device_brand }} {{ $order->device_model }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-300">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ $order->assignedTo->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">{{ $order->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
