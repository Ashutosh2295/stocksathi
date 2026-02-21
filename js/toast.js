/**
 * Toast Notification System
 * Provides success, error, warning, and info notifications
 */

class Toast {
    static container = null;

    /**
     * Initialize toast container
     */
    static init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
            `;
            document.body.appendChild(this.container);
        }
    }

    /**
     * Show toast notification
     * @param {string} message Message to display
     * @param {string} type Type: success, error, warning, info
     * @param {number} duration Duration in ms (default 3000)
     */
    static show(message, type = 'info', duration = 3000) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        // Icon based on type
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        // Colors based on type
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#4A6FA5'
        };

        toast.innerHTML = `
            <div style="
                background: white;
                border-left: 4px solid ${colors[type]};
                border-radius: 8px;
                padding: 16px 20px;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 300px;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            ">
                <div style="
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    background: ${colors[type]};
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    flex-shrink: 0;
                ">${icons[type]}</div>
                <div style="flex: 1; color: #1f2937; font-size: 14px;">${message}</div>
                <button onclick="this.parentElement.parentElement.remove()" style="
                    background: none;
                    border: none;
                    color: #9ca3af;
                    cursor: pointer;
                    font-size: 20px;
                    padding: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                ">×</button>
            </div>
        `;

        this.container.appendChild(toast);

        // Auto-remove after duration
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    /**
     * Show success toast
     * @param {string} message Message
     */
    static success(message) {
        this.show(message, 'success');
    }

    /**
     * Show error toast
     * @param {string} message Message
     */
    static error(message) {
        this.show(message, 'error', 5000); // Errors stay longer
    }

    /**
     * Show warning toast
     * @param {string} message Message
     */
    static warning(message) {
        this.show(message, 'warning');
    }

    /**
     * Show info toast
     * @param {string} message Message
     */
    static info(message) {
        this.show(message, 'info');
    }
}

// Add slide-in animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
`;
document.head.appendChild(style);

// Make Toast available globally
window.Toast = Toast;
