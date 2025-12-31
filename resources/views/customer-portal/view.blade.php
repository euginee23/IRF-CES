<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <title>Job Order #{{ $jobOrder->job_order_number }} - {{ config('app.name') }}</title>
</head>
<body class="antialiased bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-zinc-900 dark:via-zinc-900 dark:to-indigo-950">
    
    <x-navbar />

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-24">
        
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

        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h1 class="text-2xl font-bold text-white">{{ $jobOrder->job_order_number }}</h1>
                        </div>
                        <p class="text-indigo-100 text-sm">Submitted on {{ $jobOrder->created_at->format('F d, Y') }}</p>
                    </div>
                    @php
                        $statusColors = [
                            'pending' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-700 dark:text-amber-300', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'assigned' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-700 dark:text-blue-300', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            'awaiting_approval' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-700 dark:text-yellow-300', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                            'approved' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-700 dark:text-emerald-300', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'in_progress' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-300', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                            'completed' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-700 dark:text-green-300', 'icon' => 'M5 13l4 4L19 7'],
                            'delivered' => ['bg' => 'bg-teal-100 dark:bg-teal-900/30', 'text' => 'text-teal-700 dark:text-teal-300', 'icon' => 'M5 13l4 4L19 7'],
                        ];
                        $statusStyle = $statusColors[$jobOrder->status->value] ?? ['bg' => 'bg-zinc-100', 'text' => 'text-zinc-700', 'icon' => 'M9 12h6'];
                    @endphp
                    <div class="inline-flex items-center gap-2 px-4 py-2 {{ $statusStyle['bg'] }} {{ $statusStyle['text'] }} rounded-full font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statusStyle['icon'] }}"/>
                        </svg>
                        {{ $jobOrder->status->label() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column - Details -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Customer Information -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 px-6 py-4 border-b border-blue-100 dark:border-blue-800">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Your Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Name</dt>
                                <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $jobOrder->customer_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Phone</dt>
                                <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $jobOrder->customer_phone }}</dd>
                            </div>
                            @if($jobOrder->customer_email)
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Email</dt>
                                <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $jobOrder->customer_email }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Device Information -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 px-6 py-4 border-b border-purple-100 dark:border-purple-800">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Device Information
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Brand</dt>
                                <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $jobOrder->device_brand }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Model</dt>
                                <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $jobOrder->device_model }}</dd>
                            </div>
                            @if($jobOrder->serial_number)
                            <div class="md:col-span-2">
                                <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Serial / IMEI</dt>
                                <dd class="text-sm font-medium text-zinc-900 dark:text-white font-mono">{{ $jobOrder->serial_number }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Issue Description -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 px-6 py-4 border-b border-red-100 dark:border-red-800">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Reported Issues
                        </h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $jobOrder->issue_description }}</p>
                    </div>
                </div>

                <!-- Services Required -->
                @if($jobOrder->issues && count($jobOrder->issues) > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 px-6 py-4 border-b border-indigo-100 dark:border-indigo-800">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Required Services
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($jobOrder->issues as $issue)
                                @php
                                    $dbService = null;
                                    if (!empty($issue['type'])) {
                                        $dbService = \App\Models\Service::where('name', $issue['type'])->first();
                                    }
                                @endphp
                                <div class="flex items-start justify-between gap-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $issue['type'] ?? 'N/A' }}</p>
                                        @if(!empty($issue['diagnosis']))
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $issue['diagnosis'] }}</p>
                                        @endif
                                    </div>
                                    @if($dbService)
                                        <div class="text-sm font-semibold text-zinc-900 dark:text-white">₱{{ number_format($dbService->labor_price, 2) }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Parts Needed -->
                @if($jobOrder->parts_needed && count($jobOrder->parts_needed) > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 px-6 py-4 border-b border-emerald-100 dark:border-emerald-800">
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Required Parts
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                        <th class="text-left py-3 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Part</th>
                                        <th class="text-center py-3 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Qty</th>
                                        <th class="text-right py-3 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Price</th>
                                        <th class="text-right py-3 text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach($jobOrder->parts_needed as $part)
                                        @php
                                            $partName = $part['part_name'] ?? 'N/A';
                                            $unitPrice = isset($part['unit_sale_price']) ? (float)$part['unit_sale_price'] : 0;
                                            $qty = isset($part['quantity']) ? (int)$part['quantity'] : 1;
                                        @endphp
                                        <tr>
                                            <td class="py-3 font-medium text-zinc-900 dark:text-white">{{ $partName }}</td>
                                            <td class="py-3 text-center text-zinc-700 dark:text-zinc-300">{{ $qty }}</td>
                                            <td class="py-3 text-right text-zinc-700 dark:text-zinc-300">₱{{ number_format($unitPrice, 2) }}</td>
                                            <td class="py-3 text-right font-semibold text-zinc-900 dark:text-white">₱{{ number_format($unitPrice * $qty, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Cost Summary & Actions -->
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-6">
                    <!-- Cost Summary -->
                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-2xl shadow-md border border-emerald-200 dark:border-emerald-800 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Cost Summary
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center pb-3 border-b border-emerald-200 dark:border-emerald-800">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Parts</span>
                                <span class="text-lg font-bold text-zinc-900 dark:text-white">₱{{ number_format($partsTotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center pb-3 border-b border-emerald-200 dark:border-emerald-800">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">Labor</span>
                                <span class="text-lg font-bold text-zinc-900 dark:text-white">₱{{ number_format($laborTotal, 2) }}</span>
                            </div>
                            <div class="pt-3 border-t-2 border-emerald-300 dark:border-emerald-700">
                                <div class="flex justify-between items-center">
                                    <span class="text-base font-semibold text-emerald-700 dark:text-emerald-400">Estimated Total</span>
                                    <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">₱{{ number_format($estimatedTotal, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        @if($jobOrder->status->value === 'awaiting_approval')
                            <div class="mt-6 space-y-3">
                                <form action="{{ route('customer.portal.approve', ['token' => $jobOrder->portal_token]) }}" method="POST">
                                    @csrf
                                    <button 
                                        type="submit"
                                        onclick="return confirm('Are you sure you want to approve this repair quote? We will begin work once approved.')"
                                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-base font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-[1.02]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Approve Quote
                                    </button>
                                </form>
                                <p class="text-xs text-center text-zinc-600 dark:text-zinc-400">
                                    By approving, you authorize us to proceed with the repair.
                                </p>
                            </div>
                        @elseif($jobOrder->status->value === 'approved')
                            <div class="mt-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200 mb-1">Quote Approved</p>
                                        <p class="text-xs text-emerald-700 dark:text-emerald-300">
                                            @if($jobOrder->isApprovedByCustomer())
                                                You approved this quote on {{ $jobOrder->approved_by_customer_at->format('F d, Y \a\t g:i A') }}.
                                            @else
                                                This quote has been approved. We're working on your repair.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($jobOrder->expected_completion_date)
                            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs font-semibold text-blue-900 dark:text-blue-200 mb-1">Expected Completion</p>
                                        <p class="text-sm font-bold text-blue-700 dark:text-blue-300">{{ $jobOrder->expected_completion_date->format('F d, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                    <!-- Help Section -->
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md border border-zinc-200 dark:border-zinc-700 p-6">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Need Help?
                    </h3>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-3">
                        If you have questions about your repair or quote, please contact us.
                    </p>
                    <div class="space-y-2 text-xs">
                        @if($jobOrder->customer_phone)
                            <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span>Call: {{ $jobOrder->customer_phone }}</span>
                            </div>
                        @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <x-footer />

</body>
</html>
