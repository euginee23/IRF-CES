<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Forbidden</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-zinc-50 via-slate-50 to-zinc-100 dark:from-zinc-950 dark:via-slate-950 dark:to-zinc-900">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-2xl w-full">
            <!-- Error Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                <!-- Header with gradient -->
                <div class="bg-gradient-to-r from-red-600 to-orange-600 px-8 py-6">
                    <div class="flex items-center justify-center">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl">
                            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-8 py-12 text-center">
                    <h1 class="text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-red-600 to-orange-600 mb-4">
                        403
                    </h1>
                    <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">
                        Access Forbidden
                    </h2>
                    <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-8 max-w-md mx-auto">
                        {{ $exception->getMessage() ?: "You don't have permission to access this resource. Please contact your administrator if you believe this is an error." }}
                    </p>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="javascript:history.back()" 
                           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-xl transition-all shadow-md hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Go Back
                        </a>
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
                <div class="px-8 py-6 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-800">
                    <p class="text-sm text-center text-zinc-500 dark:text-zinc-400">
                        Need help? Contact your system administrator.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
