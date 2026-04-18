<?php
/**
 * ADHD Dashboard - Registration Disabled
 * Public registration is closed. New users are provisioned by administrators only.
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';

// If already logged in, redirect to dashboard
if (Auth::isAuthenticated()) {
    header('Location: ' . Config::redirectUrl('/views/dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Closed - ADHD Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo Config::url('css'); ?>adhd-theme.css" rel="stylesheet">
    <link href="<?php echo Config::url('css'); ?>adhd-dashboard.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito Sans', sans-serif;
            background: linear-gradient(135deg, #FFB300 0%, #FF9F43 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            text-align: center;
        }
        .info-card h1 {
            color: #2D3A4E;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .info-card p {
            color: #8A95A3;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #FFB300;
            border: none;
            color: #2D3A4E;
            font-weight: 600;
            margin-top: 1rem;
        }
        .btn-primary:hover {
            background-color: #E6A100;
            color: #2D3A4E;
        }
        /* Accessibility: Skip link style */
        .visually-hidden-focusable:not(:focus) {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
    </style>
</head>
<body>
    <!-- Skip to main content link -->
    <a href="#main-content" class="btn btn-warning visually-hidden-focusable" style="position: fixed; top: 0; left: 0; z-index: 9999; padding: 0.5rem 1rem;">
        <i class="bi bi-skip-forward me-2"></i> Skip to main content
    </a>

    <div id="main-content" class="info-card">
        <h1>🔐 Registration Closed</h1>
        <p>Public registration is currently closed.</p>
        <p>New accounts are provisioned by system administrators only.</p>
        <p>If you believe you should have access, please contact your administrator.</p>
        <a href="login.php" class="btn btn-primary w-100" aria-label="Go back to login">Back to Login</a>
    </div>
</body>
</html>
