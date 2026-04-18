<?php
/**
 * Request Email Verification
 * POST /api/profile/request-email-verification
 * Sends verification email when user wants to change their email
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = db();
    $user = Auth::getCurrentUser();
    $user_id = $user['id'];
    $new_email = $_POST['new_email'] ?? '';

    if (empty($new_email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'New email is required']);
        exit;
    }

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }

    if ($new_email === $user['email']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'New email must be different from current email']);
        exit;
    }

    // Check if email is already in use
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $stmt->execute([$new_email, $user_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'This email is already in use']);
        exit;
    }

    // Generate verification token (valid for 24 hours)
    $verification_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + 86400);

    // Store verification request
    $stmt = $pdo->prepare('
        INSERT INTO email_verifications (user_id, new_email, token, expires_at)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), is_verified = 0
    ');
    
    $stmt->execute([$user_id, $new_email, $verification_token, $expires_at]);

    // Send verification email
    $verify_link = Config::url('/api/profile/verify-email.php?token=' . urlencode($verification_token));
    $recipient_name = $user['first_name'] ?? 'there';
    
    $email_subject = 'Verify Your New Email Address';
    $email_body = "Hello {$recipient_name},\n\n" .
        "You requested to change your email address to: {$new_email}\n\n" .
        "Click this link to verify your new email address (valid for 24 hours):\n" .
        $verify_link . "\n\n" .
        "If you didn't request this, you can ignore this email.\n\n" .
        "Best regards,\nADHD Dashboard Team";

    // Simple mail function - in production, use proper email library like PHPMailer
    $headers = "From: noreply@" . parse_url(Config::url('/'), PHP_URL_HOST);
    
    if (@mail($new_email, $email_subject, $email_body, $headers)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Verification email sent to ' . htmlspecialchars($new_email) . '. Please check your inbox.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to send verification email. Please try again later.'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
