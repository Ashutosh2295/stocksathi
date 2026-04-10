/**
 * Theme Manager - Handles color schemes and light/dark mode
 * Applies themes across all pages
 */

if (typeof window.ThemeManager === 'undefined') {
    class ThemeManager {
        constructor() {
            this.colorSchemes = {
                'Teal (Default)': { primary: '#0F766E', primaryDark: '#3a63a5', primaryLight: '#14B8A6', primaryLighter: '#CCFBF1', secondary: '#0891B2', secondaryDark: '#0E7490', secondaryLight: '#06B6D4' },
                'Blue': { primary: '#4A6FA5', primaryDark: '#2E4A73', primaryLight: '#6B8FC7', primaryLighter: '#E8EDF5', secondary: '#5B7FA8', secondaryDark: '#3D5A7F', secondaryLight: '#7A9BC5' },
                'Green': { primary: '#059669', primaryDark: '#047857', primaryLight: '#10B981', primaryLighter: '#D1FAE5', secondary: '#0891B2', secondaryDark: '#0E7490', secondaryLight: '#06B6D4' },
                'Purple': { primary: '#7C3AED', primaryDark: '#6D28D9', primaryLight: '#8B5CF6', primaryLighter: '#EDE9FE', secondary: '#9333EA', secondaryDark: '#7E22CE', secondaryLight: '#A855F7' }
            };
            this.init();
        }

        init() {
            const savedColorScheme = localStorage.getItem('themeColorScheme') || 'Blue';
            const savedMode = localStorage.getItem('themeModePreference') || localStorage.getItem('themeMode') || 'light';
            this.applyColorScheme(savedColorScheme);
            this.applyMode(savedMode);
            this.setupThemeListeners();
        }

        setupThemeListeners() {
            window.addEventListener('storage', (e) => {
                if (e.key === 'themeColorScheme') this.applyColorScheme(e.newValue);
                if (e.key === 'themeMode') this.applyMode(e.newValue);
            });
        }

        applyColorScheme(schemeName) {
            const scheme = this.colorSchemes[schemeName] || this.colorSchemes['Blue'];
            const root = document.documentElement;
            root.style.setProperty('--color-primary', scheme.primary);
            root.style.setProperty('--color-primary-dark', scheme.primaryDark);
            root.style.setProperty('--color-primary-light', scheme.primaryLight);
            root.style.setProperty('--color-primary-lighter', scheme.primaryLighter);
            root.style.setProperty('--color-primary-hover', scheme.primaryDark);
            root.style.setProperty('--color-secondary', scheme.secondary);
            root.style.setProperty('--color-secondary-dark', scheme.secondaryDark);
            root.style.setProperty('--color-secondary-light', scheme.secondaryLight);
            root.style.setProperty('--color-info', scheme.primary);
            root.style.setProperty('--color-info-light', scheme.primaryLighter);
            root.style.setProperty('--border-focus', scheme.primary);
            localStorage.setItem('themeColorScheme', schemeName);
        }

        applyMode(mode) {
            const root = document.documentElement;
            const originalMode = mode;
            let resolvedMode = mode;

            if (mode === 'auto') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                resolvedMode = prefersDark ? 'dark' : 'light';
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                const handleChange = (e) => { this.applyMode('auto'); };
                if (this.mediaQueryListener) mediaQuery.removeEventListener('change', this.mediaQueryListener);
                this.mediaQueryListener = handleChange;
                mediaQuery.addEventListener('change', handleChange);
            }

            mode = resolvedMode;

            // Delayed body update in case script executes in <head>
            const applyToBody = () => {
                const body = document.body;
                if (!body) return;
                body.classList.remove('light-mode', 'dark-mode', 'auto-mode');
                if (mode === 'dark') body.classList.add('dark-mode');
                else body.classList.add('light-mode');
            };

            if (document.body) {
                applyToBody();
            } else {
                document.addEventListener('DOMContentLoaded', applyToBody);
            }

            if (mode === 'dark') {
                root.style.setProperty('--bg-body', '#0F172A');
                root.style.setProperty('--bg-sidebar', '#1E293B');
                root.style.setProperty('--bg-card', '#1E293B');
                root.style.setProperty('--bg-header', '#1E293B');
                root.style.setProperty('--bg-hover', '#334155');
                root.style.setProperty('--bg-surface', '#0F172A');
                root.style.setProperty('--text-primary', '#F8FAFC');
                root.style.setProperty('--text-secondary', '#CBD5E1');
                root.style.setProperty('--text-tertiary', '#94A3B8');
                root.style.setProperty('--border-light', '#334155');
                root.style.setProperty('--border-medium', '#475569');
            } else {
                root.style.setProperty('--bg-body', '#F8FAFC');
                root.style.setProperty('--bg-sidebar', '#F1F5F9');
                root.style.setProperty('--bg-card', '#FFFFFF');
                root.style.setProperty('--bg-header', '#FFFFFF');
                root.style.setProperty('--bg-hover', '#F1F5F9');
                root.style.setProperty('--bg-surface', '#F8FAFC');
                root.style.setProperty('--text-primary', '#0F172A');
                root.style.setProperty('--text-secondary', '#475569');
                root.style.setProperty('--text-tertiary', '#94A3B8');
                root.style.setProperty('--border-light', '#E5E7EB');
                root.style.setProperty('--border-medium', '#D1D5DB');
            }

            localStorage.setItem('themeMode', mode);
            localStorage.setItem('themeModePreference', originalMode);
        }

        getCurrentColorScheme() { return localStorage.getItem('themeColorScheme') || 'Blue'; }
        getCurrentMode() { return localStorage.getItem('themeModePreference') || 'light'; }
    }

    const themeManager = new ThemeManager();
    window.ThemeManager = ThemeManager;
    window.themeManager = themeManager;
}
