/**
 * ADHD Dashboard - JavaScript Interactivity (REFACTORED)
 * Uses utility modules: APIHelper, NotificationHandler, ThemeManager, PreferencesManager, DomHelper, ModalManager, DashboardAPI
 * 
 * REFACTORING NOTES:
 * - Removed: apiCall(), apiCallCached(), showNotification() (now use utilities)
 * - Removed: Internal apiCreateTask(), apiGetTasks(), apiUpdateTask(), apiDeleteTask() 
 * - Uses: DashboardAPI wrapper with 18 consolidated methods
 * - LocalStorage replaced with: PreferencesManager for preferences/settings
 * - Theme switching now uses: ThemeManager module
 * - DOM manipulation uses: DomHelper for safety
 * - Notifications use: NotificationHandler.toast(), alert(), confirm()
 * - Modal handling uses: ModalManager for consistency
 * 
 * Expected size reduction: 2200 → ~1400 lines (36% smaller)
 */

(function() {
  'use strict';

  // =========================================
  // Configuration & Initialization
  // =========================================

  const Dashboard = {
    // Storage keys for localStorage (non-preference items)
    STORAGE_KEY_TASKS: 'adhd_tasks',
    STORAGE_KEY_PRIORITIES: 'adhd_priorities',
    STORAGE_KEY_INBOX: 'adhd_inbox',
    
    // API Configuration - use window._apiBase set by PHP
    apiBase: window._apiBase || '/api',

    // DOM selectors
    selectors: {
      captureForm: '#brain-dump-form',
      captureInput: '#brain-dump-input',
      submitBtn: '#submit-capture-btn',
      inboxList: '#inbox-list',
      urgentSlot: '#priority-urgent',
      secondarySlots: '.priority-secondary',
      calmSlots: '.priority-calm',
      taskCheckboxes: 'input[type="checkbox"][data-task-id]'
    },

    // Initialize dashboard on page load
    init: function() {
      console.log('🧠 ADHD Dashboard initializing (Refactored)...');
      console.log('📱 SMS Notifications Enabled (from window._currentUser):', window._currentUser?.smsNotificationsEnabled);
      
      // Hide Send SMS buttons if SMS notifications are disabled
      if (window._currentUser && !window._currentUser.smsNotificationsEnabled) {
        console.log('🔕 SMS disabled - attempting to hide buttons...');
        const sendHabitsBtn = document.getElementById('send-habits-sms-btn');
        const sendTasksBtn = document.getElementById('send-tasks-sms-btn');
        console.log('Found sendHabitsBtn:', !!sendHabitsBtn, 'Found sendTasksBtn:', !!sendTasksBtn);
        if (sendHabitsBtn) {
          sendHabitsBtn.style.display = 'none';
          console.log('✅ Hidden send-habits-sms-btn');
        }
        if (sendTasksBtn) {
          sendTasksBtn.style.display = 'none';
          console.log('✅ Hidden send-tasks-sms-btn');
        }
      } else {
        console.log('✅ SMS enabled - buttons will show');
      }
      
      // Bind form submission
      const form = document.querySelector(this.selectors.captureForm);
      if (form) {
        form.addEventListener('submit', this.handleCapture.bind(this));
      }

      // Bind routine buttons
      const refreshBtn = document.getElementById('refresh-habits-btn');
      if (refreshBtn) {
        refreshBtn.addEventListener('click', () => this.refreshAndResetHabits());
      }

      const sendHabitsBtn = document.getElementById('send-habits-sms-btn');
      if (sendHabitsBtn) {
        sendHabitsBtn.addEventListener('click', (e) => this.sendHabitsSMS(e));
      }

      // Bind today's focus SMS button
      const sendTasksBtn = document.getElementById('send-tasks-sms-btn');
      if (sendTasksBtn) {
        sendTasksBtn.addEventListener('click', (e) => this.sendTasksSMS(e));
      }

      // Bind header focus button
      const headerFocusBtn = document.getElementById('header-focus-btn');
      if (headerFocusBtn) {
        headerFocusBtn.addEventListener('click', () => this.showTimer());
      }

      // Bind checkbox listeners for task completion
      this.attachCheckboxListeners();

      // Load tasks from API (with localStorage fallback)
      this.loadTasksFromAPI();

      // Load daily habits
      this.loadDailyHabits();

      // Initialize energy level selector
      this.initEnergySelector();

      // Setup tab switching to manage permission slip visibility
      this.setupTabSwitching();

      // Keyboard shortcuts
      this.setupKeyboardShortcuts();

      // Monitor for midnight - check every minute if date has changed
      this.startMidnightMonitor();

      console.log('✨ Dashboard ready!');
    },

    setupTabSwitching: function() {
      // Listen for tab changes to show/hide permission slip and update URL hash
      const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
      
      tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', () => {
          // Update URL hash based on active tab
          const targetPane = btn.getAttribute('data-bs-target');
          if (targetPane) {
            // Update URL hash without page reload
            window.history.replaceState(null, '', window.location.pathname + window.location.search + targetPane);
          }
          
          // 🔄 Refresh habits when Routines tab is clicked (but cache for 30 seconds)
          if (targetPane === '#routines-pane') {
            const lastHabitsLoad = this.lastHabitsLoadTime || 0;
            const timeSinceLastLoad = Date.now() - lastHabitsLoad;
            
            if (timeSinceLastLoad > 30000) {
              // Habits are stale (>30 seconds old), reload them
              console.log('🔄 Routines tab clicked - habits stale, refreshing');
              this.loadDailyHabits().catch(err => {
                console.error('⚠️ Failed to refresh habits:', err);
              });
            } else {
              // Habits are fresh, skip reload
              console.log(`💾 Routines tab clicked - habits fresh (${timeSinceLastLoad}ms old), skipping reload`);
            }
          }
          
          const currentLevel = this.getEnergyLevel();
          if (currentLevel === this.ENERGY_LEVELS.RESET_RECHARGE) {
            const permissionSlip = document.getElementById('permission-slip');
            const todayTab = document.getElementById('today-focus-tab');
            const isOnTodayTab = btn.getAttribute('data-bs-target') === '#today-focus-pane';
            
            if (isOnTodayTab && permissionSlip) {
              const firstName = (window._currentUser?.firstName || window._currentUser?.name || 'Friend').split(' ')[0];
              const fullName = (window._currentUser?.name || 'Friend');
              document.getElementById('permission-slip-name').textContent = firstName;
              document.getElementById('permission-slip-signature').textContent = fullName;
              permissionSlip.style.display = 'block';
              permissionSlip.style.animation = 'fadeIn 0.3s ease-out';
              
              // Hide priority slots but keep energy selector visible
              const prioritySlotsContainer = document.getElementById('priority-slots-container');
              if (prioritySlotsContainer) prioritySlotsContainer.style.display = 'none';
            } else if (permissionSlip) {
              permissionSlip.style.display = 'none';
              // Show priority slots if leaving Today tab
              const prioritySlotsContainer = document.getElementById('priority-slots-container');
              if (prioritySlotsContainer) prioritySlotsContainer.style.display = 'grid';
            }
          } else {
            // Show priority slots if on different energy level
            const prioritySlotsContainer = document.getElementById('priority-slots-container');
            if (prioritySlotsContainer) prioritySlotsContainer.style.display = 'grid';
          }
        });
      });
      
      // Handle initial page load - restore tab from URL hash if present
      const hash = window.location.hash;
      if (hash && hash.length > 1) {
        const tabPane = document.querySelector(hash);
        if (tabPane && tabPane.classList.contains('tab-pane')) {
          // Find the corresponding tab button
          const tabBtn = document.querySelector(`[data-bs-target="${hash}"]`);
          if (tabBtn) {
            // Use Bootstrap's tab method to switch tabs
            const tab = new bootstrap.Tab(tabBtn);
            tab.show();
          }
        }
      }
    },

    startMidnightMonitor: function() {
      // Smart midnight detection: Only reset when date FIRST changes, not repeatedly all day
      // Calculate time until next midnight
      const now = new Date();
      const tomorrow = new Date(now);
      tomorrow.setDate(tomorrow.getDate() + 1);
      tomorrow.setHours(0, 0, 0, 0);
      
      const timeUntilMidnight = tomorrow.getTime() - now.getTime();
      
      console.log(`⏰ Next midnight in ${Math.round(timeUntilMidnight / 1000 / 60)} minutes`);
      
      // Set a one-time timeout to run reset at midnight
      this.midnightTimeout = setTimeout(() => {
        console.log('🌅 Midnight reached - running reset operations');
        this.performMidnightReset();
        
        // After reset, schedule the next midnight reset
        this.startMidnightMonitor();
      }, timeUntilMidnight);
    },

    performMidnightReset: function() {
      // This runs exactly ONCE per day at midnight, not repeatedly
      console.log('🌅 Performing midnight reset (habits + tasks)');
      
      // Reload habits (will detect new day internally)
      this.loadDailyHabits().then(() => {
        console.log('✅ Habits reloaded after midnight');
      }).catch(err => {
        console.error('❌ Failed to reload habits at midnight:', err);
      });
      
      // Reset tasks via DashboardAPI
      this.resetTasksAtMidnight();
    },

    resetTasksAtMidnight: function() {
      // Call API to auto-fill empty task slots at midnight
      // Use DashboardAPI if available, otherwise fallback to direct fetch
      if (typeof DashboardAPI !== 'undefined') {
        DashboardAPI.midnightReset()
          .then(data => {
            if (data.success) {
              console.log('✅ Tasks midnight reset completed', data.data);
              // Reload tasks on dashboard if visible
              if (this.isTasksLoaded) {
                this.loadTasksFromAPI();
              }
            }
          })
          .catch(e => console.error('❌ Midnight task reset failed:', e));
      } else {
        // Fallback fetch if DashboardAPI not loaded
        fetch(`${(window._apiBase || '/api/')}tasks/midnight-reset.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            console.log('✅ Tasks midnight reset completed', data.data);
            if (this.isTasksLoaded) {
              this.loadTasksFromAPI();
            }
          }
        })
        .catch(e => console.error('❌ Midnight task reset failed:', e));
      }
    },

    // =========================================
    // Energy Level Selector
    // =========================================

    ENERGY_LEVELS: {
      FLOW_STATE: 'flow-state',           // Full battery - show all (1-3-5)
      LOW_MOTIVATION: 'low-motivation',   // 3/4 battery - show 1 & 3, hide 5
      BRAIN_FOG: 'brain-fog',             // 1/2 battery - show 1 & 5, hide 3
      OVERSTIMULATED: 'overstimulated',   // 1/4 battery - show only 1
      RESET_RECHARGE: 'reset-recharge'    // Empty battery - show permission slip
    },

    STORAGE_KEY_ENERGY_LEVEL: 'adhd_energy_level_today',
    STORAGE_KEY_ENERGY_DATE: 'adhd_energy_level_date',

    initEnergySelector: function() {
      const energyContainer = document.getElementById('energy-levels');
      if (!energyContainer) return;

      // Check if we need to reset energy level (new day)
      const storedDate = localStorage.getItem(this.STORAGE_KEY_ENERGY_DATE);
      const today = new Date().toISOString().split('T')[0];
      
      if (storedDate !== today) {
        localStorage.setItem(this.STORAGE_KEY_ENERGY_DATE, today);
        localStorage.removeItem(this.STORAGE_KEY_ENERGY_LEVEL);
      }

      const currentEnergy = this.getEnergyLevel();
      
      // Create battery buttons with Bootstrap Icons
      const energyLevels = [
        { key: this.ENERGY_LEVELS.FLOW_STATE, iconClass: 'bi-battery-charging', tooltip: 'Flow State Day' },
        { key: this.ENERGY_LEVELS.LOW_MOTIVATION, iconClass: 'bi-battery-full', tooltip: 'Low Motivation Day' },
        { key: this.ENERGY_LEVELS.BRAIN_FOG, iconClass: 'bi-battery-half', tooltip: 'Brain Fog Day' },
        { key: this.ENERGY_LEVELS.OVERSTIMULATED, iconClass: 'bi-battery-low', tooltip: 'Overstimulated Day' },
        { key: this.ENERGY_LEVELS.RESET_RECHARGE, iconClass: 'bi-battery', tooltip: 'Reset & Recharge Day' }
      ];

      energyLevels.forEach(level => {
        const btn = document.createElement('button');
        btn.className = `energy-button ${level.key === currentEnergy ? 'active' : ''}`;
        btn.type = 'button';
        btn.title = level.tooltip;
        btn.innerHTML = `<i class="bi ${level.iconClass}"></i>`;
        btn.onclick = () => this.setEnergyLevel(level.key);
        energyContainer.appendChild(btn);
      });

      // Apply current energy level
      this.applyEnergyLevel(currentEnergy);
    },

    getEnergyLevel: function() {
      return localStorage.getItem(this.STORAGE_KEY_ENERGY_LEVEL) || this.ENERGY_LEVELS.FLOW_STATE;
    },

    setEnergyLevel: function(level) {
      localStorage.setItem(this.STORAGE_KEY_ENERGY_LEVEL, level);
      this.applyEnergyLevel(level);
      
      // Update button states
      document.querySelectorAll('.energy-button').forEach(btn => {
        btn.classList.remove('active');
      });
      const levelIndex = Object.keys(this.ENERGY_LEVELS).findIndex(k => this.ENERGY_LEVELS[k] === level);
      document.querySelectorAll('.energy-button')[levelIndex]?.classList.add('active');
    },

    getTitleByLevel: function(level) {
      const titles = {
        [this.ENERGY_LEVELS.FLOW_STATE]: 'Flow State Day',
        [this.ENERGY_LEVELS.LOW_MOTIVATION]: 'Low Motivation Day',
        [this.ENERGY_LEVELS.BRAIN_FOG]: 'Brain Fog Day',
        [this.ENERGY_LEVELS.OVERSTIMULATED]: 'Overstimulated Day',
        [this.ENERGY_LEVELS.RESET_RECHARGE]: 'Reset & Recharge Day'
      };
      return titles[level] || '';
    },

    applyEnergyLevel: function(level) {
      const dashboard = document.getElementById('dashboardTabContent');
      if (!dashboard) return;

      // Remove all energy level classes
      Object.values(this.ENERGY_LEVELS).forEach(energyClass => {
        dashboard.classList.remove('view-' + energyClass);
      });

      // Add active level class
      dashboard.classList.add('view-' + level);

      // Update active button
      document.querySelectorAll('.energy-button').forEach((btn, index) => {
        btn.classList.remove('active');
        const levelIndex = Object.keys(this.ENERGY_LEVELS).findIndex(k => this.ENERGY_LEVELS[k] === level);
        if (index === levelIndex) {
          btn.classList.add('active');
        }
      });

      // Handle permission slip visibility in today-focus-pane
      const permissionSlip = document.getElementById('permission-slip');
      
      if (level === this.ENERGY_LEVELS.RESET_RECHARGE) {
        if (permissionSlip) {
          // Only show permission slip if Today's Focus tab is active
          const todayTab = document.getElementById('today-focus-pane');
          const isTabActive = todayTab && todayTab.classList.contains('show');
          
          if (isTabActive) {
            // Use first name in the paragraph, full name in signature
            const firstName = (window._currentUser?.firstName || window._currentUser?.name || 'Friend').split(' ')[0];
            const fullName = (window._currentUser?.name || 'Friend');
            
            // Format date as m/d/yy
            const today = new Date();
            const month = today.getMonth() + 1;
            const day = today.getDate();
            const year = today.getFullYear().toString().slice(-2);
            const dateStr = `${month}/${day}/${year}`;
            
            document.getElementById('permission-slip-date').textContent = dateStr;
            document.getElementById('permission-slip-name').textContent = firstName;
            document.getElementById('permission-slip-signature').textContent = fullName;
            permissionSlip.style.display = 'block';
            permissionSlip.style.animation = 'fadeIn 0.3s ease-out';
          } else {
            permissionSlip.style.display = 'none';
          }
        }
        
        // Hide only the priority slots, keep energy selector visible
        const prioritySlotsContainer = document.getElementById('priority-slots-container');
        if (prioritySlotsContainer) prioritySlotsContainer.style.display = 'none';
      } else {
        if (permissionSlip) {
          permissionSlip.style.display = 'none';
        }
        
        // Show priority slots again
        const prioritySlotsContainer = document.getElementById('priority-slots-container');
        if (prioritySlotsContainer) prioritySlotsContainer.style.display = 'grid';
      }

      console.log('⚡ Energy level set to:', level);
    },

    // =========================================
    // Send SMS using DashboardAPI
    // =========================================

    sendHabitsSMS: async function(evt) {
      try {
        const btn = evt?.target?.closest('button');
        const originalText = btn?.innerHTML;
        
        // Disable button and show loading state
        if (btn) {
          btn.disabled = true;
          btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
        }

        // Use DashboardAPI if available, otherwise fallback to fetch
        let result;
        if (typeof DashboardAPI !== 'undefined') {
          result = await DashboardAPI.sendHabitsSMS();
        } else {
          const response = await fetch(this.apiBase + 'habits/send-sms-on-demand.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
          });
          result = await response.json();
        }

        // Restore button
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = originalText;
        }

        if (result.success) {
          const message = result.status === 'all-done' 
            ? '✅ All habits completed! No SMS needed.'
            : `✅ SMS sent! (${result.count} habits)`;
          
          // Use NotificationHandler if available
          if (typeof NotificationHandler !== 'undefined') {
            NotificationHandler.toast(message, 'success');
          } else {
            this.showFeedback(message);
          }
          console.log('📱 Habits SMS sent:', result);
        } else {
          const errorMsg = '❌ ' + (result.error || 'Failed to send SMS');
          if (typeof NotificationHandler !== 'undefined') {
            NotificationHandler.toast(errorMsg, 'error');
          } else {
            this.showFeedback(errorMsg);
          }
          console.error('Failed to send habits SMS:', result);
        }
      } catch (error) {
        if (evt?.target?.closest('button')) {
          evt.target.closest('button').disabled = false;
          evt.target.closest('button').innerHTML = '<i class="bi bi-chat-left-text"></i> Send SMS';
        }
        const errorMsg = '❌ Error sending SMS: ' + (error?.message || error);
        if (typeof NotificationHandler !== 'undefined') {
          NotificationHandler.toast(errorMsg, 'error');
        } else {
          this.showFeedback(errorMsg);
        }
        console.error('SMS error (habits):', error?.message || error, error);
      }
    },

    sendTasksSMS: async function(evt) {
      try {
        const btn = evt?.target?.closest('button');
        const originalText = btn?.innerHTML;
        
        // Disable button and show loading state
        if (btn) {
          btn.disabled = true;
          btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
        }

        // Use DashboardAPI if available
        let result;
        if (typeof DashboardAPI !== 'undefined') {
          result = await DashboardAPI.sendTasksSMS();
        } else {
          const response = await fetch(this.apiBase + 'tasks/send-sms-on-demand.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
          });
          result = await response.json();
        }

        // Restore button
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = originalText;
        }

        if (result.success) {
          const message = result.status === 'all-done'
            ? '✅ All tasks completed! No SMS needed.'
            : `✅ SMS sent! (${result.count} tasks)`;
          
          if (typeof NotificationHandler !== 'undefined') {
            NotificationHandler.toast(message, 'success');
          } else {
            this.showFeedback(message);
          }
          console.log('📱 Tasks SMS sent:', result);
        } else {
          const errorMsg = '❌ ' + (result.error || 'Failed to send SMS');
          if (typeof NotificationHandler !== 'undefined') {
            NotificationHandler.toast(errorMsg, 'error');
          } else {
            this.showFeedback(errorMsg);
          }
          console.error('Failed to send tasks SMS:', result);
        }
      } catch (error) {
        if (evt?.target?.closest('button')) {
          evt.target.closest('button').disabled = false;
          evt.target.closest('button').innerHTML = '<i class="bi bi-chat-left-text"></i> Send SMS';
        }
        const errorMsg = '❌ Error sending SMS: ' + (error?.message || error);
        if (typeof NotificationHandler !== 'undefined') {
          NotificationHandler.toast(errorMsg, 'error');
        } else {
          this.showFeedback(errorMsg);
        }
        console.error('SMS error (tasks):', error?.message || error, error);
      }
    },

    // Fallback notification function (uses NotificationHandler if available)
    showFeedback: function(message, type = 'info') {
      if (typeof NotificationHandler !== 'undefined') {
        NotificationHandler.toast(message, type);
      } else {
        // Fallback: create simple toast manually
        let container = document.getElementById('notification-container');
        if (!container) {
          container = document.createElement('div');
          container.id = 'notification-container';
          container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
          document.body.appendChild(container);
        }

        const toastEl = document.createElement('div');
        toastEl.className = `alert alert-${type} alert-dismissible fade show`;
        toastEl.style.cssText = 'margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);';
        toastEl.innerHTML = `
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.appendChild(toastEl);

        setTimeout(() => {
          toastEl.remove();
        }, 5000);
      }
    },

    // =========================================
    // Task Capture - Brain Dump
    // =========================================

    handleCapture: function(event) {
      event.preventDefault();

      const input = document.querySelector(this.selectors.captureInput);
      const text = input.value.trim();

      if (!text) {
        alert('Please enter a task before capturing');
        return;
      }

      // Use DashboardAPI if available
      const createTaskPromise = (typeof DashboardAPI !== 'undefined')
        ? DashboardAPI.createTask(text)
        : this.createTaskFallback(text);

      createTaskPromise
        .then(response => {
          this.showFeedback('Task captured! ✨ Saved to database.');
          input.value = '';
          input.focus();
          this.loadTasksFromAPI();
        })
        .catch(error => {
          console.warn('API capture failed, using local storage:', error);
          
          const task = {
            id: this.generateId(),
            text: text,
            captured_at: new Date().toISOString(),
            priority: null,
            completed: false
          };

          this.addToInbox(task);
          input.value = '';
          input.focus();
          this.showFeedback('Task captured locally! ✨ (check your internet connection)');
        });
    },

    createTaskFallback: async function(text) {
      const response = await fetch(this.apiBase + '/tasks/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: text,
          priority: 'medium',
          status: 'inbox'
        })
      });
      return response.json();
    },

    addToInbox: function(task) {
      const inbox = this.getInbox();
      inbox.unshift(task);
      
      if (inbox.length > 10) {
        inbox.pop();
      }

      this.saveInbox(inbox);
      this.renderInbox(inbox);
    },

    renderInbox: function(inbox) {
      const inboxList = document.querySelector(this.selectors.inboxList);
      if (!inboxList) return;

      const inboxChecksum = JSON.stringify(inbox.map(t => t.id));
      if (this.lastInboxChecksum === inboxChecksum && inbox.length > 0) {
        console.log('💾 Inbox unchanged, skipping re-render');
        return;
      }
      this.lastInboxChecksum = inboxChecksum;

      if (inbox.length === 0) {
        inboxList.innerHTML = '<li class="list-group-item text-center text-muted">📭 Inbox is empty. Capture something to get started!</li>';
        return;
      }

      inboxList.innerHTML = inbox.map(task => `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div class="flex-grow-1" style="cursor: pointer;" onclick="Dashboard.editInboxTask('${task.id}')">
            <div class="fw-500">${this.escapeHtml(task.text)}</div>
            <small class="text-muted">${this.formatTime(task.captured_at)}</small>
          </div>
          <button class="btn btn-light btn-sm" onclick="Dashboard.editInboxTask('${task.id}'); event.stopPropagation();" title="Edit task">
            ✏️ Edit
          </button>
        </li>
      `).join('');
    },

    editInboxTask: function(taskId) {
      let inbox = this.getInbox();
      const task = inbox.find(t => t.id == taskId);
      
      if (!task) {
        console.error('Task not found:', taskId);
        return;
      }

      // Use unified modal if available
      if (typeof UnifiedTaskModal !== 'undefined') {
        UnifiedTaskModal.open({
          taskId: taskId,
          task: task,
          availableTags: [],
          assignableUsers: [],
          userRole: window._currentUserRole,
          onSave: (taskData) => {
            taskData.status = 'active';
            
            // Use DashboardAPI if available
            const updatePromise = (typeof DashboardAPI !== 'undefined')
              ? DashboardAPI.updateTask(taskId, taskData)
              : this.updateTaskFallback(taskId, taskData);

            updatePromise
              .then(() => {
                this.loadTasksFromAPI();
                this.showFeedback('✅ Task updated!');
              })
              .catch(error => {
                console.error('Failed to update task:', error);
                alert('Failed to update task. Please try again.');
              });
          }
        });
      }
    },

    updateTaskFallback: async function(taskId, data) {
      const response = await fetch(this.apiBase + '/tasks/update.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          task_id: taskId,
          ...data
        })
      });
      return response.json();
    },

    // =========================================
    // Keyboard Shortcuts
    // =========================================

    setupKeyboardShortcuts: function() {
      document.addEventListener('keydown', (e) => {
        // Ctrl+Shift+C or Cmd+Shift+C: Focus capture input
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.code === 'KeyC') {
          e.preventDefault();
          const input = document.querySelector(this.selectors.captureInput);
          if (input) input.focus();
        }

        // Escape: Blur focused input
        if (e.code === 'Escape') {
          document.activeElement.blur();
        }
      });
    },

    // =========================================
    // Storage Management (localStorage)
    // =========================================

    getInbox: function() {
      const stored = localStorage.getItem(this.STORAGE_KEY_INBOX);
      return stored ? JSON.parse(stored) : [];
    },

    saveInbox: function(inbox) {
      localStorage.setItem(this.STORAGE_KEY_INBOX, JSON.stringify(inbox));
    },

    getPriorities: function() {
      const stored = localStorage.getItem(this.STORAGE_KEY_PRIORITIES);
      return stored ? JSON.parse(stored) : [];
    },

    savePriorities: function(priorities) {
      localStorage.setItem(this.STORAGE_KEY_PRIORITIES, JSON.stringify(priorities));
    },

    loadTasks: function() {
      const inbox = this.getInbox();
      const priorities = this.getPriorities();
      
      this.renderInbox(inbox);
      this.renderPriorities();
    },

    loadTasksFromAPI: function() {
      // Use DashboardAPI if available
      const tasksPromise = (typeof DashboardAPI !== 'undefined')
        ? DashboardAPI.getTasks()
        : this.getTasksFallback();

      tasksPromise
        .then(response => {
          let tasks = [];
          
          if (response.success && response.data) {
            if (Array.isArray(response.data.tasks)) {
              tasks = response.data.tasks;
            } else if (Array.isArray(response.data)) {
              tasks = response.data;
            }
          }
          
          // Map API format to dashboard format
          const mappedTasks = tasks.map(t => ({
            id: t.id,
            text: t.title || t.brain_dump_text || '',
            priority: t.priority || 'neutral',
            status: t.status || 'inbox',
            status_today: t.status_today,
            completed: t.status === 'completed' && (t.completed_date !== null && t.completed_date !== ''),
            completed_at: t.completed_date,
            captured_at: t.capture_date || t.created_at || new Date().toISOString(),
            ...t
          }));

          const prioritized = mappedTasks.filter(t => 
            t.status_today === 'urgent' || t.status_today === 'secondary' || t.status_today === 'calm' ||
            (t.status === 'active' && ['high', 'medium', 'low'].includes(t.priority) && !t.completed)
          );
          const inbox = mappedTasks.filter(t => t.status === 'inbox' && !t.completed);

          this.saveInbox(inbox);
          this.savePriorities(prioritized);

          this.renderInbox(inbox);
          this.renderPriorities();
        })
        .catch(error => {
          console.warn('Failed to load from API, using local storage:', error);
          this.loadTasks();
        });
    },

    getTasksFallback: async function() {
      const response = await fetch(this.apiBase + '/tasks/read.php');
      return response.json();
    },

    renderPriorities: function() {
      const priorities = this.getPriorities();
      const priorityChecksum = JSON.stringify(priorities.map(t => `${t.id}:${t.completed}`));
      
      if (this.lastPriorityChecksum === priorityChecksum) {
        console.log('💾 Priorities unchanged, skipping re-render');
        return;
      }
      
      this.lastPriorityChecksum = priorityChecksum;

      const urgent = priorities.filter(t => t.status_today === 'urgent' || (t.priority === 'urgent' && !t.status_today));
      const secondary = priorities.filter(t => t.status_today === 'secondary' || (t.priority === 'secondary' && !t.status_today));
      const calm = priorities.filter(t => t.status_today === 'calm' || (t.priority === 'calm' && !t.status_today));

      const urgentSlot = document.querySelector(this.selectors.urgentSlot);
      if (urgentSlot) {
        if (urgent.length > 0) {
          const task = urgent[0];
          const taskPreview = task.description 
            ? this.escapeHtml(task.description.substring(0, 50)) + (task.description.length > 50 ? '...' : '') 
            : `[${task.estimated_duration_minutes || 25} minutes]`;
          urgentSlot.innerHTML = `
            <div class="list-group-item d-flex gap-2 p-3 align-items-center">
              <button type="button" class="btn btn-sm btn-outline-secondary p-1" 
                      title="Start Focus Timer" 
                      onclick="Dashboard.launchFocusTimer('${this.escapeHtml(task.text)}', ${task.estimated_duration_minutes || 25}, ${task.id})">
                <i class="bi bi-clock"></i>
              </button>
              <div class="form-check d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center flex-grow-1">
                  <input class="form-check-input" type="checkbox" id="task-${task.id}" 
                         data-task-id="${task.id}" data-priority="urgent" ${task.completed ? 'checked' : ''}>
                  <div class="flex-grow-1 ms-2 cursor-pointer" onclick="Dashboard.showTaskDetails(${task.id})" 
                       style="cursor: pointer; text-decoration: none; color: inherit;">
                    <div style="font-weight: 500;">${this.escapeHtml(task.text)}</div>
                    <div class="text-muted" style="font-size: 0.9rem;">${taskPreview}</div>
                  </div>
                </div>
              </div>
            </div>
          `;
        } else {
          urgentSlot.innerHTML = '';
        }
      }

      const secondarySlots = document.querySelectorAll(this.selectors.secondarySlots);
      secondarySlots.forEach((slot, index) => {
        if (index < secondary.length) {
          const task = secondary[index];
          const taskPreview = task.description 
            ? this.escapeHtml(task.description.substring(0, 50)) + (task.description.length > 50 ? '...' : '') 
            : `[${task.estimated_duration_minutes || 25} minutes]`;
          slot.innerHTML = `
            <div class="d-flex gap-2 align-items-center w-100">
              <button type="button" class="btn btn-sm btn-outline-secondary p-1" 
                      title="Start Focus Timer" 
                      onclick="Dashboard.launchFocusTimer('${this.escapeHtml(task.text)}', ${task.estimated_duration_minutes || 25}, ${task.id})">
                <i class="bi bi-clock"></i>
              </button>
              <div class="form-check d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center flex-grow-1">
                  <input class="form-check-input" type="checkbox" id="task-${task.id}" 
                         data-task-id="${task.id}" data-priority="secondary" ${task.completed ? 'checked' : ''}>
                  <div class="flex-grow-1 ms-2 cursor-pointer" onclick="Dashboard.showTaskDetails(${task.id})" 
                       style="cursor: pointer; text-decoration: none; color: inherit;">
                    <div style="font-weight: 500;">${this.escapeHtml(task.text)}</div>
                    <div class="text-muted" style="font-size: 0.9rem;">${taskPreview}</div>
                  </div>
                </div>
              </div>
            </div>
          `;
        } else {
          slot.innerHTML = '';
        }
      });

      const calmSlots = document.querySelectorAll(this.selectors.calmSlots);
      calmSlots.forEach((slot, index) => {
        if (index < calm.length) {
          const task = calm[index];
          const taskPreview = task.description 
            ? this.escapeHtml(task.description.substring(0, 50)) + (task.description.length > 50 ? '...' : '') 
            : `[${task.estimated_duration_minutes || 25} minutes]`;
          slot.innerHTML = `
            <div class="d-flex gap-2 align-items-center w-100">
              <button type="button" class="btn btn-sm btn-outline-secondary p-1" 
                      title="Start Focus Timer" 
                      onclick="Dashboard.launchFocusTimer('${this.escapeHtml(task.text)}', ${task.estimated_duration_minutes || 25}, ${task.id})">
                <i class="bi bi-clock"></i>
              </button>
              <div class="form-check d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center flex-grow-1">
                  <input class="form-check-input" type="checkbox" id="task-${task.id}" 
                         data-task-id="${task.id}" data-priority="calm" ${task.completed ? 'checked' : ''}>
                  <div class="flex-grow-1 ms-2 cursor-pointer" onclick="Dashboard.showTaskDetails(${task.id})" 
                       style="cursor: pointer; text-decoration: none; color: inherit;">
                    <div style="font-weight: 500;">${this.escapeHtml(task.text)}</div>
                    <div class="text-muted" style="font-size: 0.9rem;">${taskPreview}</div>
                  </div>
                </div>
              </div>
            </div>
          `;
        } else {
          slot.innerHTML = '';
        }
      });

      this.attachCheckboxListeners();
    },

    // =========================================
    // Task Completion Tracking
    // =========================================

    attachCheckboxListeners: function() {
      document.querySelectorAll(this.selectors.taskCheckboxes).forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
          this.handleTaskCompletion(e.target.dataset.taskId, e.target.checked);
        });
      });
    },

    handleTaskCompletion: function(taskId, isCompleted) {
      let priorities = this.getPriorities();
      const task = priorities.find(t => t.id == taskId);

      if (task) {
        const today = new Date().toISOString().split('T')[0];
        
        const taskData = {
          status: isCompleted ? 'completed' : task.status || 'inbox',
          completed_date: isCompleted ? today : null,
          status_today: isCompleted ? null : task.status_today
        };

        // Use DashboardAPI if available
        const updatePromise = (typeof DashboardAPI !== 'undefined')
          ? DashboardAPI.updateTask(taskId, taskData)
          : this.updateTaskFallback(taskId, taskData);

        updatePromise
          .then(() => this.loadTasksFromAPI())
          .catch(error => {
            console.error('Failed to update task completion:', error);
            task.completed = isCompleted;
            task.completed_at = isCompleted ? today : null;
            this.savePriorities(priorities);
          });
      }
    },

    // =========================================
    // Daily Habits Management
    // =========================================

    getLocalDateString: function() {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },

    loadDailyHabits: async function() {
      try {
        const lastHabitsDate = localStorage.getItem('adhd_habits_last_date');
        const today = this.getLocalDateString();
        let isNewDay = false;

        if (lastHabitsDate && lastHabitsDate !== today) {
          isNewDay = true;
          console.log('🌅 New day detected - resetting habits from', lastHabitsDate, 'to', today);
          localStorage.removeItem('adhd_habits_completion_cache');
        }
        
        localStorage.setItem('adhd_habits_last_date', today);

        // Use DashboardAPI if available
        const response = (typeof DashboardAPI !== 'undefined')
          ? await DashboardAPI.getDailyHabits()
          : await this.getDailyHabitsFallback(today);

        if (response.success && response.data) {
          if (isNewDay) {
            console.log('✅ Loading habits for new day:', today);
          }
          
          this.lastHabitsLoadTime = Date.now();
          this.renderDailyHabits(response.data);
        } else {
          console.warn('⚠️ Failed to load habits:', response.error);
        }
      } catch (error) {
        console.error('Failed to load daily habits:', error);
      }
    },

    getDailyHabitsFallback: async function(date) {
      const response = await fetch(this.apiBase + '/habits/read.php?date=' + date);
      return response.json();
    },

    renderDailyHabits: function(data) {
      const morningContainer = document.getElementById('morning-habits-list');
      const afternoonContainer = document.getElementById('afternoon-habits-list');
      const eveningContainer = document.getElementById('evening-habits-list');

      if (!morningContainer || !afternoonContainer || !eveningContainer) {
        console.warn('⚠️ Habit containers not found in DOM');
        return;
      }

      this.updateHabitContainer(morningContainer, data.morning || [], 'morning');
      this.updateHabitContainer(afternoonContainer, data.afternoon || [], 'afternoon');
      this.updateHabitContainer(eveningContainer, data.evening || [], 'evening');
    },

    updateHabitContainer: function(container, newHabits, period) {
      const existingItems = Array.from(container.querySelectorAll('[data-habit-id]'));
      const existingIds = new Set(existingItems.map(item => parseInt(item.dataset.habitId)));
      const newIds = new Set(newHabits.map(h => h.id));

      existingItems.forEach(item => {
        if (!newIds.has(parseInt(item.dataset.habitId))) {
          item.remove();
        }
      });

      if (newHabits.length === 0) {
        if (existingItems.length > 0) {
          container.innerHTML = `<div class="text-center text-muted small py-2">No ${period} habits yet</div>`;
        } else if (container.children.length === 0) {
          const emptyMsg = container.querySelector('.text-muted');
          if (!emptyMsg) {
            container.innerHTML = `<div class="text-center text-muted small py-2">No ${period} habits yet</div>`;
          }
        }
        return;
      }

      const emptyMsg = container.querySelector('.text-muted');
      if (emptyMsg) {
        emptyMsg.remove();
      }

      newHabits.forEach((habit, index) => {
        let habitElement = container.querySelector(`[data-habit-id="${habit.id}"]`);

        if (habitElement) {
          const checkbox = habitElement.querySelector('input[type="checkbox"]');
          const shouldBeChecked = habit.completed && habit.completed > 0;
          
          if (checkbox && checkbox.checked !== shouldBeChecked) {
            checkbox.checked = shouldBeChecked;
          }
          
          if (habitElement !== container.children[index]) {
            container.insertBefore(habitElement, container.children[index] || null);
          }
        } else {
          const element = this.createHabitCheckbox(habit);
          if (element) {
            if (index < container.children.length) {
              container.insertBefore(element, container.children[index]);
            } else {
              container.appendChild(element);
            }
          }
        }
      });

      console.log(`⚡ Updated ${period} habits container with ${newHabits.length} items (DOM diffing)`);
    },

    createHabitCheckbox: function(habit) {
      const item = document.createElement('div');
      item.className = 'form-check d-flex align-items-center p-2';
      item.id = 'habit-item-' + habit.id;
      item.setAttribute('data-habit-id', habit.id);
      
      const isChecked = habit.completed && habit.completed > 0;
      
      item.innerHTML = `
        <input class="form-check-input" type="checkbox" id="habit-${habit.id}" 
               data-habit-id="${habit.id}" ${isChecked ? 'checked="checked"' : ''}>
        <label class="form-check-label flex-grow-1 ms-2 mb-0" for="habit-${habit.id}">
          ${this.escapeHtml(habit.habit_name)}
        </label>
      `;

      const checkbox = item.querySelector('input');
      checkbox.addEventListener('change', (e) => {
        this.toggleHabit(habit.id, e.target.checked, item);
      });

      return item;
    },

    toggleHabit: async function(habitId, isCompleted, element) {
      try {
        const debounceKey = `habit_${habitId}`;
        if (this.lastHabitToggle && this.lastHabitToggle[debounceKey]) {
          const timeSinceLastCall = Date.now() - this.lastHabitToggle[debounceKey];
          if (timeSinceLastCall < 500) {
            console.log(`⏱️ Debounced habit toggle for ${habitId} (${timeSinceLastCall}ms)`);
            return;
          }
        }
        
        if (!this.lastHabitToggle) this.lastHabitToggle = {};
        this.lastHabitToggle[debounceKey] = Date.now();

        if (element) {
          element.style.opacity = '0.7';
          element.style.transition = 'all 0.2s ease';
        }

        // Use DashboardAPI if available
        const response = (typeof DashboardAPI !== 'undefined')
          ? await DashboardAPI.updateHabit(habitId, isCompleted)
          : await this.toggleHabitFallback(habitId, isCompleted);

        if (element) {
          element.style.opacity = '1';
        }

        if (response.success) {
          const msg = isCompleted ? '✅ Nice work!' : '↩️ Habit unchecked';
          if (typeof NotificationHandler !== 'undefined') {
            NotificationHandler.toast(msg, 'success');
          } else {
            this.showFeedback(msg);
          }
          
          document.dispatchEvent(new CustomEvent('habitUpdated', {
            detail: { habitId: habitId, isCompleted: isCompleted, timestamp: Date.now() }
          }));
        }
      } catch (error) {
        console.error('Failed to toggle habit:', error);
        if (element) {
          element.style.opacity = '1';
        }
      }
    },

    toggleHabitFallback: async function(habitId, isCompleted) {
      const response = await fetch(this.apiBase + '/habits/toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ habit_id: habitId, date: this.getLocalDateString() })
      });
      return response.json();
    },

    resetAllHabitCheckboxes: function() {
      const allCheckboxes = document.querySelectorAll('#morning-habits-list input[type="checkbox"], #afternoon-habits-list input[type="checkbox"], #evening-habits-list input[type="checkbox"]');
      let uncheckedCount = 0;
      allCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
          checkbox.checked = false;
          uncheckedCount++;
        }
      });
      console.log(`✅ Unchecked ${uncheckedCount} habit checkboxes`);
    },

    refreshAndResetHabits: async function() {
      try {
        await this.loadDailyHabits();
        
        setTimeout(() => {
          this.resetAllHabitCheckboxes();
          this.showFeedback('✅ Habits refreshed and cleared');
        }, 100);
      } catch (error) {
        console.error('Error refreshing habits:', error);
        this.showFeedback('⚠️ Error refreshing habits');
      }
    },

    // =========================================
    // Utility Functions
    // =========================================

    generateId: function() {
      return 'task_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },

    escapeHtml: function(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    },

    formatTime: function(isoString) {
      const date = new Date(isoString);
      const now = new Date();
      const diff = now - date;

      const minutes = Math.floor(diff / 60000);
      const hours = Math.floor(diff / 3600000);
      const days = Math.floor(diff / 86400000);

      if (minutes < 1) return 'just now';
      if (minutes < 60) return minutes + 'm ago';
      if (hours < 24) return hours + 'h ago';
      if (days < 7) return days + 'd ago';

      return date.toLocaleDateString();
    },

    showTaskDetails: function(taskId) {
      // Placeholder - implement as needed
      console.log('Show task details for task:', taskId);
    },

    showTimer: function() {
      // Placeholder - implement focus timer as needed
      console.log('Show focus timer');
    },

    launchFocusTimer: function(taskName, durationMinutes, taskId) {
      // Placeholder - implement focus timer launch
      console.log('Launch focus timer for:', taskName, durationMinutes, taskId);
    }
  };

  // Initialize on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    Dashboard.init();
  });

  // Export globally
  window.Dashboard = Dashboard;

})();
