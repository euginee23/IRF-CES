@auth
    <!-- Authenticated sidebar for dashboard and app pages -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-zinc-900 text-zinc-100 border-r border-zinc-800 hidden lg:flex flex-col">
        <div class="h-16 flex items-center px-4 border-b border-zinc-800">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-lg font-semibold">IRF-CES</span>
            </a>
        </div>

        <div class="px-3 py-4 border-b border-zinc-800 relative">
            <button id="sidebar-profile-btn" type="button" aria-expanded="false" aria-controls="sidebar-profile-menu" class="w-full text-left flex items-center gap-3 px-2 py-2 rounded bg-zinc-800 hover:bg-zinc-700 transition-colors">
                <div class="w-9 h-9 bg-zinc-700 rounded flex items-center justify-center text-sm font-medium">{{ strtoupper(substr(auth()->user()->name ?? 'U',0,2)) }}</div>
                <div class="flex-1 text-left">
                    <div class="text-sm font-medium">{{ auth()->user()->name ?? 'User' }}</div>
                    <div class="text-xs text-zinc-400">View profile</div>
                </div>
                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            <div id="sidebar-profile-menu" class="profile-menu hidden absolute left-3 right-3 bottom-full mb-2 bg-white dark:bg-zinc-800 border border-zinc-700 rounded-lg shadow-lg overflow-hidden text-left z-60">
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-900">Profile & Settings</a>
                <div class="border-t border-zinc-100 dark:border-zinc-700"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-900">Log Out</button>
                </form>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <p class="text-xs text-zinc-400 px-3 mb-3">Platform</p>
            <ul class="space-y-1">
                <li>
                    <a href="{{ url('/dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-zinc-800 transition-colors {{ request()->is('dashboard') ? 'bg-zinc-800' : '' }}">
                        <svg class="w-5 h-5 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span class="text-sm">Dashboard</span>
                    </a>
                </li>
                @if(auth()->user()->isAdministrator())
                    <li>
                        <a href="{{ route('staff.index') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded hover:bg-zinc-800 transition-colors {{ request()->is('staff*') ? 'bg-zinc-800' : '' }}">
                            <svg class="w-5 h-5 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="text-sm">Manage Staff</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.quote-requests') }}" wire:navigate class="flex items-center gap-3 px-3 py-2 rounded hover:bg-zinc-800 transition-colors {{ request()->is('admin/quote-requests') ? 'bg-zinc-800' : '' }}">
                            <svg class="w-5 h-5 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span class="text-sm">Quote Requests</span>
                        </a>
                    </li>
                @endif
                <!-- Additional nav items removed per request -->
            </ul>
        </nav>
    </aside>

    <!-- spacer to offset page content on large screens -->
    <div class="hidden lg:block w-64 flex-shrink-0" aria-hidden="true"></div>
@else
    <!-- Top navigation for guests -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-xl font-bold text-zinc-900 dark:text-white">IRF-CES</span>
                </a>
                
                @if (Route::has('login'))
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
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
