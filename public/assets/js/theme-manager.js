// public/assets/js/theme-manager.js
// Simplified instant theme switching - no transitions, perfect consistency

class ThemeManager {
    constructor() {
        this.storageKey = 'darkMode';
        this.init();
    }

    init() {
        // Apply theme immediately before page renders
        this.applyThemeImmediately();
        
        // Set up Alpine.js integration when it's ready
        document.addEventListener('alpine:init', () => {
            this.setupAlpineIntegration();
        });
    }

    applyThemeImmediately() {
        // Get theme from localStorage immediately
        const isDarkMode = this.getStoredTheme();
        
        // Apply theme class to html element immediately
        if (isDarkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // Store the theme state globally for Alpine.js
        window.initialDarkMode = isDarkMode;
    }

    getStoredTheme() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored !== null) return stored === 'true';
            
            // Fallback to system preference if no stored preference exists
            return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        } catch (e) {
            return false;
        }
    }

    setTheme(isDark) {
        try {
            // Store preference
            localStorage.setItem(this.storageKey, isDark.toString());
            
            // Apply theme instantly - no transitions
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            // Dispatch event for other components that might need to know
            window.dispatchEvent(new CustomEvent('theme-changed', { 
                detail: { isDark } 
            }));
            
        } catch (e) {
            console.warn('Failed to save theme preference:', e);
        }
    }

    setupAlpineIntegration() {
        window.themeManager = this;
        
        // Create a global Alpine.js store for theme
        Alpine.store('theme', {
            darkMode: window.initialDarkMode || false,
            
            toggle() {
                this.darkMode = !this.darkMode;
                window.themeManager.setTheme(this.darkMode);
            },
            
            set(isDark) {
                this.darkMode = isDark;
                window.themeManager.setTheme(isDark);
            }
        });
    }

    // Watch for system theme changes (optional)
    watchSystemTheme() {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', (e) => {
                // Only apply system theme if user hasn't set a preference
                if (localStorage.getItem(this.storageKey) === null) {
                    this.setTheme(e.matches);
                    // Update Alpine store if it exists
                    if (window.Alpine && window.Alpine.store) {
                        window.Alpine.store('theme').darkMode = e.matches;
                    }
                }
            });
        }
    }
}

// Initialize theme manager immediately
new ThemeManager();