/**
 * Preferences Manager - User preferences and settings storage
 * Handles local storage and session data management
 */

const PreferencesManager = (function() {
    'use strict';

    const STORAGE_PREFIX = 'adhd_dashboard_';

    // Default preferences
    const DEFAULTS = {
        theme: 'light',
        sidebarCollapsed: false,
        notificationsEnabled: true,
        soundEnabled: true,
        compactMode: false,
        autoSave: true
    };

    /**
     * Initialize preferences from storage
     */
    function init() {
        // Load saved preferences
        loadPreferences();
    }

    /**
     * Get preference value
     * @param {string} key - Preference key
     * @param {*} defaultValue - Default value if not set
     * @returns {*}
     */
    function get(key, defaultValue = null) {
        try {
            const stored = localStorage.getItem(STORAGE_PREFIX + key);
            if (stored === null) {
                return defaultValue !== null ? defaultValue : DEFAULTS[key];
            }

            // Try to parse as JSON
            try {
                return JSON.parse(stored);
            } catch {
                // Return as string if not JSON
                return stored;
            }
        } catch (e) {
            console.warn(`[PreferencesManager] Could not get preference: ${key}`, e);
            return defaultValue !== null ? defaultValue : DEFAULTS[key];
        }
    }

    /**
     * Set preference value
     * @param {string} key - Preference key
     * @param {*} value - Value to set
     * @returns {boolean} - Success
     */
    function set(key, value) {
        try {
            const serialized = typeof value === 'string' ? value : JSON.stringify(value);
            localStorage.setItem(STORAGE_PREFIX + key, serialized);
            
            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('preference-changed', {
                detail: { key, value }
            }));

            return true;
        } catch (e) {
            console.warn(`[PreferencesManager] Could not set preference: ${key}`, e);
            return false;
        }
    }

    /**
     * Remove preference
     * @param {string} key - Preference key
     * @returns {boolean} - Success
     */
    function remove(key) {
        try {
            localStorage.removeItem(STORAGE_PREFIX + key);
            return true;
        } catch (e) {
            console.warn(`[PreferencesManager] Could not remove preference: ${key}`, e);
            return false;
        }
    }

    /**
     * Clear all preferences
     */
    function clear() {
        try {
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith(STORAGE_PREFIX)) {
                    localStorage.removeItem(key);
                }
            });
        } catch (e) {
            console.warn('[PreferencesManager] Could not clear preferences', e);
        }
    }

    /**
     * Get all preferences
     * @returns {Object}
     */
    function getAll() {
        const prefs = { ...DEFAULTS };
        try {
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith(STORAGE_PREFIX)) {
                    const prefKey = key.substring(STORAGE_PREFIX.length);
                    prefs[prefKey] = get(prefKey);
                }
            });
        } catch (e) {
            console.warn('[PreferencesManager] Could not get all preferences', e);
        }
        return prefs;
    }

    /**
     * Load and apply saved preferences
     */
    function loadPreferences() {
        const theme = get('theme', DEFAULTS.theme);
        const sidebarCollapsed = get('sidebarCollapsed', DEFAULTS.sidebarCollapsed);
        const compactMode = get('compactMode', DEFAULTS.compactMode);

        // Apply theme
        if (window.ThemeManager) {
            ThemeManager.applyTheme(theme);
        }

        // Apply sidebar state
        if (sidebarCollapsed) {
            document.body.classList.add('sidebar-collapsed');
        }

        // Apply compact mode
        if (compactMode) {
            document.body.classList.add('compact-mode');
        }
    }

    /**
     * Toggle preference boolean value
     * @param {string} key - Preference key
     * @returns {boolean} - New value
     */
    function toggle(key) {
        const current = get(key, DEFAULTS[key]);
        const newValue = !current;
        set(key, newValue);
        return newValue;
    }

    /**
     * Get theme preference
     */
    function getTheme() {
        return get('theme', DEFAULTS.theme);
    }

    /**
     * Set theme preference
     */
    function setTheme(theme) {
        set('theme', theme);
        if (window.ThemeManager) {
            ThemeManager.applyTheme(theme);
        }
    }

    /**
     * Get notifications enabled
     */
    function isNotificationsEnabled() {
        return get('notificationsEnabled', DEFAULTS.notificationsEnabled);
    }

    /**
     * Set notifications enabled
     */
    function setNotificationsEnabled(enabled) {
        set('notificationsEnabled', enabled);
    }

    /**
     * Get sound enabled
     */
    function isSoundEnabled() {
        return get('soundEnabled', DEFAULTS.soundEnabled);
    }

    /**
     * Set sound enabled
     */
    function setSoundEnabled(enabled) {
        set('soundEnabled', enabled);
    }

    /**
     * Get sidebar collapsed state
     */
    function isSidebarCollapsed() {
        return get('sidebarCollapsed', DEFAULTS.sidebarCollapsed);
    }

    /**
     * Set sidebar collapsed state
     */
    function setSidebarCollapsed(collapsed) {
        set('sidebarCollapsed', collapsed);
        if (collapsed) {
            document.body.classList.add('sidebar-collapsed');
        } else {
            document.body.classList.remove('sidebar-collapsed');
        }
    }

    /**
     * Get compact mode
     */
    function isCompactMode() {
        return get('compactMode', DEFAULTS.compactMode);
    }

    /**
     * Set compact mode
     */
    function setCompactMode(compact) {
        set('compactMode', compact);
        if (compact) {
            document.body.classList.add('compact-mode');
        } else {
            document.body.classList.remove('compact-mode');
        }
    }

    /**
     * Get auto-save enabled
     */
    function isAutoSaveEnabled() {
        return get('autoSave', DEFAULTS.autoSave);
    }

    /**
     * Set auto-save enabled
     */
    function setAutoSaveEnabled(enabled) {
        set('autoSave', enabled);
    }

    /**
     * Reset to defaults
     */
    function resetToDefaults() {
        clear();
        loadPreferences();
    }

    /**
     * Export preferences as JSON
     */
    function exportAsJSON() {
        return JSON.stringify(getAll(), null, 2);
    }

    /**
     * Import preferences from JSON
     */
    function importFromJSON(json) {
        try {
            const prefs = JSON.parse(json);
            Object.entries(prefs).forEach(([key, value]) => {
                set(key, value);
            });
            loadPreferences();
            return true;
        } catch (e) {
            console.error('[PreferencesManager] Could not import preferences', e);
            return false;
        }
    }

    // Public API
    return {
        init,
        get,
        set,
        remove,
        clear,
        getAll,
        loadPreferences,
        toggle,
        getTheme,
        setTheme,
        isNotificationsEnabled,
        setNotificationsEnabled,
        isSoundEnabled,
        setSoundEnabled,
        isSidebarCollapsed,
        setSidebarCollapsed,
        isCompactMode,
        setCompactMode,
        isAutoSaveEnabled,
        setAutoSaveEnabled,
        resetToDefaults,
        exportAsJSON,
        importFromJSON
    };
})();

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => PreferencesManager.init());
} else {
    PreferencesManager.init();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PreferencesManager;
}
