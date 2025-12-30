<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Skillset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = '';
    public array $skillsets = [];

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

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Add Staff Member');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'role' => ['required', 'string', 'in:' . implode(',', Role::values())],
            'skillsets' => ['array'],
            'skillsets.*.name' => ['nullable', 'string', 'max:255'],
            'skillsets.*.description' => ['nullable', 'string'],
            'skillsets.*.years_experience' => ['nullable', 'integer', 'min:0'],
            'skillsets.*.certifications' => ['nullable', 'string'],
            'skillsets.*.is_primary' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        // Attach skillsets when technician
        if ($validated['role'] === Role::TECHNICIAN->value && !empty($this->skillsets)) {
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
        }

        $this->dispatch('success', message: 'Staff member created successfully.');
        $this->redirect(route('staff.index'), navigate: true);
    }
}; ?>

<div>
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-6">
                <a href="{{ route('staff.index') }}" wire:navigate class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:border-zinc-300 dark:hover:border-zinc-600 transition-all shadow-sm hover:shadow">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Add Staff Member</h1>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Create a new staff member account with role assignment</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Account Information
                </h2>
            </div>
            <form wire:submit="save" class="p-6 space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="name"
                            wire:model="name"
                            required
                            class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                            placeholder="Enter full name"
                        />
                    </div>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input
                            type="email"
                            id="email"
                            wire:model="email"
                            required
                            class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                            placeholder="staff@example.com"
                        />
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select
                            id="role"
                            wire:model="role"
                            required
                            class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none cursor-pointer shadow-sm"
                        >
                            <option value="">Select a role</option>
                            @foreach(\App\Enums\Role::cases() as $roleOption)
                                <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                    @error('role')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400 flex items-start gap-1.5">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Select the role that best describes this staff member's responsibilities and access level</span>
                    </p>
                
                {{-- Technician Skillsets --}}
                @if($role === \App\Enums\Role::TECHNICIAN->value)
                    <div class="pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Technician Skillsets</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Add specialties and details about the technician's areas of expertise (e.g., LCD repair, board-level, water damage).</p>
                            </div>
                            <div>
                                <button type="button" wire:click="addSkill" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-xl shadow-sm hover:bg-blue-700">Add Skill</button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @foreach($skillsets as $i => $s)
                                <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <div>
                                                <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Specialty</label>
                                                <select wire:model.defer="skillsets.{{ $i }}.name" class="w-full mt-1 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
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
                                                <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Years Experience</label>
                                                <input type="number" min="0" wire:model.defer="skillsets.{{ $i }}.years_experience" class="w-full mt-1 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900" />
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Primary</label>
                                                <div class="mt-1">
                                                    <label class="inline-flex items-center gap-2 px-3 py-2 border rounded-xl cursor-pointer">
                                                        <input type="checkbox" wire:model.defer="skillsets.{{ $i }}.is_primary" class="w-4 h-4" />
                                                        <span class="text-xs">Mark as primary specialty</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 grid grid-cols-1 gap-3">
                                        <div>
                                            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Certifications</label>
                                            <input type="text" wire:model.defer="skillsets.{{ $i }}.certifications" class="w-full mt-1 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900" placeholder="e.g., iFixit, Huawei certified" />
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">Notes / Description</label>
                                            <input type="text" wire:model.defer="skillsets.{{ $i }}.description" class="w-full mt-1 px-3 py-2 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900" placeholder="Optional details..." />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-3">
                                        <button type="button" wire:click="removeSkill({{ $i }})" class="text-sm text-red-600 hover:underline">Remove</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                </div>

                <!-- Password -->
                <div class="pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Security Credentials
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Set a secure password for the new staff member</p>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password"
                                    wire:model="password"
                                    required
                                    class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                                    placeholder="Enter secure password"
                                />
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                Confirm Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    wire:model="password_confirmation"
                                    required
                                    class="w-full pl-10 pr-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-sm"
                                    placeholder="Confirm password"
                                />
                            </div>
                            @error('password_confirmation')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <a href="{{ route('staff.index') }}" wire:navigate class="px-5 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 font-semibold rounded-xl transition-all shadow-sm hover:shadow">
                        Cancel
                    </a>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Staff Member
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>