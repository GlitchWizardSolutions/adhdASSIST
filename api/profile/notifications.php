<?php
/**
 * ADHD Dashboard - Notifications Preferences API
 * POST /api/profile/notifications
 * Updates user notification preferences
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
    
    // Get POST data with defaults
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $in_app_notifications = isset($_POST['in_app_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $email_reminder_type = isset($_POST['email_reminder_type']) ? trim($_POST['email_reminder_type']) : 'immediate';
    $quiet_hours_start = isset($_POST['quiet_hours_start']) ? trim($_POST['quiet_hours_start']) : '21:00:00';
    $quiet_hours_end = isset($_POST['quiet_hours_end']) ? trim($_POST['quiet_hours_end']) : '08:00:00';
    
    // Note: Phone number is now managed in the profile settings, not here
    
    // Check if user preferences exist
    $exists = false;
    try {
        $stmt = $pdo->prepare('SELECT id FROM user_preferences WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $exists = $stmt->fetch();
    } catch (PDOException $e) {
        // Table doesn't exist yet, will create on insert
    }
    
    if ($exists) {
        try {
            // Update existing preferences
            $stmt = $pdo->prepare('
            UPDATE user_preferences 
            SET email_notifications_enabled = ?,
                in_app_notifications_enabled = ?,
                sms_notifications_enabled = ?,
                email_reminder_type = ?,
                quiet_hours_start = ?,
                quiet_hours_end = ?,
                updated_at = NOW()
            WHERE user_id = ?
        ');
        
            $result = $stmt->execute([
                $email_notifications,
                $in_app_notifications,
                $sms_notifications,
                $email_reminder_type,
                $quiet_hours_start,
                $quiet_hours_end,
                $user_id
            ]);
        } catch (PDOException $e) {
            $result = false;
        }
    } else {
        try {
            // Insert new preferences
            $stmt = $pdo->prepare('
            INSERT INTO user_preferences 
            (user_id, email_notifications_enabled, in_app_notifications_enabled, sms_notifications_enabled, email_reminder_type, quiet_hours_start, quiet_hours_end)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
            $result = $stmt->execute([
                $user_id,
                $email_notifications,
                $in_app_notifications,
                $sms_notifications,
                $email_reminder_type,
                $quiet_hours_start,
                $quiet_hours_end
            ]);
        } catch (PDOException $e) {
            $result = false;
        }
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification preferences updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update preferences']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
