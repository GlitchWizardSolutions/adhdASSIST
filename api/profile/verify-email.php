<?php
/**
 * Verify Email Change
 * GET /api/profile/verify-email.php?token=...
 * Confirms email change when user clicks the verification link
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';

$token = $_GET['token'] ?? '';
$success = false;
$message = '';

if (!empty($token)) {
    try {
        $pdo = db();
        
        // Find valid verification token
        $stmt = $pdo->prepare('
            SELECT user_id, new_email FROM email_verifications 
            WHERE token = ? AND expires_at > NOW() AND is_verified = 0
            LIMIT 1
        ');
        $stmt->execute([$token]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($verification) {
            $user_id = $verification['user_id'];
            $new_email = $verification['new_email'];

            // Begin transaction
            $pdo->beginTransaction();

            try {
                // Update user email
                $stmt = $pdo->prepare('
                    UPDATE users 
                    SET email = ?, updated_at = NOW()
                    WHERE id = ?
                ');
                $stmt->execute([$new_email, $user_id]);

                // Mark verification as complete
                $stmt = $pdo->prepare('
                    UPDATE email_verifications 
                    SET is_verified = 1, verified_at = NOW()
                    WHERE token = ?
                ');
                $stmt->execute([$token]);

                $pdo->commit();
                $success = true;
                $message = 'Email address successfully updated to ' . htmlspecialchars($new_email) . '. You will be redirected to the dashboard.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Failed to update email. Please contact support.';
            }
        } else {
            $message = 'Invalid or expired verification link.';
        }
    } catch (Exception $e) {
        $message = 'An error occurred: ' . $e->getMessage();
    }
}

// If already authenticated, redirect to dashboard
if ($success && Auth::isAuthenticated()) {
    $redirect_after = 2;
} else if ($success && !Auth::isAuthenticated()) {
    $redirect_after = 3;
} else {
    $redirect_after = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? 'Email Verified' : 'Verification Failed'; ?> - ADHD Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito Sans', sans-serif;
            background: linear-gradient(135deg, #FFB300 0%, #FF9F43 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verify-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            text-align: center;
        }
        .verify-card h1 {
            color: #2D3A4E;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .verify-card p {
            color: #8A95A3;
            margin-bottom: 1rem;
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
        .spinner {
            display: none;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <?php if ($success): ?>
            <h1>✅ Email Verified</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <div class="spinner-border text-warning" role="status">
                <span class="visually-hidden">Redirecting...</span>
            </div>
            <script>
                setTimeout(() => {
                    window.location.href = '<?php echo Config::url('/views/dashboard.php'); ?>';
                }, <?php echo $redirect_after * 1000; ?>);
            </script>
        <?php else: ?>
            <h1>❌ Verification Failed</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="<?php echo Config::url('/views/settings.php'); ?>" class="btn btn-primary w-100">Back to Settings</a>
        <?php endif; ?>
    </div>
</body>
</html>
