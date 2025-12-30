@auth
    <!-- Authenticated top navbar matching landing page -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-700 shadow-sm dark:shadow-black/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">IRF-CES</span>
                </a>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ url('/dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('dashboard') || request()->is('admin/dashboard') || request()->is('technician/dashboard') || request()->is('counter/dashboard') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                        Dashboard
                    </a>
                    @if(auth()->user()->isAdministrator())
                        <a href="{{ route('staff.index') }}" wire:navigate class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('staff*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                            Staff
                        </a>
                        <a href="{{ route('admin.quote-requests') }}" wire:navigate class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/quote-requests') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                            Quotes
                        </a>
                        <a href="{{ route('admin.parts-inventory') }}" wire:navigate class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/parts-inventory') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                            Inventory
                        </a>
                        <a href="{{ route('admin.suppliers') }}" wire:navigate class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/suppliers') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' }}">
                            Suppliers
                        </a>
                    @endif
                </div>

                <!-- User Menu -->
                <div class="flex items-center gap-3">
                    {{-- Theme Toggle --}}
                    <x-theme-toggle />
                    
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" 
                                class="flex items-center gap-2 px-3 py-2 rounded-xl transition-all duration-200 cursor-pointer"
                                :class="open ? 'bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-500/20' : 'hover:bg-zinc-100 dark:hover:bg-zinc-700'">
                            <div class="relative w-9 h-9 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-lg ring-2 ring-white dark:ring-zinc-900 transform transition-transform"
                                 :class="{'scale-110': open}">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U',0,1)) }}
                                <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white dark:border-zinc-900"></div>
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ auth()->user()->name ?? 'User' }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->role?->label() }}</div>
                            </div>
                            <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Enhanced Dropdown -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95 -translate-y-2"
                             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="transform opacity-0 scale-95 -translate-y-2"
                             class="absolute right-0 mt-3 w-72 bg-white dark:bg-zinc-800 dark:backdrop-blur-md rounded-2xl shadow-xl dark:shadow-black/50 border border-zinc-200 dark:border-zinc-700 overflow-hidden"
                             style="display: none;">
                            
                            <!-- User Info Header -->
                            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center text-white text-lg font-bold ring-2 ring-white/30">
                                        {{ strtoupper(substr(auth()->user()->name ?? 'U',0,1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-blue-100 truncate">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/20 backdrop-blur-sm rounded-lg">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <span class="text-xs font-semibold text-white">{{ auth()->user()->role?->label() }}</span>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="p-2">
                                <a href="{{ route('profile.edit') }}" 
                                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-all group cursor-pointer">
                                    <div class="w-9 h-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 transition-colors">
                                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold">Profile Settings</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Manage your account</div>
                                    </div>
                                    <svg class="w-4 h-4 text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>

                            <!-- Logout Button -->
                            <div class="p-2 border-t border-zinc-100 dark:border-zinc-700">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" 
                                            class="flex items-center gap-3 w-full px-4 py-3 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all group cursor-pointer">
                                        <div class="w-9 h-9 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center group-hover:bg-red-100 dark:group-hover:bg-red-900/30 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <div class="font-semibold">Log Out</div>
                                            <div class="text-xs text-red-500 dark:text-red-400">Sign out of your account</div>
                                        </div>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="md:hidden border-t border-zinc-200 dark:border-zinc-700" x-data="{ mobileOpen: false }">
            <div class="px-4 py-2">
                <button @click="mobileOpen = !mobileOpen" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    <span>Menu</span>
                    <svg class="w-5 h-5" :class="{'rotate-180': mobileOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="mobileOpen" x-transition class="mt-2 space-y-1" style="display: none;">
                    <a href="{{ url('/dashboard') }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->is('dashboard') || request()->is('admin/dashboard') || request()->is('technician/dashboard') || request()->is('counter/dashboard') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                        Dashboard
                    </a>
                    @if(auth()->user()->isAdministrator())
                        <a href="{{ route('staff.index') }}" wire:navigate class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->is('staff*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                            Staff
                        </a>
                        <a href="{{ route('admin.quote-requests') }}" wire:navigate class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->is('admin/quote-requests') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                            Quotes
                        </a>
                        <a href="{{ route('admin.parts-inventory') }}" wire:navigate class="block px-3 py-2 rounded-lg text-sm font-medium {{ request()->is('admin/parts-inventory') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                            Inventory
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </nav>
@else
    <!-- Top navigation for guests -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-700/50 shadow-sm dark:shadow-zinc-950/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-xl font-bold text-zinc-900 dark:text-zinc-100">IRF-CES</span>
                </a>
                
                @if (Route::has('login'))
                    <div class="flex items-center gap-3">
                        {{-- Theme Toggle for guests --}}
                        <x-theme-toggle />
                        
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 rounded-lg transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 rounded-lg transition-colors">
                                Staff Login
                            </a>
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </nav>
@endauth

<script>
// Profile dropdown toggle for sidebar with enter/exit animations
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('sidebar-profile-btn');
    const menu = document.getElementById('sidebar-profile-menu');

    if (!btn || !menu) return;

    const ANIM_DURATION = 200; // ms, keep in sync with CSS

    const openMenu = () => {
        // Reset any previous inline positioning
        menu.style.top = '';
        menu.style.bottom = '';

        // Make visible to measure
        menu.classList.remove('hidden');
        menu.style.visibility = 'hidden';

        const btnRect = btn.getBoundingClientRect();
        const menuHeight = menu.scrollHeight;
        const spaceBelow = window.innerHeight - btnRect.bottom;
        const spaceAbove = btnRect.top;

        // Decide where to position the menu
        if (spaceBelow >= menuHeight + 8) {
            // place below the button
            menu.style.top = '100%';
            menu.style.bottom = 'auto';
            menu.classList.remove('animate-slide-up');
            // trigger enter animation
            requestAnimationFrame(() => {
                menu.style.visibility = '';
                menu.classList.add('animate-slide-down');
            });
        } else if (spaceAbove >= menuHeight + 8) {
            // place above the button
            menu.style.bottom = '100%';
            menu.style.top = 'auto';
            menu.classList.remove('animate-slide-down');
            requestAnimationFrame(() => {
                menu.style.visibility = '';
                menu.classList.add('animate-slide-up');
            });
        } else {
            // default to below but constrain with max-height and allow scroll
            menu.style.top = '100%';
            menu.style.bottom = 'auto';
            menu.style.maxHeight = Math.max(100, spaceBelow - 16) + 'px';
            requestAnimationFrame(() => {
                menu.style.visibility = '';
                menu.classList.add('animate-slide-down');
            });
        }

        btn.setAttribute('aria-expanded', 'true');
    };

    const closeMenu = () => {
        // play exit animation then hide
        if (menu.classList.contains('animate-slide-down')) {
            menu.classList.remove('animate-slide-down');
            menu.classList.add('animate-fade-out-down');
        } else if (menu.classList.contains('animate-slide-up')) {
            menu.classList.remove('animate-slide-up');
            menu.classList.add('animate-fade-out-up');
        }

        btn.setAttribute('aria-expanded', 'false');

        setTimeout(() => {
            menu.classList.add('hidden');
            menu.classList.remove('animate-fade-out-down', 'animate-fade-out-up');
            // cleanup inline styles
            menu.style.maxHeight = '';
            menu.style.top = '';
            menu.style.bottom = '';
        }, ANIM_DURATION);
    };

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (menu.classList.contains('hidden')) openMenu(); else closeMenu();
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target) && !btn.contains(e.target)) closeMenu();
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });
});
</script>

<style>
/* Enter animations */
@keyframes slide-down {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes slide-up {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}
/* Exit animations */
@keyframes fade-out-down {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(-6px); }
}
@keyframes fade-out-up {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(6px); }
}

.animate-slide-down { animation: slide-down 0.18s cubic-bezier(.2,.8,.2,1) both; }
.animate-slide-up { animation: slide-up 0.18s cubic-bezier(.2,.8,.2,1) both; }
.animate-fade-out-down { animation: fade-out-down 0.14s ease-in both; }
.animate-fade-out-up { animation: fade-out-up 0.14s ease-in both; }

/* Make sure the menu can scroll when constrained */
.profile-menu { overflow: auto; }
</style>
