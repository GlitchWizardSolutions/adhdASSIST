<?php
/**
 * ADHD Dashboard - Delete Account API
 * POST /api/profile/delete-account
 * Initiates account deletion with 30-day grace period
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
    $password = $_POST['delete_password'] ?? '';
    $confirm = $_POST['confirm_deletion'] ?? '';
    
    // Validate input
    if (!$password || !$confirm) {
        echo json_encode(['success' => false, 'error' => 'Password confirmation required']);
        exit;
    }
    
    // Verify password
    if (!Auth::verifyPassword($password, $user['password'])) {
        echo json_encode(['success' => false, 'error' => 'Password is incorrect']);
        exit;
    }
    
    // Mark account for deletion (soft delete with grace period)
    $deletion_date = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $pdo->prepare('
        UPDATE users 
        SET is_active = 0,
            updated_at = NOW()
        WHERE id = ?
    ');
    
    $result = $stmt->execute([$user_id]);
    
    if ($result) {
        // Create audit log
        $stmt = $pdo->prepare('
            INSERT INTO audit_log (user_id, action, resource_type, resource_id, changes, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        
        $stmt->execute([
            $user_id,
            'REQUEST_ACCOUNT_DELETION',
            'users',
            $user_id,
            json_encode(['deletion_scheduled_for' => $deletion_date]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Your account has been scheduled for deletion in 30 days. You can reactivate by logging in during this period.'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to process deletion request']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
