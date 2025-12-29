// Livewire and Alpine are automatically loaded via @vite directive

// Ultra-fast theme management to prevent ANY flicker
const THEME_KEY = 'theme';
const DARK_BG = '#09090b';
const LIGHT_BG = '#ffffff';

function isDarkMode() {
    const theme = localStorage.getItem(THEME_KEY) || 'system';
    return theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
}

function forceApplyTheme() {
    const html = document.documentElement;
    const dark = isDarkMode();
    
    // Use requestAnimationFrame for immediate visual update
    requestAnimationFrame(() => {
        if (dark) {
            html.classList.add('dark');
            html.setAttribute('data-theme', 'dark');
            html.style.cssText = `background-color: ${DARK_BG} !important; color-scheme: dark !important;`;
            if (document.body) {
                document.body.style.cssText = `background-color: ${DARK_BG} !important;`;
            }
        } else {
            html.classList.remove('dark');
            html.setAttribute('data-theme', 'light');
            html.style.cssText = `background-color: ${LIGHT_BG} !important; color-scheme: light !important;`;
            if (document.body) {
                document.body.style.cssText = `background-color: ${LIGHT_BG} !important;`;
            }
        }
    });
}

function applyTheme(theme) {
    // Store preference
    localStorage.setItem(THEME_KEY, theme);
    // Apply immediately
    forceApplyTheme();
}

// Apply immediately on script load
forceApplyTheme();

// Ultra-aggressive protection: Watch for ANY DOM changes that might affect theme
const observer = new MutationObserver(() => {
    const html = document.documentElement;
    const shouldBeDark = isDarkMode();
    
    if (shouldBeDark && !html.classList.contains('dark')) {
        requestAnimationFrame(() => {
            html.classList.add('dark');
            html.style.backgroundColor = DARK_BG;
        });
    } else if (!shouldBeDark && html.classList.contains('dark')) {
        requestAnimationFrame(() => {
            html.classList.remove('dark');
            html.style.backgroundColor = LIGHT_BG;
        });
    }
});

observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class'],
    childList: false
});

// Livewire navigation hooks - apply theme at every stage
document.addEventListener('livewire:navigating', forceApplyTheme);
document.addEventListener('livewire:navigate', forceApplyTheme);
document.addEventListener('livewire:navigated', forceApplyTheme);

// Livewire initialization
document.addEventListener('livewire:init', () => {
    // Listen for theme updates from components
    Livewire.on('theme-updated', (event) => {
        const theme = event[0] || 'system';
        applyTheme(theme);
    });
    
    // Apply on init
    forceApplyTheme();
});

// Watch for system theme changes when in system mode
const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
darkModeMediaQuery.addEventListener('change', (e) => {
    const storedTheme = localStorage.getItem('theme') || 'system';
    if (storedTheme === 'system') {
        applyTheme('system');
    }
});




