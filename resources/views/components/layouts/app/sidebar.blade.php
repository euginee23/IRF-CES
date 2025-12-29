<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme-loading="true">
    <head>
        @include('partials.head')
        {{-- Inline script to remove loading state after theme is applied --}}
        <script>
            document.documentElement.removeAttribute('data-theme-loading');
        </script>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-50/30 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950" wire:persist="theme">
        {{-- Use the shared navbar component --}}
        <x-navbar />

        <main class="pt-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto py-6">
                {{ $slot }}
            </div>
        </main>

        {{-- Global Notification Components --}}
        <x-notification-toast />
        <x-delete-confirmation />

        @stack('scripts')
    </body>
</html>
