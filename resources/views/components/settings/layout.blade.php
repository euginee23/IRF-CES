<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <nav class="space-y-1">
            <a href="{{ route('profile.edit') }}" wire:navigate class="block px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('profile.edit') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }} transition-colors">
                {{ __('Profile') }}
            </a>
            <a href="{{ route('user-password.edit') }}" wire:navigate class="block px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('user-password.edit') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }} transition-colors">
                {{ __('Password') }}
            </a>
            <a href="{{ route('appearance.edit') }}" wire:navigate class="block px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('appearance.edit') ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }} transition-colors">
                {{ __('Appearance') }}
            </a>
        </nav>
    </div>

    <hr class="border-zinc-200 dark:border-zinc-700 md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $heading ?? '' }}</h2>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
