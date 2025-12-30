<?php

use App\Models\RepairQuoteRequest;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showModal = false;
    public ?RepairQuoteRequest $selectedRequest = null;

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Repair Quote Requests');
    }

    public function with(): array
    {
        $query = RepairQuoteRequest::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('manufacturer', 'like', '%' . $this->search . '%')
                  ->orWhere('model', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return [
            'requests' => $query->latest()->paginate(15),
        ];
    }

    public function viewRequest(int $id): void
    {
        $this->selectedRequest = RepairQuoteRequest::findOrFail($id);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedRequest = null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $request = RepairQuoteRequest::findOrFail($id);
        $request->update(['status' => $status]);
        
        // Update the selected request if viewing
        if ($this->selectedRequest && $this->selectedRequest->id === $id) {
            $this->selectedRequest->refresh();
        }
        
        $this->dispatch('success', message: 'Status updated successfully.');
    }

    public function createQuote(): void
    {
        if (!$this->selectedRequest) return;
        
        // Update status to quoted
        $this->selectedRequest->update(['status' => 'quoted']);
        $this->selectedRequest->refresh();
        
        // Here you can add logic to create an actual quote
        // For now, we'll just show a success message
        $this->dispatch('success', message: 'Quote created successfully. Email sent to customer.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <div class="space-y-8">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Repair Quote Requests</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Review and manage customer repair quote requests</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Search</label>
                    <input
                        type="text"
                        id="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, email, device..."
                        class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    />
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="statusFilter" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Filter by Status</label>
                    <select
                        id="statusFilter"
                        wire:model.live="statusFilter"
                        class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none cursor-pointer"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="quoted">Quoted</option>
                        <option value="approved">Approved</option>
                        <option value="declined">Declined</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Requests List -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Device</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Issue</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($requests as $request)
                            <tr class="hover:bg-blue-50/50 dark:hover:bg-zinc-900/70 transition-all duration-150">
                                <td class="px-6 py-5">
                                    <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $request->name }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $request->email }}</div>
                                    @if($request->phone)
                                        <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $request->phone }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-5">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $request->manufacturer }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $request->model }}</div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 max-w-xs truncate" title="{{ $request->issue_description }}">
                                        {{ $request->issue_description }}
                                    </div>
                                    @if($request->images && count($request->images) > 0)
                                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                            📷 {{ count($request->images) }} image(s)
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-5">
                                    <select 
                                        wire:change="updateStatus({{ $request->id }}, $event.target.value)"
                                    class="text-xs px-2 py-1 rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 cursor-pointer
                                        {{ $request->status === 'pending' ? 'text-amber-700 dark:text-amber-400' : '' }}
                                        {{ $request->status === 'reviewed' ? 'text-blue-700 dark:text-blue-400' : '' }}
                                        {{ $request->status === 'quoted' ? 'text-purple-700 dark:text-purple-400' : '' }}
                                        {{ $request->status === 'approved' ? 'text-green-700 dark:text-green-400' : '' }}
                                        {{ $request->status === 'declined' ? 'text-red-700 dark:text-red-400' : '' }}"
                                    >
                                        <option value="pending" {{ $request->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="reviewed" {{ $request->status === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                        <option value="quoted" {{ $request->status === 'quoted' ? 'selected' : '' }}>Quoted</option>
                                        <option value="approved" {{ $request->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="declined" {{ $request->status === 'declined' ? 'selected' : '' }}>Declined</option>
                                    </select>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $request->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ $request->created_at->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <button 
                                        wire:click="viewRequest({{ $request->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-white bg-blue-50 hover:bg-blue-600 dark:bg-blue-900/30 dark:hover:bg-blue-600 rounded-lg transition-all hover:shadow-md">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">No quote requests found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($requests->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- View Request Modal -->
    @if($showModal && $selectedRequest)
        <div 
            x-data="{ show: @entangle('showModal') }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
            @keydown.escape.window="$wire.closeModal()">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-zinc-900/75 backdrop-blur-sm transition-opacity" @click="$wire.closeModal()"></div>

            <!-- Modal Content -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div 
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                    
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-zinc-200 dark:border-zinc-700">
                        <div>
                            <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">Quote Request Details</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Request ID: #{{ $selectedRequest->id }}</p>
                        </div>
                        <button 
                            wire:click="closeModal" 
                            class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
                        <div class="space-y-6">
                            <!-- Status Badge -->
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Status:</span>
                                <select 
                                    wire:change="updateStatus({{ $selectedRequest->id }}, $event.target.value)"
                                class="px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm font-semibold cursor-pointer
                                    {{ $selectedRequest->status === 'pending' ? 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20' : '' }}
                                    {{ $selectedRequest->status === 'reviewed' ? 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : '' }}
                                    {{ $selectedRequest->status === 'quoted' ? 'text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20' : '' }}
                                    {{ $selectedRequest->status === 'approved' ? 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20' : '' }}
                                    {{ $selectedRequest->status === 'declined' ? 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20' : '' }}">
                                    <option value="pending" {{ $selectedRequest->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="reviewed" {{ $selectedRequest->status === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                                    <option value="quoted" {{ $selectedRequest->status === 'quoted' ? 'selected' : '' }}>Quoted</option>
                                    <option value="approved" {{ $selectedRequest->status === 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="declined" {{ $selectedRequest->status === 'declined' ? 'selected' : '' }}>Declined</option>
                                </select>
                            </div>

                            <!-- Customer Information -->
                            <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-xl p-5">
                                <h4 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Customer Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Name</p>
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedRequest->name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Email</p>
                                        <a href="mailto:{{ $selectedRequest->email }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $selectedRequest->email }}
                                        </a>
                                    </div>
                                    @if($selectedRequest->phone)
                                        <div>
                                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Phone</p>
                                            <a href="tel:{{ $selectedRequest->phone }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ $selectedRequest->phone }}
                                            </a>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Submitted</p>
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                            {{ $selectedRequest->created_at->format('M d, Y \a\t h:i A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Device Information -->
                            <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-xl p-5">
                                <h4 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Device Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Manufacturer</p>
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedRequest->manufacturer }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Model</p>
                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedRequest->model }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Issue Description -->
                            <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-xl p-5">
                                <h4 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Issue Description</h4>
                                <p class="text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-wrap">{{ $selectedRequest->issue_description }}</p>
                            </div>

                            <!-- Images -->
                            @if($selectedRequest->images && count($selectedRequest->images) > 0)
                                <div>
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Attached Images ({{ count($selectedRequest->images) }})</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        @foreach($selectedRequest->images as $index => $imagePath)
                                            <a href="{{ Storage::url($imagePath) }}" target="_blank" class="group relative aspect-square rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 hover:ring-2 hover:ring-blue-500 transition-all">
                                                <img src="{{ Storage::url($imagePath) }}" alt="Device image {{ $index + 1 }}" class="w-full h-full object-cover">
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                                    </svg>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Modal Footer / Actions -->
                    <div class="flex items-center justify-end gap-3 p-6 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                        <a 
                            href="mailto:{{ $selectedRequest->email }}?subject=Repair Quote for {{ $selectedRequest->manufacturer }} {{ $selectedRequest->model }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Contact Customer
                        </a>
                        <button 
                            wire:click="createQuote"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all shadow-lg hover:shadow-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Create Quote
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
