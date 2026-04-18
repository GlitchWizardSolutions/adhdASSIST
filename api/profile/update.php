<?php
/**
 * ADHD Dashboard - Profile Update API
 * POST /api/profile/update
 * Updates core user profile information
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
    
    // Get POST data
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : null;
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : null;
    $timezone = isset($_POST['timezone']) ? trim($_POST['timezone']) : null;
    
    // Validate email format
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Check if email is already taken (if different from current)
    $currentUser = Auth::getCurrentUser();
    if ($email && $email !== $currentUser['email']) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email already in use']);
            exit;
        }
    }
    
    // Check if username is already taken (if provided and different)
    if ($username && $username !== ($currentUser['username'] ?? '')) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Username already in use']);
            exit;
        }
    }
    
    // Update user profile
    $stmt = $pdo->prepare('
        UPDATE users 
        SET email = ?, 
            username = ?, 
            first_name = ?, 
            last_name = ?, 
            timezone = ?,
            updated_at = NOW()
        WHERE id = ?
    ');
    
    $result = $stmt->execute([
        $email ?: $currentUser['email'],
        $username,
        $first_name,
        $last_name,
        $timezone ?: 'UTC',
        $user_id
    ]);
    
    if ($result) {
        // Log audit trail
        $stmt = $pdo->prepare('
            INSERT INTO audit_log (user_id, action, resource_type, resource_id, changes, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ');
        
        $changes = [];
        if ($email && $email !== $currentUser['email']) $changes['email'] = $email;
        if ($username) $changes['username'] = $username;
        if ($first_name) $changes['first_name'] = $first_name;
        if ($last_name) $changes['last_name'] = $last_name;
        if ($timezone) $changes['timezone'] = $timezone;
        
        $stmt->execute([
            $user_id,
            'UPDATE_PROFILE',
            'users',
            $user_id,
            json_encode($changes),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
