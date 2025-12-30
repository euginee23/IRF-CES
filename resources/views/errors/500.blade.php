<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-zinc-50 via-slate-50 to-zinc-100 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-950">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-2xl w-full">
            <!-- Error Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <!-- Header with gradient -->
                <div class="bg-gradient-to-r from-red-600 to-rose-600 px-8 py-6">
                    <div class="flex items-center justify-center">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl">
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-8 py-12 text-center">
                    <h1 class="text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-rose-600 mb-4">
                        500
                    </h1>
                    <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">
                        Server Error
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-8 max-w-md mx-auto">
                        Something went wrong on our end. We're working to fix the issue. Please try again later.
                    </p>

                    <!-- Error Details (only in development) -->
                    @if(config('app.debug') && isset($exception))
                        <div class="mb-8 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl max-w-lg mx-auto text-left">
                            <h3 class="text-sm font-bold text-red-800 dark:text-red-200 mb-2">Debug Information:</h3>
                            <p class="text-xs text-red-700 dark:text-red-300 font-mono break-all">
                                {{ $exception->getMessage() }}
                            </p>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <button onclick="window.location.reload()" 
                           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-xl transition-all shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Try Again
                        </button>
                        <a href="{{ url('/') }}" 
                           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl transition-all shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Back to Home
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-8 py-6 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700">
                    <p class="text-sm text-center text-zinc-500 dark:text-zinc-400">
                        Our technical team has been notified and is working on a fix.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
