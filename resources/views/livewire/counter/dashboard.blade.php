<?php

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use App\Models\RepairQuoteRequest;
use function Livewire\Volt\{state, layout};

layout('components.layouts.app');

state([
    'todayStats' => fn() => [
        'today_orders' => JobOrder::whereDate('created_at', today())->count(),
        'pending_orders' => JobOrder::where('status', JobOrderStatus::PENDING)->count(),
        'awaiting_approval' => JobOrder::where('status', JobOrderStatus::AWAITING_APPROVAL)->count(),
        'pending_quotes' => RepairQuoteRequest::where('status', 'pending')->count(),
    ],
    'recentOrders' => fn() => JobOrder::with(['receivedBy', 'assignedTo'])->latest()->take(5)->get(),
]);

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-teal-600 to-cyan-800 bg-clip-text text-transparent">Counter Staff Dashboard</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Front desk operations &mdash; {{ now()->format('F d, Y') }}</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today's New Orders -->
        <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Today's New Orders</p>
                    <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $todayStats['today_orders'] }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">created today</p>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl shadow-md">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    @if($todayStats['pending_orders'] > 0)
                        <span class="px-3 py-1 text-xs font-semibold text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 rounded-full">Action needed</span>
                    @endif
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending Orders</p>
                    <p class="text-4xl font-bold text-amber-600 dark:text-amber-400">{{ $todayStats['pending_orders'] }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">awaiting assignment</p>
                </div>
            </div>
        </div>

        <!-- Awaiting Approval -->
        <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-md">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Awaiting Approval</p>
                    <p class="text-4xl font-bold text-yellow-600 dark:text-yellow-400">{{ $todayStats['awaiting_approval'] }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">customer approval pending</p>
                </div>
            </div>
        </div>

        <!-- Pending Quote Requests -->
        <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-md">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    @if($todayStats['pending_quotes'] > 0)
                        <span class="px-3 py-1 text-xs font-semibold text-purple-700 bg-purple-100 dark:text-purple-300 dark:bg-purple-900/30 rounded-full">New</span>
                    @endif
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending Quotes</p>
                    <p class="text-4xl font-bold text-purple-600 dark:text-purple-400">{{ $todayStats['pending_quotes'] }}</p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">quote requests to review</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Two-column: Quick Actions + Recent Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Quick Actions</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Common counter operations</p>

            <div class="mt-6 space-y-3">
                <a href="{{ route('counter.job-orders.create') }}" wire:navigate class="flex items-center gap-4 p-4 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-xl font-medium hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                    <div class="p-2 bg-white/10 dark:bg-zinc-900/10 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold">New Job Order</p>
                        <p class="text-xs opacity-75">Create a new repair job order</p>
                    </div>
                </a>

                <a href="{{ route('counter.job-orders') }}" wire:navigate class="flex items-center gap-4 p-4 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-xl font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <div class="p-2 bg-zinc-100 dark:bg-zinc-700 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold">View Job Orders</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Manage all job orders</p>
                    </div>
                </a>

                <a href="{{ route('counter.quote-requests') }}" wire:navigate class="flex items-center gap-4 p-4 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-xl font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    <div class="p-2 bg-zinc-100 dark:bg-zinc-700 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold">Quote Requests</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Review and manage repair quote requests</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Job Orders -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Job Orders</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Latest orders in the system</p>
            </div>

            @if($recentOrders->isEmpty())
                <div class="p-12 text-center">
                    <svg class="mx-auto w-12 h-12 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No job orders yet.</p>
                </div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
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
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ $order->job_order_number }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-300">
                                        {{ $order->status->label() }}
                                    </span>
                                </div>
                                <p class="text-sm text-zinc-900 dark:text-white font-medium mt-0.5">{{ $order->customer_name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $order->device_brand }} {{ $order->device_model }} &middot; {{ $order->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
