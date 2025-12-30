<?php

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use App\Models\User;
use App\Models\Service;
use App\Models\Part;
use App\Enums\Role;
use Livewire\Volt\Component;

new class extends Component {
    public string $customer_name = '';
    public string $customer_email = '';
    public string $customer_phone = '';
    public string $customer_address = '';
    public string $device_brand = '';
    public string $device_model = '';
    public string $serial_number = '';
    public string $issue_description = '';
    public array $services = []; 
    public array $selectedParts = []; 
    public string $expected_completion_date = '';
    public $assigned_to = null;
    
    // Parts selection properties
    public string $partSearch = '';
    public string $partCategoryFilter = '';

    public function mount()
    {
        $this->services = [['type' => '', 'diagnosis' => '']];
        $this->selectedParts = [];
    }

    public function addService()
    {
        $this->services[] = ['type' => '', 'diagnosis' => ''];
    }

    public function removeService($index)
    {
        if (count($this->services) > 1) {
            unset($this->services[$index]);
            $this->services = array_values($this->services);
        }
    }

    public function addPartToJob($partId)
    {
        $part = Part::find($partId);
        if (!$part) return;

        $existingIndex = array_search($partId, array_column($this->selectedParts, 'part_id'));
        
        if ($existingIndex !== false) {
            $this->selectedParts[$existingIndex]['quantity']++;
        } else {
            $this->selectedParts[] = [
                'part_id' => $partId,
                'part_name' => $part->name,
                'quantity' => 1,
                'unit_sale_price' => $part->unit_sale_price,
            ];
        }
    }

    public function removePartFromJob($partId)
    {
        $this->selectedParts = array_values(
            array_filter($this->selectedParts, fn($part) => $part['part_id'] !== $partId)
        );
    }

    public function updatePartQuantity($partId, $quantity)
    {
        foreach ($this->selectedParts as $index => $part) {
            if ($part['part_id'] == $partId) {
                $this->selectedParts[$index]['quantity'] = max(1, (int)$quantity);
                break;
            }
        }
    }

    public function getEstimatedCostProperty()
    {
        $serviceTotal = 0;
        $partsTotal = 0;

        // Calculate services total from database labor_price
        foreach ($this->services as $service) {
            if (!empty($service['type'])) {
                $dbService = Service::where('name', $service['type'])->first();
                if ($dbService) {
                    $serviceTotal += $dbService->labor_price;
                }
            }
        }

        // Calculate parts total from selected parts
        foreach ($this->selectedParts as $part) {
            $partsTotal += ($part['unit_sale_price'] ?? 0) * ($part['quantity'] ?? 1);
        }

        return $serviceTotal + $partsTotal;
    }

    public function getJobOrderNumberPreviewProperty()
    {
        $count = JobOrder::whereDate('created_at', today())->count() + 1;
        return 'JO-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Create Job Order');
    }

    public function manufacturers(): array
    {
        return [
            'Samsung', 'Apple', 'Xiaomi', 'Oppo', 'Vivo', 'Realme', 'Huawei',
            'Infinix', 'Tecno', 'Cherry Mobile', 'OnePlus', 'Honor', 'Other',
        ];
    }

    public function technicians(): array
    {
        return User::where('role', Role::TECHNICIAN)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function with(): array
    {
        $partsQuery = Part::where('in_stock', '>', 0)->where('is_active', true);

        if ($this->partSearch) {
            $partsQuery->where(function($q) {
                $q->where('name', 'like', '%' . $this->partSearch . '%')
                  ->orWhere('sku', 'like', '%' . $this->partSearch . '%');
            });
        }

        if ($this->partCategoryFilter) {
            $partsQuery->where('category', $this->partCategoryFilter);
        }

        $availableParts = $partsQuery->orderBy('name')->get();
        
        $categories = Part::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return [
            'manufacturers' => $this->manufacturers(),
            'technicians' => $this->technicians(),
            'servicesGrouped' => Service::getGroupedByCategory(),
            'availableParts' => $availableParts,
            'partCategories' => $categories,
        ];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'required|string|max:255',
            'customer_address' => 'nullable|string',
            'device_brand' => 'required|string|max:255',
            'device_model' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'issue_description' => 'required|string',
            'services' => 'required|array|min:1',
            'services.*.type' => 'required|string',
            'services.*.diagnosis' => 'nullable|string',
            'selectedParts' => 'nullable|array',
            'selectedParts.*.part_id' => 'required|exists:parts,id',
            'selectedParts.*.quantity' => 'required|integer|min:1',
            'expected_completion_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if (empty($validated['expected_completion_date'])) {
            $validated['expected_completion_date'] = null;
        }

        $validated['issues'] = $validated['services'];
        unset($validated['services']);

        $validated['parts_needed'] = $validated['selectedParts'] ?? [];
        unset($validated['selectedParts']);

        $validated['estimated_cost'] = $this->estimated_cost;

        $jobOrder = JobOrder::create(array_merge($validated, [
            'status' => JobOrderStatus::PENDING,
            'received_by' => auth()->id(),
        ]));

        $this->dispatch('success', message: 'Job order created successfully: ' . $jobOrder->job_order_number);
        $this->redirect(route('counter.job-orders'), navigate: true);
    }
}; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- LEFT: Quote/Receipt -->
    <div class="lg:sticky lg:top-20 lg:z-30 h-fit">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Job Order Quote
                </h2>
            </div>

            <div class="p-4 space-y-4">
                <!-- Customer Info -->
                <div class="pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400 font-semibold mb-1">Customer</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $customer_name ?: '—' }}</p>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $customer_phone ?: '—' }}</p>
                </div>

                <!-- Device Info -->
                <div class="pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400 font-semibold mb-1">Device</p>
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">{{ $device_brand ?: '—' }} {{ $device_model ?: '' }}</p>
                    @if($serial_number)
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">S/N: {{ $serial_number }}</p>
                    @endif
                </div>

                <!-- Services -->
                <div class="pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400 font-semibold mb-2">Services</p>
                    <div class="space-y-1.5">
                        @forelse($services as $service)
                            @if(!empty($service['type']))
                                @php
                                    $dbService = \App\Models\Service::where('name', $service['type'])->first();
                                @endphp
                                <div class="flex justify-between items-start text-sm">
                                    <span class="text-zinc-700 dark:text-zinc-300 flex-1">{{ $service['type'] }}</span>
                                    @if($dbService)
                                        <span class="font-semibold text-zinc-900 dark:text-white">₱{{ number_format($dbService->labor_price, 2) }}</span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </div>
                            @endif
                        @empty
                            <p class="text-xs text-zinc-400 italic">No services selected</p>
                        @endforelse
                    </div>
                </div>

                <!-- Parts -->
                <div class="pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <p class="text-xs uppercase text-zinc-500 dark:text-zinc-400 font-semibold mb-2">Parts</p>
                    <div class="space-y-1.5">
                        @forelse($selectedParts as $part)
                            <div class="flex justify-between items-start text-sm">
                                <span class="text-zinc-700 dark:text-zinc-300 flex-1">{{ $part['part_name'] }} <span class="text-xs text-zinc-500">(x{{ $part['quantity'] }})</span></span>
                                <span class="font-semibold text-zinc-900 dark:text-white">₱{{ number_format($part['unit_sale_price'] * $part['quantity'], 2) }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-zinc-400 italic">No parts selected</p>
                        @endforelse
                    </div>
                </div>

                <!-- Total -->
                <div class="pt-2">
                    <div class="flex justify-between items-center p-3 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg">
                        <span class="text-sm font-bold text-zinc-900 dark:text-white uppercase">Estimated Total</span>
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">₱{{ number_format($this->estimated_cost, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Form -->
    <div class="lg:col-span-2">
        <form wire:submit="save" class="space-y-4">
            <!-- Job Order Preview (read-only) -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden px-4 py-3">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Job Order No.</label>
                <input type="text" value="{{ $this->jobOrderNumberPreview }}" disabled class="w-full px-3 py-2 text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-white">
            </div>
            <!-- Customer Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Customer Information
                    </h3>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input wire:model.blur="customer_name" type="text" placeholder="Juan Dela Cruz" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500" required>
                        @error('customer_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Phone <span class="text-red-500">*</span></label>
                        <input wire:model.blur="customer_phone" type="tel" placeholder="09xxxxxxxxx" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500" required>
                        @error('customer_phone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Email</label>
                        <input wire:model="customer_email" type="email" placeholder="email@example.com" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Address</label>
                        <input wire:model="customer_address" type="text" placeholder="Street, City, Province" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Device Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Device Information
                    </h3>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Brand <span class="text-red-500">*</span></label>
                        <select wire:model.live="device_brand" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">Select</option>
                            @foreach($manufacturers as $manufacturer)
                                <option value="{{ $manufacturer }}">{{ $manufacturer }}</option>
                            @endforeach
                        </select>
                        @error('device_brand') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Model <span class="text-red-500">*</span></label>
                        <input wire:model.live="device_model" type="text" placeholder="Model (e.g., Galaxy S21)" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500" required>
                        @error('device_model') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Serial / IMEI</label>
                        <input wire:model="serial_number" type="text" placeholder="Serial or IMEI" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Issue & Services -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Issue & Services
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Issue Description <span class="text-red-500">*</span></label>
                        <textarea wire:model="issue_description" rows="2" placeholder="Describe the issue in detail (symptoms, when it started, any error messages)" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500" required></textarea>
                        @error('issue_description') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Services <span class="text-red-500">*</span></label>
                            <button type="button" wire:click="addService" class="text-xs px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 cursor-pointer">+ Add</button>
                        </div>
                        <div class="space-y-2">
                            @foreach($services as $index => $service)
                                <div class="flex gap-2">
                                    <select wire:model.live="services.{{ $index }}.type" class="flex-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900">
                                        <option value="">Select service</option>
                                        @foreach($servicesGrouped as $category => $serviceList)
                                            <optgroup label="{{ $category }}">
                                                @foreach($serviceList as $svc)
                                                    <option value="{{ $svc->name }}">{{ $svc->name }} (₱{{ number_format($svc->labor_price, 2) }})</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @if(count($services) > 1)
                                        <button type="button" wire:click="removeService({{ $index }})" class="px-2 py-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @error('services') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Parts -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Parts Required</h3>
                </div>
                <div class="p-4 space-y-3">
                    @if(count($selectedParts) > 0)
                        <div class="space-y-2 mb-3">
                            @foreach($selectedParts as $part)
                                <div class="flex items-center justify-between p-2 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $part['part_name'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">Quantity:</span>
                                        <input type="number" min="1" value="{{ $part['quantity'] }}" wire:change="updatePartQuantity({{ $part['part_id'] }}, $event.target.value)" class="w-14 px-2 py-1 text-xs text-center border border-zinc-300 dark:border-zinc-700 rounded bg-white dark:bg-zinc-900">
                                        <button type="button" wire:click="removePartFromJob({{ $part['part_id'] }})" class="text-red-600 hover:text-red-700 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <input type="text" wire:model.live.debounce.300ms="partSearch" placeholder="Search parts..." class="px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900">
                        <select wire:model.live="partCategoryFilter" class="px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900">
                            <option value="">All Categories</option>
                            @foreach($partCategories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="max-h-60 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <table class="w-full text-xs">
                            <thead class="bg-zinc-100 dark:bg-zinc-900 sticky top-0">
                                <tr>
                                    <th class="px-2 py-1.5 text-left font-semibold">Part</th>
                                    <th class="px-2 py-1.5 text-center font-semibold">Stock</th>
                                    <th class="px-2 py-1.5 text-center font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @forelse($availableParts as $part)
                                    @php $isSelected = in_array($part->id, array_column($selectedParts, 'part_id')); @endphp
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 {{ $isSelected ? 'bg-emerald-50 dark:bg-emerald-900/10' : '' }}">
                                        <td class="px-2 py-1.5">{{ $part->name }}</td>
                                        <td class="px-2 py-1.5 text-center">{{ $part->in_stock }}</td>
                                        <td class="px-2 py-1.5 text-center">
                                            @if($isSelected)
                                                <span class="text-emerald-600 text-xs">✓ Added</span>
                                            @else
                                                <button type="button" wire:click="addPartToJob({{ $part->id }})" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium cursor-pointer">+ Add</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-2 py-4 text-center text-zinc-400">No parts found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Assignment -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-md border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="bg-zinc-50 dark:bg-zinc-900/50 px-4 py-2.5 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Assignment</h3>
                </div>
                <div class="p-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Expected Completion</label>
                        <input wire:model="expected_completion_date" type="date" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Assign Technician</label>
                        <select wire:model="assigned_to" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 focus:ring-2 focus:ring-indigo-500">
                            <option value="">Unassigned</option>
                            @foreach($technicians as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('counter.job-orders') }}" wire:navigate class="px-4 py-2 border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-semibold rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow-lg transition">Create Job Order</button>
            </div>
        </form>
    </div>
</div>
