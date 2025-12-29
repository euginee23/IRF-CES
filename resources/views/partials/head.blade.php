<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="color-scheme" content="light dark">

<title>{{ $title ?? 'IRF-CES - Intelligent Repair Flow & Client Engagement System' }}</title>

{{-- CRITICAL: Apply theme IMMEDIATELY before anything else renders --}}
<script>
    // Ultra-fast theme detection and application - runs synchronously
    (function() {
        try {
            const theme = localStorage.getItem('theme') || 'system';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = theme === 'dark' || (theme === 'system' && prefersDark);
            const html = document.documentElement;
            
            if (isDark) {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                html.style.cssText = 'background-color: #09090b !important; color-scheme: dark !important;';
            } else {
                html.classList.remove('dark');
                html.setAttribute('data-theme', 'light');
                html.style.cssText = 'background-color: #ffffff !important; color-scheme: light !important;';
            }
        } catch(e) {}
    })();
</script>

{{-- Ultra-aggressive inline styles - applied BEFORE any external CSS --}}
<style>
    /* Force immediate theme application - highest priority */
    html, body {
        margin: 0;
        padding: 0;
    }
    
    html.dark,
    html.dark body,
    html[data-theme="dark"],
    html[data-theme="dark"] body {
        background-color: #09090b !important;
        color: #fafafa !important;
        color-scheme: dark !important;
    }
    
    html:not(.dark),
    html:not(.dark) body,
    html[data-theme="light"],
    html[data-theme="light"] body {
        background-color: #ffffff !important;
        color: #18181b !important;
        color-scheme: light !important;
    }
    
    /* Prevent any transition during initial load and navigation */
    html, html *, html *::before, html *::after {
        transition: none !important;
        animation-duration: 0s !important;
    }
    
    /* Force dark mode on all common container elements */
    html.dark main,
    html.dark div,
    html.dark section,
    html.dark article,
    html.dark nav,
    html.dark header,
    html.dark footer {
        border-color: rgb(63 63 70 / 0.5);
    }
</style>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
