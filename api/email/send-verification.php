<?php
/**
 * ADHD Dashboard - Send Email Verification
 * POST /api/email/send-verification
 * Sends email verification link to user
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../../private/lib/email-service.php';

header('Content-Type: application/json');

// Require authentication
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
    $user = Auth::getCurrentUser();
    $email = $_POST['email'] ?? $user['email'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        exit;
    }

    // Check if email is already in use by another user
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    $stmt->execute([$email, $user['id']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already in use']);
        exit;
    }

    // Generate verification token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + 86400); // 24 hours

    // Store verification token
    $stmt = $pdo->prepare('
        INSERT INTO email_verifications (user_id, email, token, expires_at)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
    ');
    
    $stmt->execute([$user['id'], $email, $token, $expires_at]);

    // Send verification email
    $verification_link = Config::url('base') . 'api/email/verify?token=' . urlencode($token);
    
    try {
        $emailService = new EmailService();
        $result = $emailService->sendEmailVerification(
            $email,
            $user['first_name'] ?? 'User',
            $verification_link
        );

        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Verification email sent. Check your inbox.',
                'messageId' => $result['messageId']
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to send verification email: ' . $result['message']
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