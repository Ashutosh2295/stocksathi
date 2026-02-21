/**
 * Universal AJAX CRUD Operations for PHP API
 * Handles all database operations without page refresh
 */

class CrudAjax {
    constructor(moduleName) {
        this.moduleName = moduleName;
        this.apiBase = '/stocksathi/stocksathi/api';
    }

    /**
     * Create a new record
     * @param {object} data - Data to create
     * @param {function} onSuccess - Success callback
     * @param {function} onError - Error callback
     */
    async create(data, onSuccess = null, onError = null) {
        try {
            const response = await fetch(`${this.apiBase}/${this.moduleName}/create.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess(result.message || 'Record created successfully');
                if (onSuccess) onSuccess(result.data);
                return result;
            } else {
                throw new Error(result.message || 'Failed to create record');
            }
        } catch (error) {
            this.showError(error.message || 'Error creating record');
            if (onError) onError(error);
            throw error;
        }
    }

    /**
     * Read records with pagination and filters
     * @param {object} params - Query parameters (page, limit, search, filters)
     * @returns {Promise<object>} Response data
     */
    async read(params = {}) {
        try {
            const queryString = new URLSearchParams(params).toString();
            const url = `${this.apiBase}/${this.moduleName}/read.php${queryString ? '?' + queryString : ''}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (result.success) {
                return result;
            } else {
                throw new Error(result.message || 'Failed to fetch records');
            }
        } catch (error) {
            console.error('Error reading records:', error);
            throw error;
        }
    }

    /**
     * Update a record
     * @param {number} id - Record ID
     * @param {object} data - Data to update
     * @param {function} onSuccess - Success callback
     * @param {function} onError - Error callback
     */
    async update(id, data, onSuccess = null, onError = null) {
        try {
            const updateData = { ...data, id: id };
            
            const response = await fetch(`${this.apiBase}/${this.moduleName}/update.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(updateData)
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess(result.message || 'Record updated successfully');
                if (onSuccess) onSuccess(result.data);
                return result;
            } else {
                throw new Error(result.message || 'Failed to update record');
            }
        } catch (error) {
            this.showError(error.message || 'Error updating record');
            if (onError) onError(error);
            throw error;
        }
    }

    /**
     * Delete a record
     * @param {number} id - Record ID
     * @param {function} onSuccess - Success callback
     * @param {function} onError - Error callback
     */
    async delete(id, onSuccess = null, onError = null) {
        try {
            const response = await fetch(`${this.apiBase}/${this.moduleName}/delete.php`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ id: id })
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess(result.message || 'Record deleted successfully');
                if (onSuccess) onSuccess();
                return result;
            } else {
                throw new Error(result.message || 'Failed to delete record');
            }
        } catch (error) {
            this.showError(error.message || 'Error deleting record');
            if (onError) onError(error);
            throw error;
        }
    }

    /**
     * Show success notification
     */
    showSuccess(message) {
        // Try to use existing toast system, fallback to alert
        if (window.showToast) {
            window.showToast(message, 'success');
        } else {
            // Create a simple toast notification
            this.createToast(message, 'success');
        }
    }

    /**
     * Show error notification
     */
    showError(message) {
        // Try to use existing toast system, fallback to alert
        if (window.showToast) {
            window.showToast(message, 'danger');
        } else {
            // Create a simple toast notification
            this.createToast(message, 'error');
        }
    }

    /**
     * Create a simple toast notification
     */
    createToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#4A6FA5'};
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = message;

        // Add animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        if (!document.getElementById('toast-styles')) {
            style.id = 'toast-styles';
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Export for global use
window.CrudAjax = CrudAjax;

// Helper function to create CRUD instance
window.createCrud = function(moduleName) {
    return new CrudAjax(moduleName);
};

console.log('✅ AJAX CRUD module loaded');

