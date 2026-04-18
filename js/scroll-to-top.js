/**
 * Scroll-to-Top Component
 * Provides accessible scroll-to-top button with keyboard support
 * Usage: Include on any page that needs scroll functionality
 * The button auto-hides when user is at top, respects user theme preferences
 */

(function() {
  'use strict';

  // Create scroll button dynamically
  function initScrollToTop() {
    // Check if already initialized
    if (document.getElementById('scroll-to-top-btn')) {
      return;
    }

    // Create button element
    const button = document.createElement('button');
    button.id = 'scroll-to-top-btn';
    button.type = 'button';
    button.className = 'btn btn-primary scroll-to-top-btn';
    button.setAttribute('aria-label', 'Scroll to top of page');
    button.setAttribute('title', 'Scroll to top (or press End key)');
    button.innerHTML = '<i class="bi bi-chevron-up" aria-hidden="true"></i>';

    // Get user theme preference
    const userTheme = document.body.getAttribute('data-theme') || 'light';

    // Add styles
    const style = document.createElement('style');
    style.textContent = `
      .scroll-to-top-btn {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      }

      .scroll-to-top-btn.show {
        opacity: 1;
        visibility: visible;
      }

      .scroll-to-top-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
      }

      .scroll-to-top-btn:active {
        transform: translateY(-1px);
      }

      .scroll-to-top-btn:focus {
        outline: 2px solid #FFB300;
        outline-offset: 2px;
      }

      /* Theme-aware styling */
      [data-theme="light"] .scroll-to-top-btn {
        background-color: #667eea;
        color: white;
      }

      [data-theme="light"] .scroll-to-top-btn:hover {
        background-color: #5568d3;
      }

      [data-theme="dark"] .scroll-to-top-btn {
        background-color: #764ba2;
        color: #fff;
      }

      [data-theme="dark"] .scroll-to-top-btn:hover {
        background-color: #6a3d92;
      }

      /* Accessible focus state */
      .scroll-to-top-btn:focus-visible {
        outline: 3px solid #FFB300;
        outline-offset: 2px;
      }

      /* Mobile responsiveness */
      @media (max-width: 768px) {
        .scroll-to-top-btn {
          bottom: 1rem;
          right: 1rem;
          width: 44px;
          height: 44px;
          font-size: 1.1rem;
        }
      }
    `;
    document.head.appendChild(style);

    // Add button to page
    document.body.appendChild(button);

    // Show/hide button based on scroll position
    const toggleScrollButton = () => {
      const scrollThreshold = 300;
      if (window.scrollY > scrollThreshold) {
        button.classList.add('show');
      } else {
        button.classList.remove('show');
      }
    };

    // Scroll to top smoothly
    const scrollToTop = () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
      // Return focus to main content after scroll
      setTimeout(() => {
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
          mainContent.focus();
        }
      }, 600);
    };

    // Event listeners
    button.addEventListener('click', scrollToTop);

    // Keyboard shortcut: End key scrolls to top
    document.addEventListener('keydown', (e) => {
      // Only trigger if not inside a form input
      const isInput = e.target.matches('input, textarea, select');
      if (e.key === 'End' && !isInput && window.scrollY > 300) {
        e.preventDefault();
        scrollToTop();
      }
    });

    // Show/hide on scroll
    window.addEventListener('scroll', toggleScrollButton, { passive: true });

    // Initial check
    toggleScrollButton();
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScrollToTop);
  } else {
    initScrollToTop();
  }
})();
