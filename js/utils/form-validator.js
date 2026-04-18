/**
 * Form Validator - Centralized form validation
 * Provides consistent validation patterns across all forms
 */

const FormValidator = (function() {
    'use strict';

    /**
     * Validate email
     * @param {string} email - Email to validate
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validateEmail(email) {
        if (!email) {
            return { valid: false, error: 'Email is required' };
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return { valid: false, error: 'Invalid email format' };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate password
     * @param {string} password - Password to validate
     * @param {Object} options - Validation options
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validatePassword(password, options = {}) {
        const {
            minLength = 8,
            requireUppercase = true,
            requireNumbers = true,
            requireSpecialChars = false
        } = options;

        if (!password) {
            return { valid: false, error: 'Password is required' };
        }

        if (password.length < minLength) {
            return { valid: false, error: `Password must be at least ${minLength} characters` };
        }

        if (requireUppercase && !/[A-Z]/.test(password)) {
            return { valid: false, error: 'Password must contain at least one uppercase letter' };
        }

        if (requireNumbers && !/\d/.test(password)) {
            return { valid: false, error: 'Password must contain at least one number' };
        }

        if (requireSpecialChars && !/[!@#$%^&*]/.test(password)) {
            return { valid: false, error: 'Password must contain at least one special character' };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate password confirmation
     * @param {string} password - Original password
     * @param {string} confirmation - Confirmation password
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validatePasswordConfirmation(password, confirmation) {
        if (!confirmation) {
            return { valid: false, error: 'Password confirmation is required' };
        }

        if (password !== confirmation) {
            return { valid: false, error: 'Passwords do not match' };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate phone number
     * @param {string} phone - Phone number to validate
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validatePhone(phone) {
        if (!phone) {
            return { valid: false, error: 'Phone number is required' };
        }

        // Basic international format support
        const phoneRegex = /^[\d\s\-\+\(\)]+$/;
        if (!phoneRegex.test(phone) || phone.length < 10) {
            return { valid: false, error: 'Invalid phone number format' };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate URL
     * @param {string} url - URL to validate
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validateURL(url) {
        if (!url) {
            return { valid: false, error: 'URL is required' };
        }

        try {
            new URL(url);
            return { valid: true, error: '' };
        } catch (e) {
            return { valid: false, error: 'Invalid URL format' };
        }
    }

    /**
     * Validate required field
     * @param {string} value - Value to validate
     * @param {string} fieldName - Field name for error message
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validateRequired(value, fieldName = 'This field') {
        if (!value || (typeof value === 'string' && value.trim() === '')) {
            return { valid: false, error: `${fieldName} is required` };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate minimum length
     * @param {string} value - Value to validate
     * @param {number} minLength - Minimum length
     * @param {string} fieldName - Field name for error message
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validateMinLength(value, minLength, fieldName = 'Field') {
        if (!value || value.length < minLength) {
            return { valid: false, error: `${fieldName} must be at least ${minLength} characters` };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate maximum length
     * @param {string} value - Value to validate
     * @param {number} maxLength - Maximum length
     * @param {string} fieldName - Field name for error message
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validateMaxLength(value, maxLength, fieldName = 'Field') {
        if (value && value.length > maxLength) {
            return { valid: false, error: `${fieldName} cannot exceed ${maxLength} characters` };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate number range
     * @param {number} value - Value to validate
     * @param {number} min - Minimum value
     * @param {number} max - Maximum value
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validateRange(value, min, max) {
        const num = Number(value);
        if (isNaN(num) || num < min || num > max) {
            return { valid: false, error: `Value must be between ${min} and ${max}` };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate file upload
     * @param {File} file - File to validate
     * @param {Object} options - Validation options
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validateFile(file, options = {}) {
        const {
            maxSize = 5 * 1024 * 1024, // 5MB
            allowedTypes = ['image/jpeg', 'image/png', 'image/gif']
        } = options;

        if (!file) {
            return { valid: false, error: 'File is required' };
        }

        if (file.size > maxSize) {
            return { valid: false, error: `File size must not exceed ${maxSize / (1024 * 1024)}MB` };
        }

        if (!allowedTypes.includes(file.type)) {
            return { valid: false, error: `File type must be one of: ${allowedTypes.join(', ')}` };
        }

        return { valid: true, error: '' };
    }

    /**
     * Validate form element
     * @param {HTMLInputElement} element - Form element
     * @param {string} validationType - Validation type
     * @param {Object} options - Additional options
     * @returns {Object} - { valid: boolean, error: string }
     */
    function validateElement(element, validationType, options = {}) {
        const value = element.value;

        switch (validationType) {
            case 'email':
                return validateEmail(value);
            case 'password':
                return validatePassword(value, options);
            case 'phone':
                return validatePhone(value);
            case 'url':
                return validateURL(value);
            case 'required':
                return validateRequired(value, options.fieldName);
            case 'minLength':
                return validateMinLength(value, options.minLength, options.fieldName);
            case 'maxLength':
                return validateMaxLength(value, options.maxLength, options.fieldName);
            case 'range':
                return validateRange(value, options.min, options.max);
            default:
                return { valid: true, error: '' };
        }
    }

    /**
     * Show validation error on element
     * @param {HTMLElement} element - Form element
     * @param {string} errorMessage - Error message
     */
    function showError(element, errorMessage) {
        element.classList.add('is-invalid');
        element.classList.remove('is-valid');

        let errorElement = element.parentElement.querySelector('.invalid-feedback');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback d-block';
            element.parentElement.appendChild(errorElement);
        }
        errorElement.textContent = errorMessage;
    }

    /**
     * Clear validation error on element
     * @param {HTMLElement} element - Form element
     */
    function clearError(element) {
        element.classList.remove('is-invalid');
        element.classList.add('is-valid');

        const errorElement = element.parentElement.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.textContent = '';
        }
    }

    /**
     * Validate entire form
     * @param {HTMLFormElement} form - Form element
     * @param {Object} rules - Validation rules
     * @returns {Object} - { valid: boolean, errors: {} }
     */
    function validateForm(form, rules) {
        const errors = {};
        let isValid = true;

        Object.entries(rules).forEach(([fieldName, validation]) => {
            const element = form.elements[fieldName];
            if (!element) return;

            const result = validateElement(element, validation.type, validation.options);
            if (!result.valid) {
                errors[fieldName] = result.error;
                showError(element, result.error);
                isValid = false;
            } else {
                clearError(element);
            }
        });

        return { valid: isValid, errors };
    }

    /**
     * Setup real-time validation on form
     * @param {HTMLFormElement} form - Form element
     * @param {Object} rules - Validation rules
     */
    function setupLiveValidation(form, rules) {
        Object.entries(rules).forEach(([fieldName, validation]) => {
            const element = form.elements[fieldName];
            if (!element) return;

            element.addEventListener('blur', () => {
                const result = validateElement(element, validation.type, validation.options);
                if (!result.valid) {
                    showError(element, result.error);
                } else {
                    clearError(element);
                }
            });

            element.addEventListener('input', () => {
                if (element.classList.contains('is-invalid')) {
                    const result = validateElement(element, validation.type, validation.options);
                    if (result.valid) {
                        clearError(element);
                    }
                }
            });
        });
    }

    // Public API
    return {
        validateEmail,
        validatePassword,
        validatePasswordConfirmation,
        validatePhone,
        validateURL,
        validateRequired,
        validateMinLength,
        validateMaxLength,
        validateRange,
        validateFile,
        validateElement,
        showError,
        clearError,
        validateForm,
        setupLiveValidation
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormValidator;
}
