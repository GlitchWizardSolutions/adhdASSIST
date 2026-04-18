/**
 * Notification Handler - Centralized notification display
 * Handles toasts, alerts, and notifications UI
 */

const NotificationHandler = (function() {
    'use strict';

    const NOTIFICATION_TYPES = ['success', 'error', 'warning', 'info'];
    const DEFAULT_DURATION = 5000; // 5 seconds

    /**
     * Show notification toast
     * @param {string} message - Message text
     * @param {string} type - Type: success, error, warning, info
     * @param {number} duration - Duration in milliseconds
     * @returns {HTMLElement}
     */
    function toast(message, type = 'info', duration = DEFAULT_DURATION) {
        if (!NOTIFICATION_TYPES.includes(type)) {
            console.warn(`[NotificationHandler] Invalid type: ${type}`);
            type = 'info';
        }

        const toast = createToastElement(message, type);
        document.body.appendChild(toast);

        // Auto-dismiss after duration
        if (duration > 0) {
            setTimeout(() => {
                dismissNotification(toast);
            }, duration);
        }

        return toast;
    }

    /**
     * Show success notification
     * @param {string} message - Message text
     * @param {number} duration - Duration in milliseconds
     */
    function success(message, duration = DEFAULT_DURATION) {
        return toast(message, 'success', duration);
    }

    /**
     * Show error notification
     * @param {string} message - Message text
     * @param {number} duration - Duration in milliseconds
     */
    function error(message, duration = DEFAULT_DURATION) {
        return toast(message, 'error', duration);
    }

    /**
     * Show warning notification
     * @param {string} message - Message text
     * @param {number} duration - Duration in milliseconds
     */
    function warning(message, duration = DEFAULT_DURATION) {
        return toast(message, 'warning', duration);
    }

    /**
     * Show info notification
     * @param {string} message - Message text
     * @param {number} duration - Duration in milliseconds
     */
    function info(message, duration = DEFAULT_DURATION) {
        return toast(message, 'info', duration);
    }

    /**
     * Create toast element
     * @private
     */
    function createToastElement(message, type) {
        const container = document.createElement('div');
        container.className = `notification-toast toast alert alert-${getAlertClass(type)} show`;
        container.setAttribute('role', 'alert');
        container.setAttribute('aria-live', 'polite');

        const iconClass = getIconClass(type);
        const closeBtn = `
            <button type="button" class="btn-close" 
                    aria-label="Close notification"
                    onclick="this.closest('.notification-toast').remove()"></button>
        `;

        container.innerHTML = `
            <div class="d-flex gap-2 align-items-start">
                ${iconClass ? `<i class="bi ${iconClass} flex-shrink-0"></i>` : ''}
                <div class="flex-grow-1">${escapeHtml(message)}</div>
                ${closeBtn}
            </div>
        `;

        return container;
    }

    /**
     * Dismiss notification
     */
    function dismissNotification(element) {
        if (!element) return;
        element.classList.remove('show');
        setTimeout(() => {
            element.remove();
        }, 300);
    }

    /**
     * Clear all notifications
     */
    function clearAll() {
        document.querySelectorAll('.notification-toast').forEach(el => {
            dismissNotification(el);
        });
    }

    /**
     * Show modal alert
     * @param {string} title - Alert title
     * @param {string} message - Alert message
     * @param {string} type - Type: success, error, warning, info
     */
    function alert(title, message, type = 'info') {
        const modal = createAlertModal(title, message, type);
        document.body.appendChild(modal);
        const bootstrapModal = new (window.bootstrap?.Modal || BootstrapModal)(modal);
        bootstrapModal.show();
        return modal;
    }

    /**
     * Create alert modal element
     * @private
     */
    function createAlertModal(title, message, type) {
        const id = `alert-modal-${Date.now()}`;
        const alertClass = getAlertClass(type);
        
        const modal = document.createElement('div');
        modal.id = id;
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-labelledby', `${id}-title`);

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-${alertClass}">
                    <div class="modal-header bg-${alertClass} text-white">
                        <h5 class="modal-title" id="${id}-title">${escapeHtml(title)}</h5>
                        <button type="button" class="btn-close btn-close-white" 
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ${escapeHtml(message)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-${alertClass}" 
                                data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        `;

        return modal;
    }

    /**
     * Show confirmation dialog
     * @param {string} title - Dialog title
     * @param {string} message - Dialog message
     * @param {Function} onConfirm - Callback on confirm
     * @param {Function} onCancel - Callback on cancel
     */
    function confirm(title, message, onConfirm, onCancel) {
        const modal = createConfirmModal(title, message, onConfirm, onCancel);
        document.body.appendChild(modal);
        const bootstrapModal = new (window.bootstrap?.Modal || BootstrapModal)(modal);
        bootstrapModal.show();
        return modal;
    }

    /**
     * Create confirmation modal element
     * @private
     */
    function createConfirmModal(title, message, onConfirm, onCancel) {
        const id = `confirm-modal-${Date.now()}`;
        
        const modal = document.createElement('div');
        modal.id = id;
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-labelledby', `${id}-title`);

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="${id}-title">${escapeHtml(title)}</h5>
                        <button type="button" class="btn-close" 
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ${escapeHtml(message)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" 
                                data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary confirm-btn">Confirm</button>
                    </div>
                </div>
            </div>
        `;

        // Add event listeners
        const confirmBtn = modal.querySelector('.confirm-btn');
        confirmBtn.addEventListener('click', () => {
            if (onConfirm) onConfirm();
            const bsModal = window.bootstrap?.Modal?.getInstance(modal);
            if (bsModal) bsModal.hide();
        });

        modal.addEventListener('hidden.bs.modal', () => {
            if (onCancel) onCancel();
            modal.remove();
        });

        return modal;
    }

    /**
     * Play notification sound
     * @param {string} type - Sound type: success, error, warning
     */
    function playSound(type = 'success') {
        try {
            // Simple beep using Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            // Different frequencies for different types
            const frequencies = {
                success: 800,
                error: 400,
                warning: 600
            };

            oscillator.frequency.value = frequencies[type] || 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            console.warn('[NotificationHandler] Could not play sound', e);
        }
    }

    /**
     * Get Bootstrap alert class
     * @private
     */
    function getAlertClass(type) {
        const mapping = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info'
        };
        return mapping[type] || 'info';
    }

    /**
     * Get icon class
     * @private
     */
    function getIconClass(type) {
        const mapping = {
            success: 'bi-check-circle-fill text-success',
            error: 'bi-exclamation-circle-fill text-danger',
            warning: 'bi-exclamation-triangle-fill text-warning',
            info: 'bi-info-circle-fill text-info'
        };
        return mapping[type] || '';
    }

    /**
     * Escape HTML to prevent XSS
     * @private
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Public API
    return {
        toast,
        success,
        error,
        warning,
        info,
        alert,
        confirm,
        clearAll,
        playSound
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationHandler;
}
