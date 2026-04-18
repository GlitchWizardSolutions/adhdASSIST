/**
 * Modal Manager - Centralized modal handling
 * Consolidates modal creation, opening, and closing logic
 */

const ModalManager = (function() {
    'use strict';

    const openModals = new Map();

    /**
     * Create a modal
     * @param {string} id - Modal ID
     * @param {Object} options - Modal options
     * @returns {HTMLElement}
     */
    function createModal(id, options = {}) {
        const {
            title = '',
            size = 'md', // sm, md, lg, xl
            centered = true,
            backdrop = true,
            keyboard = true,
            scrollable = false
        } = options;

        const modal = document.createElement('div');
        modal.id = id;
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('data-bs-backdrop', backdrop ? 'true' : 'false');
        modal.setAttribute('data-bs-keyboard', keyboard ? 'true' : 'false');

        if (title) {
            modal.setAttribute('aria-labelledby', `${id}-title`);
        }

        const sizeClass = size === 'md' ? '' : `modal-${size}`;
        const centeredClass = centered ? 'modal-dialog-centered' : '';
        const scrollClass = scrollable ? 'modal-dialog-scrollable' : '';

        modal.innerHTML = `
            <div class="modal-dialog ${sizeClass} ${centeredClass} ${scrollClass}">
                <div class="modal-content">
                    ${title ? `
                        <div class="modal-header">
                            <h5 class="modal-title" id="${id}-title">${escapeHtml(title)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    ` : ''}
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;

        return modal;
    }

    /**
     * Open modal
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     * @returns {Modal} - Bootstrap Modal instance
     */
    function openModal(idOrElement) {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
            if (!modal) {
                console.error(`[ModalManager] Modal not found: ${idOrElement}`);
                return null;
            }
        } else {
            modal = idOrElement;
        }

        let bootstrapModal = openModals.get(modal.id);
        if (!bootstrapModal) {
            try {
                bootstrapModal = new (window.bootstrap?.Modal || Bootstrap.Modal)(modal);
                openModals.set(modal.id, bootstrapModal);
            } catch (e) {
                console.error('[ModalManager] Could not create Bootstrap Modal', e);
                return null;
            }
        }

        bootstrapModal.show();
        return bootstrapModal;
    }

    /**
     * Close modal
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     */
    function closeModal(idOrElement) {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
        } else {
            modal = idOrElement;
        }

        if (!modal) return;

        const bootstrapModal = openModals.get(modal.id);
        if (bootstrapModal) {
            bootstrapModal.hide();
        }
    }

    /**
     * Close all modals
     */
    function closeAll() {
        openModals.forEach((modal) => {
            modal.hide();
        });
    }

    /**
     * Set modal body content
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     * @param {string} content - HTML content
     */
    function setBodyContent(idOrElement, content) {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
        } else {
            modal = idOrElement;
        }

        if (!modal) return;

        const body = modal.querySelector('.modal-body');
        if (body) {
            body.innerHTML = content;
        }
    }

    /**
     * Set modal footer content
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     * @param {string} content - HTML content
     */
    function setFooterContent(idOrElement, content) {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
        } else {
            modal = idOrElement;
        }

        if (!modal) return;

        const footer = modal.querySelector('.modal-footer');
        if (footer) {
            footer.innerHTML = content;
        }
    }

    /**
     * Set modal title
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     * @param {string} title - New title
     */
    function setTitle(idOrElement, title) {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
        } else {
            modal = idOrElement;
        }

        if (!modal) return;

        const titleEl = modal.querySelector('.modal-title');
        if (titleEl) {
            titleEl.textContent = title;
        }
    }

    /**
     * Add action button to modal footer
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     * @param {string} label - Button label
     * @param {Function} callback - Button click callback
     * @param {string} className - Button class (primary, danger, etc.)
     */
    function addActionButton(idOrElement, label, callback, className = 'primary') {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
        } else {
            modal = idOrElement;
        }

        if (!modal) return;

        const footer = modal.querySelector('.modal-footer');
        if (!footer) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn btn-${className}`;
        button.textContent = label;

        button.addEventListener('click', () => {
            callback();
        });

        footer.insertBefore(button, footer.firstChild);
    }

    /**
     * Check if modal is open
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     * @returns {boolean}
     */
    function isOpen(idOrElement) {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
        } else {
            modal = idOrElement;
        }

        if (!modal) return false;

        return modal.classList.contains('show');
    }

    /**
     * Listen to modal events
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     * @param {string} eventType - Event type: show, shown, hide, hidden
     * @param {Function} callback - Event callback
     */
    function on(idOrElement, eventType, callback) {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
        } else {
            modal = idOrElement;
        }

        if (!modal) return;

        const eventName = `bs.modal.${eventType}`;
        modal.addEventListener(eventName, callback);
    }

    /**
     * Remove modal from DOM
     * @param {string|HTMLElement} idOrElement - Modal ID or element
     */
    function removeModal(idOrElement) {
        let modal;

        if (typeof idOrElement === 'string') {
            modal = document.getElementById(idOrElement);
        } else {
            modal = idOrElement;
        }

        if (!modal) return;

        closeModal(modal);
        openModals.delete(modal.id);
        modal.remove();
    }

    /**
     * Create and show a simple confirmation modal
     * @param {string} title - Modal title
     * @param {string} message - Modal message
     * @param {Function} onConfirm - Confirm callback
     * @param {Function} onCancel - Cancel callback
     */
    function confirm(title, message, onConfirm, onCancel) {
        const id = `modal-confirm-${Date.now()}`;
        const modal = createModal(id, { title, size: 'md', centered: true });

        document.body.appendChild(modal);
        setBodyContent(modal, `<p>${escapeHtml(message)}</p>`);
        setFooterContent(modal, `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="${id}-confirm">Confirm</button>
        `);

        const confirmBtn = modal.querySelector(`#${id}-confirm`);
        confirmBtn.addEventListener('click', () => {
            if (onConfirm) onConfirm();
            closeModal(modal);
        });

        modal.addEventListener('hidden.bs.modal', () => {
            if (onCancel) onCancel();
            removeModal(modal);
        });

        openModal(modal);
        return modal;
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
        createModal,
        openModal,
        closeModal,
        closeAll,
        setBodyContent,
        setFooterContent,
        setTitle,
        addActionButton,
        isOpen,
        on,
        removeModal,
        confirm
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModalManager;
}
