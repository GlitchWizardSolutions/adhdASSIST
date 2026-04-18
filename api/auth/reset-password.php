<?php
/**
 * Password reset token verification and update
 * POST /api/auth/reset-password
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = db();
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($new_password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Token and password are required']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Passwords do not match']);
        exit;
    }

    if (strlen($new_password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
        exit;
    }

    // Find valid reset token
    $stmt = $pdo->prepare('
        SELECT user_id FROM password_resets 
        WHERE token = ? AND expires_at > NOW()
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $reset_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset_record) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid or expired reset token']);
        exit;
    }

    $user_id = $reset_record['user_id'];

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('
        UPDATE users 
        SET password = ?, updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$hashed_password, $user_id]);

    // Delete used token
    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
    $stmt->execute([$user_id]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully. You can now login with your new password.'
    ]);

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
