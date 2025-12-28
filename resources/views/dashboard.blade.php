<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <!-- Total Users -->
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Total Users</p>
                        <p class="text-3xl font-bold mt-1">5</p>
                    </div>
                    <div class="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Repairs -->
            <div class="relative overflow-hidden rounded-xl bg-white dark:bg-zinc-800 p-6 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Active Repairs</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">8</p>
                    </div>
                    <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="relative overflow-hidden rounded-xl bg-white dark:bg-zinc-800 p-6 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Completed</p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">24</p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="relative flex-1 overflow-hidden rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Recent Activity</h2>
                <button class="px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                    View All
                </button>
            </div>

            <div class="space-y-4">
                <!-- Activity Item -->
                <div class="flex items-start gap-4 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-zinc-900 dark:text-white">New repair request submitted</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">iPhone 14 Pro - Battery replacement</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">2 hours ago</p>
                    </div>
                </div>

                <!-- Activity Item -->
                <div class="flex items-start gap-4 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700">
                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-zinc-900 dark:text-white">Repair completed</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">MacBook Pro - Screen repair</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">5 hours ago</p>
                    </div>
                </div>

                <!-- Activity Item -->
                <div class="flex items-start gap-4 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700">
                    <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-zinc-900 dark:text-white">Awaiting approval</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Samsung Galaxy S23 - Charging port</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">1 day ago</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
