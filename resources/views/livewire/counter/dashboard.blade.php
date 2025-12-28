<?php

use function Livewire\Volt\{state, layout};

layout('components.layouts.app');

state(['todayStats' => fn() => [
    'customers_served' => 42,
    'pending_tickets' => 8,
    'avg_wait_time' => '12 min',
]]);

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
            Counter Staff Dashboard
        </h1>
    </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Customers Served Today</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ $todayStats['customers_served'] }}</div>
                    </div>
                    <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending Tickets</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ $todayStats['pending_tickets'] }}</div>
                    </div>
                    <svg class="w-12 h-12 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                    </svg>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg. Wait Time</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ $todayStats['avg_wait_time'] }}</div>
                    </div>
                    <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Quick Actions</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Common counter operations</p>

                <div class="mt-6 grid grid-cols-2 gap-4">
                    <button class="flex items-center justify-center gap-2 px-4 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-lg font-medium hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Customer
                    </button>
                    <button class="flex items-center justify-center gap-2 px-4 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        Search Records
                    </button>
                    <button class="flex items-center justify-center gap-2 px-4 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Receipt
                    </button>
                    <button class="flex items-center justify-center gap-2 px-4 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Generate Report
                    </button>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Activity</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Latest transactions and updates</p>

                <div class="mt-6 space-y-3">
                    <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">Customer #1234 processed</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">2 minutes ago</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">Receipt printed for #1233</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">5 minutes ago</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">New customer registered</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">12 minutes ago</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
