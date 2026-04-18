<?php
/**
 * ADHD Dashboard - Unified Header Component
 * Include this file on all pages: require_once __DIR__ . '/header.php';
 * Uses global $user and $current_page variables
 */

// Make sure auth and config are loaded
if (!defined('Config')) {
    require_once __DIR__ . '/../lib/config.php';
    require_once __DIR__ . '/../lib/auth.php';
}

// Get current user if not already set
if (!isset($user)) {
    $user = Auth::isAuthenticated() ? Auth::getCurrentUser() : null;
}

// Set default current_page if not provided
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}

// Determine if user is admin or developer
$user_role = $user['role'] ?? 'user';
$is_admin = $user && in_array($user_role, ['admin', 'developer']);

// Get avatar URL or use generic placeholder (make it absolute path)
$avatar_url = (!empty($user['avatar_url'])) ? APP_SUBDIR . '/' . htmlspecialchars($user['avatar_url']) : null;
$user_display_name = $user ? (htmlspecialchars(($user['username'] ?? '') ?: ($user['first_name'] ?? $user['email']))) : 'User';

// Generate initials for fallback avatar
$user_initials = '';
if ($user) {
    if ($user['first_name']) {
        $user_initials = strtoupper(substr($user['first_name'], 0, 1));
        if ($user['last_name']) {
            $user_initials .= strtoupper(substr($user['last_name'], 0, 1));
        }
    } else {
        $user_initials = strtoupper(substr($user['email'], 0, 2));
    }
}
?>

<!-- Skip to Content Link (Accessible to keyboard and screen reader users) -->
<a href="#main-content" class="btn btn-warning visually-hidden-focusable" style="position: fixed; top: 0; left: 0; z-index: 9999; padding: 0.5rem 1rem;">
    <i class="bi bi-skip-forward me-2"></i> Skip to main content
</a>

<!-- Unified Header Navigation -->
<header class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top" role="navigation" aria-label="Main navigation">
    <div class="container-fluid px-4">
        <!-- Logo & Brand -->
        <a class="navbar-brand fw-bold" href="<?php echo Config::redirectUrl('/views/dashboard.php'); ?>" aria-label="ADHD Assist Dashboard">
            <i class="bi bi-check2-circle text-warning me-2"></i>
            <span>ADHD Assist</span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Center Focus Button -->
            <div style="flex: 1; display: flex; justify-content: center; gap: 1rem; align-items: center;">
                <button id="header-focus-btn" class="btn btn-primary btn-sm" title="Start 25 minute focus timer" style="margin: 0; padding: 0.4rem 1rem;">
                    <i class="bi bi-play-circle me-2"></i> Focus
                </button>
            </div>
            
            <!-- Right-aligned items -->
            <ul class="navbar-nav align-items-center">
                <!-- Dashboard Link (hidden if on dashboard) -->
                <?php if ($current_page !== 'dashboard'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo Config::redirectUrl('/views/dashboard.php'); ?>">📊 Dashboard</a>
                    </li>
                <?php endif; ?>

             
 

                <!-- Reminders Notification Bell -->
                <li class="nav-item ms-lg-2">
                    <button 
                        class="btn btn-link nav-link" 
                        id="remindersDropdownBtn" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                        data-bs-title="Upcoming Reminders"
                        style="position: relative; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; padding: 0.5rem 0.75rem; border: none; background: none; color: inherit;">
                        <i class="bi bi-bell" style="font-size: 1.3rem;"></i>
                        <span id="reminders-badge" class="badge bg-warning text-dark" style="position: absolute; top: -2px; right: -2px; min-width: 24px; height: 24px; display: none; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">0</span>
                    </button>
                    
                    <!-- Reminders Dropdown Menu -->
                    <ul class="dropdown-menu dropdown-menu-end" id="remindersDropdownMenu" aria-labelledby="remindersDropdownBtn" style="min-width: 300px;">
                        <li class="dropdown-header">Upcoming Reminders</li>
                        <div id="remindersList" style="max-height: 400px; overflow-y: auto;">
                            <li><a class="dropdown-item text-muted small" href="#">Loading...</a></li>
                        </div>
                    </ul>
                </li>

                <!-- Habit Notification Badge -->
                <li class="nav-item ms-lg-2">
                    <button 
                        class="btn btn-link nav-link" 
                        id="habitDropdownBtn" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                        data-bs-title="Today's Habits Remaining"
                        style="position: relative; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; padding: 0.5rem 0.75rem; border: none; background: none; color: inherit;">
                        <i class="bi bi-check2-circle" style="font-size: 1.3rem;"></i>
                        <span id="habit-badge" class="badge bg-warning text-dark" style="position: absolute; top: -2px; right: -2px; min-width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">0</span>
                    </button>
                    
                    <!-- Habits Dropdown Menu -->
                    <ul class="dropdown-menu dropdown-menu-end" id="habitDropdownMenu" aria-labelledby="habitDropdownBtn" style="min-width: 300px;">
                        <li class="dropdown-header">Today's Incomplete Habits</li>
                        <div id="habitsList" style="max-height: 400px; overflow-y: auto;">
                            <li><a class="dropdown-item text-muted small" href="#">Loading...</a></li>
                        </div>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="<?php echo Config::redirectUrl('/views/dashboard.php'); ?>#routines-pane">View All Habits</a></li>
                    </ul>
                </li>

                <!-- Assigned Tasks Notification Bell -->
                <li class="nav-item ms-lg-2">
                    <button 
                        class="btn btn-link nav-link" 
                        id="assignedTasksDropdownBtn" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                        data-bs-title="Tasks Assigned to You"
                        style="position: relative; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; padding: 0.5rem 0.75rem; border: none; background: none; color: inherit;">
                        <i class="bi bi-list-task" id="assignedTasksIcon" style="font-size: 1.3rem;"></i>
                        <span id="assigned-tasks-badge" class="badge bg-info text-dark" style="position: absolute; top: -2px; right: -2px; min-width: 24px; height: 24px; display: none; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">0</span>
                    </button>
                    
                    <!-- Assigned Tasks Dropdown Menu -->
                    <ul class="dropdown-menu dropdown-menu-end" id="assignedTasksDropdownMenu" aria-labelledby="assignedTasksDropdownBtn" style="min-width: 320px;">
                        <li class="dropdown-header">Tasks Assigned to You</li>
                        <div id="assignedTasksList" style="max-height: 400px; overflow-y: auto;">
                            <li><a class="dropdown-item text-muted small" href="#">Loading...</a></li>
                        </div>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="<?php echo Config::redirectUrl('/views/task-planner.php'); ?>?filter=assigned">View All Assigned Tasks</a></li>
                    </ul>
                </li>

                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown ms-lg-3">
                    <button class="btn btn-link nav-link dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; padding: 0.5rem 0.75rem;">
                        <!-- Avatar Circle -->
                        <?php if ($avatar_url): ?>
                            <img src="<?php echo $avatar_url; ?>" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #dee2e6;">
                        <?php else: ?>
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.85rem; border: 2px solid #dee2e6;">
                                <?php echo htmlspecialchars($user_initials); ?>
                            </div>
                        <?php endif; ?>
                        <!-- Username -->
                        <span class="d-none d-md-inline text-dark"><?php echo $user_display_name; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <!-- Admin Dashboard (admin/developer only) -->
                        <?php if ($is_admin): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo Config::redirectUrl('/admin/dashboard.php'); ?>">
                                    <i class="bi bi-shield-lock me-2"></i> Admin Dashboard
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>

                        <!-- Profile -->
                        <li>
                            <a class="dropdown-item" href="<?php echo Config::redirectUrl('/views/profile.php'); ?>">
                                <i class="bi bi-person-circle me-2"></i> Profile
                            </a>
                        </li>

                        <!-- Settings -->
                        <li>
                            <a class="dropdown-item" href="<?php echo Config::redirectUrl('/views/settings.php'); ?>">
                                <i class="bi bi-gear me-2"></i> Settings
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <!-- Logout -->
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo Config::redirectUrl('/views/logout.php'); ?>">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</header>

<style>
    .dropdown-toggle::after {
        display: none;
    }
    
    .nav-link.dropdown-toggle:hover {
        opacity: 0.8;
    }
    
    .dropdown-item i {
        width: 20px;
        text-align: center;
    }
</style>

<!-- Task Details Modal -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailsModalTitle">Task Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="taskDetailsModalId">
                
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted">Priority</label>
                    <div id="taskDetailsModalPriority" class="fw-bold text-secondary">Normal</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted">Description</label>
                    <div id="taskDetailsModalDescription" class="text-dark">No description provided</div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-muted">Due Date</label>
                        <div id="taskDetailsModalDueDate" class="text-dark">No due date</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-muted">Assigned By</label>
                        <div id="taskDetailsModalAssignedBy" class="text-dark">Admin</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Set global API base URL from PHP config
    const API_BASE = '<?php echo rtrim(Config::url("api"), "/"); ?>';
    
    // Fetch upcoming reminders and update badge
    async function updateRemindersBadge() {
        try {
            const response = await fetch(API_BASE + '/user/reminders-list.php');
            const data = await response.json();
            if (data.success) {
                const badge = document.getElementById('reminders-badge');
                if (badge) {
                    badge.textContent = data.total;
                    // Hide badge if count is 0
                    badge.style.display = data.total > 0 ? 'flex' : 'none';
                }
            }
        } catch (error) {
            console.error('Error fetching reminders count:', error);
        }
    }

    // Fetch and populate reminders dropdown
    async function updateRemindersList() {
        try {
            const response = await fetch(API_BASE + '/user/reminders-list.php');
            const data = await response.json();
            const remindersList = document.getElementById('remindersList');
            
            if (data.success && remindersList) {
                if (data.total === 0) {
                    remindersList.innerHTML = '<li><a class="dropdown-item text-muted small py-2">✓ No upcoming reminders</a></li>';
                } else {
                    let html = '';
                    data.data.forEach(reminder => {
                        const date = new Date(reminder.reminder_at);
                        const dateStr = date.toLocaleDateString();
                        const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        const taskTitle = reminder.title ? reminder.title : 'General Reminder';
                        
                        // Color code by priority if it's a task reminder
                        let priorityClass = '';
                        if (reminder.task_id) {
                            if (reminder.priority === 'high') priorityClass = 'text-danger';
                            else if (reminder.priority === 'medium') priorityClass = 'text-warning';
                        }
                        
                        html += `<li>
                                    <a class="dropdown-item small py-2" href="#">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-bell-fill ${priorityClass}" style="font-size: 0.8rem; margin-top: 2px; flex-shrink: 0;"></i>
                                            <div style="overflow: hidden; flex: 1;">
                                                <div class="text-dark fw-500" style="font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(taskTitle)}</div>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">${reminder.type_label}</small>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">${dateStr} at ${timeStr}</small>
                                            </div>
                                        </div>
                                    </a>
                                </li>`;
                    });
                    remindersList.innerHTML = html;
                }
            }
        } catch (error) {
            console.error('Error fetching reminders list:', error);
        }
    }

    // HTML escape utility
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Fetch uncompleted habits count and update badge
    async function updateHabitBadge() {
        try {
            const response = await fetch(API_BASE + '/habits/uncompleted-count.php');
            const data = await response.json();
            if (data.success) {
                const badge = document.getElementById('habit-badge');
                if (badge) {
                    badge.textContent = data.uncompleted;
                    // Hide badge if count is 0
                    badge.style.display = data.uncompleted > 0 ? 'flex' : 'none';
                }
            }
        } catch (error) {
            console.error('Error fetching habit count:', error);
        }
    }

    // Fetch and populate incomplete habits dropdown
    async function updateHabitsList() {
        try {
            const response = await fetch(API_BASE + '/habits/incomplete-list.php');
            const data = await response.json();
            const habitsList = document.getElementById('habitsList');
            
            if (data.success && habitsList) {
                if (data.habits.length === 0) {
                    habitsList.innerHTML = '<li><a class="dropdown-item text-muted small py-2">✓ All habits completed!</a></li>';
                } else {
                    let html = '';
                    data.habits.forEach(habit => {
                        html += `<li><a class="dropdown-item small py-2" href="#" data-habit-id="${habit.id}">
                                    <i class="bi bi-circle me-2" style="font-size: 0.6rem;"></i>
                                    <span>${habit.habit_name}</span>
                                    <small class="text-muted d-block" style="margin-left: 1.5rem; font-size: 0.75rem;">${habit.period}</small>
                                </a></li>`;
                    });
                    habitsList.innerHTML = html;
                }
            }
        } catch (error) {
            console.error('Error fetching habits list:', error);
        }
    }

    // Fetch assigned tasks count and update badge
    async function updateAssignedTasksBadge() {
        try {
            const response = await fetch(API_BASE + '/tasks/get-assigned-count.php');
            const data = await response.json();
            if (data.success) {
                const badge = document.getElementById('assigned-tasks-badge');
                const icon = document.getElementById('assignedTasksIcon');
                if (badge && icon) {
                    const count = data.data.count;
                    badge.textContent = count;
                    // Show/hide badge
                    badge.style.display = count > 0 ? 'flex' : 'none';
                    // Change icon based on count
                    icon.className = count > 0 ? 'bi bi-list-task' : 'bi bi-check2-all';
                }
            }
        } catch (error) {
            console.error('Error fetching assigned tasks count:', error);
        }
    }

    // Fetch and populate assigned tasks dropdown
    async function updateAssignedTasksList() {
        try {
            const response = await fetch(API_BASE + '/tasks/get-assigned-count.php');
            const data = await response.json();
            const tasksList = document.getElementById('assignedTasksList');
            
            if (data.success && tasksList) {
                if (data.data.count === 0) {
                    tasksList.innerHTML = '<li><a class="dropdown-item text-muted small py-2">✓ No tasks assigned to you</a></li>';
                } else {
                    let html = '';
                    data.data.recent.forEach(task => {
                        const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date';
                        const assignedBy = task.assigned_by_first || task.assigned_by_last ? 
                            `${task.assigned_by_first || ''} ${task.assigned_by_last || ''}`.trim() : 'Admin';
                        
                        let priorityClass = '';
                        if (task.priority === 'high') priorityClass = 'text-danger';
                        else if (task.priority === 'medium') priorityClass = 'text-warning';
                        
                        html += `<li>
                                    <a class="dropdown-item small py-2" href="#" onclick="showTaskDetailsModal(${task.id}); return false;" style="cursor: pointer;">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-arrow-right ${priorityClass}" style="font-size: 0.8rem; margin-top: 2px; flex-shrink: 0;"></i>
                                            <div style="overflow: hidden; flex: 1;">
                                                <div class="text-dark fw-500" style="font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(task.title)}</div>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">From: ${escapeHtml(assignedBy)}</small>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">Due: ${dueDate}</small>
                                            </div>
                                        </div>
                                    </a>
                                </li>`;
                    });
                    tasksList.innerHTML = html;
                }
            }
        } catch (error) {
            console.error('Error fetching assigned tasks list:', error);
        }
    }

    // Initialize Bootstrap tooltips (prevent multiple instances)
    function initTooltips() {
        const habitBtn = document.getElementById('habitDropdownBtn');
        const remindersBtn = document.getElementById('remindersDropdownBtn');
        const assignedTasksBtn = document.getElementById('assignedTasksDropdownBtn');
        
        if (typeof bootstrap !== 'undefined') {
            // Initialize habits button
            if (habitBtn) {
                const existingTooltip = bootstrap.Tooltip.getInstance(habitBtn);
                if (!existingTooltip) {
                    new bootstrap.Tooltip(habitBtn);
                }
            }
            
            // Initialize reminders button
            if (remindersBtn) {
                const existingTooltip = bootstrap.Tooltip.getInstance(remindersBtn);
                if (!existingTooltip) {
                    new bootstrap.Tooltip(remindersBtn);
                }
            }

            // Initialize assigned tasks button
            if (assignedTasksBtn) {
                const existingTooltip = bootstrap.Tooltip.getInstance(assignedTasksBtn);
                if (!existingTooltip) {
                    new bootstrap.Tooltip(assignedTasksBtn);
                }
            }
        }
    }

    // Update all notifications on page load
    document.addEventListener('DOMContentLoaded', () => {
        updateRemindersBadge();
        updateRemindersList();
        updateHabitBadge();
        updateHabitsList();
        updateAssignedTasksBadge();
        updateAssignedTasksList();
        initTooltips();
    });

    // Listen for custom 'habitUpdated' event (triggered by habits page when checkbox changes)
    document.addEventListener('habitUpdated', () => {
        updateHabitBadge();
        updateHabitsList();
    });

    // Also watch for any checkbox changes on the page
    document.addEventListener('change', (e) => {
        if (e.target.type === 'checkbox' && e.target.closest('[data-habit-id]')) {
            // If a habit checkbox changed, update the badge and list after a short delay
            setTimeout(() => {
                updateHabitBadge();
                updateHabitsList();
            }, 200);
        }
    });

    // Optionally refresh every 30 seconds
    setInterval(() => {
        updateRemindersBadge();
        updateRemindersList();
        updateHabitBadge();
        updateHabitsList();
    }, 30000);

    // Show task details modal from assigned tasks dropdown
    function showTaskDetailsModal(taskId) {
        // Fetch task details from API
        fetch(API_BASE + '/tasks/get-task-details.php?id=' + taskId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const task = data.data;
                    // Populate modal with task details
                    document.getElementById('taskDetailsModalTitle').textContent = task.title || '';
                    document.getElementById('taskDetailsModalId').value = task.id || '';
                    document.getElementById('taskDetailsModalDescription').textContent = task.description || '';
                    document.getElementById('taskDetailsModalPriority').textContent = (task.priority || 'normal').charAt(0).toUpperCase() + (task.priority || 'normal').slice(1);
                    document.getElementById('taskDetailsModalDueDate').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date';
                    document.getElementById('taskDetailsModalAssignedBy').textContent = task.assigned_by_first || task.assigned_by_last ? 
                        `${task.assigned_by_first || ''} ${task.assigned_by_last || ''}`.trim() : 'Admin';
                    
                    // Set priority color
                    const priorityElement = document.getElementById('taskDetailsModalPriority');
                    priorityElement.className = 'fw-bold';
                    if (task.priority === 'high') {
                        priorityElement.classList.add('text-danger');
                    } else if (task.priority === 'medium') {
                        priorityElement.classList.add('text-warning');
                    } else {
                        priorityElement.classList.add('text-secondary');
                    }
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
                    modal.show();
                } else {
                    alert('Error loading task details');
                }
            })
            .catch(error => {
                console.error('Error fetching task details:', error);
                alert('Error loading task details');
            });
    }

    // Reset all habit/routine checkboxes (called on Refresh or at midnight)
    function resetHabitsCheckboxes() {
        const habitCheckboxes = document.querySelectorAll('[data-habit-id] input[type="checkbox"]');
        habitCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        // Refresh the habits list in header
        if (typeof updateHabitBadge === 'function') {
            updateHabitBadge();
            updateHabitsList();
        }
    }
</script>
