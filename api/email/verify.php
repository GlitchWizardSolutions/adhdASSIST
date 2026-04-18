<?php
/**
 * ADHD Dashboard - Email Verification Handler
 * GET /api/email/verify?token=xxx
 * Verifies email and updates user profile
 */

require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';

header('Content-Type: application/json');

try {
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Verification token required']);
        exit;
    }

    $pdo = db();

    // Find valid verification token
    $stmt = $pdo->prepare('
        SELECT id, user_id, email FROM email_verifications 
        WHERE token = ? AND expires_at > NOW() AND verified_at IS NULL
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid or expired verification token',
            'redirect' => '/views/login.php'
        ]);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Update user email
    $stmt = $pdo->prepare('
        UPDATE users 
        SET email = ?, email_verified_at = NOW(), updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$verification['email'], $verification['user_id']]);

    // Mark verification as complete
    $stmt = $pdo->prepare('
        UPDATE email_verifications 
        SET verified_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$verification['id']]);

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully',
        'email' => $verification['email'],
        'redirect' => '/views/profile.php'
    ]);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
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