<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? 'IRF-CES - Intelligent Repair Flow & Client Engagement System' }}</title>

{{-- CRITICAL: Apply theme IMMEDIATELY - This MUST run before anything else --}}
<script>
    // STEP 1: Detect and apply theme SYNCHRONOUSLY (blocking is REQUIRED)
    (function() {
        const html = document.documentElement;
        
        // Disable ALL transitions globally until page loads
        html.classList.add('theme-loading');
        
        const theme = localStorage.getItem('theme') || 'system';
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = theme === 'dark' || (theme === 'system' && prefersDark);
        
        if (isDark) {
            html.classList.add('dark');
            html.setAttribute('data-theme', 'dark');
            html.style.backgroundColor = '#1e1e1e';
            html.style.colorScheme = 'dark';
            // Set color-scheme meta dynamically
            const meta = document.createElement('meta');
            meta.name = 'color-scheme';
            meta.content = 'dark';
            document.head.appendChild(meta);
        } else {
            html.classList.remove('dark');
            html.setAttribute('data-theme', 'light');
            html.style.backgroundColor = '#ffffff';
            html.style.colorScheme = 'light';
            const meta = document.createElement('meta');
            meta.name = 'color-scheme';
            meta.content = 'light';
            document.head.appendChild(meta);
        }
        
        // Store reusable function for navigation
        window.__applyTheme = function() {
            const theme = localStorage.getItem('theme') || 'system';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = theme === 'dark' || (theme === 'system' && prefersDark);
            
            if (isDark) {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                html.style.backgroundColor = '#1e1e1e';
                html.style.colorScheme = 'dark';
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
                html.style.backgroundColor = '#ffffff';
                html.style.colorScheme = 'light';
            }
        };
    })();
</script>

{{-- Ultra-aggressive inline styles - MUST be before Vite assets --}}
<style>
    /* CRITICAL: Force theme colors at highest specificity */
    html {
        margin: 0;
        padding: 0;
    }
    
    body {
        margin: 0;
        padding: 0;
    }
    
    /* Dark mode - Maximum priority - Improved visibility */
    html.dark {
        background-color: #1e1e1e !important;
        color: #f5f5f5 !important;
        color-scheme: dark !important;
    }
    
    html.dark body {
        background-color: #1e1e1e !important;
        color: #f5f5f5 !important;
    }
    
    html.dark *:not(svg):not(path):not(circle):not(rect):not(line):not(polyline):not(polygon) {
        border-color: rgb(63 63 70 / 0.5);
    }
    
    /* Light mode - Maximum priority */
    html:not(.dark) {
        background-color: #ffffff !important;
        color: #18181b !important;
        color-scheme: light !important;
    }
    
    html:not(.dark) body {
        background-color: #ffffff !important;
        color: #18181b !important;
    }
    
    /* Disable transitions until page fully loads */
    html.theme-loading,
    html.theme-loading *,
    html.theme-loading *::before,
    html.theme-loading *::after {
        transition: none !important;
        animation-duration: 0s !important;
    }
</style>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Re-enable transitions after everything loads --}}
<script>
    // Wait for complete page load before enabling transitions
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.documentElement.classList.remove('theme-loading');
            }, 200);
        });
    } else {
        // Page already loaded
        setTimeout(function() {
            document.documentElement.classList.remove('theme-loading');
        }, 200);
    }
    
    // Handle Livewire navigation - CRITICAL for maintaining theme
    document.addEventListener('livewire:navigating', function() {
        const html = document.documentElement;
        // Keep transitions disabled during navigation
        html.classList.add('theme-loading');
        // Apply theme IMMEDIATELY before any content changes
        if (window.__applyTheme) {
            window.__applyTheme();
        }
    });
    
    document.addEventListener('livewire:navigated', function() {
        // Re-apply theme IMMEDIATELY after navigation
        if (window.__applyTheme) {
            window.__applyTheme();
        }
        // Wait longer before re-enabling transitions to ensure content is stable
        setTimeout(function() {
            document.documentElement.classList.remove('theme-loading');
        }, 150);
    });
    
    // Also apply on page show (back/forward navigation)
    window.addEventListener('pageshow', function(event) {
        if (window.__applyTheme) {
            window.__applyTheme();
        }
    });
</script>
