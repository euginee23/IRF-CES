<?php

use App\Models\RepairQuoteRequest;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component {
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('required|string')]
    public string $manufacturer = '';

    #[Validate('required|string|max:255')]
    public string $model = '';

    #[Validate('required|string|min:10')]
    public string $issue_description = '';

    #[Validate('nullable|array|max:5')]
    #[Validate('images.*', 'nullable|image|max:5120')] // 5MB max per image
    public array $images = [];

    public bool $submitted = false;

    // Common phone manufacturers
    public function manufacturers(): array
    {
        return [
            'Apple',
            'Samsung',
            'Huawei',
            'Xiaomi',
            'Oppo',
            'Vivo',
            'OnePlus',
            'Google',
            'Motorola',
            'LG',
            'Sony',
            'Nokia',
            'Other',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $imagePaths = [];
        if (!empty($this->images)) {
            foreach ($this->images as $image) {
                if ($image) {
                    $imagePaths[] = $image->store('repair-quotes', 'public');
                }
            }
        }

        RepairQuoteRequest::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'issue_description' => $this->issue_description,
            'images' => $imagePaths,
            'status' => 'pending',
        ]);

        $this->submitted = true;
        $this->reset(['name', 'email', 'phone', 'manufacturer', 'model', 'issue_description', 'images']);
    }

    public function resetForm(): void
    {
        $this->submitted = false;
        $this->reset();
    }
}; ?>

<div class="w-full" id="quote-form">
    @if($submitted)
        <!-- Success Message -->
        <div class="max-w-2xl mx-auto bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">Quote Request Submitted!</h3>
            <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                Thank you for your repair quote request. We'll review your device details and get back to you with a quote within 24 hours.
            </p>
            <button wire:click="resetForm" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                Submit Another Request
            </button>
        </div>
    @else
        <!-- Quote Request Form -->
        <div class="max-w-3xl mx-auto bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 px-8 py-6 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Request Repair Quote</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Get a free estimate for your device repair</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form wire:submit="submit" class="p-8 space-y-6">
                <!-- Contact Information -->
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Contact Information
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" wire:model="name" required
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="John Doe" />
                            @error('name') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                Phone Number
                            </label>
                            <input type="tel" id="phone" wire:model="phone"
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="+1 (555) 000-0000" />
                            @error('phone') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="email" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" wire:model="email" required
                            class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="john@example.com" />
                        @error('email') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Device Information -->
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Device Information
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="manufacturer" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                Manufacturer <span class="text-red-500">*</span>
                            </label>
                            <select id="manufacturer" wire:model="manufacturer" required
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none cursor-pointer">
                                <option value="">Select manufacturer</option>
                                @foreach($this->manufacturers() as $mfr)
                                    <option value="{{ $mfr }}">{{ $mfr }}</option>
                                @endforeach
                            </select>
                            @error('manufacturer') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="model" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                                Model <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="model" wire:model="model" required
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="e.g., iPhone 13 Pro, Galaxy S21" />
                            @error('model') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Issue Description -->
                <div>
                    <label for="issue_description" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                        Describe the Issue <span class="text-red-500">*</span>
                    </label>
                    <textarea id="issue_description" wire:model="issue_description" required rows="5"
                        class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                        placeholder="Please describe the issue with your device in detail. For example: 'Screen is cracked in the top-right corner after dropping the phone...'"></textarea>
                    @error('issue_description') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">Minimum 10 characters required</p>
                </div>

                <!-- Image Upload -->
                <div>
                    <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                        Upload Photos (Optional)
                    </label>
                    <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-700 rounded-xl p-6 text-center hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
                        <input type="file" id="images" wire:model="images" multiple accept="image/*" class="hidden" />
                        <label for="images" class="cursor-pointer block">
                            <svg class="w-12 h-12 mx-auto text-zinc-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Click to upload images</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">PNG, JPG up to 5MB each (max 5 images)</p>
                        </label>
                    </div>
                    @error('images.*') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    
                    @if (!empty($images))
                        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                            @foreach($images as $index => $image)
                                <div class="relative group">
                                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-full h-24 object-cover rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <button type="button" wire:click="$set('images.{{ $index }}', null)" class="absolute top-1 right-1 w-6 h-6 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading wire:target="images" class="mt-3 text-sm text-blue-600 dark:text-blue-400 flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Uploading...
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="submit">Submit Quote Request</span>
                        <span wire:loading wire:target="submit" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </button>
                    <p class="mt-3 text-xs text-center text-zinc-500 dark:text-zinc-400">
                        We'll review your request and send you a quote within 24 hours
                    </p>
                </div>
            </form>
        </div>
    @endif
</div>
