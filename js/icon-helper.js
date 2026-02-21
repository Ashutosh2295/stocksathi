/**
 * Lucide Icon Helper
 * Provides utility functions for using Lucide icons consistently across the application
 */

// Icon mapping for the entire application
const ICON_MAP = {
    // Dashboard icons
    'dashboard': 'layout-dashboard',
    'sales-dashboard': 'trending-up',
    'total-products': 'package',
    'stock-value': 'wallet',
    'low-stock': 'alert-triangle',
    'expired-items': 'clock',
    'sales-analytics': 'line-chart',
    'stock-distribution': 'pie-chart',

    // Navigation menu icons
    'products': 'package',
    'categories': 'tag',
    'brands': 'award',
    'stock-in': 'arrow-down-to-line',
    'stock-out': 'arrow-up-from-line',
    'adjustments': 'sliders',
    'transfers': 'arrow-right-left',
    'invoices': 'file-text',
    'quotations': 'file-edit',
    'sales-returns': 'undo-2',
    'promotions': 'gift',
    'expenses': 'credit-card',
    'customers': 'users',
    'suppliers': 'truck',
    'stores': 'store',
    'warehouses': 'warehouse',
    'employees': 'user-cog',
    'departments': 'building-2',
    'attendance': 'calendar-check',
    'leave': 'calendar-x',
    'reports': 'bar-chart-3',
    'users-admin': 'user-plus',
    'roles': 'shield',
    'activity-logs': 'scroll-text',
    'settings': 'settings',
    'logout': 'log-out',

    // Action icons
    'add': 'plus',
    'edit': 'pencil',
    'delete': 'trash-2',
    'view': 'eye',
    'search': 'search',
    'filter': 'filter',
    'download': 'download',
    'upload': 'upload',
    'print': 'printer',
    'save': 'check',
    'cancel': 'x',
    'refresh': 'refresh-ccw'
};

/**
 * Get Lucide icon HTML
 * @param {string} iconName - Name from ICON_MAP
 * @param {number} size - Icon size in pixels (default: 20)
 * @param {string} className - Additional CSS classes
 * @returns {string} HTML string for icon
 */
function getLucideIcon(iconName, size = 20, className = '') {
    const lucideName = ICON_MAP[iconName] || iconName;
    return `<i data-lucide="${lucideName}" class="lucide-icon ${className}" style="width:${size}px;height:${size}px"></i>`;
}

/**
 * Replace all icons in the document
 */
function initializeLucideIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        console.log('✅ Lucide icons initialized');
    } else {
        console.error('❌ Lucide library not loaded');
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeLucideIcons);
} else {
    initializeLucideIcons();
}

// Export for use in other modules
window.iconHelper = {
    ICON_MAP,
    getLucideIcon,
    initializeLucideIcons
};
