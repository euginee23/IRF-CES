<?php

use App\Models\Part;
use App\Models\Supplier;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public bool $showLowStock = false;
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?Part $selectedPart = null;
    public bool $showRestockModal = false;
    public ?int $restockPartId = null;
    public string $restockNotes = '';

    // Form fields
    public string $name = '';
    public string $sku = '';
    public string $category = '';
    public string $description = '';
    public $in_stock = null;
    public $reorder_point = null;
    public $unit_price = null;
    public string $supplier = '';
    public string $manufacturer = '';
    public string $model = '';
    public bool $is_active = true;

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Parts Inventory');
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
            'Fujitsu', 'Kyocera', 'Casio', 'NEC', 'Toshiba', 'Siemens',
            'Philips', 'BLU', 'Cat', 'Emporia', 'Fairphone', 'Wiko', 'Archos',
            'Crosscall', 'Gigaset', 'Energizer', 'Plum', 'Vertu', 'Point Mobile',
            'Honeywell', 'Zebra', 'Sonim', 'Kyocera', 'Caterpillar', 'Bullitt',
            'Ruggear', 'AGM', 'Conquest', 'Runbo', 'Thuraya', 'Inmarsat',
            'Iridium', 'Globalstar', 'Other',
        ];
    }

    public function suppliers(): array
    {
        return Supplier::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function with(): array
    {
        $query = Part::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('supplier', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->showLowStock) {
            $query->whereRaw('in_stock <= reorder_point');
        }

        $parts = $query->latest()->paginate(15);
        $categories = collect([
            'Display & Input Components',
            'Power & Charging Components',
            'Motherboard & Core Components',
            'Camera & Audio Components',
            'Network & Connectivity Components',
            'Sensors & Security Components',
            'Structural & Physical Components',
            'Accessories & External Parts',
        ]);
        $lowStockCount = Part::whereRaw('in_stock <= reorder_point')->count();

        return [
            'parts' => $parts,
            'categories' => $categories,
            'lowStockCount' => $lowStockCount,
            'suppliers' => $this->suppliers(),
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->selectedPart = Part::findOrFail($id);
        $this->name = $this->selectedPart->name;
        $this->sku = $this->selectedPart->sku;
        $this->category = $this->selectedPart->category ?? '';
        $this->description = $this->selectedPart->description ?? '';
        $this->in_stock = $this->selectedPart->in_stock;
        $this->reorder_point = $this->selectedPart->reorder_point;
        $this->unit_price = $this->selectedPart->unit_price;
        $this->supplier = $this->selectedPart->supplier ?? '';
        $this->manufacturer = $this->selectedPart->manufacturer ?? '';
        $this->model = $this->selectedPart->model ?? '';
        $this->is_active = $this->selectedPart->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:parts,sku,' . ($this->selectedPart->id ?? 'NULL'),
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'in_stock' => 'required|integer|min:1',
            'reorder_point' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0.01',
            'supplier' => 'nullable|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($this->isEditing && $this->selectedPart) {
            $this->selectedPart->update($validated);
            $message = 'Part updated successfully.';
        } else {
            Part::create($validated);
            $message = 'Part created successfully.';
        }

        $this->closeModal();
        $this->dispatch('success', message: $message);
    }

    public function delete(int $id): void
    {
        $part = Part::findOrFail($id);
        $part->delete();
        $this->dispatch('success', message: 'Part deleted successfully.');
    }

    public function adjustStock(int $id, string $type): void
    {
        $part = Part::findOrFail($id);
        
        if ($type === 'add') {
            $part->addStock(1);
        } elseif ($type === 'subtract' && $part->in_stock > 0) {
            $part->deductStock(1);
        }
    }

    public function openRestockModal(int $partId): void
    {
        $part = Part::findOrFail($partId);
        
        // Check if part has a supplier
        if (!$part->supplier) {
            $this->dispatch('error', message: 'This part does not have a supplier assigned.');
            return;
        }

        // Find the supplier by name
        $supplier = Supplier::where('name', $part->supplier)->first();
        
        if (!$supplier) {
            $this->dispatch('error', message: 'Supplier not found in the database.');
            return;
        }

        if (!$supplier->email) {
            $this->dispatch('error', message: 'Supplier does not have an email address.');
            return;
        }

        $this->restockPartId = $partId;
        $this->restockNotes = '';
        $this->showRestockModal = true;
    }

    public function confirmRestock(): void
    {
        if (!$this->restockPartId) {
            return;
        }

        $part = Part::findOrFail($this->restockPartId);
        $supplier = Supplier::where('name', $part->supplier)->first();
        
        // Calculate requested quantity (double the reorder point minus current stock)
        $requestedQuantity = max(1, ($part->reorder_point * 2) - $part->in_stock);

        try {
            \Mail::to($supplier->email)->send(new \App\Mail\RestockRequestMail($part, $supplier, $requestedQuantity, $this->restockNotes));
            $this->dispatch('success', message: 'Restock request email sent to ' . $supplier->name . ' successfully!');
            $this->closeRestockModal();
        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function closeRestockModal(): void
    {
        $this->showRestockModal = false;
        $this->restockPartId = null;
        $this->restockNotes = '';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->selectedPart = null;
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->sku = '';
        $this->category = '';
        $this->description = '';
        $this->in_stock = null;
        $this->reorder_point = null;
        $this->unit_price = null;
        $this->supplier = '';
        $this->manufacturer = '';
        $this->model = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedShowLowStock(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <div class="space-y-6">  

        <!-- Header -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Parts Inventory</h1>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Manage spare parts stock and reorder points</p>
                </div>
            </div>

            <!-- Stats Cards with Modern Design -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Parts Card -->
                <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/30 rounded-full">Active</span>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Parts</p>
                            <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $parts->total() }}</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">items in inventory</p>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Card -->
                <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-orange-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl shadow-md">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            @if($lowStockCount > 0)
                                <span class="px-3 py-1 text-xs font-semibold text-orange-700 bg-orange-100 dark:text-orange-300 dark:bg-orange-900/30 rounded-full animate-pulse">Alert</span>
                            @endif
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Low Stock Items</p>
                            <p class="text-4xl font-bold text-orange-600 dark:text-orange-400">{{ $lowStockCount }}</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">need reordering soon</p>
                        </div>
                    </div>
                </div>

                <!-- Total Value Card -->
                <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-3 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl shadow-md">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Value</p>
                            <p class="text-4xl font-bold text-emerald-600 dark:text-emerald-400">
                                ₱{{ number_format($parts->sum(fn($p) => $p->in_stock * $p->unit_price), 2) }}
                            </p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">current inventory worth</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters Section -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">Filters</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-2">
                            <label for="search" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Search
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    id="search"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="Search by name, SKU, supplier..."
                                    class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="categoryFilter" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Filter by Category
                            </label>
                            <div class="relative">
                                <select
                                    id="categoryFilter"
                                    wire:model.live="categoryFilter"
                                    class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all appearance-none cursor-pointer"
                                >
                                    <option value="">All Categories</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Quick Filter
                            </label>
                            <label class="flex items-center gap-3 px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 cursor-pointer hover:border-orange-400 dark:hover:border-orange-500 transition-all group">
                                <input type="checkbox" wire:model.live="showLowStock" class="w-5 h-5 text-orange-600 bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-2 focus:ring-orange-500 focus:ring-offset-0 transition-all">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">Show Low Stock Only</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Parts Table -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">Parts List</h3>
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $parts->total() }} total items</span>
                        </div>
                        <button 
                            type="button"
                            wire:click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Part
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-100 dark:bg-zinc-800/70 border-b-2 border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Part Details</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Manufacturer/Model</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Supplier</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/50">
                            @forelse($parts as $part)
                                <tr class="group hover:bg-blue-50/50 dark:hover:bg-blue-950/20 transition-all duration-150">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-transform">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-base font-bold text-zinc-900 dark:text-white">{{ $part->name }}</div>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded">{{ $part->sku }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="space-y-1">
                                            @if($part->manufacturer)
                                                <div class="flex items-center gap-1.5 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                    <span>{{ $part->manufacturer }}</span>
                                                </div>
                                            @endif
                                            @if($part->model)
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $part->model }}</div>
                                            @endif
                                            @if(!$part->manufacturer && !$part->model)
                                                <span class="text-xs text-zinc-400 dark:text-zinc-500">N/A</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        @if($part->category)
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-gradient-to-r from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700">
                                                {{ $part->category }}
                                            </span>
                                        @else
                                            <span class="text-xs text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1">
                                                    <div class="text-base font-bold {{ $part->isLowStock() ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                        {{ $part->in_stock }} units
                                                    </div>
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                        Reorder at: {{ $part->reorder_point }}
                                                    </div>
                                                </div>
                                                @if($part->isLowStock())
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-red-500 to-orange-500 text-white shadow-lg shadow-red-500/30 animate-pulse">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/>
                                                        </svg>
                                                        Low
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-lg shadow-emerald-500/20">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                                        </svg>
                                                        OK
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="relative w-full h-2 bg-zinc-200 dark:bg-zinc-800 rounded-full overflow-hidden">
                                                <div class="absolute inset-y-0 left-0 {{ $part->isLowStock() ? 'bg-gradient-to-r from-red-500 to-orange-500' : 'bg-gradient-to-r from-emerald-500 to-green-500' }} rounded-full transition-all duration-300"
                                                    style="width: {{ min(100, ($part->in_stock / max($part->reorder_point, 1)) * 100) }}%">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="space-y-1">
                                            <div class="text-base font-bold text-zinc-900 dark:text-white">₱{{ number_format($part->unit_price, 2) }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Total: <span class="font-semibold text-emerald-600 dark:text-emerald-400">₱{{ number_format($part->in_stock * $part->unit_price, 2) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $part->supplier ?: 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button 
                                                    type="button"
                                                    wire:click="openEditModal({{ $part->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button 
                                                    type="button"
                                                    @click="$dispatch('open-delete-dialog', {
                                                        title: 'Delete Part',
                                                        message: 'Are you sure you want to delete {{ addslashes($part->name) }}? This action cannot be undone.',
                                                        confirmText: 'Delete',
                                                        cancelText: 'Cancel',
                                                    callback: () => $wire.delete({{ $part->id }})
                                                })"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                        @if($part->supplier && $part->isLowStock())
                                            <button 
                                                type="button"
                                                wire:click="openRestockModal({{ $part->id }})"
                                                class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer"
                                                title="Request restock from supplier">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                                Request Restock
                                            </button>
                                        @endif
                                    </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center space-y-4">
                                            <div class="p-4 bg-zinc-100 dark:bg-zinc-800 rounded-2xl">
                                                <svg class="w-16 h-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-base font-semibold text-zinc-900 dark:text-white">No parts found</p>
                                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Try adjusting your search or filters</p>
                                            </div>
                                            <button 
                                                type="button"
                                                wire:click="openCreateModal"
                                                class="mt-2 inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                Add Your First Part
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($parts->hasPages())
                    <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-800">
                        {{ $parts->links() }}
                    </div>
                @endif
            </div>

        <!-- Redesigned Create/Edit Modal with Animations -->
        <div 
            x-data="{ show: @entangle('showModal') }"
            x-show="show"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
        >
            <!-- Backdrop with blur -->
                <div 
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    wire:click="closeModal"
                    class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity"
                ></div>

            <!-- Modal Dialog -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div 
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-3xl w-full border border-zinc-200 dark:border-zinc-800 overflow-hidden transform transition-all"
                >
                        
                        <!-- Compact Modal Header -->
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-white">
                                    {{ $isEditing ? 'Edit Part' : 'Add New Part' }}
                                </h3>
                            </div>
                            <button 
                                wire:click="closeModal" 
                                class="p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form wire:submit="save" class="p-6">
                            <!-- Basic Information Section -->
                            <div class="mb-6">
                                <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Basic Information
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Part Name -->
                                    <div>
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Part Name <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            wire:model="name" 
                                            class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" 
                                            placeholder="e.g., iPhone 13 LCD Screen"
                                        />
                                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- SKU -->
                                    <div>
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            SKU <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            wire:model="sku" 
                                            class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-mono focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" 
                                            placeholder="LCD-IP13-001"
                                        />
                                        @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Category <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <select
                                                wire:model="category"
                                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all appearance-none cursor-pointer"
                                            >
                                                <option value="">Select Category</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                                @endforeach
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Supplier -->
                                    <div>
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Supplier <span class="text-xs text-zinc-500 dark:text-zinc-400 font-normal">(optional)</span>
                                        </label>
                                        <div class="relative">
                                            <select
                                                wire:model="supplier"
                                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all appearance-none cursor-pointer"
                                            >
                                                <option value="">Select Supplier</option>
                                                @foreach($suppliers as $supplierId => $supplierName)
                                                    <option value="{{ $supplierName }}">{{ $supplierName }}</option>
                                                @endforeach
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        @error('supplier') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Manufacturer -->
                                    <div class="col-span-2">
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Manufacturer <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <select
                                                wire:model="manufacturer"
                                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all appearance-none cursor-pointer"
                                            >
                                                <option value="">Select Manufacturer</option>
                                                @foreach($this->manufacturers() as $mfr)
                                                    <option value="{{ $mfr }}">{{ $mfr }}</option>
                                                @endforeach
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                                                <svg class="w-4 h-2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>
                                        </div>
                                        @error('manufacturer') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Model -->
                                    <div class="col-span-2">
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Device Model <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            wire:model="model" 
                                            class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all" 
                                            placeholder="e.g., iPhone 13 Pro, Galaxy S21, Redmi Note 10"
                                        />
                                        @error('model') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory & Pricing Section -->
                            <div class="mb-6">
                                <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    Inventory & Pricing
                                </h4>
                                <div class="grid grid-cols-3 gap-4">
                                    <!-- In Stock -->
                                    <div>
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Current Stock <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input 
                                                type="number" 
                                                wire:model="in_stock" 
                                                min="0" 
                                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" 
                                                placeholder="0"
                                            />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-400">units</span>
                                        </div>
                                        @error('in_stock') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Reorder Point with Tooltip -->
                                    <div>
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5 group relative">
                                            Reorder Point <span class="text-red-500">*</span>
                                            <span class="ml-1 inline-flex items-center justify-center w-4 h-4 text-xs rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 cursor-help">?</span>
                                            <span class="invisible group-hover:visible absolute left-0 top-full mt-1 w-56 p-2 bg-zinc-900 text-white text-xs rounded-lg shadow-lg z-10">
                                                Alert threshold: System warns when stock falls below this level
                                            </span>
                                        </label>
                                        <div class="relative">
                                            <input 
                                                type="number" 
                                                wire:model="reorder_point" 
                                                min="0" 
                                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition-all" 
                                                placeholder="0"
                                            />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-400">units</span>
                                        </div>
                                        @error('reorder_point') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Unit Price -->
                                    <div>
                                        <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Unit Price <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-zinc-500">₱</span>
                                            <input 
                                                type="number" 
                                                wire:model="unit_price" 
                                                step="0.01" 
                                                min="0" 
                                                class="w-full pl-7 pr-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all" 
                                                placeholder="0.00"
                                            />
                                        </div>
                                        @error('unit_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Details Section -->
                            <div class="mb-6">
                                <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                    </svg>
                                    Additional Details <span class="text-xs text-zinc-400 normal-case font-normal">(optional)</span>
                                </h4>
                                <textarea 
                                    wire:model="description" 
                                    rows="2" 
                                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all resize-none"
                                    placeholder="Optional: Add specifications, compatibility notes, or other details..."
                                ></textarea>
                                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <!-- Active Status -->
                            <div class="mb-6">
                                <label class="flex items-center gap-2.5 p-3 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 cursor-pointer hover:border-blue-500 dark:hover:border-blue-500 transition-all">
                                    <input 
                                        type="checkbox" 
                                        wire:model="is_active" 
                                        class="w-4 h-4 text-blue-600 bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 rounded focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 transition-all"
                                    >
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">This part is active and available for use</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                                <button 
                                    type="button" 
                                    wire:click="closeModal" 
                                    class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all cursor-pointer">
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="save"
                                    class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-lg transition-all shadow-md hover:shadow-lg cursor-pointer inline-flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="save">
                                        {{ $isEditing ? '✓ Update Part' : '+ Create Part' }}
                                    </span>

                                    <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                        </svg>
                                        
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restock Confirmation Modal -->
        <div 
            x-data="{ show: @entangle('showRestockModal') }"
            x-show="show"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
        >
            <!-- Backdrop with blur -->
            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                wire:click="closeRestockModal"
                class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity"
            ></div>

            <!-- Modal Dialog -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div 
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-lg w-full border border-zinc-200 dark:border-zinc-800 overflow-hidden transform transition-all"
                >
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-orange-600 to-amber-600 px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-white">Confirm Restock Request</h3>
                        </div>
                        <button 
                            wire:click="closeRestockModal" 
                            class="p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-xl">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-base font-semibold text-zinc-900 dark:text-white mb-2">
                                    Send restock request email to supplier?
                                </p>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    This will notify the supplier about the low stock status and request a restock.
                                </p>
                            </div>
                        </div>

                        <!-- Additional Notes Input -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                Additional Notes <span class="text-xs text-zinc-500 dark:text-zinc-400 font-normal">(optional)</span>
                            </label>
                            <textarea 
                                wire:model="restockNotes"
                                rows="4"
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-orange-500 dark:focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition-all resize-none"
                                placeholder="Add any specific requirements, delivery preferences, or special instructions for the supplier..."
                            ></textarea>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                These notes will be included in the email to the supplier.
                            </p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            wire:click="closeRestockModal" 
                            class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all cursor-pointer">
                            Cancel
                        </button>
                        <button 
                            type="button"
                            wire:click="confirmRestock"
                            wire:loading.attr="disabled"
                            wire:target="confirmRestock"
                            class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-lg transition-all shadow-md hover:shadow-lg cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                            <svg wire:loading.remove wire:target="confirmRestock" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <svg wire:loading wire:target="confirmRestock" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="confirmRestock">Send Request</span>
                            <span wire:loading wire:target="confirmRestock">Sending...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
