<?php
/**
 * ADHD Dashboard - Change Password API
 * POST /api/profile/change-password
 * Allows user to change their password with current password verification
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
    
    // Get POST data
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (!$current_password || !$new_password || !$confirm_password) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }
    
    // Verify current password
    if (!Auth::verifyPassword($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        exit;
    }
    
    // Check password match
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
        exit;
    }
    
    // Validate password strength (minimum 8 chars)
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long']);
        exit;
    }
    
    // Check if new password is same as current (security best practice)
    if (Auth::verifyPassword($new_password, $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'New password must be different from current password']);
        exit;
    }
    
    // Hash and update password
    $hashedPassword = Auth::hashPassword($new_password);
    
    $stmt = $pdo->prepare('
        UPDATE users 
        SET password = ?,
            updated_at = NOW()
        WHERE id = ?
    ');
    
    $result = $stmt->execute([$hashedPassword, $user_id]);
    
    if ($result) {
        // Log audit trail
        $stmt = $pdo->prepare('
            INSERT INTO audit_log (user_id, action, resource_type, resource_id, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ');
        
        $stmt->execute([
            $user_id,
            'CHANGE_PASSWORD',
            'users',
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to change password']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
