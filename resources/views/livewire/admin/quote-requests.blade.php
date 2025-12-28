<?php

use App\Models\RepairQuoteRequest;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

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

    public function updateStatus(int $id, string $status): void
    {
        $request = RepairQuoteRequest::findOrFail($id);
        $request->update(['status' => $status]);
        $this->dispatch('success', message: 'Status updated successfully.');
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
                                        class="text-xs px-2 py-1 rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 cursor-pointer
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
                                    <a href="mailto:{{ $request->email }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-white bg-blue-50 hover:bg-blue-600 dark:bg-blue-900/30 dark:hover:bg-blue-600 rounded-lg transition-all hover:shadow-md">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        Email
                                    </a>
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
</div>
