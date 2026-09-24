{{--
    A job order's history.

    Expects $timelineEvents (a collection of App\Models\JobOrderEvent) and
    optionally $timelineTitle. This is the internal view: it shows every
    event, including the ones hidden from the customer, and says who did it.
    The customer portal renders its own, filtered, version.

    An @include rather than a component so it reads the host Livewire
    component's scope, matching partials/contact-customer-modal.blade.php.
--}}
@php
    $timelineTitle ??= 'History';

    $iconFor = fn (string $type) => match ($type) {
        \App\Models\JobOrderEvent::TYPE_CREATED => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
        \App\Models\JobOrderEvent::TYPE_ASSIGNED => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        \App\Models\JobOrderEvent::TYPE_PAYMENT_RECEIVED => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
        \App\Models\JobOrderEvent::TYPE_NOTE => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
        default => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    };
@endphp

<div>
    @if($timelineTitle !== '')
        <h4 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide mb-4">
            {{ $timelineTitle }}
        </h4>
    @endif

    @if($timelineEvents->isEmpty())
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Nothing recorded yet.</p>
    @else
        <ol class="relative space-y-4 ps-6">
            {{-- The spine the markers sit on. --}}
            <span class="absolute start-[7px] top-1 bottom-1 w-px bg-zinc-200 dark:bg-zinc-700" aria-hidden="true"></span>

            @foreach($timelineEvents as $event)
                <li class="relative">
                    <span class="absolute -start-6 top-0.5 flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white dark:ring-zinc-800 {{ $event->to_status ? $event->to_status->badgeClasses() : 'bg-zinc-200 dark:bg-zinc-700' }}">
                        <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconFor($event->type) }}" />
                        </svg>
                    </span>

                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $event->description }}
                        </p>
                        <time class="text-xs text-zinc-500 dark:text-zinc-400" datetime="{{ $event->created_at->toIso8601String() }}">
                            {{ $event->created_at->format('d M Y, g:ia') }}
                        </time>
                    </div>

                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        @if($event->from_status && $event->to_status)
                            {{ $event->from_status->label() }} &rarr; {{ $event->to_status->label() }} &middot;
                        @endif

                        {{-- No user means the customer acted on the portal, which
                             is a materially different fact from staff doing it. --}}
                        {{ $event->user?->name ?? 'Customer' }}

                        @unless($event->is_customer_visible)
                            <span class="ms-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                Internal
                            </span>
                        @endunless
                    </p>
                </li>
            @endforeach
        </ol>
    @endif
</div>
