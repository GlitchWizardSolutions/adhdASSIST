<?php
/**
 * ADHD Dashboard - Login Page
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';

// DEBUG: Check if we're already authenticated
$isAuth = Auth::isAuthenticated();
error_log("[Login.php] Authenticated: " . ($isAuth ? 'YES' : 'NO'));

// Only redirect to dashboard if logged IN AND not already on login page
// Prevent redirect loops
if ($isAuth) {
    error_log("[Login.php] Redirecting authenticated user to dashboard");
    header('Location: ' . Config::redirectUrl('/views/dashboard.php'));
    exit;
}

// If we reach here, user is NOT authenticated - show login form
error_log("[Login.php] Showing login form for unauthenticated user");

$error = '';
$prefillEmail = '';

// Get email from session (set by accept-invite.php) or URL parameter
if (isset($_SESSION['invite_email'])) {
    $prefillEmail = $_SESSION['invite_email'];
    unset($_SESSION['invite_email']); // Clear after using
} elseif (isset($_GET['email'])) {
    $prefillEmail = $_GET['email'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $result = Auth::login($email, $password);
        if ($result['success']) {
            header('Location: ' . Config::redirectUrl('/views/dashboard.php'));
            exit;
        }
        $error = $result['error'] ?? 'Login failed';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ADHD Dashboard</title>
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
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }
        .login-card h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2D3A4E;
            font-size: 1.8rem;
        }
        .btn-primary {
            background-color: #FFB300;
            border: none;
            color: #2D3A4E;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #E6A100;
            color: #2D3A4E;
        }
        .form-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.95rem;
        }
        .form-link a {
            color: #FFB300;
            text-decoration: none;
            font-weight: 600;
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

    <div id="main-content" class="login-card">
        <h1>👋 Welcome Back</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate aria-label="Login form">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    required
                    autocomplete="email"
                    placeholder="your@email.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? $prefillEmail); ?>"
                    aria-label="Email address"
                    aria-describedby="email-help"
                >
                <small id="email-help" class="form-text text-muted">Enter the email address associated with your account.</small>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    aria-label="Password"
                    aria-describedby="password-help"
                >
                <small id="password-help" class="form-text text-muted">Enter your password to log in.</small>
            </div>

            <button type="submit" class="btn btn-primary w-100" aria-label="Submit login form">
                Login
            </button>
        </form>

        <div class="form-link">
            <a href="forgot-password.php" aria-label="Go to forgot password page">Forgot your password?</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
