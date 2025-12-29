<x-layouts.app :title="__('Dashboard')">
    <!-- Welcome Header -->
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 mb-2">
            Welcome back, <span class="text-blue-600 dark:text-blue-400">{{ auth()->user()->name }}</span>
        </h1>
        <p class="text-lg text-zinc-600 dark:text-zinc-300">
            Here's what's happening with your repair management system today.
        </p>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-6 md:grid-cols-3 mb-8">
        <!-- Total Users -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Total Users</p>
                    <p class="text-4xl font-bold">5</p>
                    <p class="text-xs opacity-75 mt-2">Active staff members</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <div class="absolute -bottom-6 -right-6 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
        </div>

        <!-- Active Repairs -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-zinc-800/50 dark:backdrop-blur-sm p-6 border border-zinc-200 dark:border-zinc-700/50 shadow-sm hover:shadow-md dark:hover:shadow-zinc-900/50 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300 mb-1">Active Repairs</p>
                    <p class="text-4xl font-bold text-zinc-900 dark:text-zinc-100">8</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">Currently in progress</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-amber-100 to-amber-200 dark:from-amber-900/30 dark:to-amber-800/30 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-zinc-800/50 dark:backdrop-blur-sm p-6 border border-zinc-200 dark:border-zinc-700/50 shadow-sm hover:shadow-md dark:hover:shadow-zinc-900/50 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300 mb-1">Completed</p>
                    <p class="text-4xl font-bold text-zinc-900 dark:text-zinc-100">24</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">This month</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Recent Activity -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-zinc-800/50 dark:backdrop-blur-sm border border-zinc-200 dark:border-zinc-700/50 shadow-sm">
            <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-900/30 dark:to-zinc-800/30">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Recent Activity
                </h2>
                <button class="px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                    View All
                </button>
            </div>

            <div class="p-6 space-y-4">
                <!-- Activity Item -->
                <div class="flex items-start gap-4 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 hover:bg-zinc-100 dark:hover:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700/30 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/40 dark:to-blue-800/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">New repair request submitted</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">iPhone 14 Pro - Battery replacement</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">2 hours ago</p>
                    </div>
                </div>

                <!-- Activity Item -->
                <div class="flex items-start gap-4 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 hover:bg-zinc-100 dark:hover:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700/30 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/40 dark:to-green-800/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Repair completed</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">MacBook Pro - Screen repair</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">5 hours ago</p>
                    </div>
                </div>

                <!-- Activity Item -->
                <div class="flex items-start gap-4 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 hover:bg-zinc-100 dark:hover:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700/30 transition-all">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-800/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Awaiting approval</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">Samsung Galaxy S23 - Charging port</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">1 day ago</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="relative overflow-hidden rounded-2xl bg-white dark:bg-zinc-800/50 dark:backdrop-blur-sm border border-zinc-200 dark:border-zinc-700/50 shadow-sm">
            <div class="p-6 border-b border-zinc-200 dark:border-zinc-700/50 bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-900/30 dark:to-zinc-800/30">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Quick Actions
                </h2>
            </div>

            <div class="p-6 grid grid-cols-2 gap-4">
                @if(auth()->user()->isAdministrator())
                    <a href="{{ route('staff.index') }}" wire:navigate class="group p-6 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border border-blue-200 dark:border-blue-700/50 hover:shadow-lg dark:hover:shadow-blue-900/20 hover:-translate-y-1 transition-all">
                        <div class="w-12 h-12 bg-white dark:bg-zinc-900/50 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Manage Staff</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">Add or edit staff members</p>
                    </a>

                    <a href="{{ route('admin.quote-requests') }}" wire:navigate class="group p-6 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 border border-purple-200 dark:border-purple-700/50 hover:shadow-lg dark:hover:shadow-purple-900/20 hover:-translate-y-1 transition-all">
                        <div class="w-12 h-12 bg-white dark:bg-zinc-900/50 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Quote Requests</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">Review repair quotes</p>
                    </a>

                    <a href="{{ route('admin.parts-inventory') }}" wire:navigate class="group p-6 rounded-xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 border border-green-200 dark:border-green-700/50 hover:shadow-lg dark:hover:shadow-green-900/20 hover:-translate-y-1 transition-all">
                        <div class="w-12 h-12 bg-white dark:bg-zinc-900/50 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Parts Inventory</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">Manage spare parts</p>
                    </a>
                @endif

                <a href="{{ route('profile.edit') }}" class="group p-6 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/30 border border-amber-200 dark:border-amber-700/50 hover:shadow-lg dark:hover:shadow-amber-900/20 hover:-translate-y-1 transition-all">
                    <div class="w-12 h-12 bg-white dark:bg-zinc-900/50 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">My Profile</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">Update your information</p>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
