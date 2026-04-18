<?php
/**
 * ADHD Dashboard - Logout All Other Sessions API
 * POST /api/profile/logout-all-other-sessions
 * Logs user out of all other devices/sessions
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
    $user_id = Auth::getCurrentUser()['id'];
    
    // Get current session ID (if storing sessions in DB)
    $current_session_id = session_id();
    
    // Invalidate all other sessions for this user
    // If sessions are stored in DB:
    $stmt = $pdo->prepare('
        UPDATE sessions 
        SET is_active = 0
        WHERE user_id = ? AND session_token != ?
    ');
    
    $result = $stmt->execute([$user_id, $current_session_id]);
    
    if ($result) {
        // Log audit trail
        $stmt = $pdo->prepare('
            INSERT INTO audit_log (user_id, action, resource_type, resource_id, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ');
        
        $stmt->execute([
            $user_id,
            'LOGOUT_ALL_SESSIONS',
            'sessions',
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'All other sessions have been logged out'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to log out other sessions']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
