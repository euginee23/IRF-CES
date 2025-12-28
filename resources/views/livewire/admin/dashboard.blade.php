<?php

use function Livewire\Volt\{state, layout};

layout('components.layouts.app');

state(['stats' => fn() => [
    'total_users' => \App\Models\User::count(),
    'administrators' => \App\Models\User::where('role', 'administrator')->count(),
    'technicians' => \App\Models\User::where('role', 'technician')->count(),
    'counter_staff' => \App\Models\User::where('role', 'counter_staff')->count(),
]]);

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
            Administrator Dashboard
        </h1>
    </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Users</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ $stats['total_users'] }}</div>
                    </div>
                    <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Administrators</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ $stats['administrators'] }}</div>
                    </div>
                    <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Technicians</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ $stats['technicians'] }}</div>
                    </div>
                    <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                    </svg>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Counter Staff</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ $stats['counter_staff'] }}</div>
                    </div>
                    <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Administrator Controls</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Manage users, system settings, and view reports</p>

            <div class="mt-6 flex gap-3">
                <button class="px-4 py-2 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-lg font-medium hover:bg-zinc-800 dark:hover:bg-zinc-100 transition-colors">
                    Manage Users
                </button>
                <button class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    System Settings
                </button>
                <button class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    View Reports
                </button>
            </div>
        </div>
    </div>
</div>
