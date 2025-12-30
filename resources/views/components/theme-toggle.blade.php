{{-- Theme Toggle Component --}}
<div x-data="{ 
    theme: localStorage.getItem('theme') || 'system',
    open: false,
    init() {
        this.$watch('theme', value => {
            this.applyTheme(value);
        });
        
        // Listen for theme updates from other components (e.g., settings page)
        window.addEventListener('theme-changed', (event) => {
            this.theme = event.detail.theme;
        });
        
        // Listen for Livewire theme-updated event
        Livewire.on('theme-updated', (theme) => {
            this.theme = Array.isArray(theme) ? theme[0] : theme;
        });
    },
    applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            document.cookie = 'theme=dark;path=/;max-age=31536000';
        } else if (theme === 'light') {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            document.cookie = 'theme=light;path=/;max-age=31536000';
        } else {
            localStorage.setItem('theme', 'system');
            document.cookie = 'theme=system;path=/;max-age=31536000';
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
    },
    setTheme(newTheme) {
        this.theme = newTheme;
        this.open = false;
    }
}" class="relative">
    <button @click="open = !open" @click.away="open = false"
            class="p-2 rounded-lg text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
            aria-label="Toggle theme">
        {{-- Sun icon (light mode) --}}
        <svg x-show="theme === 'light'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        
        {{-- Moon icon (dark mode) --}}
        <svg x-show="theme === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
        
        {{-- System icon --}}
        <svg x-show="theme === 'system'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
    </button>

    {{-- Dropdown menu --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50"
         style="display: none;">
        
        <button @click="setTheme('light')"
                class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition-colors cursor-pointer"
                :class="{ 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400': theme === 'light' }">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="flex-1 text-left font-medium">Light</span>
            <svg x-show="theme === 'light'" class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>

        <button @click="setTheme('dark')"
                class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition-colors cursor-pointer"
                :class="{ 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400': theme === 'dark' }">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <span class="flex-1 text-left font-medium">Dark</span>
            <svg x-show="theme === 'dark'" class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>

        <button @click="setTheme('system')"
                class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700/50 transition-colors cursor-pointer"
                :class="{ 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400': theme === 'system' }">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span class="flex-1 text-left font-medium">System</span>
            <svg x-show="theme === 'system'" class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

</div>

{{-- Non-Alpine fallback: ensure toggle works when Alpine isn't available (e.g., auth pages) --}}
<script>
    (function(){
        if (window.Alpine) return; // Alpine present — do nothing

        try {
            const root = document.currentScript && document.currentScript.previousElementSibling ? document.currentScript.previousElementSibling : null;
            // find the nearest theme-toggle container if possible
            const container = root && root.matches('[x-data]') ? root : document.querySelector('[x-data*="theme"]');
            if (!container) return;

            const btn = container.querySelector('button[aria-label="Toggle theme"]');
            const menu = container.querySelector('div[style]');
            const themeKey = 'theme';

            const getStored = () => localStorage.getItem(themeKey) || 'system';
            const apply = (theme) => {
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem(themeKey, 'dark');
                    document.cookie = 'theme=dark;path=/;max-age=31536000';
                } else if (theme === 'light') {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem(themeKey, 'light');
                    document.cookie = 'theme=light;path=/;max-age=31536000';
                } else {
                    localStorage.setItem(themeKey, 'system');
                    document.cookie = 'theme=system;path=/;max-age=31536000';
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            };

            // initialize visible icon state
            const setIconVisibility = (theme) => {
                container.querySelectorAll('svg').forEach(svg => svg.style.display = 'none');
                if (theme === 'dark') {
                    const s = container.querySelectorAll('svg')[1]; if (s) s.style.display = '';
                } else if (theme === 'light') {
                    const s = container.querySelectorAll('svg')[0]; if (s) s.style.display = '';
                } else {
                    const s = container.querySelectorAll('svg')[2]; if (s) s.style.display = '';
                }
            };

            // read current
            const initial = getStored();
            apply(initial);
            setIconVisibility(initial);

            // toggle menu visibility
            if (btn && menu) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = menu.style.display === '' || menu.style.display === 'block';
                    menu.style.display = isOpen ? 'none' : 'block';
                });
                document.addEventListener('click', () => { if (menu) menu.style.display = 'none'; });
            }

            // buttons inside menu
            const items = container.querySelectorAll('div[style] button');
            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    const btnText = item.textContent.trim().toLowerCase();
                    if (btnText.indexOf('light') !== -1) {
                        apply('light'); setIconVisibility('light');
                    } else if (btnText.indexOf('dark') !== -1) {
                        apply('dark'); setIconVisibility('dark');
                    } else {
                        apply('system'); setIconVisibility('system');
                    }
                    if (menu) menu.style.display = 'none';
                });
            });
        } catch (e) {
            console.error('Theme toggle fallback init failed', e);
        }
    })();
</script>
