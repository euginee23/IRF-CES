{{--
    Services on a job order: what is booked, plus a search to add more.

    Shared by the create and edit screens. Expects the component's
    $services, $serviceResults and $servicePrices, and its serviceSearch,
    addServiceToJob() and removeService().
--}}
<div>
    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Services <span class="text-red-500">*</span></label>

    @if(count($services) > 0)
        <div class="space-y-2 mb-3">
            @foreach($services as $index => $service)
                <div wire:key="service-line-{{ $index }}-{{ $service['type'] }}" class="flex items-center justify-between gap-2 p-2 bg-indigo-50 dark:bg-indigo-900/10 rounded-lg border border-indigo-200 dark:border-indigo-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $service['type'] }}</span>
                    <div class="flex items-center gap-3">
                        @if(isset($servicePrices[$service['type']]))
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">₱{{ number_format($servicePrices[$service['type']], 2) }}</span>
                        @endif
                        <button type="button" wire:click="removeService({{ $index }})" class="text-red-600 hover:text-red-700 cursor-pointer" title="Remove service">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <input type="text" wire:model.live.debounce.300ms="serviceSearch" placeholder="Search services (e.g. screen, battery)..." class="w-full mb-2 px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900">

    <div class="max-h-48 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
        <table class="w-full text-xs">
            <thead class="bg-zinc-100 dark:bg-zinc-900 sticky top-0">
                <tr>
                    <th class="px-2 py-1.5 text-left font-semibold">Service</th>
                    <th class="px-2 py-1.5 text-right font-semibold">Labor</th>
                    <th class="px-2 py-1.5 text-center font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @php($bookedServices = array_column($services, 'type'))
                @forelse($serviceResults as $svc)
                    @php($isBooked = in_array($svc->name, $bookedServices, true))
                    <tr wire:key="service-result-{{ $svc->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 {{ $isBooked ? 'bg-indigo-50 dark:bg-indigo-900/10' : '' }}">
                        <td class="px-2 py-1.5">
                            {{ $svc->name }}
                            <span class="block text-[10px] text-zinc-500 dark:text-zinc-400">{{ $svc->category }}</span>
                        </td>
                        <td class="px-2 py-1.5 text-right">₱{{ number_format($svc->labor_price, 2) }}</td>
                        <td class="px-2 py-1.5 text-center">
                            @if($isBooked)
                                <span class="text-emerald-600 text-xs">✓ Added</span>
                            @else
                                <button type="button" wire:click="addServiceToJob({{ $svc->id }})" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium cursor-pointer">+ Add</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-2 py-4 text-center text-zinc-400">No services found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @error('services') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
</div>
