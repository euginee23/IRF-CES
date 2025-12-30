<?php

use App\Enums\JobOrderStatus;
use App\Models\JobOrder;
use App\Models\User;
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
    public $estimated_cost = '';
    public string $expected_completion_date = '';
    public $assigned_to = null;
    
    // Parts selection properties
    public string $partSearch = '';
    public string $partCategoryFilter = '';

    public function mount()
    {
        // Initialize with one empty service
        $this->services = [
            ['type' => '', 'diagnosis' => '']
        ];
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

    public function addPartToJob($partId, $partName)
    {
        // Check if part already added
        $existingIndex = array_search($partId, array_column($this->selectedParts, 'part_id'));
        
        if ($existingIndex !== false) {
            // Increment quantity if already exists
            $this->selectedParts[$existingIndex]['quantity']++;
        } else {
            // Add new part
            $this->selectedParts[] = [
                'part_id' => $partId,
                'part_name' => $partName,
                'quantity' => 1
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

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Create Job Order');
    }

    public function serviceTypes(): array
    {
        return [
            'Display & Input' => [
                'Screen Repair',
                'Touchscreen / Digitizer Repair',
                'Button Repair (Power / Volume / Home)',
                'Fingerprint / Face ID Repair',
                'Vibrator / Haptic Repair',
            ],
            'Power & Charging' => [
                'Battery Replacement',
                'Charging Port Repair',
            ],
            'Motherboard & Internal Components' => [
                'Motherboard / Logic Board Repair',
                'Soldering / Micro-Soldering',
                'SIM / SD Slot Repair',
                'Camera Repair',
                'Speaker / Microphone Repair',
            ],
            'Water & Physical Damage' => [
                'Water Damage Repair',
                'Physical Damage Repair',
            ],
            'Software & Firmware' => [
                'OS & Firmware Flashing',
                'Software Troubleshooting',
                'Unlocking / Rooting / Jailbreaking',
            ],
            'Data & Security' => [
                'Data Recovery / Backup',
            ],
            'Network & Diagnostics' => [
                'Network / Signal Diagnostics',
                'Diagnostics & Testing',
            ],
            'Other' => [
                'Other Repair',
            ],
        ];
    }

    public function manufacturers(): array
    {
        return [
            'Samsung', 'Apple', 'Xiaomi', 'Oppo', 'Vivo', 'Realme', 'Huawei',
            'Infinix', 'Tecno', 'Cherry Mobile', 'OnePlus', 'Honor', 'ZTE',
            'Lenovo', 'Meizu', 'Coolpad', 'TCL', 'Alcatel', 'Blackview',
            'Doogee', 'Elephone', 'Gionee', 'Ulefone', 'Umidigi', 'Leagoo',
            'Oukitel', 'Cubot', 'Bluboo', 'Vernee', 'Homtom', 'Gretel',
            'Leeco', 'Nubia', 'iQOO', 'Poco', 'Redmi', 'Black Shark',
            'Google', 'Motorola', 'Nokia', 'Sony', 'LG', 'HTC', 'Asus', 'Acer',
            'Itel', 'Lava', 'Micromax', 'Karbonn', 'Panasonic', 'Sharp',
            'Other',
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
        // Get available parts with search and filter
        $partsQuery = \App\Models\Part::where('in_stock', '>', 0)
            ->where('is_active', true);

        if ($this->partSearch) {
            $partsQuery->where(function($q) {
                $q->where('name', 'like', '%' . $this->partSearch . '%')
                  ->orWhere('sku', 'like', '%' . $this->partSearch . '%')
                  ->orWhere('description', 'like', '%' . $this->partSearch . '%');
            });
        }

        if ($this->partCategoryFilter) {
            $partsQuery->where('category', $this->partCategoryFilter);
        }

        $availableParts = $partsQuery->orderBy('name')->get();
        
        // Get unique categories for filter
        $categories = \App\Models\Part::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return [
            'manufacturers' => $this->manufacturers(),
            'technicians' => $this->technicians(),
            'serviceTypes' => $this->serviceTypes(),
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
            'estimated_cost' => 'nullable|numeric|min:0',
            'expected_completion_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // Convert empty string to null for expected_completion_date
        if (empty($validated['expected_completion_date'])) {
            $validated['expected_completion_date'] = null;
        }

        // Rename services to issues for database storage
        $validated['issues'] = $validated['services'];
        unset($validated['services']);

        // Store parts as JSON and enrich with current part details
        $validated['parts_needed'] = $validated['selectedParts'] ?? [];
        if (is_array($validated['parts_needed']) && count($validated['parts_needed']) > 0) {
            foreach ($validated['parts_needed'] as $i => $p) {
                $partModel = \App\Models\Part::find($p['part_id'] ?? null);
                if ($partModel) {
                    $validated['parts_needed'][$i]['part_name'] = $partModel->name;
                    $validated['parts_needed'][$i]['unit_sale_price'] = (float) $partModel->unit_sale_price;
                    $validated['parts_needed'][$i]['quantity'] = (int) ($p['quantity'] ?? 1);
                } else {
                    $validated['parts_needed'][$i]['part_name'] = $p['part_name'] ?? 'N/A';
                    $validated['parts_needed'][$i]['unit_sale_price'] = isset($p['unit_sale_price']) ? (float) $p['unit_sale_price'] : 0.0;
                    $validated['parts_needed'][$i]['quantity'] = (int) ($p['quantity'] ?? 1);
                }
            }
        }
        unset($validated['selectedParts']);

        $jobOrder = JobOrder::create(array_merge($validated, [
            'status' => JobOrderStatus::PENDING,
            'received_by' => auth()->id(),
        ]));

        $this->dispatch('success', message: 'Job order created successfully: ' . $jobOrder->job_order_number);
        $this->redirect(route('counter.job-orders'), navigate: true);
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-800 bg-clip-text text-transparent">Create Job Order</h1>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Fill in the details to create a new cellphone repair job order</p>
                </div>
                <a href="{{ route('counter.job-orders') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-4 py-2 border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-semibold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Job Orders
                </a>
            </div>
        </div>

        <form wire:submit="save">
            <!-- Customer Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">Customer Information</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-2">
                            <label for="customer_name" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Customer Name <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="customer_name" type="text" id="customer_name" 
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                placeholder="Enter customer full name"
                                required>
                            @error('customer_name') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="customer_phone" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="customer_phone" type="tel" id="customer_phone" 
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                placeholder="+63 912 345 6789"
                                required>
                            @error('customer_phone') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="customer_email" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Email Address
                            </label>
                            <input wire:model="customer_email" type="email" id="customer_email" 
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                placeholder="customer@example.com">
                            @error('customer_email') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="customer_address" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Address
                            </label>
                            <input wire:model="customer_address" type="text" id="customer_address" 
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                placeholder="Street, City, Province">
                            @error('customer_address') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Device Information -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">Device Information</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-2">
                            <label for="device_brand" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Brand/Manufacturer <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="device_brand" id="device_brand" 
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                required>
                                <option value="">Select brand</option>
                                @foreach($manufacturers as $manufacturer)
                                    <option value="{{ $manufacturer }}">{{ $manufacturer }}</option>
                                @endforeach
                            </select>
                            @error('device_brand') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="device_model" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Model <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="device_model" type="text" id="device_model" 
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                placeholder="e.g., Galaxy S24, iPhone 15 Pro"
                                required>
                            @error('device_model') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label for="serial_number" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Serial Number / IMEI
                            </label>
                            <input wire:model="serial_number" type="text" id="serial_number" 
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                placeholder="Optional">
                            @error('serial_number') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Details -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">Job Details</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <label for="issue_description" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Issue Description <span class="text-red-500">*</span>
                            </label>
                            <textarea wire:model="issue_description" id="issue_description" rows="3" 
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                placeholder="Describe the problem reported by the customer in detail..."
                                required></textarea>
                            @error('issue_description') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <!-- Services Repeater -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                    Identified Services Needed <span class="text-red-500">*</span>
                                </label>
                                <button type="button" wire:click="addService"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm hover:shadow cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Add Service
                                </button>
                            </div>

                            @foreach($services as $index => $service)
                                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label for="services_{{ $index }}_type" class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                                    Service Type <span class="text-red-500">*</span>
                                                </label>
                                                <select wire:model="services.{{ $index }}.type" id="services_{{ $index }}_type" 
                                                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                                    <option value="">Select service type</option>
                                                    @foreach($serviceTypes as $category => $types)
                                                        <optgroup label="{{ $category }}">
                                                            @foreach($types as $type)
                                                                <option value="{{ $type }}">{{ $type }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                                @error('services.' . $index . '.type') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                            </div>

                                            <div class="space-y-1">
                                                <label for="services_{{ $index }}_diagnosis" class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                                    Initial Diagnosis
                                                </label>
                                                <input wire:model="services.{{ $index }}.diagnosis" type="text" id="services_{{ $index }}_diagnosis" 
                                                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                                    placeholder="Optional diagnosis notes">
                                                @error('services.' . $index . '.diagnosis') <span class="text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                            </div>
                                        </div>

                                        @if(count($services) > 1)
                                            <button type="button" wire:click="removeService({{ $index }})"
                                                class="flex-shrink-0 p-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors cursor-pointer">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @error('services') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <!-- Parts Selection Component -->
                        <div class="space-y-4">
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Parts Required from Inventory
                            </label>

                            <!-- Selected Parts Summary -->
                            @if(count($selectedParts) > 0)
                                <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-xl border border-emerald-200 dark:border-emerald-800 p-4">
                                    <h4 class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-3 uppercase tracking-wide">Selected Parts</h4>
                                    <div class="space-y-2">
                                        @foreach($selectedParts as $part)
                                            <div class="flex items-center justify-between bg-white dark:bg-zinc-800 rounded-lg px-3 py-2 border border-emerald-200 dark:border-emerald-800">
                                                <div class="flex-1">
                                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $part['part_name'] }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <input type="number" min="1" value="{{ $part['quantity'] }}"
                                                        wire:change="updatePartQuantity({{ $part['part_id'] }}, $event.target.value)"
                                                        class="w-16 px-2 py-1 text-sm text-center border border-zinc-300 dark:border-zinc-700 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                                                    <button type="button" wire:click="removePartFromJob({{ $part['part_id'] }})"
                                                        class="p-1.5 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Search and Filter -->
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                    <div>
                                        <input type="text" wire:model.live.debounce.300ms="partSearch" placeholder="Search by name, SKU, or description..."
                                            class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    </div>
                                    <div>
                                        <select wire:model.live="partCategoryFilter"
                                            class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                            <option value="">All Categories</option>
                                            @foreach($partCategories as $category)
                                                <option value="{{ $category }}">{{ $category }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Parts Table -->
                                <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <table class="w-full text-sm">
                                        <thead class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300">Part Name</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300">SKU</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300">Category</th>
                                                <th class="px-3 py-2 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300">Stock</th>
                                                <th class="px-3 py-2 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                                            @forelse($availableParts as $part)
                                                @php
                                                    $isSelected = in_array($part->id, array_column($selectedParts, 'part_id'));
                                                @endphp
                                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors {{ $isSelected ? 'bg-emerald-50 dark:bg-emerald-900/10' : '' }}">
                                                    <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100 font-medium">{{ $part->name }}</td>
                                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $part->sku }}</td>
                                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $part->category ?? 'N/A' }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $part->in_stock > $part->reorder_point ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                                            {{ $part->in_stock }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 text-center">
                                                        @if($isSelected)
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded text-xs font-medium">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                                </svg>
                                                                Added
                                                            </span>
                                                        @else
                                                            <button type="button" wire:click="addPartToJob({{ $part->id }}, '{{ $part->name }}')"
                                                                class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded transition-colors">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                                </svg>
                                                                Add
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                                        No parts found matching your search criteria.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @error('selectedParts') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div class="space-y-2">
                                <label for="estimated_cost" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                    Estimated Cost (₱)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500 dark:text-zinc-400 font-medium">₱</span>
                                    <input wire:model="estimated_cost" type="number" id="estimated_cost" step="0.01" min="0"
                                        class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                        placeholder="0.00">
                                </div>
                                @error('estimated_cost') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="expected_completion_date" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                    Expected Completion
                                </label>
                                <input wire:model="expected_completion_date" type="date" id="expected_completion_date" 
                                    class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                @error('expected_completion_date') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="assigned_to" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                    Assign Technician
                                </label>
                                <select wire:model="assigned_to" id="assigned_to" 
                                    class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    <option value="">Unassigned</option>
                                    @foreach($technicians as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('assigned_to') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('counter.job-orders') }}" wire:navigate
                    class="px-6 py-3 border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 text-sm font-semibold rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all cursor-pointer">
                    Cancel
                </a>
                <button type="submit" 
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Job Order
                </button>
            </div>
        </form>
    </div>

    <!-- Notification Toast -->
    <x-notification-toast />
</div>
