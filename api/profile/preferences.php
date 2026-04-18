<?php
/**
 * ADHD Dashboard - User Preferences API
 * POST /api/profile/preferences
 * Updates user display and behavior preferences
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
    $current_user = Auth::getCurrentUser();
    
    // Get POST data
    $email = isset($_POST['email']) ? trim($_POST['email']) : $current_user['email'];
    $username = isset($_POST['username']) ? trim($_POST['username']) : $current_user['username'];
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : null;
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : null;
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
    $notification_phone = isset($_POST['notification_phone']) ? trim($_POST['notification_phone']) : null;
    $mailing_address = isset($_POST['mailing_address']) ? trim($_POST['mailing_address']) : null;
    $theme = isset($_POST['theme']) ? trim($_POST['theme']) : 'light';
    $timezone = isset($_POST['timezone']) ? trim($_POST['timezone']) : 'UTC';
    $low_energy_mode = isset($_POST['low_energy_mode']) ? 1 : 0;
    $task_reschedule_mode = isset($_POST['task_reschedule_mode']) ? trim($_POST['task_reschedule_mode']) : 'manual';
    $daily_habits_reset_time = isset($_POST['daily_habits_reset_time']) ? trim($_POST['daily_habits_reset_time']) : '00:00:00';
    $pomodoro_duration = isset($_POST['pomodoro_duration']) ? intval($_POST['pomodoro_duration']) : 25;
    $break_duration = isset($_POST['break_duration']) ? intval($_POST['break_duration']) : 5;
    $pomodoro_sound = isset($_POST['pomodoro_sound']) ? intval($_POST['pomodoro_sound']) : 0;
    $show_badges_widget = isset($_POST['show_badges_widget']) ? intval($_POST['show_badges_widget']) : 0;
    $focus_planning = isset($_POST['focus_planning']) ? intval($_POST['focus_planning']) : 0;
    $email_notifications = isset($_POST['email_notifications']) ? intval($_POST['email_notifications']) : 0;
    $in_app_notifications = isset($_POST['in_app_notifications']) ? intval($_POST['in_app_notifications']) : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? intval($_POST['sms_notifications']) : 0;
    $email_reminder_type = isset($_POST['email_reminder_type']) ? trim($_POST['email_reminder_type']) : 'immediate';
    $quiet_hours_start = isset($_POST['quiet_hours_start']) ? trim($_POST['quiet_hours_start']) : '21:00:00';
    $quiet_hours_end = isset($_POST['quiet_hours_end']) ? trim($_POST['quiet_hours_end']) : '08:00:00';
    $mobile_carrier = isset($_POST['mobile_carrier']) ? trim($_POST['mobile_carrier']) : null;
    
    // Validate username uniqueness if changed
    if ($username && $username !== $current_user['username']) {
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'error' => 'Username is already taken. Please choose another.']);
            exit;
        }
        
        // Validate username format (alphanumeric, underscore, hyphen only)
        if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
            echo json_encode(['success' => false, 'error' => 'Username must be 3-50 characters and contain only letters, numbers, underscores, and hyphens.']);
            exit;
        }
    }
    
    // Validate email if changed
    if ($email && $email !== $current_user['email']) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
            exit;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'error' => 'Email is already in use by another account.']);
            exit;
        }
    }
    
    // Validate pomodoro durations
    if ($pomodoro_duration < 5 || $pomodoro_duration > 120) {
        echo json_encode(['success' => false, 'error' => 'Invalid Pomodoro duration']);
        exit;
    }
    
    if ($break_duration < 1 || $break_duration > 60) {
        echo json_encode(['success' => false, 'error' => 'Invalid break duration']);
        exit;
    }
    
    // Update user settings
    $stmt = $pdo->prepare('
        UPDATE users 
        SET email = ?,
            username = ?,
            first_name = ?,
            last_name = ?,
            phone_number = ?,
            notification_phone = ?,
            mailing_address = ?,
            theme_preference = ?,
            timezone = ?,
            low_energy_mode = ?,
            task_reschedule_mode = ?,
            daily_habits_reset_time = ?,
            mobile_carrier = ?,
            updated_at = NOW()
        WHERE id = ?
    ');
    
    $stmt->execute([
        $email,
        $username,
        $first_name,
        $last_name,
        $phone_number,
        $notification_phone,
        $mailing_address,
        $theme,
        $timezone,
        $low_energy_mode,
        $task_reschedule_mode,
        $daily_habits_reset_time,
        $mobile_carrier,
        $user_id
    ]);
    
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
                SET pomodoro_duration_minutes = ?,
                    pomodoro_break_duration_minutes = ?,
                    pomodoro_sound_enabled = ?,
                    show_recent_badges_widget = ?,
                    focus_planning_enabled = ?,
                    email_notifications_enabled = ?,
                    in_app_notifications_enabled = ?,
                    sms_notifications_enabled = ?,
                    email_reminder_type = ?,
                    quiet_hours_start = ?,
                    quiet_hours_end = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ');
            
            $result = $stmt->execute([
                $pomodoro_duration,
                $break_duration,
                $pomodoro_sound,
                $show_badges_widget,
                $focus_planning,
                $email_notifications,
                $in_app_notifications,
                $sms_notifications,
                $email_reminder_type,
                $quiet_hours_start,
                $quiet_hours_end,
                $user_id
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database update error: ' . $e->getMessage()]);
            exit;
        }
    } else {
        try {
            // Insert new preferences
            $stmt = $pdo->prepare('
                INSERT INTO user_preferences 
                (user_id, pomodoro_duration_minutes, pomodoro_break_duration_minutes, pomodoro_sound_enabled, show_recent_badges_widget, focus_planning_enabled, email_notifications_enabled, in_app_notifications_enabled, sms_notifications_enabled, email_reminder_type, quiet_hours_start, quiet_hours_end)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $result = $stmt->execute([
                $user_id,
                $pomodoro_duration,
                $break_duration,
                $pomodoro_sound,
                $show_badges_widget,
                $focus_planning,
                $email_notifications,
                $in_app_notifications,
                $sms_notifications,
                $email_reminder_type,
                $quiet_hours_start,
                $quiet_hours_end
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database insert error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Preferences updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update preferences (no rows affected)']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
