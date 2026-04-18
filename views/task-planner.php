<?php
/**
 * ADHD Dashboard - Task Planner Page
 * Dedicated page for organizing tasks into 1-3-5 priority slots
 * 
 * Features:
 * - Inbox: All unorganized tasks
 * - Priority Slots: 1 Urgent, 3 Secondary, 5 Calm
 * - Drag-drop task assignment
 * - Quick edit/delete/complete actions
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';

// Redirect to login if not authenticated
if (!Auth::isAuthenticated()) {
    header('Location: ' . Config::redirectUrl('/views/login.php'));
    exit;
}

// Get current user
$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Planner - ADHD Dashboard</title>
  
  <!-- Bootstrap 5.3.8 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&family=Poppins:wght@600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  
  <!-- ADHD Custom Theme - Using Config::url() for environment-aware paths -->
  <link href="<?php echo Config::url('css'); ?>adhd-theme.css" rel="stylesheet">
  <link href="<?php echo Config::url('css'); ?>adhd-dashboard.css" rel="stylesheet">
  
  <style>
    :root {
      --default-font: 'Nunito Sans', sans-serif;
      --heading-font: 'Poppins', sans-serif;
      --mono-font: 'JetBrains Mono', monospace;
    }
    
    body {
      font-family: var(--default-font);
      background-color: var(--color-bg-secondary);
    }
    
    h1, h2, h3, h4, h5, h6 {
      font-family: var(--heading-font);
    }

    /* Task Planner Specific Styles */
    .task-card {
      background: var(--color-bg-primary);
      border: 1px solid var(--color-border-light);
      border-radius: 6px;
      padding: 12px;
      margin-bottom: 10px;
      cursor: grab;
      transition: all 0.2s ease;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }

    .task-card:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border-color: var(--color-urgent);
    }

    .task-card.dragging {
      opacity: 0.5;
      cursor: grabbing;
    }

    .task-card-content {
      flex-grow: 1;
      min-width: 0;
    }

    .task-card-title {
      font-weight: 500;
      color: var(--color-text-dark);
      margin: 0;
      word-break: break-word;
    }

    .task-card-meta {
      font-size: 0.75rem;
      color: var(--color-text-muted);
      margin-top: 4px;
    }

    .task-card-actions {
      display: flex;
      gap: 4px;
      flex-shrink: 0;
    }

    .task-card-actions .btn {
      padding: 4px 8px;
      font-size: 0.75rem;
      border: none;
    }

    .priority-slot {
      background: var(--color-bg-secondary);
      border: 2px dashed var(--color-border-light);
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 16px;
      min-height: 80px;
      transition: all 0.3s ease;
    }

    .priority-slot.drag-over {
      background-color: var(--color-bg-urgent);
      border-color: var(--color-urgent);
      box-shadow: inset 0 0 8px rgba(255, 179, 0, 0.1);
    }

    .priority-slot-header {
      font-weight: 600;
      font-family: var(--heading-font);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .priority-slot-count {
      font-size: 0.75rem;
      background: var(--color-neutral);
      color: var(--color-text-dark);
      padding: 2px 6px;
      border-radius: 12px;
      margin-left: auto;
    }

    .priority-slot-empty {
      text-align: center;
      color: var(--color-text-light);
      font-size: 0.9rem;
      padding: 20px;
    }

    /* Priority-specific colors */
    .priority-urgent .priority-slot-header {
      color: var(--color-text-dark);
    }

    .priority-urgent {
      border-left: 4px solid var(--color-success);
      background-color: var(--color-bg-secondary);
    }

    .priority-secondary .priority-slot-header {
      color: var(--color-text-dark);
    }

    .priority-secondary {
      border-left: 4px solid var(--color-secondary);
      background-color: var(--color-bg-urgent);
    }

    .priority-calm .priority-slot-header {
      color: var(--color-text-dark);
    }

    .priority-calm {
      border-left: 4px solid var(--color-calm);
      background-color: var(--color-bg-calm);
    }

    /* Available Tasks - Priority-based coloring - override Bootstrap table styles */
    .available-tasks-table tbody tr.priority-urgent td {
      background-color: var(--color-bg-secondary) !important;
    }

    .available-tasks-table tbody tr.priority-secondary td {
      background-color: var(--color-bg-urgent) !important;
    }

    .available-tasks-table tbody tr.priority-calm td {
      background-color: var(--color-bg-calm) !important;
    }

    .available-tasks-table tbody tr.priority-inbox td {
      background-color: #F9FAFB !important;
    }

    /* Due-soon tasks get pink background (overrides priority) */
    .available-tasks-table tbody tr.due-soon td {
      background-color: #FDF5F7 !important;
      border-left: 3px solid #E85D75 !important;
    }

    .available-tasks-table tbody tr:hover td {
      background-color: #FFFAF0 !important;
    }

    .task-due-date {
      font-size: 0.75rem;
      color: #8A95A3;
      margin-top: 2px;
      font-weight: 500;
    }

    .task-due-date.due-soon {
      color: #E85D75;
      font-weight: 600;
    }

    .available-tasks-table tbody tr {
      vertical-align: middle;
    }

    .available-tasks-table .dropdown-menu {
      z-index: 1050 !important;
    }

    .table-responsive {
      position: relative;
      z-index: 0;
      overflow: visible !important;
    }

    .table-responsive > table {
      overflow: visible;
    }

    /* Static Available Tasks Container - no scrolling */
    #available-tasks-container {
      flex: 1;
      overflow: visible;
      border: 1px solid #E0E4E8;
      border-radius: 6px;
      background: white;
    }

    #available-tasks-container .table-responsive {
      margin: 0;
      border: none;
      overflow: visible !important;
    }

    #available-tasks-container .table {
      overflow: visible;
    }

    /* Improve spacing in available tasks table */
    #available-tasks-container .table thead th,
    #available-tasks-container .table tbody td {
      padding: 14px 16px;
      border-bottom: 2px solid #f5f5f5;
      overflow: visible;
    }

    #available-tasks-container .table tbody td {
      vertical-align: middle;
      overflow: visible;
    }

    #available-tasks-container .table tbody tr {
      transition: background-color 0.2s ease;
      cursor: grab;
      position: relative;
      overflow: visible;
      background-color: white;
    }

    #available-tasks-container .table tbody tr:active {
      cursor: grabbing;
    }

    #available-tasks-container .table tbody tr:hover td {
      background-color: rgba(102, 126, 234, 0.05) !important;
    }

    /* Add visual separation between task rows */
    #available-tasks-container .table tbody tr + tr td {
      padding-top: 14px;
      border-top: 8px solid #f9f9f9;
    }

    /* Dropdown menu positioning - let it overflow scroll container */
    #available-tasks-container .dropdown {
      position: relative;
    }

    #available-tasks-container .dropdown-menu {
      position: absolute;
      z-index: 1100;
      top: 100%;
      right: 0;
      overflow: visible;
    }

    .inbox-section {
      background: white;
      border-radius: 8px;
      border: 1px solid #E0E4E8;
      padding: 16px;
      margin-bottom: 24px;
    }

    .inbox-header {
      font-family: var(--heading-font);
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .inbox-count {
      font-size: 0.85rem;
      background: #E8E8E8;
      color: #2D3A4E;
      padding: 2px 8px;
      border-radius: 12px;
      margin-left: auto;
    }

    .planner-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 24px;
      align-items: start;
    }

    .planner-left {
      order: 1;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .planner-left .card {
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .planner-left .card-body {
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .planner-right {
      order: 2;
      position: sticky;
      top: 20px;
    }

    @media (max-width: 1024px) {
      .planner-container {
        grid-template-columns: 1fr;
        align-items: auto;
      }

      .planner-right {
        position: static;
      }
    }

    .priorities-section {
      background: white;
      border-radius: 8px;
      border: 1px solid #E0E4E8;
      padding: 16px;
    }

    .priorities-header {
      font-family: var(--heading-font);
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 16px;
    }

    .action-bar {
      display: flex;
      gap: 8px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
  </style>
</head>
<body <?php echo isset($user['theme_preference']) ? "data-theme='{$user['theme_preference']}'" : "data-theme='light'"; ?>>
  <?php $current_page = 'task-planner'; require_once __DIR__ . '/header.php'; ?>

  <!-- Main Content -->
  <main class="container-fluid py-4">
    <h1 class="visually-hidden">Task Planner</h1>

    <!-- Page Title -->
    <div class="mb-4">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
          <h1 class="h3 mb-1"><i class="bi bi-clipboard-check me-2"></i>Task Planner</h1>
          <p class="text-muted mb-0">Organize your tasks into today's 1-3-5 priority slots</p>
        </div>
        <div class="action-bar">
          <button id="refresh-tasks-btn" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
          </button>
          <button id="clear-priorities-btn" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Today
          </button>
        </div>
      </div>
    </div>

    <!-- Two Column Layout: Available Tasks | Priorities -->
    <div class="planner-container">
      
      <!-- LEFT: Available Tasks Section -->
      <div class="planner-left">
        <!-- Available Tasks Table -->
        <div class="card card-spacious border-0 shadow-sm">
          <div class="card-body">
            <h3 class="card-title h6 mb-3" style="color: var(--color-text-dark);">
              <i class="bi bi-list-check text-info me-2"></i>
              Available Tasks
              <span id="available-count" class="badge bg-secondary ms-2">0</span>
            </h3>
            
            <!-- Filter & Sort Controls -->
            <div class="row g-2 mb-3 align-items-end">
              <div class="col-md-4">
                <input type="text" id="task-filter" class="form-control form-control-sm" placeholder="Search tasks..." onkeyup="TaskPlanner.filterTasks()">
              </div>
              <div class="col-md-4">
                <select id="tag-filter" class="form-select form-select-sm" onchange="TaskPlanner.filterTasks()">
                  <option value="">All Tags</option>
                </select>
              </div>
              <div class="col-md-4">
                <select id="task-sort" class="form-select form-select-sm" onchange="TaskPlanner.sortTasks()">
                  <option value="name-asc">Sort: Name (A-Z)</option>
                  <option value="name-desc">Name (Z-A)</option>
                  <option value="created-new">Newest First</option>
                  <option value="created-old">Oldest First</option>
                  <option value="due-date-asc">Due Date (Closest)</option>
                  <option value="due-date-desc">Due Date (Furthest)</option>
                </select>
              </div>
            </div>
            
            <!-- Available Tasks Container -->
            <div id="available-tasks-container">
              <div class="text-center text-muted py-4">
                <p><i class="bi bi-hourglass-split me-1"></i>Loading tasks...</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT: Priorities Section -->
      <div class="planner-right">
        <div class="priorities-section">
          <div class="priorities-header">
            <i class="bi bi-star-fill text-warning"></i>
            Today's 1-3-5 Priority
          </div>

          <!-- 1 URGENT -->
          <div class="priority-slot priority-urgent" id="priority-urgent-container" data-priority="urgent" data-max="1">
            <div class="priority-slot-header">
              <i class="bi bi-lightning-fill text-warning me-2"></i>1 Big Task (Urgent)
              <span class="priority-slot-count"><span class="count">0</span>/1</span>
            </div>
            <div id="priority-urgent-tasks" class="priority-tasks">
              <div class="priority-slot-empty">+ Drag a task here to set as your big priority</div>
            </div>
          </div>

          <!-- 3 SECONDARY -->
          <div class="priority-slot priority-secondary" id="priority-secondary-container" data-priority="secondary" data-max="3">
            <div class="priority-slot-header">
              <i class="bi bi-clock-history text-info me-2"></i>3 Medium Tasks (Secondary)
              <span class="priority-slot-count"><span class="count">0</span>/3</span>
            </div>
            <div id="priority-secondary-tasks" class="priority-tasks">
              <div class="priority-slot-empty">+ Drag tasks here for medium priorities</div>
            </div>
          </div>

          <!-- 5 CALM -->
          <div class="priority-slot priority-calm" id="priority-calm-container" data-priority="calm" data-max="5">
            <div class="priority-slot-header">
              <i class="bi bi-check-circle text-success me-2"></i>5 Quick Wins (Calm)
              <span class="priority-slot-count"><span class="count">0</span>/5</span>
            </div>
            <div id="priority-calm-tasks" class="priority-tasks">
              <div class="priority-slot-empty">+ Drag tasks here for quick wins</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Unified Task Modal -->
  <script src="<?php echo Config::url('js'); ?>unified-task-modal.js"></script>

  <!-- Set correct API base for task planner -->
  <script>
    window._apiBase = "<?php echo Config::url('api'); ?>";
    window._currentUserId = <?php echo $user['id']; ?>;
  </script>

  <!-- Task Planner Script -->
  <script>
    /**
     * Task Planner - Full drag-drop task organization
     */
    (function() {
      'use strict';

      const TaskPlanner = {
        // API base - use window._apiBase set by PHP config
        apiBase: window._apiBase || '/api',
        
        // State
        tasks: {
          inbox: [],
          urgent: [],
          secondary: [],
          calm: [],
          available: []  // Tasks with priority but NOT in today's 1-3-5
        },
        
        // Current filter (if any)
        currentFilter: null,

        // DOM selectors
        sel: {
          inboxContainer: '#inbox-container',
          inboxCount: '#inbox-count',
          refreshBtn: '#refresh-tasks-btn',
          clearBtn: '#clear-priorities-btn',
          priorityContainers: '[data-priority]',
          priorityTasks: '.priority-tasks'
        },

        // ===== INITIALIZATION =====
        init: function() {
          console.log('🎯 Task Planner initializing...');
          
          // Check for filter parameter in URL
          const params = new URLSearchParams(window.location.search);
          this.currentFilter = params.get('filter');
          
          this.bindEvents();
          this.loadTasks();
          
          // Check if it's a new day and reset tasks if needed
          this.checkAndResetAtMidnight();
          
          // Start monitoring for midnight
          this.startMidnightMonitor();
          
          // Handle edit mode - open edit modal for specific task
          const mode = params.get('mode');
          const taskId = params.get('taskId');
          if (mode === 'edit' && taskId) {
            // Wait a moment for tasks to load, then open edit modal
            setTimeout(() => {
              this.editTask(taskId);
            }, 500);
          }
        },

        checkAndResetAtMidnight: function() {
          const storedDate = localStorage.getItem('task_planner_last_date');
          const today = new Date().toISOString().split('T')[0];
          
          if (storedDate !== today) {
            localStorage.setItem('task_planner_last_date', today);
            this.resetTasksAtMidnight();
          }
        },

        startMidnightMonitor: function() {
          // Check every minute if date has changed (for page left open overnight)
          setInterval(() => {
            const storedDate = localStorage.getItem('task_planner_last_date');
            const currentDate = new Date().toISOString().split('T')[0];
            
            if (storedDate && storedDate !== currentDate) {
              console.log('ðŸŒ… Midnight detected - resetting tasks');
              localStorage.setItem('task_planner_last_date', currentDate);
              this.resetTasksAtMidnight();
            }
          }, 60000); // Check every minute
        },

        resetTasksAtMidnight: function() {
          // Call API to auto-fill empty task slots
          fetch((window._apiBase || '/api/') + 'tasks/midnight-reset.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
          })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              console.log('<i class="bi bi-check-circle text-success"></i> Tasks midnight reset completed', data.data);
              this.loadTasks();
            }
          })
          .catch(e => console.error('âŒ Midnight task reset failed:', e));
        },

        bindEvents: function() {
          // Brain dump form - REMOVE if not on this page
          const captureForm = document.getElementById('task-planner-capture-form');
          if (captureForm) {
            captureForm.addEventListener('submit', (e) => {
              e.preventDefault();
              const input = document.getElementById('task-planner-capture-input');
              if (input.value.trim()) {
                this.createTask(input.value.trim());
                input.value = '';
                input.focus();
              }
            });
          }

          // Refresh & Clear buttons
          const refreshBtn = document.querySelector(this.sel.refreshBtn);
          const clearBtn = document.querySelector(this.sel.clearBtn);
          
          if (refreshBtn) {
            refreshBtn.addEventListener('click', async () => {
              console.log('Refresh clicked');
              refreshBtn.disabled = true;
              refreshBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Loading...';
              
              try {
                // Load/refresh tasks
                await this.loadTasks();
                
                // Reset habits/routines (clear checkboxes)
                if (typeof resetHabitsCheckboxes === 'function') {
                  resetHabitsCheckboxes();
                }
                
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Refresh';
                this.showAlert('<i class="bi bi-check-circle text-success"></i> Tasks and routines refreshed', 'success');
              } catch (error) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Refresh';
                console.error('Refresh failed:', error);
              }
            });
          } else {
            console.warn('Refresh button not found');
          }
          
          if (clearBtn) {
            clearBtn.addEventListener('click', () => this.confirmClearPriorities());
          } else {
            console.warn('Clear button not found');
          }
          
          // Setup drag-drop for priority slots (drop to select for today)
          document.querySelectorAll(this.sel.priorityContainers).forEach(container => {
            container.addEventListener('dragover', this.handleDragOver.bind(this));
            container.addEventListener('drop', this.handleDrop.bind(this));
            container.addEventListener('dragleave', this.handleDragLeave.bind(this));
          });

          // Setup drag-drop for available tasks table (allows dragging within it)
          const availableContainer = document.querySelector('#available-tasks-container');
          if (availableContainer) {
            availableContainer.addEventListener('dragover', this.handleDragOver.bind(this));
            availableContainer.addEventListener('drop', (e) => this.handleDropToPool(e, 'available'));
            availableContainer.addEventListener('dragleave', this.handleDragLeave.bind(this));
          }
        },

        // ===== API CALLS =====
        apiCall: async function(method, endpoint, data = null) {
          try {
            const options = {
              method,
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              }
            };

            if (data) options.body = JSON.stringify(data);

            const response = await fetch(this.apiBase + endpoint, options);
            if (!response.ok) throw new Error(`API Error: ${response.status}`);
            
            return await response.json();
          } catch (error) {
            console.error('API Call Failed:', error);
            throw error;
          }
        },

        loadTasks: async function() {
          try {
            const result = await this.apiCall('GET', 'tasks/read.php');
            
            // Handle different response formats
            let tasks = [];
            if (result.success && result.data) {
              // API returns {success: true, data: {tasks: [...], total, count, ...}, message}
              if (result.data.tasks && Array.isArray(result.data.tasks)) {
                tasks = result.data.tasks;
              } else if (Array.isArray(result.data)) {
                tasks = result.data;
              }
            } else if (Array.isArray(result)) {
              tasks = result;
            }

            console.log('ðŸ“¥ API Response:', result);
            console.log('ðŸ“‹ Raw tasks:', tasks);

            // Sort into buckets
            // Inbox: unprocessed/new tasks (shown on Dashboard)
            this.tasks.inbox = tasks.filter(t => !t.status || t.status === 'inbox');
            
            // Apply URL filter if one was specified
            if (this.currentFilter) {
              console.log('Applying filter:', this.currentFilter);
              if (this.currentFilter === 'assigned') {
                // Filter for assigned tasks (assigned_to is not null)
                tasks = tasks.filter(t => t.assigned_to !== null && t.assigned_to !== undefined);
              } else {
                // Filter by status
                tasks = tasks.filter(t => t.status === this.currentFilter);
              }
              // Re-filter the inbox after applying URL filter
              this.tasks.inbox = tasks.filter(t => !t.status || t.status === 'inbox');
            }
            
            // Today's 1-3-5 slots: tasks SELECTED FOR TODAY (status_today)
            // This is separate from status (base priority)
            this.tasks.urgent = tasks.filter(t => t.status_today === 'urgent');
            this.tasks.secondary = tasks.filter(t => t.status_today === 'secondary');
            this.tasks.calm = tasks.filter(t => t.status_today === 'calm');
            
            // Available pool: all active tasks (not completed/archived) that are NOT in today's schedule
            // Filters out tasks already scheduled for today (status_today set) to help with ADHD working memory
            // Shows only tasks that can be added to today's 1-3-5 slots
            // Also excludes tasks assigned to other users (only show if assigned_to is null OR assigned to self)
            this.tasks.available = tasks.filter(t => 
              t.status !== 'completed' && 
              t.status !== 'archived' &&
              t.status !== null && t.status !== undefined &&
              !t.status_today &&  // Hide tasks already in today's schedule
              (!t.assigned_to || t.assigned_to === window._currentUserId)  // Hide tasks assigned to others
            );

            // Load tags for all tasks BEFORE rendering
            await this.loadAllTasksTags();
            
            this.render();
            
            console.log('<i class="bi bi-check-circle text-success"></i> Tasks loaded and sorted:', this.tasks);
          } catch (error) {
            console.error('Failed to load tasks:', error);
            // Show loading error but don't crash
            const container = document.querySelector(this.sel.inboxContainer);
            if (container) {
              container.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Error loading tasks. <button class="btn btn-sm btn-outline-secondary ms-2" onclick="TaskPlanner.loadTasks()">Retry</button></div>';
            }
          }
        },

        loadAllTasksTags: async function() {
          try {
            // Get all unique task IDs
            const allTasks = [...this.tasks.inbox, ...this.tasks.urgent, ...this.tasks.secondary, ...this.tasks.calm, ...this.tasks.available];
            
            // Fetch tags for each task
            const tagPromises = allTasks.map(task => 
              fetch(`../api/tasks/get-task-tags.php?task_id=${task.id}`)
                .then(r => r.json())
                .then(data => ({
                  taskId: task.id,
                  tagIds: data.data || []
                }))
                .catch(e => ({
                  taskId: task.id,
                  tagIds: []
                }))
            );

            const tagResults = await Promise.all(tagPromises);
            
            // Fetch all available tags once
            const tagsResponse = await fetch('../api/tasks/get-tags.php');
            const tagsData = await tagsResponse.json();
            const allTags = tagsData.data || [];
            const tagsById = {};
            allTags.forEach(tag => {
              tagsById[tag.id] = tag;
            });

            // Map tags to tasks
            tagResults.forEach(result => {
              const task = allTasks.find(t => t.id === result.taskId);
              if (task) {
                task.tags = result.tagIds.map(tagId => tagsById[tagId]).filter(Boolean);
              }
            });
          } catch (error) {
            console.error('Failed to load task tags:', error);
            // Continue rendering even if tags fail to load
          }
        },

        // ===== RENDER =====
        render: function() {
          this.renderAvailable();
          this.populateTagFilter();
          this.renderPriorities();
        },

        renderAvailable: function() {
          const container = document.querySelector('#available-tasks-container');
          if (!container) return;
          
          const count = this.tasks.available.length;
          const countEl = document.querySelector('#available-count');
          if (countEl) countEl.textContent = count;

          if (count === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4"><p>No available tasks. Create one on the <a href="' + window._apiBase.replace('/api', '') + '/dashboard.php" style="color: inherit; text-decoration: underline;">Dashboard</a>!</p></div>';
            return;
          }

          // Build compact table - Task Name + Actions only
          let tableHTML = `
            <div class="table-responsive">
              <table class="table table-hover available-tasks-table" style="margin-bottom: 0;">
                <tbody>
          `;

          this.tasks.available.forEach((task, index) => {
            const taskId = task.id;
            const title = this.escapeHtml(task.title || task.text || 'Untitled');
            
            // Determine priority class for background color
            const priorityMap = {
              'high': 'priority-urgent',
              'medium': 'priority-secondary',
              'low': 'priority-calm',
              'someday': 'priority-calm'
            };
            const priorityClass = priorityMap[task.priority] || 'priority-inbox';
            
            // Check if due date is approaching (within 3 days) for pink tinting
            let dueSoonClass = '';
            let dueDateHTML = '';
            if (task.due_date) {
              const dueDate = new Date(task.due_date);
              const today = new Date();
              today.setHours(0, 0, 0, 0);
              const daysUntilDue = Math.floor((dueDate - today) / (1000 * 60 * 60 * 24));
              
              // Format due date for display
              const dueDateStr = dueDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
              const dueDateClass = (daysUntilDue >= 0 && daysUntilDue <= 3) ? 'due-soon' : '';
              dueDateHTML = `<div class="task-due-date ${dueDateClass}">Due: ${dueDateStr}</div>`;
              
              if (daysUntilDue >= 0 && daysUntilDue <= 3) {
                dueSoonClass = 'due-soon';
              }
            }
            
            tableHTML += `
              <tr class="task-row ${priorityClass} ${dueSoonClass}" draggable="true" data-task-id="${taskId}" data-location="available" data-tags='${JSON.stringify(task.tags || [])}'>
                <td class="align-middle" style="cursor: grab;">
                  <div style="margin-bottom: 0.35rem;">${title} ${task.assigned_to ? `<i class="bi bi-list-task" style="font-size: 0.7rem; color: #6c757d; margin-left: 0.25rem;" title="Task assigned to someone"></i>` : ''}</div>
                  ${dueDateHTML}
                  ${task.tags && task.tags.length > 0 ? `
                    <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.3rem;">
                      ${task.tags.map(tag => `
                        <span class="badge" style="background-color: ${tag.color_hex}; font-size: 0.65rem; padding: 0.25rem 0.5rem;">
                          ${this.escapeHtml(tag.name)}
                        </span>
                      `).join('')}
                    </div>
                  ` : ''}
                </td>
                <td class="text-end align-middle" style="width: 60px;">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdown-${taskId}" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-${taskId}">
                      <li><a class="dropdown-item" href="#" onclick="TaskPlanner.showTaskDetails(${taskId}); return false;"><i class="bi bi-eye me-2"></i>View Details</a></li>
                      <li><a class="dropdown-item" href="#" onclick="TaskPlanner.editTask(${taskId}); return false;"><i class="bi bi-pencil me-2"></i>Edit / Priority</a></li>
                      <li><a class="dropdown-item" href="#" onclick="TaskPlanner.moveToNextEmptySlot(${taskId}); return false;"><i class="bi bi-arrow-right me-2"></i>Move to Today's Slot</a></li>
                      <li><hr class="dropdown-divider"></li>
                      <li><a class="dropdown-item" href="#" onclick="TaskPlanner.rescheduleTask(${taskId}, 'tomorrow'); return false;"><i class="bi bi-calendar-plus me-2"></i>Reschedule to Tomorrow</a></li>
                      <li><a class="dropdown-item" href="#" onclick="TaskPlanner.showRescheduleModal(${taskId}); return false;"><i class="bi bi-calendar-check me-2"></i>Reschedule to Date</a></li>
                      <li><hr class="dropdown-divider"></li>
                      <li><a class="dropdown-item text-danger" href="#" onclick="TaskPlanner.deleteTask(${taskId}); return false;"><i class="bi bi-trash me-2"></i>Delete</a></li>
                    </ul>
                  </div>
                </td>
              </tr>
            `;
          });

          tableHTML += `
                </tbody>
              </table>
            </div>
          `;
          
          container.innerHTML = tableHTML;
          this.attachTaskCardListeners();
        },

        renderPriorities: function() {
          ['urgent', 'secondary', 'calm'].forEach(priority => {
            const tasks = this.tasks[priority];
            const container = document.querySelector(`#priority-${priority}-tasks`);
            const countEl = document.querySelector(`#priority-${priority}-container .count`);
            const maxSlots = document.querySelector(`#priority-${priority}-container`).dataset.max;

            countEl.textContent = tasks.length;

            if (tasks.length === 0) {
              container.innerHTML = `<div class="priority-slot-empty">+ Drag a task here to set as ${priority}</div>`;
              return;
            }

            container.innerHTML = tasks.map(task => this.renderTaskCard(task, priority)).join('');
            this.attachTaskCardListeners();
          });
        },

        renderTaskCard: function(task, location) {
          const taskId = task.id;
          const title = this.escapeHtml(task.title || task.text || 'Untitled');
          
          // Build tags HTML
          let tagsHtml = '';
          if (task.tags && task.tags.length > 0) {
            const tagBadges = task.tags.map(tag => `
              <span class="badge" style="background-color: ${tag.color_hex}; font-size: 0.65rem; padding: 0.25rem 0.5rem; margin-right: 0.25rem;">
                ${this.escapeHtml(tag.name)}
              </span>
            `).join('');
            tagsHtml = `<div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.25rem;">${tagBadges}</div>`;
          }
          
          // Add assigned indicator if task is delegated to someone
          const assignedIndicator = task.assigned_to ? `<i class="bi bi-list-task" style="font-size: 0.7rem; color: #6c757d; margin-left: 0.25rem;" title="Task assigned to someone"></i>` : '';
          
          // Priority slot cards - no buttons (edit/complete on dashboard, mark on available table)
          let buttons = '';
          
          return `
            <div class="task-card" draggable="true" data-task-id="${taskId}" data-location="${location}">
              <div class="task-card-content">
                <p class="task-card-title">${title} ${assignedIndicator}</p>
                ${tagsHtml}
              </div>
              <div class="task-card-actions">
                ${buttons}
              </div>
            </div>
          `;
        },

        attachTaskCardListeners: function() {
          // Attach to both priority cards and available task rows
          document.querySelectorAll('.task-card, .task-row').forEach(element => {
            element.addEventListener('dragstart', this.handleDragStart.bind(this));
            element.addEventListener('dragend', this.handleDragEnd.bind(this));
          });
        },

        // ===== DRAG & DROP =====
        draggedTaskId: null,
        draggedFrom: null,

        handleDragStart: function(e) {
          const element = e.target.closest('.task-card, .task-row');
          if (!element) return;
          
          this.draggedTaskId = element.dataset.taskId;
          this.draggedFrom = element.dataset.location;
          element.classList.add('dragging');
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/html', e.currentTarget.innerHTML);
        },

        handleDragEnd: function(e) {
          const element = e.target.closest('.task-card, .task-row');
          if (element) element.classList.remove('dragging');
          document.querySelectorAll(this.sel.priorityContainers).forEach(c => c.classList.remove('drag-over'));
        },

        handleDragOver: function(e) {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          e.currentTarget.classList.add('drag-over');
        },

        handleDragLeave: function(e) {
          if (e.currentTarget === e.target) {
            e.currentTarget.classList.remove('drag-over');
          }
        },

        handleDrop: async function(e) {
          e.preventDefault();
          e.stopPropagation();
          const container = e.currentTarget.closest('[data-priority]');
          container.classList.remove('drag-over');

          const targetPriority = container.dataset.priority;
          const maxSlots = parseInt(container.dataset.max);
          const currentCount = this.tasks[targetPriority].length;

          // Check if slot is full
          if (currentCount >= maxSlots) {
            this.showAlert(`${targetPriority} slots are full (${maxSlots}/${maxSlots})`, 'warning');
            return;
          }

          // Move task to today's slot
          await this.moveToToday(this.draggedTaskId, targetPriority);
        },

        handleDropToPool: async function(e, targetPool) {
          e.preventDefault();
          e.stopPropagation();
          e.currentTarget.classList.remove('drag-over');

          // Different behavior for available vs. priority slots
          if (targetPool === 'available') {
            // Remove from today's slots by clearing status_today
            // Base status (priority) stays unchanged
            await this.removeFromToday(this.draggedTaskId);
          } else {
            // Moving to a priority slot: set status_today
            await this.moveToToday(this.draggedTaskId, targetPool);
          }
        },

        removeFromToday: async function(taskId) {
          try {
            await this.apiCall('PUT', 'tasks/update.php', {
              task_id: taskId,
              status_today: null
            });
            this.loadTasks();
            this.showAlert('<i class="bi bi-check-circle text-success"></i> Task removed from today', 'success');
          } catch (error) {
            console.error('Failed to remove from today:', error);
            this.loadTasks();
            this.showAlert('Failed to remove from today', 'error');
          }
        },

        moveToToday: async function(taskId, priority) {
          try {
            await this.apiCall('PUT', 'tasks/update.php', {
              task_id: taskId,
              status_today: priority
            });
            this.loadTasks();
            this.showAlert(`<i class="bi bi-check-circle text-success"></i> Task added to today's ${priority} slot`, 'success');
          } catch (error) {
            console.error('Failed to move to today:', error);
            this.loadTasks();
            this.showAlert('Failed to move to today', 'error');
          }
        },

        moveToNextEmptySlot: async function(taskId) {
          try {
            let targetPriority = null;
            
            // Priority order: urgent (1 slot) â†’ secondary (3 slots) â†’ calm (5 slots)
            if (this.tasks.urgent.length < 1) {
              targetPriority = 'urgent';
            } else if (this.tasks.secondary.length < 3) {
              targetPriority = 'secondary';
            } else if (this.tasks.calm.length < 5) {
              targetPriority = 'calm';
            } else {
              this.showAlert('âŒ All today\'s priority slots are full. Remove a task first.', 'warning');
              return;
            }

            // Move task to the target priority
            await this.moveToToday(taskId, targetPriority);
          } catch (error) {
            console.error('Failed to move to next empty slot:', error);
            this.showAlert('Failed to move task', 'error');
          }
        },

        moveAssignedTaskToSlot: async function(taskId) {
          try {
            let targetPriority = null;
            
            // Find next empty slot (same priority order: urgent, then secondary, then calm)
            if (this.tasks.urgent.length < 1) {
              targetPriority = 'urgent';
            } else if (this.tasks.secondary.length < 3) {
              targetPriority = 'secondary';
            } else if (this.tasks.calm.length < 5) {
              targetPriority = 'calm';
            } else {
              this.showAlert('All today\'s priority slots are full. Remove a task first.', 'warning');
              return;
            }

            // Move task to the target priority
            await this.moveToToday(taskId, targetPriority);
            
            // Refresh assigned tasks badge
            await setTimeout(() => {
              if (typeof updateAssignedTasksBadge === 'function') {
                updateAssignedTasksBadge();
                updateAssignedTasksList();
              }
            }, 300);
          } catch (error) {
            console.error('Failed to move assigned task to slot:', error);
            this.showAlert('Failed to move task', 'error');
          }
        },

        moveTask: async function(taskId, newPriority, fromButton = false) {
          try {
            // Find current location of task if not from drag
            let currentLocation = this.draggedFrom;
            if (!currentLocation) {
              const allTasks = [...this.tasks.inbox, ...this.tasks.urgent, ...this.tasks.secondary, ...this.tasks.calm, ...this.tasks.available];
              const task = allTasks.find(t => t.id == taskId);
              if (!task) {
                this.showAlert('Task not found', 'error');
                return;
              }
              // Determine current location
              if (this.tasks.inbox.find(t => t.id == taskId)) currentLocation = 'inbox';
              else if (this.tasks.urgent.find(t => t.id == taskId)) currentLocation = 'urgent';
              else if (this.tasks.secondary.find(t => t.id == taskId)) currentLocation = 'secondary';
              else if (this.tasks.calm.find(t => t.id == taskId)) currentLocation = 'calm';
              else if (this.tasks.available.find(t => t.id == taskId)) currentLocation = 'available';
            }

            // Check if moving TO today's slots - fullness only matters for drags, not button clicks
            const isMovingToSlot = ['urgent', 'secondary', 'calm'].includes(newPriority);
            
            if (isMovingToSlot && !fromButton) {
              const maxSlots = parseInt(document.querySelector(`#priority-${newPriority}-container`).dataset.max);
              const currentCount = this.tasks[newPriority].length;
              if (currentCount >= maxSlots) {
                this.showAlert(`Today's ${newPriority} slots are full (${currentCount}/${maxSlots}). Drag a task out first to make room.`, 'warning');
                return;
              }
            }

            // Remove from old location
            if (currentLocation) {
              this.tasks[currentLocation] = this.tasks[currentLocation].filter(t => t.id != taskId);
            }
            
            // Find task and add to new location
            const allTasksArray = [...this.tasks.inbox, ...this.tasks.urgent, ...this.tasks.secondary, ...this.tasks.calm, ...this.tasks.available];
            const task = allTasksArray.find(t => t.id == taskId);

            if (task) {
              task.status = newPriority;
              task.priority = newPriority;
              this.tasks[newPriority] = this.tasks[newPriority] || [];
              this.tasks[newPriority].push(task);
            }

            // Update via API
            await this.apiCall('PUT', 'tasks/update.php', {
              task_id: taskId,
              status: newPriority,
              priority: newPriority
            });

            this.render();
            
            // Different messages based on action
            if (fromButton) {
              this.showAlert(`<i class="bi bi-check-circle text-success"></i> Task marked as ${newPriority}`, 'success');
            } else if (newPriority === 'inbox') {
              this.showAlert('<i class="bi bi-check-circle text-success"></i> Task moved to inbox', 'success');
            } else {
              this.showAlert(`<i class="bi bi-check-circle text-success"></i> Task moved to ${newPriority}`, 'success');
            }
            console.log(`<i class="bi bi-check-circle text-success"></i> Task ${taskId} moved to ${newPriority}`);
          } catch (error) {
            console.error('Move failed:', error);
            this.loadTasks(); // Reload on error
            this.showAlert('Failed to move task', 'error');
          }
        },

        // ===== TASK ACTIONS =====
        createTask: async function(title) {
          try {
            const result = await this.apiCall('POST', 'tasks/create.php', {
              title: title,
              priority: 'neutral',
              status: 'inbox'
            });
            this.loadTasks();
            this.showAlert('<i class="bi bi-check-circle text-success"></i> Task added to inbox', 'success');
          } catch (error) {
            console.error('Failed to create task:', error);
            this.showAlert('Failed to create task', 'error');
          }
        },

        showTaskDetails: function(taskId) {
          // Find task in all lists
          const allTasks = [...this.tasks.inbox, ...this.tasks.urgent, ...this.tasks.secondary, ...this.tasks.calm, ...this.tasks.available];
          const task = allTasks.find(t => t.id == taskId);
          
          if (!task) {
            this.showAlert('Task not found', 'error');
            return;
          }

          // Create modal
          const modal = document.createElement('div');
          modal.className = 'modal fade';
          modal.setAttribute('tabindex', '-1');
          modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">${this.escapeHtml(task.title || task.text || 'Untitled')}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  ${task.description ? `
                    <div class="mb-3">
                      <h6 class="text-muted">Description</h6>
                      <p>${this.escapeHtml(task.description).replace(/\n/g, '<br>')}</p>
                    </div>
                  ` : ''}
                  
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <h6 class="text-muted">Priority</h6>
                        <p>${task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Not set'}</p>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <h6 class="text-muted">Duration</h6>
                        <p>${task.estimated_duration_minutes || '-'} minutes</p>
                      </div>
                    </div>
                  </div>

                  ${task.due_date ? `
                    <div class="mb-3">
                      <h6 class="text-muted">Due Date</h6>
                      <p>${new Date(task.due_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</p>
                    </div>
                  ` : ''}

                  ${task.category ? `
                    <div class="mb-3">
                      <h6 class="text-muted">Category</h6>
                      <p>${this.escapeHtml(task.category)}</p>
                    </div>
                  ` : ''}
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="button" class="btn btn-primary" onclick="TaskPlanner.editTask(${taskId}); bootstrap.Modal.getInstance(document.querySelector('.modal.fade')).hide();">Edit Task</button>
                </div>
              </div>
            </div>
          `;
          
          document.body.appendChild(modal);
          const bsModal = new bootstrap.Modal(modal);
          bsModal.show();
          
          // Clean up modal on hide
          modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
          });
        },

        editTask: function(taskId) {
          // Find task in all lists
          const allTasks = [...this.tasks.inbox, ...this.tasks.urgent, ...this.tasks.secondary, ...this.tasks.calm, ...this.tasks.available];
          const task = allTasks.find(t => t.id == taskId);
          
          if (!task) {
            this.showAlert('Task not found', 'error');
            return;
          }

          // Load assignable users and tags
          Promise.all([
            fetch('../api/tasks/get-assignable-users.php').then(r => r.json()),
            fetch('../api/tasks/get-tags.php').then(r => r.json())
          ]).then(([usersData, tagsData]) => {
            const users = (usersData.success && usersData.data) ? usersData.data : [];
            const allTags = (tagsData.success && tagsData.data) ? tagsData.data : [];

            // Use unified modal
            UnifiedTaskModal.open({
              taskId: taskId,
              task: {
                title: task.title || task.text,
                priority: task.priority || 'medium',
                description: task.description || '',
                due_date: task.due_date || '',
                estimated_time: task.estimated_duration_minutes || '',
                tags: task.tags || [],
                assigned_to: task.assigned_to || ''
              },
              availableTags: allTags,
              assignableUsers: users,
              userRole: window._currentUserRole,
              onSave: async (taskData) => {
                try {
                  // Save basic task data
                  await this.apiCall('PUT', 'tasks/update.php', {
                    task_id: taskId,
                    title: taskData.title,
                    priority: taskData.priority,
                    estimated_duration_minutes: taskData.estimated_time ? parseInt(taskData.estimated_time) : null,
                    description: taskData.description || null,
                    due_date: taskData.due_date || null
                  });

                  // Save tags
                  if (taskData.tags && taskData.tags.length > 0) {
                    await fetch('../api/tasks/save-task-tags.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({
                        task_id: taskId,
                        tag_ids: taskData.tags
                      })
                    });
                  } else {
                    // Clear all tags if none selected
                    await fetch('../api/tasks/save-task-tags.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      body: JSON.stringify({
                        task_id: taskId,
                        tag_ids: []
                      })
                    });
                  }

                  // Save assignment if applicable (admin/developer only)
                  if (taskData.assigned_to) {
                    try {
                      let assignedTo = taskData.assigned_to === 'me' ? null : parseInt(taskData.assigned_to);
                      await fetch('../api/tasks/assign-task.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                          task_id: taskId,
                          assigned_to: assignedTo
                        })
                      });
                    } catch (e) {
                      console.warn('Could not save assignment (admin only):', e);
                    }
                  }

                  this.loadTasks();
                  this.showAlert('<i class="bi bi-check-circle text-success"></i> Task updated!', 'success');
                } catch (error) {
                  console.error('Failed to update task:', error);
                  this.showAlert('Failed to update task', 'error');
                }
              }
            });
          }).catch(error => {
            console.error('Failed to load data for edit modal:', error);
            this.showAlert('Failed to load task details', 'error');
          });
        },

        filterTasks: function() {
          const filterValue = document.querySelector('#task-filter').value.toLowerCase();
          const tagValue = document.querySelector('#tag-filter').value;
          const rows = document.querySelectorAll('.task-row');
          
          rows.forEach(row => {
            const taskText = row.textContent.toLowerCase();
            const taskTagsJSON = row.getAttribute('data-tags');
            let taskTags = [];
            try {
              taskTags = taskTagsJSON ? JSON.parse(taskTagsJSON) : [];
            } catch (e) {
              taskTags = [];
            }
            
            // Match search text
            const matchesSearch = filterValue === '' || taskText.includes(filterValue);
            
            // Match tag filter (check if selected tag is in the task's tags)
            const matchesTag = tagValue === '' || taskTags.some(tag => tag.id == tagValue);
            
            row.style.display = (matchesSearch && matchesTag) ? '' : 'none';
          });
        },

        populateTagFilter: function() {
          // Get unique tags from available tasks
          const allTags = new Map();
          this.tasks.available.forEach(task => {
            if (task.tags && Array.isArray(task.tags)) {
              task.tags.forEach(tag => {
                if (!allTags.has(tag.id)) {
                  allTags.set(tag.id, tag);
                }
              });
            }
          });
          
          // Populate the tag dropdown
          const select = document.querySelector('#tag-filter');
          const currentValue = select.value;
          
          // Keep the default option, add new tags
          const options = ['<option value="">All Tags</option>'];
          Array.from(allTags.values())
            .sort((a, b) => a.name.localeCompare(b.name))
            .forEach(tag => {
              options.push(`<option value="${tag.id}">${this.escapeHtml(tag.name)}</option>`);
            });
          
          select.innerHTML = options.join('');
          if (currentValue && allTags.has(parseInt(currentValue))) {
            select.value = currentValue; // Restore selection if it still exists
          }
        },

        sortTasks: function() {
          const sortValue = document.querySelector('#task-sort').value;
          
          // Sort the actual data array
          switch(sortValue) {
            case 'name-asc':
              this.tasks.available.sort((a, b) => (a.title || a.text).localeCompare(b.title || b.text));
              break;
            case 'name-desc':
              this.tasks.available.sort((a, b) => (b.title || b.text).localeCompare(a.title || a.text));
              break;
            case 'created-new':
              this.tasks.available.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
              break;
            case 'created-old':
              this.tasks.available.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
              break;
            case 'due-date-asc':
              this.tasks.available.sort((a, b) => {
                const aDate = a.due_date ? new Date(a.due_date) : new Date('9999-12-31');
                const bDate = b.due_date ? new Date(b.due_date) : new Date('9999-12-31');
                return aDate - bDate;
              });
              break;
            case 'due-date-desc':
              this.tasks.available.sort((a, b) => {
                const aDate = a.due_date ? new Date(a.due_date) : new Date('1900-01-01');
                const bDate = b.due_date ? new Date(b.due_date) : new Date('1900-01-01');
                return bDate - aDate;
              });
              break;
          }
          
          // Re-render with sorted data
          this.renderAvailable();
        },

        completeTask: async function(taskId) {
          try {
            await this.apiCall('PUT', 'tasks/update.php', {
              task_id: taskId,
              status: 'completed',
              completed_date: new Date().toISOString()
            });
            this.loadTasks();
            this.showAlert('<i class="bi bi-check-circle text-success"></i> Task completed!', 'success');
          } catch (error) {
            this.showAlert('Failed to complete task', 'error');
          }
        },

        deleteTask: async function(taskId) {
          if (!confirm('Are you sure? This cannot be undone.')) return;

          try {
            await this.apiCall('DELETE', 'tasks/delete.php', { task_id: taskId });
            this.loadTasks();
            this.showAlert('<i class="bi bi-trash"></i>‘ï¸ Task deleted', 'success');
          } catch (error) {
            this.showAlert('Failed to delete task', 'error');
          }
        },

        rescheduleTask: async function(taskId, target = 'tomorrow') {
          try {
            let dueDate;
            if (target === 'tomorrow') {
              dueDate = new Date(Date.now() + 86400000).toISOString().split('T')[0];
            } else if (typeof target === 'string' && target.match(/^\d{4}-\d{2}-\d{2}$/)) {
              dueDate = target;
            } else {
              this.showAlert('Invalid reschedule target', 'error');
              return;
            }

            await this.apiCall('PUT', 'tasks/update.php', {
              task_id: taskId,
              due_date: dueDate,
              status: 'scheduled'
            });

            this.loadTasks();
            const dateStr = target === 'tomorrow' ? 'tomorrow' : new Date(dueDate + 'T00:00:00').toLocaleDateString();
            this.showAlert(`<i class="bi bi-check-circle text-success"></i> Task rescheduled to ${dateStr}`, 'success');
          } catch (error) {
            console.error('Failed to reschedule task:', error);
            this.showAlert('Failed to reschedule task', 'error');
          }
        },

        showRescheduleModal: function(taskId) {
          const task = this.tasks.available.find(t => t.id === taskId) || 
                       this.tasks.inbox.find(t => t.id === taskId) ||
                       [...this.tasks.urgent, ...this.tasks.secondary, ...this.tasks.calm].find(t => t.id === taskId);
          
          if (!task) {
            this.showAlert('Task not found', 'error');
            return;
          }

          // Get today's date for min attribute
          const today = new Date().toISOString().split('T')[0];
          
          const modal = document.createElement('div');
          modal.className = 'modal fade';
          modal.setAttribute('tabindex', '-1');
          modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Reschedule Task</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <p class="mb-3"><strong>Task:</strong> ${this.escapeHtml(task.title || task.text)}</p>
                  
                  <div class="mb-3">
                    <label class="form-label">New Due Date</label>
                    <input type="date" class="form-control" id="reschedule-date" min="${today}" value="${today}">
                    <small class="form-text text-muted">Select when you want to work on this task</small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Quick Options</label>
                    <div class="btn-group w-100" role="group" style="flex-wrap: wrap;">
                      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="TaskPlanner.setRescheduleDate(1)">Tomorrow</button>
                      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="TaskPlanner.setRescheduleDate(3)">In 3 Days</button>
                      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="TaskPlanner.setRescheduleDate(7)">Next Week</button>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" onclick="TaskPlanner.confirmRescheduleDate(${taskId})">Reschedule</button>
                </div>
              </div>
            </div>
          `;

          document.body.appendChild(modal);
          const bsModal = new bootstrap.Modal(modal);
          bsModal.show();

          modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
          });
        },

        setRescheduleDate: function(daysFromNow) {
          const date = new Date(Date.now() + (daysFromNow * 86400000)).toISOString().split('T')[0];
          document.getElementById('reschedule-date').value = date;
        },

        confirmRescheduleDate: function(taskId) {
          const dateInput = document.getElementById('reschedule-date');
          if (!dateInput || !dateInput.value) {
            this.showAlert('Please select a date', 'warning');
            return;
          }

          // Close modal
          const modal = document.querySelector('.modal');
          if (modal) {
            bootstrap.Modal.getInstance(modal)?.hide();
          }

          // Reschedule task
          this.rescheduleTask(taskId, dateInput.value);
        },

        confirmClearPriorities: function() {
          if (!confirm('Clear all tasks from today\'s schedule? This will remove them from today\'s slots.')) return;
          this.clearPriorities();
        },

        clearPriorities: async function() {
          try {
            const allSelected = [...this.tasks.urgent, ...this.tasks.secondary, ...this.tasks.calm];
            
            for (const task of allSelected) {
              await this.apiCall('PUT', 'tasks/update.php', {
                task_id: task.id,
                status_today: null
              });
            }

            this.loadTasks();
            this.showAlert('<i class="bi bi-check-circle text-success"></i> Today\'s schedule cleared', 'success');
          } catch (error) {
            this.showAlert('Failed to clear schedule', 'error');
          }
        },

        // ===== UTILITIES =====
        showAlert: function(message, type = 'info') {
          const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : type === 'warning' ? 'alert-warning' : 'alert-info';
          
          const alert = document.createElement('div');
          alert.className = `alert ${alertClass} alert-toast alert-dismissible fade show`;
          alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          `;
          document.body.appendChild(alert);
          
          setTimeout(() => alert.remove(), 4000);
        },

        escapeHtml: function(text) {
          const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
          return text.replace(/[&<>"']/g, m => map[m]);
        },

        formatTime: function(isoString) {
          try {
            const date = new Date(isoString);
            const today = new Date();
            const isToday = date.toDateString() === today.toDateString();
            
            if (isToday) return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
          } catch {
            return 'Unknown';
          }
        }
      };

      // Initialize on DOM ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => TaskPlanner.init());
      } else {
        TaskPlanner.init();
      }

      // Expose to window for inline handlers
      window.TaskPlanner = TaskPlanner;
    })();
  </script>
</body>
</html>

