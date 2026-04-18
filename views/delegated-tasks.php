<?php
/**
 * ADHD Dashboard - Delegated Tasks Summary
 * Shows tasks you've delegated and tasks delegated to you
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
  <title>Delegated Tasks - ADHD Dashboard</title>
  
  <!-- Bootstrap 5.3.8 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&family=Poppins:wght@600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  
  <!-- ADHD Custom Theme -->
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

    /* Summary Cards */
    .summary-card {
      background: var(--color-bg-primary);
      border: 1px solid var(--color-border-light);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .summary-card .card-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--color-urgent);
      margin: 10px 0;
    }

    .summary-card .card-label {
      font-size: 0.9rem;
      color: var(--color-text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .summary-card .card-description {
      font-size: 0.85rem;
      color: var(--color-text-secondary);
      margin-top: 10px;
    }

    /* Delegation Status Badges */
    .delegation-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .delegation-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .delegation-active {
      background-color: #d1ecf1;
      color: #0c5460;
    }

    .delegation-completed {
      background-color: #d4edda;
      color: #155724;
    }

    /* Task List */
    .delegation-item {
      background: var(--color-bg-primary);
      border: 1px solid var(--color-border-light);
      border-radius: 6px;
      padding: 15px;
      margin-bottom: 12px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 15px;
    }

    .delegation-item:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border-color: var(--color-border-medium);
    }

    .delegation-item-content {
      flex-grow: 1;
      min-width: 0;
    }

    .delegation-item-title {
      font-weight: 500;
      color: var(--color-text-dark);
      margin-bottom: 6px;
      word-break: break-word;
    }

    .delegation-item-meta {
      font-size: 0.85rem;
      color: var(--color-text-muted);
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .delegation-item-actions {
      display: flex;
      gap: 8px;
      width: auto;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: var(--color-text-muted);
    }

    .empty-state-icon {
      font-size: 3rem;
      margin-bottom: 15px;
      opacity: 0.5;
    }

    /* Tab Navigation */
    .nav-tabs {
      border-bottom: 2px solid var(--color-border-light);
    }

    .nav-tabs .nav-link {
      color: var(--color-text-secondary);
      border: none;
      border-bottom: 2px solid transparent;
      cursor: pointer;
      padding: 12px 16px;
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .nav-tabs .nav-link:hover {
      color: var(--color-text-dark);
    }

    .nav-tabs .nav-link.active {
      color: var(--color-urgent);
      border-bottom-color: var(--color-urgent);
      background-color: transparent;
    }
  </style>
</head>
<body <?php echo isset($user['theme_preference']) ? "data-theme='{$user['theme_preference']}'" : "data-theme='light'"; ?>>
  <?php $current_page = 'delegated-tasks'; require_once __DIR__ . '/header.php'; ?>

  <!-- Main Content -->
  <main class="container-fluid py-4">
    <h1 class="visually-hidden">Delegated Tasks</h1>

    <!-- Page Header -->
    <div class="mb-4">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
          <h1 class="h3 mb-1"><i class="bi bi-share2 me-2"></i>Delegated Tasks</h1>
          <p class="text-muted mb-0">Manage tasks you've delegated and tasks assigned to you</p>
        </div>
        <button id="refresh-btn" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
      </div>
    </div>

    <!-- Tabs for Different Views -->
    <ul class="nav nav-tabs mb-4" id="delegationTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="assigned-to-me-tab" data-bs-toggle="tab" data-bs-target="#assigned-to-me-pane" type="button" role="tab" aria-controls="assigned-to-me-pane" aria-selected="true">
          <i class="bi bi-inbox me-2"></i> Tasks for Me
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="delegated-by-me-tab" data-bs-toggle="tab" data-bs-target="#delegated-by-me-pane" type="button" role="tab" aria-controls="delegated-by-me-pane" aria-selected="false">
          <i class="bi bi-share2 me-2"></i> Delegated by Me
        </button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="delegationTabContent">
      
      <!-- Tasks Assigned to Me -->
      <div class="tab-pane fade show active" id="assigned-to-me-pane" role="tabpanel" aria-labelledby="assigned-to-me-tab">
        <div id="assigned-to-me-list"></div>
      </div>

      <!-- Tasks Delegated by Me -->
      <div class="tab-pane fade" id="delegated-by-me-pane" role="tabpanel" aria-labelledby="delegated-by-me-tab">
        <div id="delegated-by-me-list"></div>
      </div>

    </div>
  </main>

  <!-- Task Details Modal -->
  <div class="modal fade" id="task-details-modal" tabindex="-1" aria-labelledby="taskDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="taskDetailsLabel">Task Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <h3 id="modal-task-title" style="margin-bottom: 1rem;"></h3>
          <div style="margin-bottom: 1.5rem;">
            <span id="modal-task-priority" class="badge bg-secondary me-2"></span>
          </div>
          
          <div class="row g-3">
            <div class="col-md-6">
              <div>
                <strong><i class="bi bi-tag me-2"></i>Category</strong>
                <p id="modal-task-category" class="text-muted">-</p>
              </div>
            </div>
            <div class="col-md-6">
              <div>
                <strong><i class="bi bi-calendar me-2"></i>Due Date</strong>
                <p id="modal-task-due-date" class="text-muted">-</p>
              </div>
            </div>
            <div class="col-md-6">
              <div>
                <strong><i class="bi bi-clock me-2"></i>Estimated Duration</strong>
                <p id="modal-task-estimated" class="text-muted">-</p>
              </div>
            </div>
          </div>

          <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #E0E4E8;">
            <strong><i class="bi bi-file-text me-2"></i>Description</strong>
            <p id="modal-task-description" class="text-muted mt-2" style="white-space: pre-wrap; word-wrap: break-word;">-</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Set correct API base for delegated tasks page -->
  <script>
    window._apiBase = "<?php echo Config::url('api'); ?>";
  </script>

  <!-- Delegated Tasks Script -->
  <script>
    /**
     * Delegated Tasks Manager
     * Shows both tasks assigned to you and tasks you've delegated
     */
    const DelegatedTasks = {
      apiBase: window._apiBase || '/api',
      
      tasks: {
        assignedToMe: [],
        delegatedByMe: []
      },

      // Initialize
      init: function() {
        console.log('📋 Delegated Tasks initializing...');
        this.bindEvents();
        this.loadTasks();
      },

      bindEvents: function() {
        const refreshBtn = document.getElementById('refresh-btn');
        if (refreshBtn) {
          refreshBtn.addEventListener('click', () => {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Loading...';
            this.loadTasks().then(() => {
              refreshBtn.disabled = false;
              refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Refresh';
            }).catch(() => {
              refreshBtn.disabled = false;
              refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Refresh';
            });
          });
        }
      },

      // Load tasks from API
      loadTasks: async function() {
        try {
          // Load tasks assigned to me
          const assignedResponse = await fetch(this.apiBase + 'tasks/get-assigned-to-me.php');
          const assignedData = await assignedResponse.json();
          this.tasks.assignedToMe = assignedData.data || [];

          // Load tasks delegated by me
          const delegatedResponse = await fetch(this.apiBase + 'tasks/get-delegated-by-me.php');
          const delegatedData = await delegatedResponse.json();
          this.tasks.delegatedByMe = delegatedData.data || [];

          console.log('📥 Tasks assigned to me:', this.tasks.assignedToMe);
          console.log('📤 Tasks delegated by me:', this.tasks.delegatedByMe);

          this.render();
        } catch (error) {
          console.error('Failed to load delegated tasks:', error);
          this.showError('Failed to load tasks. Please try again.');
        }
      },

      // Update summary stats
      updateStats: function() {
        const assignedCount = this.tasks.assignedToMe.length;
        const delegatedCount = this.tasks.delegatedByMe.length;
        
        const completedByMe = this.tasks.delegatedByMe.filter(t => t.status === 'completed').length;
        const inProgressByMe = this.tasks.delegatedByMe.filter(t => t.status !== 'completed' && t.status !== 'archived').length;

        document.getElementById('assigned-to-me-count').textContent = assignedCount;
        document.getElementById('delegated-count').textContent = delegatedCount;
        document.getElementById('in-progress-count').textContent = inProgressByMe;
        document.getElementById('completed-count').textContent = completedByMe;
      },

      // Render both sections
      render: function() {
        this.renderAssignedToMe();
        this.renderDelegatedByMe();
      },

      // Render tasks assigned to me
      renderAssignedToMe: function() {
        const container = document.getElementById('assigned-to-me-list');
        const tasks = this.tasks.assignedToMe;

        if (tasks.length === 0) {
          container.innerHTML = `
            <div class="empty-state">
              <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
              <h5>No tasks assigned to you</h5>
              <p>Tasks delegated to you will appear here</p>
            </div>
          `;
          return;
        }

        let html = '';
        tasks.forEach(task => {
          const status = task.status || 'pending';
          const statusClass = status === 'completed' ? 'delegation-completed' : 'delegation-active';
          const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
          
          // Build assigned_by name from API fields
          const assignedByName = task.assigned_by_first && task.assigned_by_last 
            ? `${task.assigned_by_first} ${task.assigned_by_last}`
            : (task.assigned_by_first || task.assigned_by_email || 'Unknown');
          
          html += `
            <div class="delegation-item">
              <div class="delegation-item-content">
                <div class="delegation-item-title">${this.escapeHtml(task.title || task.text || 'Untitled')}</div>
                <div class="delegation-item-meta">
                  <span><i class="bi bi-person me-1"></i>From: ${this.escapeHtml(assignedByName)}</span>
                  <span><i class="bi bi-calendar me-1"></i>${new Date(task.assignment_date).toLocaleDateString()}</span>
                  <span class="delegation-badge ${statusClass}">${statusLabel}</span>
                </div>
              </div>
              <div class="delegation-item-actions">
                <button class="btn btn-sm btn-outline-secondary" onclick="DelegatedTasks.viewTask(${task.id})">
                  <i class="bi bi-eye me-1"></i> View
                </button>
              </div>
            </div>
          `;
        });

        container.innerHTML = html;
      },

      // Render tasks delegated by me
      renderDelegatedByMe: function() {
        const container = document.getElementById('delegated-by-me-list');
        const tasks = this.tasks.delegatedByMe;

        if (tasks.length === 0) {
          container.innerHTML = `
            <div class="empty-state">
              <div class="empty-state-icon"><i class="bi bi-share2"></i></div>
              <h5>No delegated tasks</h5>
              <p>Tasks you've assigned to others will appear here</p>
            </div>
          `;
          return;
        }

        let html = '';
        tasks.forEach(task => {
          const status = task.status || 'pending';
          const statusClass = status === 'completed' ? 'delegation-completed' : (status === 'in_progress' ? 'delegation-active' : 'delegation-pending');
          const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
          
          // Build assigned_to name from API fields
          const assignedToName = task.assigned_to_first && task.assigned_to_last 
            ? `${task.assigned_to_first} ${task.assigned_to_last}`
            : (task.assigned_to_first || task.assigned_to_email || 'Unknown');
          
          html += `
            <div class="delegation-item">
              <div class="delegation-item-content">
                <div class="delegation-item-title">${this.escapeHtml(task.title || task.text || 'Untitled')}</div>
                <div class="delegation-item-meta">
                  <span><i class="bi bi-person me-1"></i>Assigned to: ${this.escapeHtml(assignedToName)}</span>
                  <span><i class="bi bi-calendar me-1"></i>${new Date(task.assignment_date).toLocaleDateString()}</span>
                  <span class="delegation-badge ${statusClass}">${statusLabel}</span>
                </div>
              </div>
              <div class="delegation-item-actions">
                <button class="btn btn-sm btn-outline-secondary" onclick="DelegatedTasks.viewTask(${task.id})">
                  <i class="bi bi-eye me-1"></i> View
                </button>
              </div>
            </div>
          `;
        });

        container.innerHTML = html;
      },

      // Utility: Escape HTML
      escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      },

      // View task details in modal
      viewTask: async function(taskId) {
        try {
          const response = await fetch(this.apiBase + 'tasks/get-task.php?id=' + taskId);
          const data = await response.json();
          
          if (data.success && data.data) {
            const task = data.data;
            const modal = document.getElementById('task-details-modal');
            
            // Populate modal
            document.getElementById('modal-task-title').textContent = task.title || task.text || 'Untitled';
            document.getElementById('modal-task-description').innerHTML = this.escapeHtml(task.description || 'No description');
            document.getElementById('modal-task-priority').textContent = task.priority || 'Normal';
            document.getElementById('modal-task-priority').parentElement.className = 'badge bg-secondary';
            
            if (task.priority === 'Urgent') {
              document.getElementById('modal-task-priority').parentElement.className = 'badge bg-danger';
            } else if (task.priority === 'Secondary') {
              document.getElementById('modal-task-priority').parentElement.className = 'badge bg-warning';
            } else if (task.priority === 'Calm') {
              document.getElementById('modal-task-priority').parentElement.className = 'badge bg-info';
            }
            
            document.getElementById('modal-task-category').textContent = task.category || 'Uncategorized';
            document.getElementById('modal-task-due-date').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date';
            document.getElementById('modal-task-estimated').textContent = (task.estimated_duration_minutes || 25) + ' minutes';
            
            // Show modal
            const bootstrap = window.bootstrap || {};
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
          }
        } catch (error) {
          console.error('Failed to load task details:', error);
          alert('Failed to load task details. Please try again.');
        }
      },

      // Show error message
      showError: function(message) {
        const container = document.getElementById('assigned-to-me-list');
        container.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>${message}</div>`;
      }
    };

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
      DelegatedTasks.init();
    });
  </script>
</body>
</html>
