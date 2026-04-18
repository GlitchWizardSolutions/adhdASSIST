<?php
/**
 * ADHD Dashboard - Admin Dashboard
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/database.php';

// Check if user is authenticated
if (!Auth::isAuthenticated()) {
    header('Location: ' . Config::redirectUrl('/views/login.php'));
    exit;
}

$user = Auth::getCurrentUser();

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('Location: ' . Config::redirectUrl('/views/dashboard.php'));
    exit;
}

try {
    $pdo = db();
    
    // Get dashboard stats
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE is_active = 1');
    $stmt->execute();
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users');
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM tasks');
    $stmt->execute();
    $total_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM tasks WHERE status = \"completed\"');
    $stmt->execute();
    $completed_tasks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get recent users
    $stmt = $pdo->prepare('
        SELECT id, email, first_name, last_name, created_at, last_login, role, is_active
        FROM users
        ORDER BY created_at DESC
        LIMIT 10
    ');
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get system stats
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM daily_habits');
    $stmt->execute();
    $total_habits = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo Config::url('css'); ?>adhd-theme.css" rel="stylesheet">
    
    <!-- Minified CSS Bundle (Production) -->
    <link href="<?php echo Config::url('base'); ?>dist/dashboard.min.css" rel="stylesheet">
    
    <!-- Unified Task Modal Script (loaded early) -->
    <script src="<?php echo Config::url('js'); ?>unified-task-modal.js"></script>
    <style>
        .admin-header {
            background: linear-gradient(135deg, #FFB300 0%, #FF9F43 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 8px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #E0E4E8;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            color: #FFB300;
            margin: 0;
            font-weight: 700;
        }
        .stat-card p {
            color: #8A95A3;
            margin: 0.25rem 0 0 0;
            font-size: 0.9rem;
        }
        .table-responsive {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        table {
            margin-bottom: 0;
        }
        th {
            background-color: #F9FAFB;
            border-top: none;
            font-weight: 600;
            color: #2D3A4E;
        }
        td {
            vertical-align: middle;
            padding: 1rem;
        }
        .badge {
            font-size: 0.8rem;
        }
        .user-email {
            font-family: monospace;
            font-size: 0.9rem;
            color: #8A95A3;
        }
    </style>
</head>
<body>
    <a href="#main-content" class="btn btn-primary skip-link" tabindex="1">Skip to main content</a>

    <?php require 'header.php'; ?>

    <main id="main-content" class="container-fluid mt-4">
        <div class="admin-header">
            <h1><i class="bi bi-shield-check me-2"></i>Admin Dashboard</h1>
            <p class="mb-0">System statistics and user management</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo $active_users; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo $total_tasks; ?></h3>
                    <p>Total Tasks</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo $completed_tasks; ?></h3>
                    <p>Completed Tasks</p>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <h2 class="mb-3">Recent Users</h2>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td><code class="user-email"><?php echo htmlspecialchars($u['email']); ?></code></td>
                            <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo ucfirst($u['role']); ?></span>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo date('M d, Y', strtotime($u['created_at'])); ?></small></td>
                            <td>
                                <?php if ($u['last_login']): ?>
                                    <small><?php echo date('M d, Y H:i', strtotime($u['last_login'])); ?></small>
                                <?php else: ?>
                                    <small class="text-muted">Never</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" data-user-id="<?php echo $u['id']; ?>" disabled>
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-info mt-4" role="alert">
            <strong>Note:</strong> User management features coming soon. Currently this dashboard displays read-only statistics.
        </div>
    </main>

    <a href="#top" class="btn btn-primary scroll-to-top" id="scrollToTop" role="button" aria-label="Scroll to top">
        <i class="bi bi-arrow-up"></i>
    </a>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Utility Modules (Load first, before API wrappers and admin) -->
    <script src="<?php echo Config::url('js'); ?>utils/api-helper.js"></script>
    <script src="<?php echo Config::url('js'); ?>utils/dom-helper.js"></script>
    <script src="<?php echo Config::url('js'); ?>utils/theme-manager.js"></script>
    <script src="<?php echo Config::url('js'); ?>utils/notification-handler.js"></script>
    <script src="<?php echo Config::url('js'); ?>utils/form-validator.js"></script>
    <script src="<?php echo Config::url('js'); ?>utils/preferences.js"></script>
    <script src="<?php echo Config::url('js'); ?>components/modal-manager.js"></script>
    
    <!-- API Wrappers (Load after utilities) -->
    <script src="<?php echo Config::url('js'); ?>api/dashboard-api.js"></script>
    <script src="<?php echo Config::url('js'); ?>api/admin-api.js"></script>
    
    <!-- Admin Minified Bundle (Production) -->
    <script src="<?php echo Config::url('base'); ?>dist/admin.min.js"></script>
    <script>\n        // Set current user role for use in modal
        window._currentUserRole = '<?php echo htmlspecialchars($user['role']); ?>';
        
        // Scroll to top button
        const scrollToTopBtn = document.getElementById('scrollToTop');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'block';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        scrollToTopBtn.style.display = 'none';
    </script>
    <style>
        .scroll-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: none;
            z-index: 1000;
            background-color: var(--color-urgent);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            padding: 0;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .scroll-to-top:hover {
            background-color: #E6A100;
            transform: translateY(-2px);
        }
        .skip-link {
            position: absolute;
            top: -40px;
            left: 0;
            background: #FFB300;
            color: white;
            padding: 8px;
            z-index: 100;
        }
        .skip-link:focus {
            top: 0;
        }
    </style>
</body>
</html>
