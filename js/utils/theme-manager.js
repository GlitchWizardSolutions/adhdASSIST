/**
 * Theme Manager - Centralized theme management
 * Handles theme switching, persistence, and CSS variable updates
 */

const ThemeManager = (function() {
    'use strict';

    const STORAGE_KEY = 'adhd_dashboard_theme';
    const THEMES = ['light', 'dark', 'blue', 'green', 'purple'];
    const DEFAULT_THEME = 'light';

    // Theme color definitions
    const THEME_COLORS = {
        light: {
            primary: '#FFB300',
            secondary: '#6C757D',
            accent: '#FF9F43',
            text: '#2D3A4E'
        },
        dark: {
            primary: '#FFB300',
            secondary: '#A0AEC0',
            accent: '#FF9F43',
            text: '#E2E8F0'
        },
        blue: {
            primary: '#3B82F6',
            secondary: '#1E40AF',
            accent: '#1E3A8A',
            text: '#1F2937'
        },
        green: {
            primary: '#10B981',
            secondary: '#059669',
            accent: '#047857',
            text: '#1F2937'
        },
        purple: {
            primary: '#8B5CF6',
            secondary: '#7C3AED',
            accent: '#6D28D9',
            text: '#1F2937'
        }
    };

    /**
     * Initialize theme manager
     */
    function init() {
        const savedTheme = getSavedTheme();
        const theme = THEMES.includes(savedTheme) ? savedTheme : DEFAULT_THEME;
        applyTheme(theme);
        observeThemeChanges();
    }

    /**
     * Get saved theme from storage
     * @returns {string}
     */
    function getSavedTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
        } catch (e) {
            console.warn('[ThemeManager] Could not access localStorage', e);
            return DEFAULT_THEME;
        }
    }

    /**
     * Apply theme to document
     * @param {string} theme - Theme name
     */
    function applyTheme(theme) {
        if (!THEMES.includes(theme)) {
            console.warn(`[ThemeManager] Invalid theme: ${theme}`);
            return;
        }

        // Set data attribute on body
        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);

        // Update CSS variables
        updateCSSVariables(theme);

        // Save to storage
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {
            console.warn('[ThemeManager] Could not save theme to localStorage', e);
        }

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
    }

    /**
     * Update CSS custom properties for theme
     * @param {string} theme - Theme name
     */
    function updateCSSVariables(theme) {
        const root = document.documentElement;
        const colors = THEME_COLORS[theme] || THEME_COLORS[DEFAULT_THEME];

        Object.entries(colors).forEach(([key, value]) => {
            root.style.setProperty(`--color-${key}`, value);
        });
    }

    /**
     * Get current theme
     * @returns {string}
     */
    function getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || DEFAULT_THEME;
    }

    /**
     * Get all available themes
     * @returns {Array<string>}
     */
    function getThemes() {
        return [...THEMES];
    }

    /**
     * Check if theme exists
     * @param {string} theme - Theme name
     * @returns {boolean}
     */
    function themeExists(theme) {
        return THEMES.includes(theme);
    }

    /**
     * Get theme colors
     * @param {string} theme - Theme name
     * @returns {Object}
     */
    function getThemeColors(theme) {
        return THEME_COLORS[theme] || THEME_COLORS[DEFAULT_THEME];
    }

    /**
     * Switch theme
     * @param {string} theme - Theme name
     * @returns {boolean} - Success
     */
    function switchTheme(theme) {
        if (!THEMES.includes(theme)) {
            console.error(`[ThemeManager] Invalid theme: ${theme}`);
            return false;
        }

        applyTheme(theme);
        return true;
    }

    /**
     * Cycle to next theme
     * @returns {string} - New theme
     */
    function nextTheme() {
        const current = getCurrentTheme();
        const index = THEMES.indexOf(current);
        const next = THEMES[(index + 1) % THEMES.length];
        switchTheme(next);
        return next;
    }

    /**
     * Cycle to previous theme
     * @returns {string} - New theme
     */
    function previousTheme() {
        const current = getCurrentTheme();
        const index = THEMES.indexOf(current);
        const next = THEMES[(index - 1 + THEMES.length) % THEMES.length];
        switchTheme(next);
        return next;
    }

    /**
     * Reset to default theme
     */
    function resetTheme() {
        applyTheme(DEFAULT_THEME);
    }

    /**
     * Get system preference (light or dark)
     * @returns {string}
     */
    function getSystemPreference() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    /**
     * Respect system preference
     */
    function respectSystemPreference() {
        const pref = getSystemPreference();
        applyTheme(pref);
    }

    /**
     * Observe system theme changes
     */
    function observeSystemTheme() {
        if (!window.matchMedia) return;

        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        darkModeQuery.addEventListener('change', (e) => {
            const theme = e.matches ? 'dark' : 'light';
            applyTheme(theme);
        });
    }

    /**
     * Observe theme selector changes
     */
    function observeThemeChanges() {
        const selectors = document.querySelectorAll('[data-theme-selector]');
        selectors.forEach(selector => {
            selector.addEventListener('change', (e) => {
                switchTheme(e.target.value);
            });
        });
    }

    /**
     * Export current theme as CSS
     * @returns {string}
     */
    function exportAsCSS() {
        const theme = getCurrentTheme();
        const colors = getThemeColors(theme);
        let css = `:root[data-theme="${theme}"] {\n`;
        Object.entries(colors).forEach(([key, value]) => {
            css += `  --color-${key}: ${value};\n`;
        });
        css += '}\n';
        return css;
    }

    // Public API
    return {
        init,
        getSavedTheme,
        applyTheme,
        getCurrentTheme,
        getThemes,
        themeExists,
        getThemeColors,
        switchTheme,
        nextTheme,
        previousTheme,
        resetTheme,
        getSystemPreference,
        respectSystemPreference,
        observeSystemTheme,
        observeThemeChanges,
        exportAsCSS
    };
})();

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ThemeManager.init());
} else {
    ThemeManager.init();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
