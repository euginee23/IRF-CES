<?php

use function Livewire\Volt\{state, layout};

layout('components.layouts.app');

state(['tasks' => fn() => [
    ['id' => 1, 'title' => 'Check equipment calibration', 'status' => 'pending'],
    ['id' => 2, 'title' => 'Perform routine maintenance', 'status' => 'in_progress'],
    ['id' => 3, 'title' => 'Update inventory records', 'status' => 'completed'],
]]);

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
            Technician Dashboard
        </h1>
    </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending Tasks</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ collect($tasks)->where('status', 'pending')->count() }}</div>
                    </div>
                    <svg class="w-12 h-12 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">In Progress</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ collect($tasks)->where('status', 'in_progress')->count() }}</div>
                    </div>
                    <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Completed</div>
                        <div class="text-3xl font-bold text-zinc-900 dark:text-white mt-2">{{ collect($tasks)->where('status', 'completed')->count() }}</div>
                    </div>
                    <svg class="w-12 h-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">My Tasks</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Technical work orders and maintenance schedule</p>

            <div class="mt-6 space-y-3">
                @foreach($tasks as $task)
                    <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $task['title'] }}</div>
                            <span class="inline-flex mt-2 items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $task['status'] === 'completed' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : '' }}
                                {{ $task['status'] === 'in_progress' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : '' }}
                                {{ $task['status'] === 'pending' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-300' : '' }}
                            ">
                                {{ ucfirst(str_replace('_', ' ', $task['status'])) }}
                            </span>
                        </div>
                        <button class="px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 text-zinc-900 dark:text-white rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                            View Details
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
