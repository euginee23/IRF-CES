<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="antialiased bg-white dark:bg-zinc-900">
        <x-navbar />

        <!-- Content -->
        <div class="min-h-screen pt-16 bg-gradient-to-br from-blue-50 to-white dark:from-zinc-900 dark:to-zinc-800">
            <div class="flex items-center justify-center px-4 sm:px-6 lg:px-8" style="min-height: calc(100vh - 4rem);">
                <div class="w-full max-w-md">
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-8 border border-zinc-200 dark:border-zinc-700">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
