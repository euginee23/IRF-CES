<div 
    x-data="{
        show: false,
        message: '',
        type: 'success',
        title: '',
        timeout: null,
        showNotification(data) {
            this.message = data.message || '';
            this.type = data.type || 'success';
            this.title = data.title || this.getDefaultTitle(data.type);
            this.show = true;
            
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                this.show = false;
            }, 4000);
        },
        getDefaultTitle(type) {
            const titles = {
                'success': 'Success',
                'error': 'Error',
                'warning': 'Warning',
                'info': 'Info'
            };
            return titles[type] || 'Notification';
        },
        getIcon() {
            const icons = {
                'success': '&lt;path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; stroke-width=&quot;2&quot; d=&quot;M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z&quot;/&gt;',
                'error': '&lt;path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; stroke-width=&quot;2&quot; d=&quot;M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z&quot;/&gt;',
                'warning': '&lt;path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; stroke-width=&quot;2&quot; d=&quot;M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z&quot;/&gt;',
                'info': '&lt;path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; stroke-width=&quot;2&quot; d=&quot;M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z&quot;/&gt;'
            };
            return icons[this.type] || icons['info'];
        },
        getColors() {
            const colors = {
                'success': {
                    bg: 'bg-emerald-50 dark:bg-emerald-950',
                    border: 'border-emerald-200 dark:border-emerald-800',
                    icon: 'text-emerald-600 dark:text-emerald-400',
                    iconBg: 'bg-emerald-100 dark:bg-emerald-900',
                    title: 'text-emerald-900 dark:text-emerald-100',
                    text: 'text-emerald-700 dark:text-emerald-300',
                    ring: 'ring-emerald-600/20'
                },
                'error': {
                    bg: 'bg-red-50 dark:bg-red-950',
                    border: 'border-red-200 dark:border-red-800',
                    icon: 'text-red-600 dark:text-red-400',
                    iconBg: 'bg-red-100 dark:bg-red-900',
                    title: 'text-red-900 dark:text-red-100',
                    text: 'text-red-700 dark:text-red-300',
                    ring: 'ring-red-600/20'
                },
                'warning': {
                    bg: 'bg-amber-50 dark:bg-amber-950',
                    border: 'border-amber-200 dark:border-amber-800',
                    icon: 'text-amber-600 dark:text-amber-400',
                    iconBg: 'bg-amber-100 dark:bg-amber-900',
                    title: 'text-amber-900 dark:text-amber-100',
                    text: 'text-amber-700 dark:text-amber-300',
                    ring: 'ring-amber-600/20'
                },
                'info': {
                    bg: 'bg-blue-50 dark:bg-blue-950',
                    border: 'border-blue-200 dark:border-blue-800',
                    icon: 'text-blue-600 dark:text-blue-400',
                    iconBg: 'bg-blue-100 dark:bg-blue-900',
                    title: 'text-blue-900 dark:text-blue-100',
                    text: 'text-blue-700 dark:text-blue-300',
                    ring: 'ring-blue-600/20'
                }
            };
            return colors[this.type] || colors['info'];
        }
    }"
    x-on:notify.window="showNotification($event.detail)"
    x-on:success.window="showNotification({message: $event.detail.message || $event.detail, type: 'success'})"
    x-on:error.window="showNotification({message: $event.detail.message || $event.detail, type: 'error'})"
    x-on:warning.window="showNotification({message: $event.detail.message || $event.detail, type: 'warning'})"
    x-on:info.window="showNotification({message: $event.detail.message || $event.detail, type: 'info'})"
    class="fixed top-4 right-4 z-50 pointer-events-none"
>
    <div 
        x-show="show"
        x-transition:enter="transform ease-out duration-300 transition"
        x-transition:enter-start="translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transform ease-in duration-200 transition"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="translate-x-full opacity-0"
        class="pointer-events-auto w-full max-w-sm"
        style="display: none;"
    >
        <div 
            x-bind:class="getColors().bg + ' ' + getColors().border + ' ' + getColors().ring"
            class="relative rounded-xl border shadow-lg ring-1 overflow-hidden"
        >
            {{-- Progress bar --}}
            <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-transparent via-current to-transparent opacity-20"
                 x-bind:class="getColors().icon"
                 x-show="show"
                 x-transition:enter="transition-all ease-linear duration-[4000ms]"
                 x-transition:enter-start="w-full"
                 x-transition:enter-end="w-0"
                 style="width: 100%;">
            </div>

            <div class="p-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <div x-bind:class="getColors().iconBg" class="p-2 rounded-lg">
                            <svg x-bind:class="getColors().icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-html="getIcon()">
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p x-bind:class="getColors().title" class="text-sm font-semibold" x-text="title"></p>
                        <p x-bind:class="getColors().text" class="mt-1 text-sm" x-text="message"></p>
                    </div>
                    <button 
                        x-on:click="show = false"
                        x-bind:class="getColors().icon"
                        class="flex-shrink-0 inline-flex rounded-lg p-1.5 hover:bg-black/5 dark:hover:bg-white/5 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
