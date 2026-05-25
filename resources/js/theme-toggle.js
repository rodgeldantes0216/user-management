const themeCookieName = 'theme';
const themeToggleId = 'themeToggle';
const themeToggleIconId = 'themeToggleIcon';
const themeToggleLabelId = 'themeToggleLabel';

const Theme = {
    DARK: 'dark',
    LIGHT: 'light',
};

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop().split(';').shift();
    }
    return null;
}

function setCookie(name, value, days = 365) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = `; expires=${date.toUTCString()}`;
    }
    document.cookie = `${name}=${encodeURIComponent(value)};path=/;SameSite=Lax${expires}`;
}

function applyTheme(theme) {
    const root = document.documentElement;
    const isDark = theme === Theme.DARK;

    root.classList.toggle(Theme.DARK, isDark);
    root.classList.toggle(Theme.LIGHT, !isDark);
    root.style.colorScheme = theme;

    const icon = document.getElementById(themeToggleIconId);
    const label = document.getElementById(themeToggleLabelId);

    if (icon && label) {
        if (isDark) {
            icon.textContent = '☀️';
            label.textContent = 'Light mode';
        } else {
            icon.textContent = '🌙';
            label.textContent = 'Dark mode';
        }
    }
}

function getPreferredTheme() {
    const persistedTheme = getCookie(themeCookieName);
    if (persistedTheme === Theme.DARK || persistedTheme === Theme.LIGHT) {
        return persistedTheme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? Theme.DARK : Theme.LIGHT;
}

function toggleTheme() {
    const currentTheme = document.documentElement.classList.contains(Theme.DARK) ? Theme.DARK : Theme.LIGHT;
    const nextTheme = currentTheme === Theme.DARK ? Theme.LIGHT : Theme.DARK;
    setCookie(themeCookieName, nextTheme, 365);
    applyTheme(nextTheme);
}

function initThemeToggle() {
    const initialTheme = getPreferredTheme();
    applyTheme(initialTheme);

    // Delegate clicks so the handler survives Livewire DOM swaps
    document.addEventListener('click', (ev) => {
        const btn = ev.target instanceof Element ? ev.target.closest(`#${themeToggleId}`) : null;
        if (btn) {
            ev.preventDefault();
            toggleTheme();
        }
    });

    // Re-apply theme after Livewire updates (keeps UI in sync when navigation swaps DOM)
    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        try {
            window.Livewire.hook('message.processed', () => {
                applyTheme(getPreferredTheme());
            });
        } catch (e) {
            // ignore hook failures
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initThemeToggle);
} else {
    initThemeToggle();
}
