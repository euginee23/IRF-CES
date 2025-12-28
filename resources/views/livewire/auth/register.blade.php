<x-layouts.auth>
    <div class="text-center">
        <div class="mb-6">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">Registration Disabled</h2>
            <p class="text-zinc-600 dark:text-zinc-400 mb-6">Staff accounts are managed by administrators</p>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
            <h3 class="font-semibold text-zinc-900 dark:text-white mb-2">Need a Repair Quote?</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                If you'd like to get your device repaired, you can request a free quote on our homepage.
            </p>
            <a href="{{ route('home') }}#quote-form" class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                Request a Quote
            </a>
        </div>

        <div class="text-sm text-zinc-600 dark:text-zinc-400">
            <p>Already have a staff account?</p>
            <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                Sign in here
            </a>
        </div>
    </div>
</x-layouts.auth>


    <form method="POST" action="{{ route('register.store') }}" class="space-y-6">
        @csrf
        
        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                Full name
            </label>
            <input 
                id="name"
                name="name" 
                type="text" 
                value="{{ old('name') }}"
                required 
                autofocus 
                autocomplete="name"
                placeholder="John Doe"
                class="w-full px-4 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                Email address
            </label>
            <input 
                id="email"
                name="email" 
                type="email" 
                value="{{ old('email') }}"
                required 
                autocomplete="email"
                placeholder="your@email.com"
                class="w-full px-4 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
