<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Skillset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule as ValidationRule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    
    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showSkillsetsModal = false;
    
    // Form fields
    public ?int $editUserId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = '';
    
    // Skillsets
    public ?int $skillsetsUserId = null;
    public array $skillsets = [];

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Staff Management');
    }

    public function with(): array
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        return [
            'users' => $query->latest()->paginate(10),
        ];
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'role']);
        $this->resetErrorBag();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->editUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role?->value ?? '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetErrorBag();
        $this->showEditModal = true;
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showSkillsetsModal = false;
        $this->reset(['editUserId', 'name', 'email', 'password', 'password_confirmation', 'role', 'skillsetsUserId', 'skillsets']);
        $this->resetErrorBag();
    }

    public function openSkillsetsModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->skillsetsUserId = $user->id;
        $this->skillsets = $user->skillsets->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
                'years_experience' => $s->years_experience,
                'certifications' => $s->certifications,
                'is_primary' => (bool)$s->is_primary,
            ];
        })->toArray();
        $this->resetErrorBag();
        $this->showSkillsetsModal = true;
    }

    public function addSkill(): void
    {
        $this->skillsets[] = [
            'name' => '',
            'description' => '',
            'years_experience' => 0,
            'certifications' => '',
            'is_primary' => false,
        ];
    }

    public function removeSkill(int $index): void
    {
        if (isset($this->skillsets[$index])) {
            unset($this->skillsets[$index]);
            $this->skillsets = array_values($this->skillsets);
        }
    }

    public function saveSkillsets(): void
    {
        $user = User::findOrFail($this->skillsetsUserId);

        $rules = [
            'skillsets' => ['array'],
            'skillsets.*.name' => ['nullable', 'string', 'max:255'],
            'skillsets.*.description' => ['nullable', 'string'],
            'skillsets.*.years_experience' => ['nullable', 'integer', 'min:0'],
            'skillsets.*.certifications' => ['nullable', 'string'],
            'skillsets.*.is_primary' => ['nullable', 'boolean'],
        ];

        $this->validate($rules);

        // Delete existing skillsets and recreate
        $user->skillsets()->delete();
        foreach ($this->skillsets as $s) {
            if (empty(trim($s['name'] ?? ''))) continue;
            Skillset::create([
                'user_id' => $user->id,
                'name' => $s['name'] ?? null,
                'description' => $s['description'] ?? null,
                'years_experience' => (int)($s['years_experience'] ?? 0),
                'certifications' => $s['certifications'] ?? null,
                'is_primary' => !empty($s['is_primary']),
            ]);
        }

        $this->dispatch('success', message: 'Technician skillsets updated successfully.');
        $this->closeModals();
    }

    public function createStaff(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'role' => ['required', 'string', 'in:' . implode(',', Role::values())],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        $this->dispatch('success', message: 'Staff member created successfully.');
        $this->closeModals();
        $this->resetPage();
    }

    public function updateStaff(): void
    {
        $user = User::findOrFail($this->editUserId);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', ValidationRule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', 'in:' . implode(',', Role::values())],
        ];

        if ($this->password) {
            $rules['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
        }

        $validated = $this->validate($rules);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if ($this->password) {
            $user->update(['password' => Hash::make($this->password)]);
        }

        $this->dispatch('success', message: 'Staff member updated successfully.');
        $this->closeModals();
    }

    public function deleteStaff(int $userId): void
    {
        $user = User::findOrFail($userId);
        
        if ($user->id === auth()->id()) {
            $this->dispatch('error', message: 'You cannot delete your own account.');
            return;
        }

        $user->delete();
        $this->dispatch('success', message: 'Staff member deleted successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <div class="space-y-8">
        <!-- Header -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Staff Management</h1>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Manage staff members, assign roles, and control access permissions</p>
                </div>
                <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Staff Member
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Filter & Search</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Search</label>
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
                            placeholder="Search by name or email..."
                            class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        />
                    </div>
                </div>

                <!-- Role Filter -->
                <div>
                    <label for="roleFilter" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Filter by Role</label>
                    <div class="relative">
                        <select
                            id="roleFilter"
                            wire:model.live="roleFilter"
                            class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none cursor-pointer"
                        >
                            <option value="">All Roles</option>
                            @foreach(\App\Enums\Role::cases() as $role)
                                <option value="{{ $role->value }}">{{ $role->label() }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff List -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-gradient-to-r from-zinc-50 to-zinc-100 dark:from-zinc-900/50 dark:to-zinc-800/50">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Staff Members
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Staff Member</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Email Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($users as $user)
                            <tr @if($user->id === auth()->id()) onclick="window.location='{{ route('profile.edit') }}'" @endif class="hover:bg-blue-50/50 dark:hover:bg-zinc-900/70 transition-all duration-150 group {{ $user->id === auth()->id() ? 'cursor-pointer' : '' }}">
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-blue-500/30 ring-2 ring-white dark:ring-zinc-800 group-hover:scale-110 transition-transform">
                                            {{ $user->initials() }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</div>
                                            <div class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 mt-0.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    @if($user->role)
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm
                                            {{ $user->role->value === 'administrator' ? 'bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 ring-1 ring-purple-300 dark:from-purple-900/40 dark:to-purple-800/40 dark:text-purple-300 dark:ring-purple-700/50' : '' }}
                                            {{ $user->role->value === 'technician' ? 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 ring-1 ring-blue-300 dark:from-blue-900/40 dark:to-blue-800/40 dark:text-blue-300 dark:ring-blue-700/50' : '' }}
                                            {{ $user->role->value === 'counter_staff' ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 ring-1 ring-green-300 dark:from-green-900/40 dark:to-green-800/40 dark:text-green-300 dark:ring-green-700/50' : '' }}">
                                            <svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $user->role->label() }}
                                        </span>
                                    @else
                                        <span class="text-sm text-zinc-400 italic">No role assigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    @if($user->email_verified_at)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 ring-1 ring-green-200 dark:from-green-900/40 dark:to-emerald-900/40 dark:text-green-300 dark:ring-green-700/50 shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Verified
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gradient-to-r from-amber-100 to-orange-100 text-amber-700 ring-1 ring-amber-200 dark:from-amber-900/40 dark:to-orange-900/40 dark:text-amber-300 dark:ring-amber-700/50 shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $user->created_at->format('M d, Y') }}
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($user->id === auth()->id())
                                            <a href="{{ route('profile.edit') }}" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:text-white bg-blue-50 hover:bg-blue-600 dark:bg-blue-900/30 dark:hover:bg-blue-600 rounded-lg transition-all hover:shadow-md" aria-label="Edit your profile">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit Your Profile
                                            </a>
                                        @else
                                            @if($user->role?->value === 'technician')
                                                <button type="button" wire:click="openSkillsetsModal({{ $user->id }})" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-600 dark:text-purple-400 hover:text-white bg-purple-50 hover:bg-purple-600 dark:bg-purple-900/30 dark:hover:bg-purple-600 rounded-lg transition-all hover:shadow-md">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                                    </svg>
                                                    Manage Skillsets
                                                </button>
                                            @endif
                                        @endif
                                        @if($user->id !== auth()->id())
                                            <button
                                                wire:click="deleteStaff({{ $user->id }})"
                                                wire:confirm="Are you sure you want to delete this staff member?"
                                                class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:text-white bg-red-50 hover:bg-red-600 dark:bg-red-900/30 dark:hover:bg-red-600 rounded-lg transition-all hover:shadow-md"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">No staff members found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        <!-- Create Staff Modal -->
        @if($showCreateModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showCreateModal') }">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 transition-opacity bg-zinc-900/75 backdrop-blur-sm" wire:click="closeModals"></div>

                    <!-- Modal Panel -->
                    <div class="relative inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-800 shadow-2xl rounded-2xl">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Add Staff Member</h3>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Create a new staff account</p>
                                    </div>
                                </div>
                                <button wire:click="closeModals" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Form -->
                        <form wire:submit="createStaff" class="p-6 space-y-5">
                            <!-- Name -->
                            <div>
                                <label for="create-name" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <input type="text" id="create-name" wire:model="name" required
                                        class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Enter full name" />
                                </div>
                                @error('name') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="create-email" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <input type="email" id="create-email" wire:model="email" required
                                        class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="staff@example.com" />
                                </div>
                                @error('email') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <!-- Role -->
                            <div>
                                <label for="create-role" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Role <span class="text-red-500">*</span>
                                </label>
                                <select id="create-role" wire:model="role" required
                                    class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none cursor-pointer">
                                    <option value="">Select a role</option>
                                    @foreach(\App\Enums\Role::cases() as $roleOption)
                                        <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                                    @endforeach
                                </select>
                                @error('role') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Password -->
                                <div>
                                    <label for="create-password" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password" id="create-password" wire:model="password" required
                                        class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="••••••••" />
                                    @error('password') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label for="create-password-confirmation" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                        Confirm Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password" id="create-password-confirmation" wire:model="password_confirmation" required
                                        class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="••••••••" />
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <button type="button" wire:click="closeModals" class="px-5 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 font-semibold rounded-xl transition-all">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40">
                                    Create Staff Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Edit Staff Modal -->
        @if($showEditModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showEditModal') }">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 transition-opacity bg-zinc-900/75 backdrop-blur-sm" wire:click="closeModals"></div>

                    <!-- Modal Panel -->
                    <div class="relative inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-800 shadow-2xl rounded-2xl">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Edit Staff Member</h3>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Update staff information</p>
                                    </div>
                                </div>
                                <button wire:click="closeModals" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Form -->
                        <form wire:submit="updateStaff" class="p-6 space-y-5">
                            <!-- Name -->
                            <div>
                                <label for="edit-name" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <input type="text" id="edit-name" wire:model="name" required
                                        class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" />
                                </div>
                                @error('name') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="edit-email" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <input type="email" id="edit-email" wire:model="email" required
                                        class="w-full pl-10 pr-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" />
                                </div>
                                @error('email') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <!-- Role -->
                            <div>
                                <label for="edit-role" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                    Role <span class="text-red-500">*</span>
                                </label>
                                <select id="edit-role" wire:model="role" required
                                    class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none cursor-pointer">
                                    <option value="">Select a role</option>
                                    @foreach(\App\Enums\Role::cases() as $roleOption)
                                        <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                                    @endforeach
                                </select>
                                @error('role') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>

                            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">Change password (leave blank to keep current)</p>
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- New Password -->
                                    <div>
                                        <label for="edit-password" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                            New Password
                                        </label>
                                        <input type="password" id="edit-password" wire:model="password"
                                            class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            placeholder="••••••••" />
                                        @error('password') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Confirm Password -->
                                    <div>
                                        <label for="edit-password-confirmation" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                            Confirm Password
                                        </label>
                                        <input type="password" id="edit-password-confirmation" wire:model="password_confirmation"
                                            class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                            placeholder="••••••••" />
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                <button type="button" wire:click="closeModals" class="px-5 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 font-semibold rounded-xl transition-all">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40">
                                    Update Staff Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Manage Skillsets Modal -->
        @if($showSkillsetsModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showSkillsetsModal') }">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 transition-opacity bg-zinc-900/75 backdrop-blur-sm" wire:click="closeModals"></div>

                    <!-- Modal Panel -->
                    <div class="relative inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-zinc-800 shadow-2xl rounded-2xl">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Manage Technician Skillsets</h3>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Add and manage repair specialties and certifications</p>
                                    </div>
                                </div>
                                <button wire:click="closeModals" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Form -->
                        <form wire:submit="saveSkillsets" class="p-6">
                            <div class="space-y-6">
                                <!-- Header with Add Button -->
                                <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-700">
                                    <div>
                                        <h4 class="text-lg font-semibold text-zinc-900 dark:text-white">Specialties & Skills</h4>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Select repair categories and add expertise details</p>
                                    </div>
                                    <button type="button" wire:click="addSkill" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-all shadow-md hover:shadow-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add Skill
                                    </button>
                                </div>

                                <!-- Skillsets List -->
                                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                                    @if(empty($skillsets))
                                        <div class="text-center py-12">
                                            <svg class="w-16 h-16 mx-auto text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No skills added yet. Click "Add Skill" to get started.</p>
                                        </div>
                                    @else
                                        @foreach($skillsets as $i => $s)
                                            <div class="p-5 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50 dark:bg-zinc-900/50 hover:border-purple-300 dark:hover:border-purple-700 transition-colors">
                                                <div class="flex items-start gap-4">
                                                    <div class="flex-1 space-y-4">
                                                        <!-- Row 1: Specialty, Years, Primary -->
                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                                            <div>
                                                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Specialty *</label>
                                                                <select wire:model.defer="skillsets.{{ $i }}.name" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                                                    <option value="">Select specialty...</option>
                                                                    @foreach(\App\Enums\TechnicianSkill::getGrouped() as $category => $skills)
                                                                        <optgroup label="{{ $category }}">
                                                                            @foreach($skills as $skill)
                                                                                <option value="{{ $skill->value }}">{{ $skill->value }}</option>
                                                                            @endforeach
                                                                        </optgroup>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Years Experience</label>
                                                                <input type="number" min="0" wire:model.defer="skillsets.{{ $i }}.years_experience" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="0" />
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Primary Specialty</label>
                                                                <label class="inline-flex items-center gap-2 px-3 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                                                                    <input type="checkbox" wire:model.defer="skillsets.{{ $i }}.is_primary" class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500" />
                                                                    <span class="text-xs text-zinc-700 dark:text-zinc-300">Mark as primary</span>
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <!-- Row 2: Certifications -->
                                                        <div>
                                                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Certifications</label>
                                                            <input type="text" wire:model.defer="skillsets.{{ $i }}.certifications" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="e.g., iFixit Certified, Manufacturer Training" />
                                                        </div>

                                                        <!-- Row 3: Description -->
                                                        <div>
                                                            <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Notes / Description</label>
                                                            <textarea wire:model.defer="skillsets.{{ $i }}.description" rows="2" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none" placeholder="Optional: Additional details about this skillset..."></textarea>
                                                        </div>
                                                    </div>

                                                    <!-- Remove Button -->
                                                    <button type="button" wire:click="removeSkill({{ $i }})" class="flex-shrink-0 p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Remove this skill">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 pt-6 mt-6 border-t border-zinc-200 dark:border-zinc-700">
                                <button type="button" wire:click="closeModals" class="px-5 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 font-semibold rounded-xl transition-all">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold rounded-xl transition-all shadow-lg shadow-purple-500/30 hover:shadow-xl hover:shadow-purple-500/40">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Save Skillsets
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>