{{--
    Approve / Disapprove for a job order quote.

    $only limits it to one answer — the confirmation page the email buttons
    open shows just the button the customer tapped. Without it both are
    shown, with the disapprove reason tucked behind a <details> so the page
    needs no JavaScript.
--}}
@php($only = $only ?? null)

<div class="space-y-3">
    @if($only !== 'decline')
        <form action="{{ route('customer.portal.approve', ['token' => $jobOrder->portal_token]) }}" method="POST">
            @csrf
            <button
                type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-base font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $only === 'approve' ? 'Yes, Approve This Quote' : 'Approve Quote' }}
            </button>
        </form>
        <p class="text-xs text-center text-zinc-600 dark:text-zinc-400">
            By approving, you authorize us to proceed with the repair.
        </p>
    @endif

    @if($only === 'decline')
        <form action="{{ route('customer.portal.decline', ['token' => $jobOrder->portal_token]) }}" method="POST" class="space-y-3">
            @csrf
            <label for="reason" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">Reason <span class="font-normal text-zinc-500">(optional)</span></label>
            <textarea id="reason" name="reason" rows="3" maxlength="1000" placeholder="e.g. Too expensive, will just pick up the device"
                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-rose-500">{{ old('reason') }}</textarea>
            <button
                type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-rose-600 hover:bg-rose-700 text-white text-base font-bold rounded-xl shadow-lg transition-all duration-200 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Yes, Disapprove This Quote
            </button>
        </form>
    @elseif($only === null)
        <details class="group rounded-xl border border-rose-200 dark:border-rose-800">
            <summary class="list-none cursor-pointer w-full inline-flex items-center justify-center gap-2 px-6 py-3 text-rose-700 dark:text-rose-300 text-sm font-bold rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Disapprove Quote
            </summary>
            <form action="{{ route('customer.portal.decline', ['token' => $jobOrder->portal_token]) }}" method="POST" class="p-4 pt-1 space-y-3">
                @csrf
                <label for="reason" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Reason <span class="font-normal text-zinc-500">(optional)</span></label>
                <textarea id="reason" name="reason" rows="2" maxlength="1000" placeholder="e.g. Too expensive, will just pick up the device"
                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-rose-500"></textarea>
                <button type="submit" class="w-full px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-lg cursor-pointer">
                    Confirm Disapproval
                </button>
            </form>
        </details>
    @endif
</div>
