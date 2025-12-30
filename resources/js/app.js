// Livewire and Alpine are automatically loaded via @vite directive

// Theme management - simplified to work with head.blade.php
const THEME_KEY = 'theme';

function isDarkMode() {
    const theme = localStorage.getItem(THEME_KEY) || 'system';
    return theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
}

function applyTheme(theme) {
    localStorage.setItem(THEME_KEY, theme);
    const html = document.documentElement;
    const dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    
    // Temporarily disable transitions during theme change
    html.classList.add('theme-loading');
    
    if (dark) {
        html.classList.add('dark');
        html.setAttribute('data-theme', 'dark');
    } else {
        html.classList.remove('dark');
        html.setAttribute('data-theme', 'light');
    }
    
    // Re-enable transitions after theme is applied
    setTimeout(() => {
        html.classList.remove('theme-loading');
    }, 50);
}

// Livewire initialization
document.addEventListener('livewire:init', () => {
    // Listen for theme updates from components
    Livewire.on('theme-updated', (event) => {
        const theme = event[0] || 'system';
        applyTheme(theme);
    });
});

// Watch for system theme changes when in system mode
const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
darkModeMediaQuery.addEventListener('change', (e) => {
    const storedTheme = localStorage.getItem(THEME_KEY) || 'system';
    if (storedTheme === 'system') {
        applyTheme('system');
    }
});




