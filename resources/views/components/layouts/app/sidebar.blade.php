<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gradient-to-br from-blue-50 to-white dark:from-zinc-900 dark:to-zinc-800">
        {{-- Use the shared navbar component which renders a sidebar for authenticated users --}}
        <x-navbar />

        <main class="min-h-screen lg:ml-64 p-6">
            {{ $slot }}
        </main>

        @stack('scripts')
    </body>
</html>
