<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <title>Repair Quote - {{ $quoteRequest->manufacturer }} {{ $quoteRequest->model }} - {{ config('app.name') }}</title>
</head>
<body class="antialiased bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-zinc-900 dark:via-zinc-900 dark:to-blue-950">
    
    <x-navbar />

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-24">
        
        <!-- Alert Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ session('info') }}</p>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h1 class="text-2xl font-bold text-white">Repair Quote</h1>
                        </div>
                        <p class="text-blue-100 text-sm">{{ $quoteRequest->manufacturer }} {{ $quoteRequest->model }}</p>
                    </div>
                    @php
                        $statusConfig = [
                            'quoted' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-300', 'label' => 'Awaiting Response'],
                            'approved' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-300', 'label' => 'Accepted'],
                            'declined' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-700 dark:text-red-300', 'label' => 'Declined'],
                        ];
                        $status = $statusConfig[$quoteRequest->status] ?? ['bg' => 'bg-zinc-100', 'text' => 'text-zinc-700', 'label' => ucfirst($quoteRequest->status)];
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 {{ $status['bg'] }} {{ $status['text'] }} rounded-full text-sm font-semibold">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                        {{ $status['label'] }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Quote Details -->
        <div class="space-y-6">
            <!-- Quoted Price -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 p-6 text-center">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-2">Estimated Repair Cost</p>
                <p class="text-5xl font-extrabold text-emerald-600 dark:text-emerald-400">₱{{ number_format($quoteRequest->quoted_price, 2) }}</p>
                @if($quoteRequest->quoted_at)
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-2">Quoted on {{ $quoteRequest->quoted_at->format('F d, Y') }}</p>
                @endif
            </div>

            <!-- Device Info -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 px-5 py-3 border-b border-purple-100 dark:border-purple-800">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Device Information
                    </h3>
                </div>
                <div class="p-5 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Manufacturer</p>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $quoteRequest->manufacturer }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Model</p>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $quoteRequest->model }}</p>
                    </div>
                </div>
            </div>

            <!-- Issue Description -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 px-5 py-3 border-b border-red-100 dark:border-red-800">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Issue Description
                    </h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $quoteRequest->issue_description }}</p>
                </div>
            </div>

            <!-- Technician Notes -->
            @if($quoteRequest->quote_notes)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 px-5 py-3 border-b border-amber-100 dark:border-amber-800">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Notes from Our Technician
                        </h3>
                    </div>
                    <div class="p-5">
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $quoteRequest->quote_notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            @if($quoteRequest->status === 'quoted')
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <p class="text-center text-sm text-zinc-600 dark:text-zinc-400 mb-4">Would you like to proceed with this repair?</p>
                    <div class="flex items-center justify-center gap-4">
                        <form action="{{ route('customer.portal.quote.accept', ['token' => $quoteRequest->portal_token]) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Accept Quote
                            </button>
                        </form>
                        <form action="{{ route('customer.portal.quote.decline', ['token' => $quoteRequest->portal_token]) }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Decline
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <!-- Back to Home -->
        <div class="mt-8 text-center">
            <a href="{{ route('home') }}" class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors">
                &larr; Back to Home
            </a>
        </div>
    </div>
</body>
</html>
