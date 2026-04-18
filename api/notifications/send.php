<?php
/**
 * Send Notifications API
 * POST /api/notifications/send
 * 
 * Sends notifications via email or SMS based on user preferences
 * Used internally by task/habit/event APIs
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../../lib/email-service.php';
require_once __DIR__ . '/../../../lib/sms-service.php';

header('Content-Type: application/json');

try {
    $pdo = db();
    
    // Get POST data
    $user_id = $_POST['user_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $message = $_POST['message'] ?? '';
    $notification_type = $_POST['notification_type'] ?? 'task_notification';
    $related_task_id = $_POST['related_task_id'] ?? null;
    $channels = explode(',', $_POST['channels'] ?? 'email,in_app'); // email, sms, in_app
    $force_send = $_POST['force_send'] ?? false; // Force immediate send even if batching is enabled

    if (!$user_id || empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'user_id and title required']);
        exit;
    }

    // Get user and preferences
    $stmt = $pdo->prepare('
        SELECT u.id, u.email, u.first_name, u.phone_number, u.mobile_carrier, 
               COALESCE(p.email_notifications_enabled, 1) as email_enabled,
               COALESCE(p.sms_notifications_enabled, 0) as sms_enabled,
               COALESCE(p.in_app_notifications_enabled, 1) as in_app_enabled,
               COALESCE(p.email_reminder_type, \"immediate\") as reminder_type,
               COALESCE(p.quiet_hours_start, \"21:00:00\") as quiet_start,
               COALESCE(p.quiet_hours_end, \"08:00:00\") as quiet_end
        FROM users u
        LEFT JOIN user_preferences p ON u.id = p.user_id
        WHERE u.id = ?
        LIMIT 1
    ');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $results = [
        'email_sent' => false,
        'email_queued' => false,
        'sms_sent' => false,
        'sms_queued' => false,
        'in_app_created' => false
    ];

    // Check if currently in quiet hours
    $current_time = date('H:i:s');
    $quiet_start = $user['quiet_start'];
    $quiet_end = $user['quiet_end'];
    $in_quiet_hours = false;

    if ($quiet_start < $quiet_end) {
        $in_quiet_hours = ($current_time >= $quiet_start && $current_time < $quiet_end);
    } else {
        // Quiet hours span midnight
        $in_quiet_hours = ($current_time >= $quiet_start || $current_time < $quiet_end);
    }

    // Handle email notifications
    if (in_array('email', $channels) && $user['email_enabled']) {
        // Check reminder frequency preference
        $reminder_type = $user['reminder_type'];
        $should_queue = ($reminder_type === 'daily_digest' || $reminder_type === 'weekly_digest') && !$force_send;

        if ($should_queue && !$in_quiet_hours) {
            // Queue email for batch delivery
            $stmt = $pdo->prepare('
                INSERT INTO notification_queue (user_id, title, message, notification_type, related_task_id, channel, status)
                VALUES (?, ?, ?, ?, ?, \"email\", \"pending\")
            ');
            $stmt->execute([
                $user_id,
                $title,
                $message,
                $notification_type,
                $related_task_id ?: null
            ]);
            $results['email_queued'] = true;
        } elseif (!$in_quiet_hours) {
            // Send email immediately
            try {
                $email_subject = $title;
                $email_body = "Hello " . ($user['first_name'] ?? 'there') . ",\n\n" .
                             $message . "\n\n" .
                             "Best regards,\nADHD Dashboard";

                $emailService = new EmailService();
                $emailResult = $emailService->send(
                    $user['email'],
                    $email_subject,
                    $email_body,
                    $email_body
                );
                
                if ($emailResult['success']) {
                    $results['email_sent'] = true;
                }
            } catch (Exception $e) {
                // Log error but don't fail the whole request
                error_log("Email notification failed for user {$user_id}: " . $e->getMessage());
            }
        }
    }

    // Handle SMS notifications
    if (in_array('sms', $channels) && $user['sms_enabled'] && $user['phone_number'] && $user['mobile_carrier'] && !$in_quiet_hours) {
        $reminder_type = $user['reminder_type'];
        $should_queue = ($reminder_type === 'daily_digest' || $reminder_type === 'weekly_digest') && !$force_send;

        if ($should_queue) {
            // Queue SMS for batch delivery
            $stmt = $pdo->prepare('
                INSERT INTO notification_queue (user_id, title, message, notification_type, related_task_id, channel, status)
                VALUES (?, ?, ?, ?, ?, \"sms\", \"pending\")
            ');
            $stmt->execute([
                $user_id,
                $title,
                $message,
                $notification_type,
                $related_task_id ?: null
            ]);
            $results['sms_queued'] = true;
        } else {
            // Send SMS immediately
            try {
                $smsService = new SMSService();
                $smsResult = $smsService->send(
                    $user['phone_number'],
                    $user['mobile_carrier'],
                    $message
                );
                
                if ($smsResult['success']) {
                    $results['sms_sent'] = true;
                }
            } catch (Exception $e) {
                // Log error but don't fail the whole request
                error_log("SMS notification failed for user {$user_id}: " . $e->getMessage());
            }
        }
    }

    // Create in-app notification record (always immediate, never queued)
    if (in_array('in_app', $channels) && $user['in_app_enabled']) {
        $expires_at = date('Y-m-d H:i:s', time() + (72 * 3600)); // 72 hours
        $stmt = $pdo->prepare('
            INSERT INTO notifications 
            (user_id, title, message, notification_type, related_task_id, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user_id,
            $title,
            $message,
            $notification_type,
            $related_task_id ?: null,
            $expires_at
        ]);
        $results['in_app_created'] = true;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Notification processed',
        'results' => $results,
        'in_quiet_hours' => $in_quiet_hours,
        'reminder_type' => $user['reminder_type']
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
