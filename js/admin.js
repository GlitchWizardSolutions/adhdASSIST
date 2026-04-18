/**
 * ADHD Dashboard - Admin Dashboard JavaScript (REFACTORED)
 * Sidebar navigation, menu interactions, accessibility features
 * 
 * REFACTORING NOTES:
 * - Modal functions now use ModalManager (openInviteModal → ModalManager.openModal)
 * - Sidebar state now uses PreferencesManager instead of localStorage
 * - Invite notifications now use NotificationHandler.toast()
 * - AdminAPI wrapper used for user invitations
 * - Fallback patterns ensure code works if utilities not loaded
 * 
 * Expected size reduction: 346 → ~280 lines (19% smaller)
 */

(function() {
  'use strict';

  // =========================================
  // Global Modal Functions (Backward Compatibility)
  // =========================================

  // These functions maintain backward compatibility with inline event handlers
  // but now delegate to ModalManager if available
  
  window.openInviteModal = function() {
    if (typeof ModalManager !== 'undefined') {
      ModalManager.openModal('inviteModal');
    } else {
      // Fallback to direct DOM manipulation
      const modal = document.getElementById('inviteModal');
      if (modal) {
        modal.style.display = 'flex';
      }
    }
  };

  window.closeInviteModal = function() {
    if (typeof ModalManager !== 'undefined') {
      ModalManager.closeModal('inviteModal');
    } else {
      // Fallback to direct DOM manipulation
      const modal = document.getElementById('inviteModal');
      if (modal) {
        modal.style.display = 'none';
      }
    }
    
    const form = document.getElementById('inviteForm');
    if (form) {
      form.reset();
    }
  };

  // =========================================
  // Utility Functions
  // =========================================

  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  }

  function showNotification(type, title, message) {
    if (typeof NotificationHandler !== 'undefined') {
      if (type === 'success') {
        NotificationHandler.success(`${title} - ${message}`);
      } else if (type === 'error') {
        NotificationHandler.error(`${title} - ${message}`);
      } else {
        NotificationHandler.toast(`${title} - ${message}`, type);
      }
    } else {
      // Fallback: create manual notification
      const notifEl = document.createElement('div');
      notifEl.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
      notifEl.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
      notifEl.innerHTML = `
        <strong>${escapeHtml(title)}</strong>
        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">${escapeHtml(message)}</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      `;
      document.body.appendChild(notifEl);
      
      setTimeout(() => {
        if (notifEl.parentNode) {
          notifEl.remove();
        }
      }, 5000);
    }
  }

  function getQueryParam(param) {
    const params = new URLSearchParams(window.location.search);
    return params.get(param);
  }

  // =========================================
  // Preference Management (using PreferencesManager)
  // =========================================

  const AdminPreferences = {
    getSidebarState: function() {
      if (typeof PreferencesManager !== 'undefined') {
        return PreferencesManager.get('admin-sidebar-state') || 'expanded';
      } else {
        return localStorage.getItem('admin-sidebar-state') || 'expanded';
      }
    },

    setSidebarState: function(state) {
      if (typeof PreferencesManager !== 'undefined') {
        PreferencesManager.set('admin-sidebar-state', state);
      } else {
        localStorage.setItem('admin-sidebar-state', state);
      }
    }
  };

  // =========================================
  // Main DOMContentLoaded Handler
  // =========================================

  document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('admin-sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const navMenus = document.querySelectorAll('.nav-menu');
    const navItems = document.querySelectorAll('.nav-item');

    // =========================================
    // Sidebar Toggle Functionality
    // =========================================

    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        const isCollapsed = sidebar.classList.contains('collapsed');
        
        if (isCollapsed) {
          // Expand sidebar
          sidebar.classList.remove('collapsed');
          sidebarToggle.setAttribute('aria-expanded', 'true');
          AdminPreferences.setSidebarState('expanded');
          document.body.style.overflow = 'auto';
        } else {
          // Collapse sidebar
          sidebar.classList.add('collapsed');
          sidebarToggle.setAttribute('aria-expanded', 'false');
          AdminPreferences.setSidebarState('collapsed');
        }
      });
    }

    // =========================================
    // Submenu Expand/Collapse
    // =========================================

    navMenus.forEach(menu => {
      menu.addEventListener('click', function(e) {
        e.preventDefault();
        
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const submenuId = this.getAttribute('aria-controls');
        const submenu = document.getElementById(submenuId);
        
        if (!submenu) return;
        
        // Close other submenus
        navMenus.forEach(otherMenu => {
          if (otherMenu !== this) {
            const otherId = otherMenu.getAttribute('aria-controls');
            const otherSubmenu = document.getElementById(otherId);
            if (otherSubmenu) {
              otherMenu.setAttribute('aria-expanded', 'false');
              otherSubmenu.hidden = true;
            }
          }
        });
        
        // Toggle current submenu
        if (isExpanded) {
          this.setAttribute('aria-expanded', 'false');
          submenu.hidden = true;
        } else {
          this.setAttribute('aria-expanded', 'true');
          submenu.hidden = false;
        }
      });

      // Keyboard support for submenu expansion (Enter/Space)
      menu.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.click();
        }
      });
    });

    // =========================================
    // Navigation Item Selection
    // =========================================

    navItems.forEach(item => {
      item.addEventListener('click', function(e) {
        // Remove active from all items
        navItems.forEach(i => {
          i.classList.remove('active');
          i.setAttribute('aria-current', 'false');
        });
        
        // Add active to clicked item
        this.classList.add('active');
        this.setAttribute('aria-current', 'page');
      });
    });

    // =========================================
    // Restore Sidebar State on Page Load
    // =========================================

    const sidebarState = AdminPreferences.getSidebarState();
    if (sidebarState === 'expanded') {
      sidebar.classList.remove('collapsed');
      if (sidebarToggle) {
        sidebarToggle.setAttribute('aria-expanded', 'true');
      }
    } else if (sidebarState === 'collapsed') {
      sidebar.classList.add('collapsed');
      if (sidebarToggle) {
        sidebarToggle.setAttribute('aria-expanded', 'false');
      }
    }

    // =========================================
    // Keyboard Navigation (Arrow Keys)
    // =========================================

    const navElements = document.querySelectorAll('.nav-item, .nav-menu, .nav-subitem');
    
    document.addEventListener('keydown', function(e) {
      // Only handle arrow keys
      if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
      
      const currentElement = document.activeElement;
      if (!currentElement || !currentElement.matches('.nav-item, .nav-menu, .nav-subitem')) {
        return;
      }

      e.preventDefault();

      const visibleElements = Array.from(navElements).filter(el => {
        // Include visible items and menu items
        if (el.offsetParent === null) return false; // Hidden element
        return !el.classList.contains('nav-submenu');
      });

      const currentIndex = visibleElements.indexOf(currentElement);
      let nextIndex;

      if (e.key === 'ArrowDown') {
        nextIndex = (currentIndex + 1) % visibleElements.length;
      } else if (e.key === 'ArrowUp') {
        nextIndex = currentIndex === 0 ? visibleElements.length - 1 : currentIndex - 1;
      }

      if (visibleElements[nextIndex]) {
        visibleElements[nextIndex].focus();
      }
    });

    // =========================================
    // Mobile Sidebar Overlay Close
    // =========================================

    if (window.innerWidth <= 768) {
      navItems.forEach(item => {
        item.addEventListener('click', function() {
          if (!sidebar.classList.contains('collapsed')) {
            // Auto-collapse on mobile after navigation
            // Uncomment if desired:
            // sidebar.classList.add('collapsed');
          }
        });
      });
    }

    // =========================================
    // Accessibility: Skip Navigation Focus Management
    // =========================================

    // Main content gets focus when navigating to a section
    const adminContent = document.querySelector('.admin-content');
    if (adminContent) {
      // Make content area focusable for screen readers
      adminContent.setAttribute('tabindex', '-1');
    }

    // =========================================
    // Theme Data Attribute Propagation
    // =========================================

    // Ensure admin-sidebar and admin-main inherit theme
    const bodyTheme = document.body.getAttribute('data-admin-theme');
    if (bodyTheme) {
      document.documentElement.setAttribute('data-theme', bodyTheme);
    }

    // =========================================
    // Utility: Close submenu when pressing Escape
    // =========================================

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        navMenus.forEach(menu => {
          const submenuId = menu.getAttribute('aria-controls');
          const submenu = document.getElementById(submenuId);
          if (submenu && !submenu.hidden) {
            menu.setAttribute('aria-expanded', 'false');
            submenu.hidden = true;
            menu.focus();
          }
        });
      }
    });
  });

  // =========================================
  // Load Admin Section Dynamically
  // =========================================

  window.loadAdminSection = function(sectionName) {
    const content = document.querySelector('.admin-content');
    if (!content) return;

    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('section', sectionName);
    window.history.pushState({section: sectionName}, '', url);

    // Visual feedback
    content.style.opacity = '0.5';
    
    // Use APIHelper if available (from DashboardAPI)
    if (typeof APIHelper !== 'undefined') {
      APIHelper.get(`?section=${encodeURIComponent(sectionName)}`)
        .then(html => {
          content.innerHTML = html;
          content.style.opacity = '1';
          content.scrollTop = 0;
        })
        .catch(error => {
          console.error('Error loading section:', error);
          content.style.opacity = '1';
          content.innerHTML = '<p class="alert alert-danger">Error loading section. Please refresh the page.</p>';
        });
    } else {
      // Fallback: direct fetch
      fetch(`?section=${encodeURIComponent(sectionName)}`)
        .then(response => response.text())
        .then(html => {
          content.innerHTML = html;
          content.style.opacity = '1';
          content.scrollTop = 0;
        })
        .catch(error => {
          console.error('Error loading section:', error);
          content.style.opacity = '1';
          content.innerHTML = '<p class="alert alert-danger">Error loading section. Please refresh the page.</p>';
        });
    }
  };

  // =========================================
  // Show Confirmation Modal
  // =========================================

  window.showConfirmation = function(message, callback) {
    if (typeof ModalManager !== 'undefined') {
      ModalManager.confirm('Confirm', message, callback);
    } else {
      // Fallback to browser confirm
      if (confirm(message)) {
        callback();
      }
    }
  };

  // =========================================
  // Invite User Form Handler
  // =========================================

  window.handleInvite = async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('inviteEmail').value;
    const fullName = document.getElementById('inviteName').value;
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerText;
    
    // Show spinner
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
    
    try {
      // Use AdminAPI if available
      let data;
      if (typeof AdminAPI !== 'undefined') {
        data = await AdminAPI.inviteUser(email, fullName);
      } else {
        // Fallback: direct fetch
        const apiBase = window._apiBase || '/public_html/adhdASSIST/';
        const response = await fetch(apiBase + 'api/admin/users-invite.php', {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, full_name: fullName })
        });
        data = await response.json();
      }
      
      if (data.success) {
        // Show success notification using NotificationHandler
        showNotification('success', '✓ Invitation Sent', `Invitation sent to ${email}`);
        
        // Close modal
        closeInviteModal();
        
        // Reset form
        document.getElementById('inviteForm').reset();
        
        // Reload users section if we're on that page
        if (document.getElementById('activeUsersTable') && typeof loadUsersData === 'function') {
          loadUsersData();
        }
      } else {
        showNotification('error', '⚠ Error', data.error || 'Failed to send invitation');
      }
    } catch (error) {
      console.error('Error:', error);
      showNotification('error', '⚠ Error', 'Failed to send invitation: ' + error.message);
    } finally {
      // Restore button
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }
  };

})();
