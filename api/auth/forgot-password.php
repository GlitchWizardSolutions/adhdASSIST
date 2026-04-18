<?php
/**
 * ADHD Dashboard - Forgot Password Request
 * POST /api/auth/forgot-password
 * Sends password reset email with token using EmailService
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../../../private/lib/email-service.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = db();
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        exit;
    }

    // Find user by email
    $stmt = $pdo->prepare('SELECT id, email, first_name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Email not found - return success=true but email_found=false for better UX
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'email_found' => false,
            'message' => 'The email provided isn\'t in our system. Please check the spelling or try a different email.'
        ]);
        exit;
    }

    // Generate reset token (valid for 24 hours)
    $reset_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + 86400);

    // Store reset token in database
    $stmt = $pdo->prepare('
        INSERT INTO password_resets (user_id, token, expires_at)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
    ');
    
    $stmt->execute([$user['id'], $reset_token, $expires_at]);

    // Send password reset email using EmailService
    $reset_link = Config::url('base') . 'views/reset-password.php?token=' . urlencode($reset_token);
    $recipient_name = $user['first_name'] ?? 'User';
    
    try {
        $emailService = new EmailService();
        $result = $emailService->sendPasswordReset($user['email'], $recipient_name, $reset_link);
        
        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'email_found' => true,
                'message' => 'Password reset email sent. Check your inbox and spam folder.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to send email: ' . $result['message']
            ]);
        }
    } catch (Exception $emailError) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Email service error: ' . $emailError->getMessage()
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
