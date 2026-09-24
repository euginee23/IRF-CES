{{--
    The "Contact Customer" composer.

    Included (not a component) so it inherits the host Livewire component's
    scope: the state and actions all live in App\Livewire\Concerns\ContactsCustomer,
    which the host mixes in. Include it once inside any component using that trait.
--}}
@php
    $contactRecordForView = $this->contactRecordForView();
    $smsAvailable = $this->contactSmsAvailable();
    $emailAvailable = $this->contactEmailAvailable();
    $phonePreview = $this->contactPhonePreview();
    $segment = $this->contactSegmentLength();
    $overhead = $this->contactSmsOverhead();
@endphp

@if($showContactModal && $contactRecordForView)
    <div class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="contact-modal-title">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeContactModal"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white dark:bg-zinc-800 rounded-xl shadow-2xl border border-zinc-200 dark:border-zinc-700">

                <!-- Header -->
                <div class="flex items-start justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <h3 id="contact-modal-title" class="text-xl font-bold text-zinc-900 dark:text-white">Contact Customer</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $contactRecordForView->contactName() }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeContactModal"
                        class="p-2 -m-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                        aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">

                    <!-- Channel -->
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Send by</label>
                        <div class="grid grid-cols-2 gap-3">
                            {{-- A channel with no contact detail on file is disabled rather than
                                 hidden, so staff can see why it is unavailable. --}}
                            <label @class([
                                'relative flex items-start gap-3 p-3 rounded-lg border transition-all',
                                'cursor-pointer hover:border-blue-400' => $emailAvailable,
                                'opacity-50 cursor-not-allowed' => ! $emailAvailable,
                                'border-blue-500 ring-2 ring-blue-500/30 bg-blue-50 dark:bg-blue-900/20' => $contactChannel === 'email',
                                'border-zinc-300 dark:border-zinc-700' => $contactChannel !== 'email',
                            ])>
                                <input
                                    type="radio"
                                    wire:model.live="contactChannel"
                                    value="email"
                                    @disabled(! $emailAvailable)
                                    class="mt-0.5 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed">
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-zinc-900 dark:text-white">Email</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                        {{ $contactRecordForView->contactEmail() ?: 'No email on file' }}
                                    </span>
                                </span>
                            </label>

                            <label @class([
                                'relative flex items-start gap-3 p-3 rounded-lg border transition-all',
                                'cursor-pointer hover:border-blue-400' => $smsAvailable,
                                'opacity-50 cursor-not-allowed' => ! $smsAvailable,
                                'border-blue-500 ring-2 ring-blue-500/30 bg-blue-50 dark:bg-blue-900/20' => $contactChannel === 'sms',
                                'border-zinc-300 dark:border-zinc-700' => $contactChannel !== 'sms',
                            ])>
                                <input
                                    type="radio"
                                    wire:model.live="contactChannel"
                                    value="sms"
                                    @disabled(! $smsAvailable)
                                    class="mt-0.5 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed">
                                <span class="min-w-0">
                                    <span class="flex items-center gap-1.5">
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">SMS</span>
                                        @if($contactNetworkUnreachable)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                                Unsupported
                                            </span>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400 truncate">
                                        {{ $phonePreview ?: 'No phone number on file' }}
                                    </span>
                                </span>
                            </label>
                        </div>

                        @if($contactChannel === 'sms')
                            @if($contactNetworkUnreachable)
                                {{-- Blocking, not advisory: the credit would be spent and the
                                     message would never arrive, and the history would then
                                     claim the customer had been told. --}}
                                <div class="mt-3 rounded-lg border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3">
                                    <div class="flex gap-2.5">
                                        <svg class="w-4 h-4 shrink-0 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        <div class="text-xs text-red-800 dark:text-red-200 leading-relaxed">
                                            <p class="font-semibold">
                                                {{ $contactNetwork }} is not supported — this SMS cannot be delivered.
                                            </p>
                                            <p class="mt-1">
                                                Smart, TNT and Sun only accept messages sent under a custom sender name
                                                approved by IPROG, and this account does not have one. Sending would use
                                                a credit and the customer would receive nothing.
                                            </p>
                                            <p class="mt-1.5 font-medium">
                                                @if($emailAvailable)
                                                    Use Email instead, or set SMS_DRIVER=routing to send these
                                                    through a provider that carries them.
                                                @else
                                                    This customer has no email either — you will need to phone them,
                                                    or set SMS_DRIVER=routing to reach this network.
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @elseif(! in_array(config('sms.default'), ['iprogsms', 'routing'], true))
                                <p class="mt-2 text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    SMS driver is "{{ config('sms.default') }}" — nothing will actually be delivered.
                                </p>
                            @else
                                <p class="mt-2 text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span>
                                        A real SMS will be sent and will use credits.
                                        @if($contactNetwork)
                                            <span class="text-zinc-500 dark:text-zinc-400">({{ $contactNetwork }})</span>
                                        @endif
                                    </span>
                                </p>
                            @endif
                        @endif
                    </div>

                    <!-- Template -->
                    <div>
                        <label for="contactTemplate" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                            Template <span class="text-xs font-normal text-zinc-400">(edit freely after picking)</span>
                        </label>
                        <select
                            id="contactTemplate"
                            wire:model.live="contactTemplate"
                            class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all cursor-pointer">
                            <option value="">Write from scratch</option>
                            @foreach($this->contactTemplateOptions() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Subject (email only) -->
                    @if($contactChannel === 'email')
                        <div>
                            <label for="contactSubject" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Subject</label>
                            <input
                                type="text"
                                id="contactSubject"
                                wire:model="contactSubject"
                                placeholder="e.g. Your repair quote is ready"
                                class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('contactSubject')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <!-- Body -->
                    <div>
                        <div class="flex items-baseline justify-between mb-2">
                            <label for="contactBody" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">Message</label>

                            @if($contactChannel === 'sms')
                                {{-- Live count, because length is what an SMS costs. The
                                     provider's sender name is counted in, since it is part
                                     of what gets billed. --}}
                                @php
                                    $length = mb_strlen($contactBody) + $overhead;
                                    $segments = (int) ceil(max($length, 1) / $segment);
                                @endphp
                                <span @class([
                                    'text-xs font-medium',
                                    'text-zinc-500 dark:text-zinc-400' => $segments <= 1,
                                    'text-amber-600 dark:text-amber-400' => $segments > 1,
                                ])>
                                    {{ $length }} / {{ $segment }} chars
                                    @if($segments > 1)
                                        · {{ $segments }} credits
                                    @endif
                                </span>
                            @endif
                        </div>

                        <textarea
                            id="contactBody"
                            wire:model{{ $contactChannel === 'sms' ? '.live' : '' }}="contactBody"
                            rows="{{ $contactChannel === 'sms' ? 4 : 9 }}"
                            placeholder="{{ $contactChannel === 'sms'
                                ? ($overhead > 0
                                    ? 'Keep it under ' . ($segment - $overhead) . ' characters for one credit.'
                                    : 'Keep it short — 160 characters is one credit.')
                                : 'Write your message to the customer…' }}"
                            class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-y"></textarea>

                        @error('contactBody')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- History -->
                    @php $history = $contactRecordForView->customerMessages()->with('sentBy')->take(5)->get(); @endphp
                    @if($history->isNotEmpty())
                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">
                                Previously sent
                            </h4>
                            <ul class="space-y-2.5 max-h-48 overflow-y-auto pr-1">
                                @foreach($history as $past)
                                    <li class="text-xs">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span @class([
                                                'px-1.5 py-0.5 rounded font-semibold uppercase tracking-wide',
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $past->isSms(),
                                                'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' => ! $past->isSms(),
                                            ])>{{ $past->channel }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400">
                                                {{ $past->created_at->format('d M Y, g:ia') }}
                                                @if($past->sentBy)
                                                    · by {{ $past->sentBy->name }}
                                                @endif
                                            </span>
                                        </div>
                                        <p class="mt-1 text-zinc-600 dark:text-zinc-400 line-clamp-2">{{ $past->body }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 p-6 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 rounded-b-xl">
                    <button
                        type="button"
                        wire:click="closeContactModal"
                        class="px-4 py-2.5 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="sendCustomerMessage"
                        wire:loading.attr="disabled"
                        wire:target="sendCustomerMessage"
                        @disabled((! $emailAvailable && ! $smsAvailable) || ($contactChannel === 'sms' && $contactNetworkUnreachable))
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:hover:bg-blue-600 disabled:cursor-not-allowed cursor-pointer">
                        <svg wire:loading.remove wire:target="sendCustomerMessage" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <svg wire:loading wire:target="sendCustomerMessage" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Send {{ $contactChannel === 'sms' ? 'SMS' : 'Email' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
