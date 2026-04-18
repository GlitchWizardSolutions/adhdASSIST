<?php
/**
 * ADHD Dashboard - Forgot Password Page
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
    <title>Forgot Password - ADHD Dashboard</title>
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
            margin-bottom: 0.5rem;
            color: #2D3A4E;
            font-size: 1.5rem;
        }
        .card-reset p {
            text-align: center;
            color: #8A95A3;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
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
        .alert {
            border-radius: 8px;
        }
        .spinner {
            display: none;
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
        <h1>🔒 Reset Password</h1>
        <p>Enter your email address and we'll send you a link to reset your password</p>

        <div id="alert-container"></div>

        <form id="forgot-form" aria-label="Forgot password form">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    required
                    placeholder="your@email.com"
                    aria-label="Email address"
                    aria-describedby="email-help"
                >
                <small id="email-help" class="form-text text-muted">Enter the email address associated with your account.</small>
            </div>

            <button type="submit" class="btn btn-primary w-100" aria-label="Send password reset link">
                <span class="spinner me-2" role="status" aria-hidden="true">
                    <span class="spinner-border spinner-border-sm"></span>
                </span>
                <span class="button-text">Send Reset Link</span>
            </button>
        </form>

        <div class="form-link">
            Remember your password? <a href="login.php" aria-label="Go to login page">Login here</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('forgot-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const spinner = document.querySelector('.spinner');
            const buttonText = document.querySelector('.button-text');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const alertContainer = document.getElementById('alert-container');

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            buttonText.textContent = 'Sending...';

            try {
                const formData = new FormData();
                formData.append('email', email);

                const response = await fetch('<?php echo Config::url('api') . 'auth/forgot-password.php'; ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Check if email was found in system
                    if (data.email_found === false) {
                        // Email not found - show warning but leave form filled
                        alertContainer.innerHTML = '<div class="alert alert-warning" role="alert">' +
                            '<strong>Email Not Found:</strong> ' + data.message +
                            '</div>';
                    } else {
                        // Email found and reset link sent
                        alertContainer.innerHTML = '<div class="alert alert-success" role="alert">' +
                            '<strong>Success!</strong> ' + data.message +
                            '</div>';
                        document.getElementById('forgot-form').reset();
                    }
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
                buttonText.textContent = 'Send Reset Link';
            }
        });
    </script>
</body>
</html>
