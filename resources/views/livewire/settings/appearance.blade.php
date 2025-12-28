<?php

use Livewire\Volt\Component;

new class extends Component {
    public string $theme = 'system';

    public function mount(): void
    {
        $this->theme = session('theme', 'system');
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
        session(['theme' => $theme]);
        
        $this->dispatch('theme-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Light Theme -->
                <button
                    type="button"
                    wire:click="setTheme('light')"
                    @class([
                        'flex flex-col items-center gap-3 p-4 rounded-lg border-2 transition-all',
                        'border-blue-500 bg-blue-50 dark:bg-blue-900/20' => $theme === 'light',
                        'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' => $theme !== 'light'
                    ])
                >
                    <svg class="w-8 h-8 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Light') }}</span>
                    @if($theme === 'light')
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @endif
                </button>

                <!-- Dark Theme -->
                <button
                    type="button"
                    wire:click="setTheme('dark')"
                    @class([
                        'flex flex-col items-center gap-3 p-4 rounded-lg border-2 transition-all',
                        'border-blue-500 bg-blue-50 dark:bg-blue-900/20' => $theme === 'dark',
                        'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' => $theme !== 'dark'
                    ])
                >
                    <svg class="w-8 h-8 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Dark') }}</span>
                    @if($theme === 'dark')
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @endif
                </button>

                <!-- System Theme -->
                <button
                    type="button"
                    wire:click="setTheme('system')"
                    @class([
                        'flex flex-col items-center gap-3 p-4 rounded-lg border-2 transition-all',
                        'border-blue-500 bg-blue-50 dark:bg-blue-900/20' => $theme === 'system',
                        'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' => $theme !== 'system'
                    ])
                >
                    <svg class="w-8 h-8 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('System') }}</span>
                    @if($theme === 'system')
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @endif
                </button>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Choose how the interface appears. System will automatically match your device\'s theme.') }}
            </p>
        </div>
    </x-settings.layout>
</section>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('theme-updated', () => {
        // Optional: Add toast notification or visual feedback
    });
});
</script>
