<div 
    x-data="{
        show: false,
        title: 'Delete Confirmation',
        message: 'Are you sure you want to delete this item? This action cannot be undone.',
        confirmText: 'Delete',
        cancelText: 'Cancel',
        callback: null,
        
        openDialog(data) {
            this.title = data.title || 'Delete Confirmation';
            this.message = data.message || 'Are you sure you want to delete this item? This action cannot be undone.';
            this.confirmText = data.confirmText || 'Delete';
            this.cancelText = data.cancelText || 'Cancel';
            this.callback = data.callback || null;
            this.show = true;
        },
        
        confirm() {
            if (typeof this.callback === 'function') {
                this.callback();
            }
            this.show = false;
        },
        
        cancel() {
            this.show = false;
        }
    }"
    @open-delete-dialog.window="openDialog($event.detail)"
    class="relative z-50"
    style="display: none;"
    x-show="show"
>
    <!-- Backdrop -->
    <div 
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="cancel()"
        class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity"
    ></div>

    <!-- Dialog -->
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.away="cancel()"
                class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-zinc-800 shadow-2xl ring-1 ring-zinc-950/5 dark:ring-white/10 transition-all"
            >
                <!-- Icon & Content -->
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/20">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white" x-text="title"></h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400" x-text="message"></p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-zinc-50 dark:bg-zinc-800/50 px-6 py-4 flex items-center justify-end gap-3 border-t border-zinc-200 dark:border-zinc-700">
                    <button 
                        type="button"
                        @click="cancel()"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-500 dark:focus:ring-offset-zinc-900 transition-all"
                        x-text="cancelText"
                    ></button>
                    <button 
                        type="button"
                        @click="confirm()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-zinc-900 shadow-sm hover:shadow transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span x-text="confirmText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
