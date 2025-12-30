<?php

use App\Models\Service;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $showModal = false;
    public $isEditing = false;
    public $serviceId = null;

    // Form fields
    public $name = '';
    public $category = '';
    public $description = '';
    public $labor_price = '';
    public $estimated_duration = '';
    public $is_active = true;

    public $showDeleteModal = false;
    public $serviceToDelete = null;

    protected $queryString = ['search', 'categoryFilter'];

    // Pagination per-page selection (10,25,50)
    public $perPage = 10;

    public function mount()
    {
        $this->is_active = true;
    }

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Services Management');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function getCategories()
    {
        return [
            'Display & Input',
            'Power & Charging',
            'Motherboard & Internal Components',
            'Water & Physical Damage',
            'Software & Firmware',
            'Data & Security',
            'Diagnostics & Testing',
            'Refurbishing & Resale',
        ];
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'category', 'description', 'labor_price', 'estimated_duration', 'is_active', 'serviceId', 'isEditing']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $service = Service::findOrFail($id);
        
        $this->serviceId = $service->id;
        $this->name = $service->name;
        $this->category = $service->category;
        $this->description = $service->description;
        $this->labor_price = $service->labor_price;
        $this->estimated_duration = $service->estimated_duration;
        $this->is_active = $service->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['name', 'category', 'description', 'labor_price', 'estimated_duration', 'is_active', 'serviceId', 'isEditing']);
    }

    public function save()

    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'labor_price' => 'required|numeric|min:0',
            'estimated_duration' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($this->isEditing) {
            $service = Service::findOrFail($this->serviceId);
            $service->update($validated);
            $message = 'Service updated successfully!';
        } else {
            Service::create($validated);
            $message = 'Service created successfully!';
            // After creating a new service, jump to the last pagination page
            $total = Service::count();
            $lastPage = (int) ceil($total / max(1, $this->perPage));
            $this->gotoPage($lastPage);
        }

        $this->closeModal();
        $this->dispatch('success', message: $message);
    }

    public function delete(int $id): void
    {
        $service = Service::findOrFail($id);
        $service->delete();
        $this->dispatch('success', message: 'Service deleted successfully!');
    }

    public function with(): array
    {
        $query = Service::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('category', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        return [
            // Order by creation time ascending so newest services appear at the end
            'services' => $query->orderBy('created_at')->orderBy('category')->orderBy('name')->paginate($this->perPage),
            'categories' => $this->getCategories(),
            'totalServices' => Service::count(),
            'activeServices' => Service::where('is_active', true)->count(),
            'totalRevenue' => Service::where('is_active', true)->sum('labor_price'),
        ];
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-800 bg-clip-text text-transparent">Services Management</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Manage repair services and labor pricing</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Services -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Services</p>
                        <p class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $totalServices }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">available services</p>
                    </div>
                </div>
            </div>

            <!-- Active Services -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30 rounded-full">Active</span>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active Services</p>
                        <p class="text-4xl font-bold text-green-600 dark:text-green-400">{{ $activeServices }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">currently active</p>
                    </div>
                </div>
            </div>

            <!-- Total Labor Value -->
            <div class="group relative bg-white dark:bg-zinc-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-md">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Labor Value</p>
                        <p class="text-4xl font-bold text-purple-600 dark:text-purple-400">₱{{ number_format($totalRevenue, 2) }}</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">combined labor pricing</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
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
                                placeholder="Search by name, category, or description..."
                                class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="categoryFilter" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            Category Filter
                        </label>
                        <select
                            id="categoryFilter"
                            wire:model.live="categoryFilter"
                            class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wide">All Services</h3>
                    <span class="px-2 py-0.5 text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 rounded-full">
                        {{ $services->total() }}
                    </span>
                </div>
                <button
                    x-on:click="$wire.openCreateModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Service
                </button>
            </div>

            @if($services->isEmpty())
                <div class="p-12 text-center">
                    <div class="flex justify-center mb-4">
                        <div class="p-4 bg-zinc-100 dark:bg-zinc-800 rounded-full">
                            <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No Services Found</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
                        @if($search || $categoryFilter)
                            Try adjusting your filters
                        @else
                            Get started by adding your first service
                        @endif
                    </p>
                    @if(!$search && !$categoryFilter)
                        <button
                            x-on:click="$wire.openCreateModal()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Service
                        </button>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Service Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Labor Price</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                            @foreach($services as $service)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $service->name }}</div>
                                        @if($service->description)
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ Str::limit($service->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                            {{ $service->category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-zinc-900 dark:text-zinc-100 font-semibold">
                                        ₱{{ number_format($service->labor_price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                        @if($service->estimated_duration)
                                            {{ $service->estimated_duration }} mins
                                        @else
                                            <span class="text-zinc-400 dark:text-zinc-500">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($service->is_active)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-400">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                x-on:click="$wire.openEditModal({{ $service->id }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit
                                            </button>
                                            <button
                                                x-on:click="$dispatch('open-delete-dialog', {
                                                    title: 'Delete Service',
                                                    message: 'Are you sure you want to delete {{ addslashes($service->name) }}? This action cannot be undone.',
                                                    confirmText: 'Delete',
                                                    cancelText: 'Cancel',
                                                    callback: () => $wire.delete({{ $service->id }})
                                                })"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-150 shadow-sm hover:shadow cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($services->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Showing page <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $services->currentPage() }}</span> of <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $services->lastPage() }}</span></div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-zinc-600 dark:text-zinc-400">Show</label>
                            <select wire:model.live="perPage" class="text-sm px-3 py-2 border rounded-md bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="gotoPage(1)" @if($services->onFirstPage()) disabled @endif class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 disabled:opacity-50 disabled:cursor-not-allowed">&laquo;</button>

                        <button type="button" wire:click="gotoPage({{ max(1, $services->currentPage()-1) }})" @if($services->onFirstPage()) disabled @endif class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 disabled:opacity-50 disabled:cursor-not-allowed">&lsaquo;</button>

                        <span class="px-4 py-1 border rounded-md text-sm font-medium bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100">{{ $services->currentPage() }}</span>

                        <button type="button" wire:click="gotoPage({{ min($services->lastPage(), $services->currentPage()+1) }})" @if($services->currentPage() == $services->lastPage()) disabled @endif class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 disabled:opacity-50 disabled:cursor-not-allowed">&rsaquo;</button>

                        <button type="button" wire:click="gotoPage({{ $services->lastPage() }})" @if($services->currentPage() == $services->lastPage()) disabled @endif class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 disabled:opacity-50 disabled:cursor-not-allowed">&raquo;</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div 
        x-data="{ show: @entangle('showModal').live }"
        x-show="show"
        x-on:keydown.escape.window="show = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">
        
        <!-- Backdrop -->
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-zinc-900/50 backdrop-blur-sm transition-opacity"
            x-on:click="$wire.closeModal()">
        </div>

        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block relative w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-800 rounded-2xl shadow-xl z-50">
                    <div class="bg-gradient-to-r from-zinc-50 to-slate-50 dark:from-zinc-800 dark:to-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ $isEditing ? 'Edit Service' : 'Add New Service' }}</h3>
                        </div>
                    </div>

                    <form wire:submit="save" class="p-6">
                        <div class="space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2 space-y-2">
                                    <label for="name" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                        Service Name <span class="text-red-500">*</span>
                                    </label>
                                    <input wire:model="name" type="text" id="name" required
                                        class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    @error('name') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label for="category" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="category" id="category" required
                                        class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                        <option value="">Select category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}">{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                    @error('category') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label for="labor_price" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                        Labor Price (₱) <span class="text-red-500">*</span>
                                    </label>
                                    <input wire:model="labor_price" type="number" id="labor_price" step="0.01" min="0" required
                                        class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    @error('labor_price') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div class="space-y-2">
                                    <label for="estimated_duration" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                        Estimated Duration (minutes)
                                    </label>
                                    <input wire:model="estimated_duration" type="number" id="estimated_duration" min="1"
                                        class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    @error('estimated_duration') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label for="description" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                        Description
                                    </label>
                                    <textarea wire:model="description" id="description" rows="3"
                                        class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"></textarea>
                                    @error('description') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="flex items-center gap-3 px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 transition-all group">
                                        <input type="checkbox" wire:model="is_active" class="w-5 h-5 text-indigo-600 bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 rounded-md focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0 transition-all">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Active Service</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <button type="button" x-on:click="$wire.closeModal()"
                                    class="px-6 py-3 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-white rounded-xl font-medium transition-colors cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all cursor-pointer">
                                    {{ $isEditing ? 'Update Service' : 'Create Service' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</div>

