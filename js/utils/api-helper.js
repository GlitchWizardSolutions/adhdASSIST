/**
 * API Helper - Centralized API call management
 * Handles all fetch requests, error handling, and response processing
 */

const APIHelper = (function() {
    'use strict';

    // Configuration
    const config = {
        baseURL: window._apiBase || '/adhdASSIST/api/',
        timeout: 15000, // 15 second timeout
        retryAttempts: 3,
        retryDelay: 1000 // milliseconds
    };

    /**
     * Make an API request with error handling
     * @param {string} endpoint - API endpoint (relative to baseURL)
     * @param {Object} options - Request options
     * @returns {Promise<Object>} Response data
     */
    async function request(endpoint, options = {}) {
        const {
            method = 'GET',
            body = null,
            headers = {},
            retries = config.retryAttempts
        } = options;

        const url = config.baseURL + endpoint;
        const fetchOptions = {
            method,
            headers: {
                'Content-Type': 'application/json',
                ...headers
            },
            signal: AbortSignal.timeout(config.timeout)
        };

        if (body && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            fetchOptions.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(url, fetchOptions);

            // Handle authentication errors
            if (response.status === 401) {
                handleAuthError();
                throw new Error('Authentication required. Please log in again.');
            }

            // Handle permission errors
            if (response.status === 403) {
                throw new Error('You do not have permission to perform this action.');
            }

            // Parse response
            const contentType = response.headers.get('content-type');
            let data;

            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = await response.text();
            }

            // Handle HTTP errors
            if (!response.ok) {
                const error = data?.error || data?.message || `HTTP ${response.status}`;
                throw new Error(error);
            }

            return data;
        } catch (error) {
            // Retry on network errors
            if (retries > 0 && isRetryableError(error)) {
                console.warn(`Request failed, retrying... (${config.retryAttempts - retries + 1}/${config.retryAttempts})`);
                await sleep(config.retryDelay);
                return request(endpoint, { ...options, retries: retries - 1 });
            }

            // Log and rethrow
            console.error(`[APIHelper] Request failed: ${endpoint}`, error);
            throw error;
        }
    }

    /**
     * GET request
     */
    async function get(endpoint, options = {}) {
        return request(endpoint, { method: 'GET', ...options });
    }

    /**
     * POST request
     */
    async function post(endpoint, body, options = {}) {
        return request(endpoint, { method: 'POST', body, ...options });
    }

    /**
     * PUT request
     */
    async function put(endpoint, body, options = {}) {
        return request(endpoint, { method: 'PUT', body, ...options });
    }

    /**
     * PATCH request
     */
    async function patch(endpoint, body, options = {}) {
        return request(endpoint, { method: 'PATCH', body, ...options });
    }

    /**
     * DELETE request
     */
    async function deleteRequest(endpoint, options = {}) {
        return request(endpoint, { method: 'DELETE', ...options });
    }

    /**
     * Handle authentication errors
     */
    function handleAuthError() {
        // Clear user session data
        localStorage.removeItem('user');
        localStorage.removeItem('theme');
        
        // Redirect to login
        window.location.href = '/adhdASSIST/views/login.php';
    }

    /**
     * Check if error is retryable
     */
    function isRetryableError(error) {
        const message = error.message.toLowerCase();
        return message.includes('network') || 
               message.includes('timeout') || 
               message.includes('fetch') ||
               error.name === 'AbortError';
    }

    /**
     * Sleep utility for retry delays
     */
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Batch multiple requests
     */
    async function batch(requests) {
        return Promise.all(
            requests.map(req => request(req.endpoint, req.options))
        );
    }

    /**
     * Set configuration
     */
    function setConfig(newConfig) {
        Object.assign(config, newConfig);
    }

    /**
     * Get current configuration
     */
    function getConfig() {
        return { ...config };
    }

    // Public API
    return {
        request,
        get,
        post,
        put,
        patch,
        delete: deleteRequest,
        batch,
        setConfig,
        getConfig,
        handleAuthError
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = APIHelper;
}
