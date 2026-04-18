<?php
/**
 * Admin Dashboard Diagnostic Tool
 * Tests database tables, API endpoints, and configuration
 */

session_start();
require_once __DIR__ . '/../lib/config.php';

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;

// Try to connect to database
$dbConnected = false;
$dbError = null;
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbConnected = true;
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// Check for required tables
$tables = [];
if ($dbConnected) {
    $stmt = $pdo->query("SHOW TABLES LIKE 'invitations'");
    $tables['invitations'] = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_crud_permissions'");
    $tables['user_crud_permissions'] = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tables['users'] = $stmt->rowCount() > 0;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard Diagnostic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f5f5f5; }
        .card { margin-bottom: 1.5rem; }
        .check { color: #27AE60; font-weight: bold; }
        .cross { color: #E74C3C; font-weight: bold; }
        .warning { color: #F39C12; font-weight: bold; }
        .code { background: #f0f0f0; padding: 0.5rem 1rem; border-radius: 4px; font-family: monospace; }
        .section-title { margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid #007bff; padding-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="bi bi-tools"></i> Admin Dashboard Diagnostic Tools</h1>
        <p class="text-muted">Verify all components are set up correctly</p>

        <!-- Authentication Status -->
        <div class="section-title">1. Authentication Status</div>
        <div class="card">
            <div class="card-body">
                <?php if ($isAuthenticated): ?>
                    <p><span class="check">✓</span> Authenticated as User ID: <?php echo htmlspecialchars($userId); ?></p>
                <?php else: ?>
                    <p><span class="cross">✗</span> Not authenticated. You must log in first to test the admin dashboard.</p>
                    <p><a href="<?php echo Config::redirectUrl('/views/login.php'); ?>" class="btn btn-primary">Go to Login</a></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Database Connection -->
        <div class="section-title">2. Database Connection</div>
        <div class="card">
            <div class="card-body">
                <?php if ($dbConnected): ?>
                    <p><span class="check">✓</span> Connected to database: <span class="code"><?php echo htmlspecialchars(DB_NAME); ?></span></p>
                    <p><span class="check">✓</span> Host: <span class="code"><?php echo htmlspecialchars(DB_HOST); ?></span></p>
                <?php else: ?>
                    <p><span class="cross">✗</span> Database connection failed:</p>
                    <p class="code"><?php echo htmlspecialchars($dbError); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Required Tables -->
        <div class="section-title">3. Database Tables</div>
        <div class="card">
            <div class="card-body">
                <?php if ($dbConnected): ?>
                    <p>
                        <?php if ($tables['users'] ?? false): ?>
                            <span class="check">✓</span> users
                        <?php else: ?>
                            <span class="cross">✗</span> users (MISSING!)
                        <?php endif; ?>
                    </p>
                    <p>
                        <?php if ($tables['invitations'] ?? false): ?>
                            <span class="check">✓</span> invitations
                        <?php else: ?>
                            <span class="cross">✗</span> invitations (MISSING - Run migration)
                        <?php endif; ?>
                    </p>
                    <p>
                        <?php if ($tables['user_crud_permissions'] ?? false): ?>
                            <span class="check">✓</span> user_crud_permissions
                        <?php else: ?>
                            <span class="cross">✗</span> user_crud_permissions (MISSING - Run migration)
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!($tables['invitations'] ?? false) || !($tables['user_crud_permissions'] ?? false)): ?>
                        <hr>
                        <p class="warning"><strong>⚠ Missing tables detected!</strong></p>
                        <p>Run the migration script to create the missing tables:</p>
                        <p class="code">
                            Get-Content 'C:\xampp\htdocs\adhd-dashboard\database\migration-dev-admin-features.sql' | C:\xampp\mysql\bin\mysql.exe -u root adhd_dashboard
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p><span class="cross">✗</span> Cannot check tables - database not connected</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Configuration -->
        <div class="section-title">4. Configuration</div>
        <div class="card">
            <div class="card-body">
                <p><span class="check">✓</span> APP_SUBDIR: <span class="code"><?php echo htmlspecialchars(APP_SUBDIR); ?></span></p>
                <p><span class="check">✓</span> Environment: <span class="code"><?php echo htmlspecialchars(ENVIRONMENT); ?></span></p>
                <p><span class="check">✓</span> Base URL: <span class="code"><?php echo htmlspecialchars(Config::url('base')); ?></span></p>
                <p><span class="check">✓</span> API URL: <span class="code"><?php echo htmlspecialchars(Config::url('base')); ?>api/</span></p>
            </div>
        </div>

        <!-- Testing API Endpoints -->
        <div class="section-title">5. API Endpoint Tests</div>
        <div class="card">
            <div class="card-body">
                <p class="text-muted">Open browser console (F12) and paste these commands to test API endpoints:</p>
                <pre style="background: #f0f0f0; padding: 1rem; border-radius: 4px; overflow-x: auto;">
// Test stats endpoint
fetch('<?php echo Config::url('base'); ?>api/admin/stats.php')
  .then(r => r.json())
  .then(d => console.log('Stats:', d))
  .catch(e => console.error('Error:', e));

// Test users list endpoint
fetch('<?php echo Config::url('base'); ?>api/admin/users-list.php?status=active')
  .then(r => r.json())
  .then(d => console.log('Users:', d))
  .catch(e => console.error('Error:', e));

// Test CRUD templates endpoint
fetch('<?php echo Config::url('base'); ?>api/admin/crud-list.php?user_id=<?php echo htmlspecialchars($userId ?? '1'); ?>')
  .then(r => r.json())
  .then(d => console.log('Templates:', d))
  .catch(e => console.error('Error:', e));
                </pre>
            </div>
        </div>

        <!-- Testing Admin Dashboard -->
        <div class="section-title">6. Test Admin Dashboard</div>
        <div class="card">
            <div class="card-body">
                <?php if ($isAuthenticated): ?>
                    <p><a href="<?php echo Config::redirectUrl('/admin/dashboard.php?section=overview'); ?>" class="btn btn-primary">
                        <i class="bi bi-speedometer2"></i> Open Admin Dashboard (Overview)
                    </a></p>
                    <p style="margin-top: 1rem; font-size: 0.9rem;" class="text-muted">
                        <strong>What to verify:</strong>
                        <ul>
                            <li>Sidebar toggle button works (hamburger icon)</li>
                            <li>Navigation links work (click sidebar items)</li>
                            <li>Statistics cards load with real data</li>
                            <li>Invite User button opens modal</li>
                            <li>Users page loads and shows users</li>
                            <li>Tasks page shows delegated tasks</li>
                            <li>CRUD page shows permissions matrix</li>
                        </ul>
                    </p>
                <?php else: ?>
                    <p><span class="warning">⚠</span> You must be logged in to access the admin dashboard.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="section-title">7. Troubleshooting</div>
        <div class="card">
            <div class="card-body">
                <h5>If tables are missing:</h5>
                <ol>
                    <li>Run the migration script from step 3 above</li>
                    <li>Refresh this page to verify tables were created</li>
                </ol>

                <h5 style="margin-top: 1rem;">If API endpoints return errors:</h5>
                <ol>
                    <li>Check browser console (F12 → Console tab) for JavaScript errors</li>
                    <li>Check Network tab to see API response status codes</li>
                    <li>Verify you're logged in with admin/developer role</li>
                </ol>

                <h5 style="margin-top: 1rem;">If sidebar doesn't work:</h5>
                <ol>
                    <li>Check if admin.js is loading (Network tab)</li>
                    <li>Check browser console for JavaScript errors</li>
                    <li>Try refreshing the page</li>
                </ol>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
