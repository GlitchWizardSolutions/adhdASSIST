<?php
/**
 * ADHD Dashboard - Accept Invitation & Register
 * Allows invited users to create their account using invitation token
 */

session_start();
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/database.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . Config::url('base') . 'views/dashboard.php');
    exit;
}

// Get token from URL
$token = $_GET['token'] ?? null;
$invitation = null;
$error = null;

if (!$token) {
    $error = 'Invalid invitation link. No token provided.';
} else {
    try {
        $pdo = db();
        
        // Look up invitation by token
        $stmt = $pdo->prepare('
            SELECT id, email, full_name, expires_at, accepted_at, cancelled_at
            FROM invitations
            WHERE token = ?
        ');
        $stmt->execute([$token]);
        $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Validate invitation
        if (!$invitation) {
            $error = 'Invalid or expired invitation link.';
        } elseif ($invitation['cancelled_at']) {
            // Format the cancellation date nicely
            $cancelledDate = date('F d, Y', strtotime($invitation['cancelled_at']));
            $error = "This invitation was cancelled on {$cancelledDate}. Please contact your administrator to request a new invitation.";
        } elseif ($invitation['accepted_at']) {
            $error = 'This invitation has already been used.';
        } elseif (strtotime($invitation['expires_at']) < time()) {
            $error = 'This invitation has expired. Please request a new invitation.';
        }
        
        // Check if email already has an active account
        if ($invitation && !$error) {
            $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1');
            $userStmt->execute([$invitation['email']]);
            if ($userStmt->fetch()) {
                $error = 'An active user account already exists for this email address.';
            }
        }
    } catch (Exception $e) {
        error_log("Accept Invite Error: " . $e->getMessage());
        $error = 'An error occurred. Please try again later.';
    }
}

// Handle registration submission
$registrationSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $invitation) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    
    // Validate inputs
    if (!$password) {
        $error = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!$firstName) {
        $error = 'First name is required.';
    } else {
        // Register user
        $registerResult = Auth::register($invitation['email'], $password, $firstName, $lastName);
        
        if ($registerResult['success']) {
            // Mark invitation as accepted
            try {
                $pdo = db();
                $updateStmt = $pdo->prepare('
                    UPDATE invitations
                    SET accepted_at = NOW()
                    WHERE token = ?
                ');
                $updateStmt->execute([$token]);
                
                $registrationSuccess = true;
                // Set email in session for login page redirect
                $_SESSION['invite_email'] = $invitation['email'];
            } catch (Exception $e) {
                error_log("Accept Invite - Mark Accepted Error: " . $e->getMessage());
                // Registration succeeded but marking failed, proceed anyway
                $registrationSuccess = true;
                $_SESSION['invite_email'] = $invitation['email'];
            }
        } else {
            $error = $registerResult['error'] ?? 'Registration failed.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error ? 'Invalid Invitation' : 'Complete Your Registration'; ?> - ADHD Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo Config::url('css'); ?>adhd-theme.css" rel="stylesheet">
    <link href="<?php echo Config::url('css'); ?>adhd-dashboard.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito Sans', sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
            padding: 2rem;
        }
        .form-container h1 {
            color: #2D3A4E;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        .form-container .subtitle {
            color: #8A95A3;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #2D3A4E;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: 'Nunito Sans', sans-serif;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-group input[type="email"] {
            background-color: #f0f4f8;
            cursor: not-allowed;
        }
        .btn-submit {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            width: 100%;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.3);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .success-message {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .success-message h2 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: #065f46;
        }
        .error-message {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #7f1d1d;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-row .form-group {
            margin-bottom: 0;
        }
        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <?php if ($registrationSuccess): ?>
            <!-- Success Message -->
            <div class="success-message">
                <h2>✓ Account Created Successfully!</h2>
                <p>Your account has been created and is ready to use.</p>
            </div>
            <p style="color: #666; margin-bottom: 1.5rem;">Welcome to the ADHD Dashboard family! You can now log in with your email and password.</p>
            <a href="views/login.php?email=<?php echo urlencode($invitation['email']); ?>" class="btn btn-submit" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); text-decoration: none; display: flex; align-items: center; justify-content: center;">
                Continue to Login
            </a>

        <?php elseif ($error): ?>
            <!-- Error Message -->
            <div class="error-message">
                <strong>⚠ Unable to Process Invitation</strong>
                <p style="margin-bottom: 0; margin-top: 0.5rem;"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <p style="color: #666; margin-bottom: 1.5rem;">
                If you believe this is an error, please contact your system administrator to resend your invitation.
            </p>
            <a href="views/login.php" class="btn btn-submit" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); text-decoration: none; display: flex; align-items: center; justify-content: center;">
                Back to Login
            </a>

        <?php elseif ($invitation): ?>
            <!-- Registration Form -->
            <h1>Complete Your Registration</h1>
            <p class="subtitle">You've been invited to join ADHD Dashboard. Create your account below.</p>

            <form method="POST">
                <!-- Email (read-only) -->
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($invitation['email']); ?>" readonly>
                </div>

                <!-- First & Last Name Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? (isset($invitation['full_name']) && $invitation['full_name'] ? explode(' ', $invitation['full_name'])[0] : '')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? (isset($invitation['full_name']) && $invitation['full_name'] ? (count(explode(' ', $invitation['full_name'])) > 1 ? implode(' ', array_slice(explode(' ', $invitation['full_name']), 1)) : '') : '')); ?>">
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required placeholder="Minimum 8 characters">
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit">Create Account</button>
            </form>

            <div class="back-link">
                <a href="views/login.php">Already have an account? Log in</a>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
