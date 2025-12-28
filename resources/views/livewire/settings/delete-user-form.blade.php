<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-10 space-y-6" x-data="{ showModal: false }">
    <div class="relative mb-5">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Delete account') }}</h3>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <button
        type="button"
        @click="showModal = true"
        data-test="delete-user-button"
        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
    >
        {{ __('Delete account') }}
    </button>

    <!-- Modal -->
    <div
        x-show="showModal"
        x-cloak
        @keydown.escape.window="showModal = false"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div
                x-show="showModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="showModal = false"
                class="fixed inset-0 bg-zinc-500 bg-opacity-75 transition-opacity"
                aria-hidden="true"
            ></div>

            <!-- Center modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                x-show="showModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            >
                <form method="POST" wire:submit="deleteUser" class="space-y-6 p-6">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" id="modal-title">
                            {{ __('Are you sure you want to delete your account?') }}
                        </h3>

                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                        </p>
                    </div>

                    <div>
                        <label for="delete_password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            {{ __('Password') }}
                        </label>
                        <input
                            id="delete_password"
                            wire:model="password"
                            type="password"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        />
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-zinc-100 font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2"
                        >
                            {{ __('Cancel') }}
                        </button>

                        <button
                            type="submit"
                            data-test="confirm-delete-user-button"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        >
                            {{ __('Delete account') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
