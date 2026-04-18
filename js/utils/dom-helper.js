/**
 * DOM Helper - Safe DOM manipulation utilities
 * Provides consistent patterns for common DOM operations
 */

const DOMHelper = (function() {
    'use strict';

    /**
     * Safely set element HTML content
     * @param {HTMLElement} element - Target element
     * @param {string} html - HTML content
     */
    function setHTML(element, html) {
        if (!element) return;
        element.innerHTML = '';
        element.insertAdjacentHTML('beforeend', html);
    }

    /**
     * Safely set element text content
     * @param {HTMLElement} element - Target element
     * @param {string} text - Text content
     */
    function setText(element, text) {
        if (!element) return;
        element.textContent = text;
    }

    /**
     * Add class to element(s)
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     * @param {string} className - Class name(s)
     */
    function addClass(selector, className) {
        const elements = getElements(selector);
        elements.forEach(el => el.classList.add(...className.split(' ')));
    }

    /**
     * Remove class from element(s)
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     * @param {string} className - Class name(s)
     */
    function removeClass(selector, className) {
        const elements = getElements(selector);
        elements.forEach(el => el.classList.remove(...className.split(' ')));
    }

    /**
     * Toggle class on element(s)
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     * @param {string} className - Class name
     */
    function toggleClass(selector, className) {
        const elements = getElements(selector);
        elements.forEach(el => el.classList.toggle(className));
    }

    /**
     * Check if element has class
     * @param {HTMLElement} element - Target element
     * @param {string} className - Class name
     * @returns {boolean}
     */
    function hasClass(element, className) {
        return element && element.classList.contains(className);
    }

    /**
     * Set element attributes
     * @param {HTMLElement} element - Target element
     * @param {Object} attributes - Key-value pairs
     */
    function setAttributes(element, attributes) {
        if (!element) return;
        Object.entries(attributes).forEach(([key, value]) => {
            if (value === null) {
                element.removeAttribute(key);
            } else {
                element.setAttribute(key, value);
            }
        });
    }

    /**
     * Get element attributes as object
     * @param {HTMLElement} element - Target element
     * @returns {Object}
     */
    function getAttributes(element) {
        if (!element) return {};
        const attrs = {};
        Array.from(element.attributes).forEach(attr => {
            attrs[attr.name] = attr.value;
        });
        return attrs;
    }

    /**
     * Add event listener(s)
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     * @param {string} eventType - Event type (e.g., 'click')
     * @param {Function} handler - Event handler
     * @param {Object} options - Event listener options
     */
    function addEventListener(selector, eventType, handler, options = false) {
        const elements = getElements(selector);
        elements.forEach(el => {
            el.addEventListener(eventType, handler, options);
        });
    }

    /**
     * Remove event listener(s)
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     * @param {string} eventType - Event type
     * @param {Function} handler - Event handler
     * @param {Object} options - Event listener options
     */
    function removeEventListener(selector, eventType, handler, options = false) {
        const elements = getElements(selector);
        elements.forEach(el => {
            el.removeEventListener(eventType, handler, options);
        });
    }

    /**
     * Delegate event handling
     * @param {HTMLElement} parent - Parent element
     * @param {string} selector - Child selector
     * @param {string} eventType - Event type
     * @param {Function} handler - Event handler
     */
    function delegateEvent(parent, selector, eventType, handler) {
        parent.addEventListener(eventType, (e) => {
            const target = e.target.closest(selector);
            if (target) {
                handler.call(target, e);
            }
        });
    }

    /**
     * Show element(s)
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     */
    function show(selector) {
        const elements = getElements(selector);
        elements.forEach(el => {
            el.style.display = '';
            el.removeAttribute('hidden');
        });
    }

    /**
     * Hide element(s)
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     */
    function hide(selector) {
        const elements = getElements(selector);
        elements.forEach(el => {
            el.style.display = 'none';
        });
    }

    /**
     * Toggle visibility
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     */
    function toggleVisibility(selector) {
        const elements = getElements(selector);
        elements.forEach(el => {
            if (el.style.display === 'none' || !el.offsetParent) {
                show(el);
            } else {
                hide(el);
            }
        });
    }

    /**
     * Check if element is visible
     * @param {HTMLElement} element - Target element
     * @returns {boolean}
     */
    function isVisible(element) {
        return element && element.offsetParent !== null;
    }

    /**
     * Get element(s) - handles strings, elements, and node lists
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     * @returns {NodeList|Array}
     */
    function getElements(selector) {
        if (!selector) return [];
        if (selector instanceof HTMLElement) return [selector];
        if (selector instanceof NodeList) return selector;
        if (typeof selector === 'string') {
            return document.querySelectorAll(selector);
        }
        return [];
    }

    /**
     * Get single element
     * @param {string} selector - CSS selector
     * @returns {HTMLElement|null}
     */
    function getElement(selector) {
        return document.querySelector(selector);
    }

    /**
     * Create element with attributes
     * @param {string} tag - Tag name
     * @param {Object} attributes - Attributes
     * @param {string} content - Text/HTML content
     * @returns {HTMLElement}
     */
    function createElement(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);
        if (Object.keys(attributes).length) {
            setAttributes(element, attributes);
        }
        if (content) {
            element.textContent = content;
        }
        return element;
    }

    /**
     * Clone element deeply
     * @param {HTMLElement} element - Element to clone
     * @returns {HTMLElement}
     */
    function cloneElement(element) {
        return element.cloneNode(true);
    }

    /**
     * Remove element(s)
     * @param {HTMLElement|NodeList|string} selector - Element or selector
     */
    function removeElement(selector) {
        const elements = getElements(selector);
        elements.forEach(el => el.remove());
    }

    /**
     * Get computed style
     * @param {HTMLElement} element - Target element
     * @param {string} property - CSS property
     * @returns {string}
     */
    function getStyle(element, property) {
        return element ? window.getComputedStyle(element).getPropertyValue(property) : '';
    }

    /**
     * Set CSS properties
     * @param {HTMLElement|string} selector - Element or selector
     * @param {Object} styles - Style object
     */
    function setStyles(selector, styles) {
        const elements = getElements(selector);
        elements.forEach(el => {
            Object.assign(el.style, styles);
        });
    }

    /**
     * Focus element
     * @param {HTMLElement|string} selector - Element or selector
     */
    function focus(selector) {
        const element = typeof selector === 'string' ? 
            document.querySelector(selector) : selector;
        element?.focus();
    }

    /**
     * Scroll element into view
     * @param {HTMLElement|string} selector - Element or selector
     * @param {Object} options - Scroll options
     */
    function scrollIntoView(selector, options = { behavior: 'smooth', block: 'nearest' }) {
        const element = typeof selector === 'string' ? 
            document.querySelector(selector) : selector;
        element?.scrollIntoView(options);
    }

    /**
     * Wait for element to appear in DOM
     * @param {string} selector - CSS selector
     * @param {number} timeout - Maximum wait time (ms)
     * @returns {Promise<HTMLElement>}
     */
    function waitForElement(selector, timeout = 5000) {
        return new Promise((resolve, reject) => {
            const element = document.querySelector(selector);
            if (element) {
                resolve(element);
                return;
            }

            const observer = new MutationObserver(() => {
                const el = document.querySelector(selector);
                if (el) {
                    observer.disconnect();
                    resolve(el);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            setTimeout(() => {
                observer.disconnect();
                reject(new Error(`Element not found: ${selector}`));
            }, timeout);
        });
    }

    // Public API
    return {
        setHTML,
        setText,
        addClass,
        removeClass,
        toggleClass,
        hasClass,
        setAttributes,
        getAttributes,
        addEventListener,
        removeEventListener,
        delegateEvent,
        show,
        hide,
        toggleVisibility,
        isVisible,
        getElements,
        getElement,
        createElement,
        cloneElement,
        removeElement,
        getStyle,
        setStyles,
        focus,
        scrollIntoView,
        waitForElement
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DOMHelper;
}
