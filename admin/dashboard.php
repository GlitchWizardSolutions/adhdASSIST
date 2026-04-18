<?php
/**
 * ADHD Dashboard - Admin Dashboard
 * Non-intrusive family/system management interface
 * Requires Admin or Developer role
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

// Check for Admin or Developer role
$userRole = $user['role'] ?? 'user';
if (!in_array($userRole, ['admin', 'developer'])) {
    // Not authorized for admin dashboard
    header('Location: ' . Config::redirectUrl('/views/dashboard.php'));
    exit;
}

// Get user's theme preference for sidebar color
$userTheme = $user['theme'] ?? 'light';
$pageTitle = 'Admin Dashboard';

// Get avatar URL or use generic placeholder
$avatar_url = (!empty($user['avatar_url'])) ? htmlspecialchars($user['avatar_url']) : null;
$user_display_name = htmlspecialchars(($user['username'] ?? '') ?: ($user['first_name'] ?? $user['email']));

// Generate initials for fallback avatar
$user_initials = '';
if ($user['first_name']) {
    $user_initials = strtoupper(substr($user['first_name'], 0, 1));
    if ($user['last_name']) {
        $user_initials .= strtoupper(substr($user['last_name'], 0, 1));
    }
} else {
    $user_initials = strtoupper(substr($user['email'], 0, 2));
}

// Determine current active section from query parameter
$section = $_GET['section'] ?? 'overview';
$validSections = ['overview', 'users', 'tasks', 'crud', 'configuration'];
if (!in_array($section, $validSections)) {
    $section = 'overview';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ADHD Dashboard Administration">
    <title><?php echo htmlspecialchars($pageTitle); ?> - ADHD Dashboard</title>
    
    <!-- Bootstrap 5.3.8 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- ADHD Custom Theme -->
    <link href="<?php echo Config::url('css'); ?>adhd-theme.css" rel="stylesheet">
    <link href="<?php echo Config::url('css'); ?>adhd-dashboard.css" rel="stylesheet">
    
    <!-- Admin Dashboard CSS -->
    <link href="<?php echo Config::url('css'); ?>admin.css" rel="stylesheet">
    
    <!-- Set theme data attribute -->
    <style>
        :root {
            --admin-theme: <?php echo htmlspecialchars($userTheme); ?>;
        }
    </style>
    <script>
        // Apply theme immediately to prevent flash
        document.documentElement.setAttribute('data-theme', '<?php echo htmlspecialchars($userTheme); ?>');
        // Set current user role for unified modal
        window._currentUserRole = '<?php echo htmlspecialchars($userRole); ?>';
    </script>
    
    <!-- Unified Task Modal -->
    <script src="<?php echo Config::url('js'); ?>unified-task-modal.js"></script>
</head>
<body data-admin-theme="<?php echo htmlspecialchars($userTheme); ?>">
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <aside id="admin-sidebar" class="admin-sidebar collapsed" role="navigation" aria-label="Admin navigation">
            <div class="admin-sidebar-header">
                <button id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="false" title="Toggle sidebar menu">
                    <i class="bi bi-list"></i>
                </button>
                <span class="sidebar-brand">Admin</span>
            </div>

            <nav class="admin-nav">
                <!-- Overview Section -->
                <div class="nav-section">
                    <a href="?section=overview" class="nav-item <?php echo $section === 'overview' ? 'active' : ''; ?>" 
                       aria-current="<?php echo $section === 'overview' ? 'page' : 'false'; ?>">
                        <i class="nav-icon bi bi-speedometer2" aria-hidden="true"></i>
                        <span class="nav-label">Overview</span>
                    </a>
                </div>

                <!-- Users Section -->
                <div class="nav-section">
                    <button class="nav-menu" aria-expanded="false" aria-controls="users-submenu">
                        <i class="nav-icon bi bi-people-fill" aria-hidden="true"></i>
                        <span class="nav-label">Users</span>
                        <i class="submenu-toggle bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div id="users-submenu" class="nav-submenu" hidden>
                        <a href="?section=users" class="nav-subitem" aria-current="<?php echo $section === 'users' ? 'page' : 'false'; ?>">
                            <i class="bi bi-list-ul" aria-hidden="true"></i>
                            <span>User List</span>
                        </a>
                        <a href="?section=users" class="nav-subitem" onclick="openInviteModal(); return false;">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            <span>Invite User</span>
                        </a>
                    </div>
                </div>

                <!-- Delegated Tasks Section -->
                <div class="nav-section">
                    <a href="?section=tasks" class="nav-item <?php echo $section === 'tasks' ? 'active' : ''; ?>" 
                       aria-current="<?php echo $section === 'tasks' ? 'page' : 'false'; ?>">
                        <i class="nav-icon bi bi-check2-square" aria-hidden="true"></i>
                        <span class="nav-label">Delegated Tasks</span>
                    </a>
                </div>

                <!-- CRUD Templates Section -->
                <div class="nav-section">
                    <button class="nav-menu" aria-expanded="false" aria-controls="crud-submenu">
                        <i class="nav-icon bi bi-table" aria-hidden="true"></i>
                        <span class="nav-label">CRUD Templates</span>
                        <i class="submenu-toggle bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div id="crud-submenu" class="nav-submenu" hidden>
                        <a href="?section=crud" class="nav-subitem" aria-current="<?php echo $section === 'crud' ? 'page' : 'false'; ?>">
                            <i class="bi bi-list-ul" aria-hidden="true"></i>
                            <span>Manage Templates</span>
                        </a>
                        <a href="?section=crud" class="nav-subitem">
                            <i class="bi bi-shield-lock" aria-hidden="true"></i>
                            <span>User Permissions</span>
                        </a>
                    </div>
                </div>

                <!-- Configuration Section -->
                <div class="nav-section">
                    <a href="?section=configuration" class="nav-item <?php echo $section === 'configuration' ? 'active' : ''; ?>" 
                       aria-current="<?php echo $section === 'configuration' ? 'page' : 'false'; ?>">
                        <i class="nav-icon bi bi-gear" aria-hidden="true"></i>
                        <span class="nav-label">Configuration</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main id="main-content" class="admin-main" role="main" aria-label="Admin dashboard content">
            <!-- Header - Bootstrap Navbar matching user dashboard -->
            <header class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm" role="banner">
                <div class="container-fluid px-4" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Left side - empty for consistency -->
                    <div style="flex: 1;"></div>
                    
                    <!-- Right side - Admin Notifications & Profile -->
                    <ul class="navbar-nav ms-auto align-items-center" style="margin: 0; display: flex; gap: 1rem;">
                        <!-- Pending Invites Bell -->
                        <li class="nav-item">
                            <button 
                                class="btn btn-link nav-link" 
                                id="invitesNotifBtn"
                                title="Pending user invitations"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom"
                                style="position: relative; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; padding: 0.5rem 0.75rem; border: none; background: none; color: inherit;"
                                onclick="toggleAdminNotif(event, 'invites')">
                                <i class="bi bi-bell" style="font-size: 1.3rem;"></i>
                                <span id="invites-badge" class="badge bg-warning text-dark" style="position: absolute; top: -8px; right: -8px; min-width: 24px; height: 24px; display: none; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">0</span>
                            </button>
                            <!-- Pending Invites Dropdown -->
                            <div id="invites-dropdown" class="dropdown-menu dropdown-menu-end" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ddd; border-radius: 8px; width: 280px; max-height: 400px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; margin-top: 0.5rem; min-width: 280px;">
                                <div style="padding: 1rem; text-align: center; color: #666;">
                                    <small>Loading invitations...</small>
                                </div>
                            </div>
                        </li>

                        <!-- Completed Tasks Bell -->
                        <li class="nav-item">
                            <button 
                                class="btn btn-link nav-link" 
                                id="tasksNotifBtn"
                                title="Completed delegated tasks"
                                data-bs-toggle="tooltip"
                                data-bs-placement="bottom"
                                style="position: relative; display: flex; align-items: center; gap: 0.5rem; text-decoration: none; padding: 0.5rem 0.75rem; border: none; background: none; color: inherit;"
                                onclick="toggleAdminNotif(event, 'tasks')">
                                <i class="bi bi-check-circle" style="font-size: 1.3rem;"></i>
                                <span id="tasks-badge" class="badge bg-success text-white" style="position: absolute; top: -8px; right: -8px; min-width: 24px; height: 24px; display: none; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700;">0</span>
                            </button>
                            <!-- Completed Tasks Dropdown -->
                            <div id="tasks-dropdown" class="dropdown-menu dropdown-menu-end" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ddd; border-radius: 8px; width: 280px; max-height: 400px; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; margin-top: 0.5rem; min-width: 280px;">
                                <div style="padding: 1rem; text-align: center; color: #666;">
                                    <small>Loading completed tasks...</small>
                                </div>
                            </div>
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
                                <!-- User Dashboard (at top) -->
                                <li>
                                    <a class="dropdown-item" href="<?php echo Config::redirectUrl('/views/dashboard.php'); ?>">
                                        <i class="bi bi-house me-2"></i> User Dashboard
                                    </a>
                                </li>

                                <li><hr class="dropdown-divider"></li>

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
            </header>

            <!-- Content Container -->
            <div class="admin-content">
                <?php
                // Load appropriate section
                $sectionFile = __DIR__ . '/sections/' . $section . '.php';
                if (file_exists($sectionFile)) {
                    include $sectionFile;
                } else {
                    // Default to overview
                    include __DIR__ . '/sections/overview.php';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Global Invite Modal (available from any section) -->
    <div id="inviteModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
      <div class="modal-content" style="background: white; border-radius: 8px; padding: 2rem; max-width: 400px; margin: auto; margin-top: 10vh;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <h3 style="margin: 0;">Invite User</h3>
          <button onclick="closeInviteModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        
        <form id="inviteForm" onsubmit="handleInvite(event)">
          <div style="margin-bottom: 1rem;">
            <label for="inviteEmail" style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Email Address</label>
            <input type="email" id="inviteEmail" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
          </div>
          
          <div style="margin-bottom: 1rem;">
            <label for="inviteName" style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Full Name (Optional)</label>
            <input type="text" id="inviteName" name="full_name" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
          </div>
          
          <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
            <button type="button" onclick="closeInviteModal()" class="btn btn-light">Cancel</button>
            <button type="submit" class="btn btn-primary">Send Invitation</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bootstrap 5.3.8 Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin Dashboard JS -->
    <script src="<?php echo Config::url('js'); ?>admin.js"></script>

    <script>
        // Admin Notification Bell Toggle
        function toggleAdminNotif(event, type) {
            event.stopPropagation();
            
            const dropdown = document.getElementById(type + '-dropdown');
            const button = type === 'invites' ? document.getElementById('invitesNotifBtn') : document.getElementById('tasksNotifBtn');
            const isOpen = dropdown.style.display === 'block';
            
            // Close all other dropdowns first
            document.getElementById('invites-dropdown').style.display = 'none';
            document.getElementById('tasks-dropdown').style.display = 'none';
            
            dropdown.style.display = isOpen ? 'none' : 'block';
            
            // Load data if opening
            if (!isOpen) {
                if (type === 'invites') {
                    loadPendingInvites();
                } else if (type === 'tasks') {
                    loadCompletedTasks();
                }
                
                // Close if clicking outside
                document.addEventListener('click', function closeNotifMenu(e) {
                    if (!e.target.closest('li.nav-item:has(#' + type + '-dropdown)')) {
                        dropdown.style.display = 'none';
                        document.removeEventListener('click', closeNotifMenu);
                    }
                });
            }
        }

        // Load Pending Invitations
        async function loadPendingInvites() {
            const dropdown = document.getElementById('invites-dropdown');
            const badge = document.getElementById('invites-badge');
            
            try {
                const response = await fetch('<?php echo Config::url('api'); ?>admin/users-invitations.php?status=pending');
                const data = await response.json();
                
                const invitations = data.data || [];
                
                // Update badge count
                if (invitations.length > 0) {
                    badge.textContent = invitations.length;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
                
                // Render invitation list
                if (invitations.length === 0) {
                    dropdown.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: #999;"><p style="margin: 0;">No pending invitations</p></div><div style="padding: 0.75rem 1.25rem; border-top: 1px solid #f0f0f0;"><a href="?section=users" style="color: #667eea; text-decoration: none; font-size: 0.9rem;">View All Users</a></div>';
                } else {
                    let html = '<div style="padding: 0.75rem 0;">';
                    invitations.forEach(inv => {
                        const date = new Date(inv.created_at).toLocaleDateString();
                        html += `
                            <div style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #f0f0f0;">
                                <div style="font-weight: 500; font-size: 0.9rem;">${escapeHtml(inv.email)}</div>
                                <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">Sent: ${date}</div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button onclick="resendInvitationFromNotif(${inv.id})" style="flex: 1; padding: 0.4rem; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Resend</button>
                                    <button onclick="cancelInvitationFromNotif(${inv.id})" style="flex: 1; padding: 0.4rem; background: #f0f0f0; color: #d32f2f; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Cancel</button>
                                </div>
                            </div>
                        `;
                    });
                    html += '<div style="padding: 0.75rem 1.25rem; border-top: 1px solid #f0f0f0;"><a href="?section=users" style="color: #667eea; text-decoration: none; font-size: 0.9rem;">View All Users</a></div>';
                    html += '</div>';
                    dropdown.innerHTML = html;
                }
            } catch (error) {
                console.error('Error loading pending invitations:', error);
                // Show graceful error state instead of error message
                dropdown.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: #999;"><p style="margin: 0;">No pending invitations</p></div><div style="padding: 0.75rem 1.25rem; border-top: 1px solid #f0f0f0;"><a href="?section=users" style="color: #667eea; text-decoration: none; font-size: 0.9rem;">View All Users</a></div>';
                badge.style.display = 'none';
            }
        }

        // Load Completed Delegated Tasks
        async function loadCompletedTasks() {
            const dropdown = document.getElementById('tasks-dropdown');
            const badge = document.getElementById('tasks-badge');
            
            try {
                const response = await fetch('<?php echo Config::url('api'); ?>admin/delegated-tasks-completed.php');
                const data = await response.json();
                
                const tasks = data.data || [];
                
                // Update badge count
                if (tasks.length > 0) {
                    badge.textContent = tasks.length;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
                
                // Render task list
                if (tasks.length === 0) {
                    dropdown.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: #999;"><p style="margin: 0;">No new completed delegated tasks</p></div><div style="padding: 0.75rem 1.25rem; border-top: 1px solid #f0f0f0;"><a href="?section=tasks" style="color: #667eea; text-decoration: none; font-size: 0.9rem;">View All Tasks</a></div>';
                } else {
                    let html = '<div style="padding: 0.75rem 0;">';
                    tasks.forEach(task => {
                        const date = new Date(task.completed_at).toLocaleDateString();
                        html += `
                            <div style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #f0f0f0;">
                                <div style="font-weight: 500; font-size: 0.9rem;">${escapeHtml(task.title)}</div>
                                <div style="font-size: 0.85rem; color: #666;">By: ${escapeHtml(task.user_name)}</div>
                                <div style="font-size: 0.85rem; color: #999;">Completed: ${date}</div>
                            </div>
                        `;
                    });
                    html += '<div style="padding: 0.75rem 1.25rem; border-top: 1px solid #f0f0f0;"><a href="?section=tasks" style="color: #667eea; text-decoration: none; font-size: 0.9rem;">View All Tasks</a></div>';
                    html += '</div>';
                    dropdown.innerHTML = html;
                }
            } catch (error) {
                console.error('Error loading completed tasks:', error);
                // Show graceful error state instead of error message
                dropdown.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: #999;"><p style="margin: 0;">No new completed delegated tasks</p></div><div style="padding: 0.75rem 1.25rem; border-top: 1px solid #f0f0f0;"><a href="?section=tasks" style="color: #667eea; text-decoration: none; font-size: 0.9rem;">View All Tasks</a></div>';
                badge.style.display = 'none';
            }
        }

        // Resend invitation from notification bell
        function resendInvitationFromNotif(invitationId) {
            fetch('<?php echo Config::url('api'); ?>admin/users-resend-invitation.php', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ invitation_id: invitationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload invitations
                    loadPendingInvites();
                } else {
                    console.error('Error:', data.error || 'Failed to resend invitation');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Cancel invitation from notification bell
        function cancelInvitationFromNotif(invitationId) {
            if (confirm('Cancel this invitation?')) {
                fetch('<?php echo Config::url('api'); ?>admin/users-cancel-invitation.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ invitation_id: invitationId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload invitations
                        loadPendingInvites();
                    } else {
                        alert('Error: ' + (data.error || 'Failed to cancel invitation'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to cancel invitation');
                });
            }
        }

        // Utility function to escape HTML
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Load admin notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips for notification bells
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            loadPendingInvites();
            loadCompletedTasks();
            
            // Refresh notifications every 2 minutes
            setInterval(() => {
                if (document.getElementById('invites-dropdown').style.display === 'block') {
                    loadPendingInvites();
                }
                if (document.getElementById('tasks-dropdown').style.display === 'block') {
                    loadCompletedTasks();
                }
            }, 120000);
        });
    </script>

    <!-- Scroll to Top Component -->
    <script src="<?php echo Config::url('js'); ?>scroll-to-top.js"></script>
</body>
</html>
