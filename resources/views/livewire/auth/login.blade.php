<x-layouts.auth>
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2">Welcome Back</h2>
        <p class="text-zinc-600 dark:text-zinc-400">Track your repairs and stay connected</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

    <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
        @csrf

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
                autofocus 
                autocomplete="email"
                placeholder="your@email.com"
                class="w-full px-4 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input 
                id="password"
                name="password" 
                type="password" 
                required 
                autocomplete="current-password"
                placeholder="Enter your password"
                class="w-full px-4 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input 
                id="remember" 
                name="remember" 
                type="checkbox" 
                {{ old('remember') ? 'checked' : '' }}
                class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:ring-blue-600"
            />
            <label for="remember" class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">
                Remember me
            </label>
        </div>

        <button 
            type="submit" 
            data-test="login-button"
            class="w-full px-4 py-3 text-white bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
        >
            Log in
        </button>
    </form>
</x-layouts.auth>
