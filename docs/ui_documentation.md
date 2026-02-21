
# Stocksathi Demo — UI Documentation

Use this doc to copy the **exact same colors and design tokens** into another project. All values are taken from `css/design-system.css`.

-
## 1. Color palette (copy-paste for another project)

### Brand / Primary (teal)
| Token | Hex | Use |
|-------|-----|-----|
| `--color-primary` | `#0d9488` | Main brand, buttons, links |
| `--color-primary-dark` | `#0f766e` | Hover states |
| `--color-primary-light` | `#5eead4` | Highlights |
| `--color-primary-lighter` | `#ccfbf1` | Light backgrounds |

### Secondary (blue)
| Token | Hex | Use |
|-------|-----|-----|
| `--color-secondary` | `#3b82f6` | Secondary actions |
| `--color-secondary-dark` | `#2563eb` | Secondary hover |

### Semantic
| Token | Hex | Use |
|-------|-----|-----|
| `--color-success` | `#10b981` | Success, positive |
| `--color-warning` | `#f59e0b` | Warnings |
| `--color-danger` | `#ef4444` | Errors, delete |
| `--color-info` | `#06b6d4` | Info messages |

### Neutrals (grays)
| Token | Hex |
|-------|-----|
| `--color-white` | `#ffffff` |
| `--color-gray-50` | `#f9fafb` |
| `--color-gray-100` | `#f3f4f6` |
| `--color-gray-200` | `#e5e7eb` |
| `--color-gray-300` | `#d1d5db` |
| `--color-gray-400` | `#9ca3af` |
| `--color-gray-500` | `#6b7280` |
| `--color-gray-600` | `#4b5563` |
| `--color-gray-700` | `#374151` |
| `--color-gray-800` | `#1f2937` |
| `--color-gray-900` | `#111827` |

### Backgrounds
| Token | Hex | Use |
|-------|-----|-----|
| `--bg-body` | `#f9fafb` | Page background |
| `--bg-sidebar` | `#ffffff` | Sidebar |
| `--bg-card` | `#ffffff` | Cards |
| `--bg-header` | `#ffffff` | Header |

### Borders
| Token | Value | Use |
|-------|--------|-----|
| `--border-light` | `#e5e7eb` | Default borders |
| `--border-medium` | `#d1d5db` | Stronger borders |
| `--border-focus` | `var(--color-primary)` | Focus ring |

### Text
| Token | Hex | Use |
|-------|-----|-----|
| `--text-primary` | `#111827` | Headings, main text |
| `--text-secondary` | `#6b7280` | Body, descriptions |
| `--text-muted` | `#9ca3af` | Hints, captions |
| `--text-inverse` | `#ffffff` | Text on dark/colored bg |

---

## 2. CSS variables block (paste into `:root` in your project)

```css
:root {
  /* Primary */
  --color-primary: #0d9488;
  --color-primary-dark: #0f766e;
  --color-primary-light: #5eead4;
  --color-primary-lighter: #ccfbf1;

  /* Secondary */
  --color-secondary: #3b82f6;
  --color-secondary-dark: #2563eb;

  /* Semantic */
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-info: #06b6d4;

  /* Neutrals */
  --color-white: #ffffff;
  --color-gray-50: #f9fafb;
  --color-gray-100: #f3f4f6;
  --color-gray-200: #e5e7eb;
  --color-gray-300: #d1d5db;
  --color-gray-400: #9ca3af;
  --color-gray-500: #6b7280;
  --color-gray-600: #4b5563;
  --color-gray-700: #374151;
  --color-gray-800: #1f2937;
  --color-gray-900: #111827;

  /* Backgrounds */
  --bg-body: #f9fafb;
  --bg-sidebar: #ffffff;
  --bg-card: #ffffff;
  --bg-header: #ffffff;

  /* Borders */
  --border-light: #e5e7eb;
  --border-medium: #d1d5db;
  --border-focus: var(--color-primary);

  /* Text */
  --text-primary: #111827;
  --text-secondary: #6b7280;
  --text-muted: #9ca3af;
  --text-inverse: #ffffff;

  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);

  /* Typography */
  --font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --font-size-2xl: 1.5rem;
  --font-size-3xl: 1.875rem;

  /* Spacing (rem) */
  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 0.75rem;
  --space-4: 1rem;
  --space-5: 1.25rem;
  --space-6: 1.5rem;
  --space-8: 2rem;
  --space-10: 2.5rem;
  --space-12: 3rem;
  --space-16: 4rem;

  /* Radius */
  --radius-sm: 0.375rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --radius-xl: 1rem;
  --radius-full: 9999px;

  /* Layout */
  --sidebar-width: 260px;
  --header-height: 64px;

  /* Transitions */
  --transition-fast: 150ms ease-in-out;
  --transition-base: 200ms ease-in-out;
  --transition-slow: 300ms ease-in-out;
}
```

---

## 3. Quick reference — hex only (for non-CSS: Figma, design tools, JS)

```
Primary:    #0d9488  (dark: #0f766e, light: #5eead4, lighter: #ccfbf1)
Secondary:  #3b82f6  (dark: #2563eb)
Success:    #10b981
Warning:    #f59e0b
Danger:     #ef4444
Info:       #06b6d4

Gray-50:    #f9fafb
Gray-100:   #f3f4f6
Gray-200:   #e5e7eb
Gray-300:   #d1d5db
Gray-400:   #9ca3af
Gray-500:   #6b7280
Gray-600:   #4b5563
Gray-700:   #374151
Gray-800:   #1f2937
Gray-900:   #111827

Text primary:   #111827
Text secondary: #6b7280
Text muted:     #9ca3af
```

---

## 4. How to use in your project

- **CSS:** Use variables, e.g. `color: var(--text-primary);`, `background: var(--color-primary);`
- **Inline style:** Same: `style="color: var(--text-primary)"` (variables must be defined in your CSS `:root`)
- **JS (e.g. charts/toast):** Use the hex values above or read from `getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()`

---

## 5. Files that define / use the design system in this project

| File | Role |
|------|------|
| `css/design-system.css` | All tokens and base styles — load this first |
| `css/layout.css` | Sidebar, header, main layout (uses tokens) |
| `css/components.css` | Buttons, forms, badges (uses tokens) |
| `css/modal.css` | Modals (uses tokens) |
| `css/nav-dropdown.css` | Nav dropdowns (uses tokens) |