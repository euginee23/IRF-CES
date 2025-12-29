<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - Service Unavailable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-zinc-50 via-slate-50 to-zinc-100 dark:from-zinc-950 dark:via-slate-950 dark:to-zinc-900">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-2xl w-full">
            <!-- Error Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                <!-- Header with gradient -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-8 py-6">
                    <div class="flex items-center justify-center">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl">
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-8 py-12 text-center">
                    <h1 class="text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-indigo-600 mb-4">
                        503
                    </h1>
                    <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">
                        Under Maintenance
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-8 max-w-md mx-auto">
                        We're currently performing scheduled maintenance to improve your experience. We'll be back shortly!
                    </p>

                    <!-- Status Box -->
                    <div class="mb-8 p-6 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl max-w-md mx-auto">
                        <div class="flex items-center justify-center gap-3 mb-3">
                            <div class="relative">
                                <div class="w-3 h-3 bg-purple-600 dark:bg-purple-400 rounded-full animate-pulse"></div>
                                <div class="absolute inset-0 w-3 h-3 bg-purple-600 dark:bg-purple-400 rounded-full animate-ping"></div>
                            </div>
                            <span class="text-sm font-semibold text-purple-800 dark:text-purple-200">
                                System Status: Maintenance Mode
                            </span>
                        </div>
                        <p class="text-sm text-purple-700 dark:text-purple-300">
                            Estimated completion time: {{ $exception?->getMessage() ?: 'Shortly' }}
                        </p>
                    </div>

                    <!-- Action Button -->
                    <div class="flex flex-col items-center justify-center gap-4">
                        <button onclick="window.location.reload()" 
                           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 rounded-xl transition-all shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Check Again
                        </button>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            This page will automatically refresh when maintenance is complete
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-8 py-6 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-800">
                    <p class="text-sm text-center text-zinc-500 dark:text-zinc-400">
                        Thank you for your patience while we make improvements.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto-refresh script (checks every 30 seconds) -->
    <script>
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
