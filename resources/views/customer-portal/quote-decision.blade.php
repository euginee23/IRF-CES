<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <title>{{ $decision === 'approve' ? 'Approve' : 'Disapprove' }} Quote #{{ $jobOrder->job_order_number }} - {{ config('app.name') }}</title>
</head>
<body class="antialiased bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-zinc-900 dark:via-zinc-900 dark:to-indigo-950">

    <x-navbar />

    <div class="max-w-xl mx-auto px-4 sm:px-6 py-8 pt-24">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="{{ $decision === 'approve' ? 'bg-gradient-to-r from-emerald-600 to-teal-600' : 'bg-rose-600' }} px-6 py-5">
                <h1 class="text-xl font-bold text-white">
                    {{ $decision === 'approve' ? 'Approve your repair quote?' : 'Disapprove your repair quote?' }}
                </h1>
                <p class="mt-1 text-sm text-white/90">Job Order #{{ $jobOrder->job_order_number }}</p>
            </div>

            <div class="p-6 space-y-5">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Customer</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ $jobOrder->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Device</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ trim("{$jobOrder->device_brand} {$jobOrder->device_model}") }}</dd>
                    </div>
                    @if($jobOrder->expected_completion_date)
                        <div class="col-span-2">
                            <dt class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Expected completion</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white">{{ $jobOrder->expected_completion_date->format('F d, Y') }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
                    @foreach($jobOrder->services as $service)
                        <div class="flex justify-between gap-4 px-4 py-2">
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $service->service_name }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">₱{{ number_format($service->labor_price, 2) }}</span>
                        </div>
                    @endforeach
                    @foreach($jobOrder->parts as $part)
                        <div class="flex justify-between gap-4 px-4 py-2">
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $part->part_name }} @if($part->quantity > 1)<span class="text-zinc-500">× {{ $part->quantity }}</span>@endif</span>
                            <span class="font-medium text-zinc-900 dark:text-white">₱{{ number_format($part->lineTotal(), 2) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between gap-4 px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50">
                        <span class="font-semibold text-zinc-900 dark:text-white">Estimated Total</span>
                        <span class="text-lg font-bold text-zinc-900 dark:text-white">₱{{ number_format($estimatedTotal, 2) }}</span>
                    </div>
                </div>

                @include('customer-portal.partials.quote-decision-forms', ['jobOrder' => $jobOrder, 'only' => $decision])

                <div class="text-center text-sm">
                    @if($decision === 'approve')
                        <a href="{{ route('customer.portal.decline.confirm', ['token' => $jobOrder->portal_token]) }}" class="text-rose-600 dark:text-rose-400 hover:underline">I want to disapprove instead</a>
                    @else
                        <a href="{{ route('customer.portal.approve.confirm', ['token' => $jobOrder->portal_token]) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">I want to approve instead</a>
                    @endif
                    <span class="mx-2 text-zinc-400">·</span>
                    <a href="{{ $jobOrder->portal_url }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">View full details</a>
                </div>
            </div>
        </div>
    </div>

    <x-footer />

</body>
</html>
