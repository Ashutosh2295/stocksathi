/**
 * API Client - Unified API communication layer
 * Replaces Firebase with PHP backend API calls
 */

class ApiClient {
    static BASE_URL = '/stocksathi/stocksathi/api';

    /**
     * Make a GET request
     * @param {string} endpoint API endpoint (e.g., '/products/read.php')
     * @param {object} params Query parameters
     * @returns {Promise<object>} Response data
     */
    static async get(endpoint, params = {}) {
        try {
            // Build query string
            const queryString = new URLSearchParams(params).toString();
            const url = `${this.BASE_URL}${endpoint}${queryString ? '?' + queryString : ''}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('API GET Error:', error);
            throw error;
        }
    }

    /**
     * Make a POST request
     * @param {string} endpoint API endpoint
     * @param {object} data Request body data
     * @returns {Promise<object>} Response data
     */
    static async post(endpoint, data = {}) {
        try {
            // Get CSRF token from meta tag or generate one
            const csrfToken = this.getCsrfToken();
            if (csrfToken) {
                data.csrf_token = csrfToken;
            }

            const response = await fetch(`${this.BASE_URL}${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('API POST Error:', error);
            throw error;
        }
    }

    /**
     * Make a PUT request
     * @param {string} endpoint API endpoint
     * @param {object} data Request body data
     * @returns {Promise<object>} Response data
     */
    static async put(endpoint, data = {}) {
        try {
            const csrfToken = this.getCsrfToken();
            if (csrfToken) {
                data.csrf_token = csrfToken;
            }

            const response = await fetch(`${this.BASE_URL}${endpoint}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('API PUT Error:', error);
            throw error;
        }
    }

    /**
     * Make a DELETE request
     * @param {string} endpoint API endpoint
     * @param {object} data Request body data (for CSRF token)
     * @returns {Promise<object>} Response data
     */
    static async delete(endpoint, data = {}) {
        try {
            const csrfToken = this.getCsrfToken();
            if (csrfToken) {
                data.csrf_token = csrfToken;
            }

            const response = await fetch(`${this.BASE_URL}${endpoint}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });

            return await this.handleResponse(response);
        } catch (error) {
            console.error('API DELETE Error:', error);
            throw error;
        }
    }

    /**
     * Handle API response
     * @param {Response} response Fetch response object
     * @returns {Promise<object>} Parsed response data
     */
    static async handleResponse(response) {
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'API request failed');
        }

        if (!data.success) {
            throw new Error(data.message || 'Operation failed');
        }

        return data;
    }

    /**
     * Show error message to user
     * @param {Error} error Error object
     * @param {string} fallbackMessage Fallback error message
     */
    static showError(error, fallbackMessage = 'An error occurred') {
        const message = error.message || fallbackMessage;
        console.error('Error:', message);

        // Use Toast if available, otherwise fall back to alert
        if (typeof Toast !== 'undefined') {
            Toast.error(message);
        } else {
            alert(message);
        }
    }

    /**
     * Show success message to user
     * @param {string} message Success message
     */
    static showSuccess(message) {
        console.log('Success:', message);

        // Use Toast if available
        if (typeof Toast !== 'undefined') {
            Toast.success(message);
        }
    }

    /**
     * Get CSRF token from meta tag or session storage
     * @returns {string|null} CSRF token
     */
    static getCsrfToken() {
        // Try to get from meta tag first
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            return metaToken.getAttribute('content');
        }

        // Try to get from session storage
        const sessionToken = sessionStorage.getItem('csrf_token');
        if (sessionToken) {
            return sessionToken;
        }

        return null;
    }

    /**
     * Set CSRF token in session storage
     * @param {string} token CSRF token
     */
    static setCsrfToken(token) {
        sessionStorage.setItem('csrf_token', token);

        // Also set in meta tag if it exists
        let metaToken = document.querySelector('meta[name="csrf-token"]');
        if (!metaToken) {
            metaToken = document.createElement('meta');
            metaToken.name = 'csrf-token';
            document.head.appendChild(metaToken);
        }
        metaToken.setAttribute('content', token);
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiClient;
}
