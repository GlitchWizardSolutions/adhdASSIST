<?php
/**
 * ADHD Dashboard - Reset Password Page
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/database.php';

// If already logged in, redirect to dashboard
if (Auth::isAuthenticated()) {
    header('Location: ' . Config::redirectUrl('/views/dashboard.php'));
    exit;
}

$token = $_GET['token'] ?? '';
$token_valid = false;
$token_error = '';

// Verify token is valid
if (!empty($token)) {
    try {
        $pdo = db();
        $stmt = $pdo->prepare('
            SELECT user_id FROM password_resets 
            WHERE token = ? AND expires_at > NOW()
            LIMIT 1
        ');
        $stmt->execute([$token]);
        $token_valid = (bool)$stmt->fetch();
    } catch (Exception $e) {
        $token_error = 'Token verification failed';
    }
}

if (!$token_valid && empty($token_error)) {
    $token_error = 'Invalid or expired reset link';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ADHD Dashboard</title>
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
        .card-reset {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }
        .card-reset h1 {
            text-align: center;
            margin-bottom: 1rem;
            color: #2D3A4E;
            font-size: 1.5rem;
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
        }
        .form-link a {
            color: #FFB300;
            text-decoration: none;
            font-weight: 500;
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

    <div id="main-content" class="card-reset">
        <h1>🔑 Set New Password</h1>

        <?php if (!$token_valid): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error:</strong> <?php echo htmlspecialchars($token_error); ?>
            </div>
            <div class="form-link">
                <p><a href="forgot-password.php" aria-label="Go to forgot password page">Try requesting another reset link</a></p>
            </div>
        <?php else: ?>
            <div id="alert-container"></div>

            <form id="reset-form" aria-label="Reset password form">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        required
                        minlength="8"
                        placeholder="••••••••"
                        aria-label="New password"
                        aria-describedby="password-help"
                    >
                    <small id="password-help" class="text-muted">At least 8 characters</small>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        minlength="8"
                        placeholder="••••••••"
                        aria-label="Confirm password"
                        aria-describedby="confirm-help"
                    >
                    <small id="confirm-help" class="text-muted">Re-enter your new password</small>
                </div>

                <button type="submit" class="btn btn-primary w-100" aria-label="Reset password">
                    <span class="spinner me-2" role="status" aria-hidden="true" style="display: none;">
                        <span class="spinner-border spinner-border-sm"></span>
                    </span>
                    <span class="button-text">Reset Password</span>
                </button>
            </form>

            <div class="form-link">
                <p><a href="login.php" aria-label="Go back to login">Back to Login</a></p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($token_valid): ?>
        document.getElementById('reset-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const token = document.querySelector('input[name="token"]').value;
            const spinner = e.target.querySelector('.spinner');
            const buttonText = e.target.querySelector('.button-text');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const alertContainer = document.getElementById('alert-container');

            if (password !== confirmPassword) {
                alertContainer.innerHTML = '<div class="alert alert-danger" role="alert">' +
                    '<strong>Error:</strong> Passwords do not match' +
                    '</div>';
                return;
            }

            if (password.length < 8) {
                alertContainer.innerHTML = '<div class="alert alert-danger" role="alert">' +
                    '<strong>Error:</strong> Password must be at least 8 characters' +
                    '</div>';
                return;
            }

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            buttonText.textContent = 'Resetting...';

            try {
                const formData = new FormData();
                formData.append('token', token);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);

                const response = await fetch('<?php echo Config::url('api') . 'auth/reset-password.php'; ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alertContainer.innerHTML = '<div class="alert alert-success" role="alert">' +
                        '<strong>Success!</strong> ' + data.message +
                        '</div>';
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    alertContainer.innerHTML = '<div class="alert alert-danger" role="alert">' +
                        '<strong>Error:</strong> ' + (data.error || 'An error occurred') +
                        '</div>';
                }
            } catch (error) {
                alertContainer.innerHTML = '<div class="alert alert-danger" role="alert">' +
                    '<strong>Error:</strong> ' + error.message +
                    '</div>';
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
                buttonText.textContent = 'Reset Password';
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
