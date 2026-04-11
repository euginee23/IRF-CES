<?php

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Component;

new class extends Component {
    public int $step = 1;
    public string $email = '';
    public string $code = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $successMessage = '';

    public function sendCode(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $key = 'password-reset-code:' . $this->email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Too many attempts. Please try again in {$seconds} seconds.");
            return;
        }
        RateLimiter::hit($key, 600);

        $user = User::where('email', $this->email)->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('password_reset_codes')->where('email', $this->email)->delete();
            DB::table('password_reset_codes')->insert([
                'email' => $this->email,
                'code' => Hash::make($code),
                'created_at' => now(),
            ]);

            Mail::to($this->email)->send(new PasswordResetCodeMail($code));
        }

        // Always advance to step 2 (don't reveal if email exists)
        $this->step = 2;
        $this->successMessage = 'If an account exists with that email, a 6-digit code has been sent.';
    }

    public function verifyCode(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $verifyKey = 'password-reset-verify:' . $this->email;
        if (RateLimiter::tooManyAttempts($verifyKey, 5)) {
            $seconds = RateLimiter::availableIn($verifyKey);
            $this->addError('code', "Too many attempts. Please try again in {$seconds} seconds.");
            return;
        }
        RateLimiter::hit($verifyKey, 600);

        $record = DB::table('password_reset_codes')
            ->where('email', $this->email)
            ->first();

        if (!$record || !Hash::check($this->code, $record->code)) {
            $this->addError('code', 'Invalid verification code.');
            return;
        }

        if (now()->diffInMinutes($record->created_at) > 10) {
            DB::table('password_reset_codes')->where('email', $this->email)->delete();
            $this->addError('code', 'This code has expired. Please request a new one.');
            return;
        }

        $this->step = 3;
        $this->successMessage = '';
    }

    public function resetPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_codes')
            ->where('email', $this->email)
            ->first();

        if (!$record || !Hash::check($this->code, $record->code)) {
            $this->addError('code', 'Invalid session. Please start over.');
            $this->step = 1;
            return;
        }

        $user = User::where('email', $this->email)->first();
        if (!$user) {
            $this->addError('email', 'Account not found.');
            $this->step = 1;
            return;
        }

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        DB::table('password_reset_codes')->where('email', $this->email)->delete();

        session()->flash('status', 'Your password has been reset successfully.');
        $this->redirect(route('login'));
    }

    public function goBack(): void
    {
        if ($this->step > 1) {
            $this->step--;
            $this->successMessage = '';
            $this->resetErrorBag();
        }
    }
}; ?>

<x-layouts.auth>
    <div class="flex flex-col gap-6">
        {{-- Step 1: Enter Email --}}
        @if($step === 1)
            <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a 6-digit reset code')" />

            @if($successMessage)
                <div class="p-3 text-sm text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-400 rounded-lg text-center">
                    {{ $successMessage }}
                </div>
            @endif

            <form wire:submit="sendCode" class="flex flex-col gap-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        {{ __('Email Address') }}
                    </label>
                    <input
                        id="email"
                        wire:model="email"
                        type="email"
                        required
                        autofocus
                        placeholder="email@example.com"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-colors"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="sendCode">{{ __('Send Reset Code') }}</span>
                    <span wire:loading wire:target="sendCode">{{ __('Sending...') }}</span>
                </button>
            </form>

        {{-- Step 2: Enter Code --}}
        @elseif($step === 2)
            <x-auth-header :title="__('Verify Code')" :description="__('Enter the 6-digit code sent to your email')" />

            @if($successMessage)
                <div class="p-3 text-sm text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-400 rounded-lg text-center">
                    {{ $successMessage }}
                </div>
            @endif

            <form wire:submit="verifyCode" class="flex flex-col gap-6">
                <div>
                    <label for="code" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        {{ __('6-Digit Code') }}
                    </label>
                    <input
                        id="code"
                        wire:model="code"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        required
                        autofocus
                        placeholder="000000"
                        class="w-full px-3 py-3 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-colors text-center text-2xl tracking-[0.5em] font-mono"
                    />
                    @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400 text-center">Code expires in 10 minutes</p>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="verifyCode">{{ __('Verify Code') }}</span>
                    <span wire:loading wire:target="verifyCode">{{ __('Verifying...') }}</span>
                </button>
            </form>

            <button wire:click="goBack" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 underline transition-colors text-center cursor-pointer">
                {{ __('Go back') }}
            </button>

        {{-- Step 3: New Password --}}
        @elseif($step === 3)
            <x-auth-header :title="__('Reset Password')" :description="__('Enter your new password')" />

            <form wire:submit="resetPassword" class="flex flex-col gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        {{ __('New Password') }}
                    </label>
                    <input
                        id="password"
                        wire:model="password"
                        type="password"
                        required
                        autofocus
                        autocomplete="new-password"
                        placeholder="{{ __('New password') }}"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-colors"
                    />
                    @error('password')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        {{ __('Confirm Password') }}
                    </label>
                    <input
                        id="password_confirmation"
                        wire:model="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="{{ __('Confirm password') }}"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-colors"
                    />
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="resetPassword">{{ __('Reset Password') }}</span>
                    <span wire:loading wire:target="resetPassword">{{ __('Resetting...') }}</span>
                </button>
            </form>
        @endif

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 underline transition-colors">{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts.auth>
