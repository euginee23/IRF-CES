<?php

use App\Models\Part;
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

    // Form fields
    public string $name = '';
    public string $sku = '';
    public string $category = '';
    public string $description = '';
    public int $in_stock = 0;
    public int $reorder_point = 0;
    public float $unit_price = 0;
    public string $supplier = '';
    public string $location = '';
    public bool $is_active = true;

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Parts Inventory');
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
        $categories = Part::distinct()->pluck('category')->filter();
        $lowStockCount = Part::whereRaw('in_stock <= reorder_point')->count();

        return [
            'parts' => $parts,
            'categories' => $categories,
            'lowStockCount' => $lowStockCount,
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
        $this->location = $this->selectedPart->location ?? '';
        $this->is_active = $this->selectedPart->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:parts,sku,' . ($this->selectedPart->id ?? 'NULL'),
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'in_stock' => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
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
        $this->in_stock = 0;
        $this->reorder_point = 0;
        $this->unit_price = 0;
        $this->supplier = '';
        $this->location = '';
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
                <div class="group relative bg-white dark:bg-zinc-900 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
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
                <div class="group relative bg-white dark:bg-zinc-900 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
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
                <div class="group relative bg-white dark:bg-zinc-900 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
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
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-900 dark:to-zinc-900 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
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
                                    class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
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
                                    class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all appearance-none cursor-pointer"
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
                            <label class="flex items-center gap-3 px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 cursor-pointer hover:border-orange-400 dark:hover:border-orange-500 transition-all group">
                                <input type="checkbox" wire:model.live="showLowStock" class="w-5 h-5 text-orange-600 bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-2 focus:ring-orange-500 focus:ring-offset-0 transition-all">
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
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-900 dark:to-zinc-900 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800">
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
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all shadow-md hover:shadow-lg cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>+ Add Part</span>
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-100 dark:bg-zinc-900/70 border-b-2 border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Part Details</th>
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
                                                    @if($part->location)
                                                        <span class="text-xs text-zinc-400 dark:text-zinc-500">📍 {{ $part->location }}</span>
                                                    @endif
                                                </div>
                                            </div>
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
                                            {{ $part->supplier ?? '—' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                type="button"
                                                wire:click="openEditModal({{ $part->id }})"
                                                class="group p-2.5 text-blue-600 hover:text-white hover:bg-blue-600 dark:text-blue-400 dark:hover:bg-blue-600 rounded-lg transition-all cursor-pointer"
                                                title="Edit Part">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
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
                                                class="group p-2.5 text-red-600 hover:text-white hover:bg-red-600 dark:text-red-400 dark:hover:bg-red-600 rounded-lg transition-all cursor-pointer"
                                                title="Delete Part">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center">
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
                                                class="mt-2 inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
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
                    <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 border-t border-zinc-200 dark:border-zinc-800">
                        {{ $parts->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Enhanced Create/Edit Modal -->
        @if($showModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" style="display: block;">
                
                <div class="fixed inset-0 bg-zinc-900/80 backdrop-blur-md transition-opacity" wire:click="closeModal"></div>

                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl max-w-4xl w-full border border-zinc-200 dark:border-zinc-800">
                        
                        <!-- Modal Header -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-700 rounded-t-3xl">
                            <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
                            <div class="relative flex items-center justify-between px-8 py-6 border-b border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2.5 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-bold text-white">
                                        {{ $isEditing ? 'Edit Part' : 'Add New Part' }}
                                    </h3>
                                </div>
                                <button 
                                    wire:click="closeModal" 
                                    class="p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-all cursor-pointer">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form wire:submit="save" class="p-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Part Name -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Part Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="name" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" 
                                        placeholder="Enter part name"
                                    />
                                    @error('name') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- SKU -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                        SKU <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="sku" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" 
                                        placeholder="e.g., LCD-IP13-001"
                                    />
                                    @error('sku') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- Category -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Category
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="category" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" 
                                        placeholder="e.g., Display, Battery"
                                    />
                                    @error('category') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- Supplier -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        Supplier
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="supplier" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" 
                                        placeholder="Enter supplier name"
                                    />
                                    @error('supplier') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- In Stock -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        In Stock <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="in_stock" 
                                        min="0" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all" 
                                        placeholder="0"
                                    />
                                    @error('in_stock') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- Reorder Point -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        Reorder Point <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="reorder_point" 
                                        min="0" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition-all" 
                                        placeholder="0"
                                    />
                                    @error('reorder_point') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- Unit Price -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Unit Price (₱) <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        wire:model="unit_price" 
                                        step="0.01" 
                                        min="0" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all" 
                                        placeholder="0.00"
                                    />
                                    @error('unit_price') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- Location -->
                                <div class="space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Location
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="location" 
                                        placeholder="e.g., Shelf A3" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                    />
                                    @error('location') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- Description (Full Width) -->
                                <div class="md:col-span-2 space-y-2">
                                    <label class="flex items-center gap-1.5 text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                        </svg>
                                        Description
                                    </label>
                                    <textarea 
                                        wire:model="description" 
                                        rows="3" 
                                        class="w-full px-4 py-3 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all resize-none"
                                        placeholder="Additional details about this part..."
                                    ></textarea>
                                    @error('description') <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>{{ $message }}</p> @enderror
                                </div>

                                <!-- Active Toggle (Full Width) -->
                                <div class="md:col-span-2">
                                    <label class="flex items-center gap-3 p-4 border-2 border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 cursor-pointer hover:border-blue-500 dark:hover:border-blue-500 transition-all group">
                                        <input 
                                            type="checkbox" 
                                            wire:model="is_active" 
                                            class="w-5 h-5 text-blue-600 bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 transition-all"
                                        >
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Active Part</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t-2 border-zinc-200 dark:border-zinc-800">
                                <button 
                                    type="button" 
                                    wire:click="closeModal" 
                                    class="px-6 py-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border-2 border-zinc-300 dark:border-zinc-700 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:border-zinc-400 dark:hover:border-zinc-600 transition-all cursor-pointer">
                                    Cancel
                                </button>
                                <button 
                                    type="submit" 
                                    class="px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 cursor-pointer">
                                    {{ $isEditing ? '✓ Update Part' : '+ Create Part' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
