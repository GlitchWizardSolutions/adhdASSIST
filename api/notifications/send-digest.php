<?php
/**
 * Send Notification Digests
 * POST /api/notifications/send-digest
 * 
 * Sends batched notifications (email/SMS digests) to users
 * Called by cron job or scheduled task
 * 
 * Parameters:
 * - digest_type: 'daily' or 'weekly' (default: 'daily')
 * - limit: max users to process per call (default: 50)
 * - test_mode: if true, only returns count without sending (default: false)
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
    
    // Get parameters
    $digest_type = $_POST['digest_type'] ?? 'daily'; // 'daily' or 'weekly'
    $limit = intval($_POST['limit'] ?? 50); // Process up to N users per call
    $test_mode = isset($_POST['test_mode']) && $_POST['test_mode'] === '1';
    
    // Calculate time window
    if ($digest_type === 'weekly') {
        $time_window = '7 days';
    } else {
        $time_window = '1 day';
    }

    // Get users with pending notifications
    $stmt = $pdo->prepare('
        SELECT DISTINCT u.id, u.first_name, u.email
        FROM users u
        INNER JOIN notification_queue nq ON u.id = nq.user_id
        INNER JOIN user_preferences up ON u.id = up.user_id
        WHERE nq.status = \"pending\"
          AND nq.created_at >= DATE_SUB(NOW(), INTERVAL ' . ($digest_type === 'weekly' ? '7' : '1') . ' DAY)
          AND (up.email_reminder_type = \"' . $pdo->quote($digest_type . '_digest') . '\" 
               OR up.email_reminder_type = \"digest\")
        GROUP BY u.id
        LIMIT ?
    ');
    $stmt->execute([$limit]);
    $users_to_notify = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [
        'users_processed' => 0,
        'emails_sent' => 0,
        'emails_failed' => 0,
        'smses_sent' => 0,
        'smses_failed' => 0,
        'total_pending_notifications' => 0,
        'test_mode' => $test_mode
    ];

    foreach ($users_to_notify as $user) {
        // Get all pending email notifications for this user
        $stmt = $pdo->prepare('
            SELECT id, title, message, created_at
            FROM notification_queue
            WHERE user_id = ? AND status = \"pending\" AND channel = \"email\"
            ORDER BY created_at DESC
            LIMIT 50
        ');
        $stmt->execute([$user['id']]);
        $pending_emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($pending_emails)) {
            continue;
        }

        $stats['users_processed']++;
        $stats['total_pending_notifications'] += count($pending_emails);

        if (!$test_mode) {
            // Build email digest
            $html_body = self::buildDigestHTML($user, $pending_emails, $digest_type);
            $plain_body = self::buildDigestPlainText($user, $pending_emails, $digest_type);

            try {
                $emailService = new EmailService();
                $subject = ($digest_type === 'weekly' ? 'Weekly' : 'Daily') . ' Notification Summary - ' . date('M d, Y');
                $result = $emailService->send(
                    $user['email'],
                    $subject,
                    $html_body,
                    $plain_body
                );

                if ($result['success']) {
                    $stats['emails_sent']++;

                    // Mark all email notifications as sent
                    $stmt = $pdo->prepare('
                        UPDATE notification_queue
                        SET status = \"sent\", sent_at = NOW()
                        WHERE user_id = ? AND status = \"pending\" AND channel = \"email\"
                    ');
                    $stmt->execute([$user['id']]);
                } else {
                    $stats['emails_failed']++;
                    
                    // Mark as failed with error message
                    $stmt = $pdo->prepare('
                        UPDATE notification_queue
                        SET status = \"failed\", error_message = ?, retry_count = retry_count + 1
                        WHERE user_id = ? AND status = \"pending\" AND channel = \"email\"
                    ');
                    $stmt->execute([$result['message'] ?? 'Unknown error', $user['id']]);
                }
            } catch (Exception $e) {
                $stats['emails_failed']++;
                
                // Mark as failed
                $stmt = $pdo->prepare('
                    UPDATE notification_queue
                    SET status = \"failed\", error_message = ?, retry_count = retry_count + 1
                    WHERE user_id = ? AND status = \"pending\" AND channel = \"email\"
                ');
                $stmt->execute([$e->getMessage(), $user['id']]);
                
                error_log("Digest email failed for user {$user['id']}: " . $e->getMessage());
            }
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Digest processing complete',
        'digest_type' => $digest_type,
        'stats' => $stats,
        'note' => 'Run this endpoint as a cron job: 0 9 * * * curl -X POST ' . Config::url('api') . 'notifications/send-digest.php -d "digest_type=daily"'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Build HTML email digest
 */
function buildDigestHTML($user, $notifications, $type = 'daily') {
    $firstName = htmlspecialchars($user['first_name'] ?? 'Friend');
    $period = $type === 'weekly' ? 'this week' : 'today';
    
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
            .notification-item { border-left: 4px solid #667eea; padding: 15px; margin-bottom: 15px; background: #f9f9f9; border-radius: 4px; }
            .notification-title { font-weight: bold; margin-bottom: 5px; }
            .notification-time { color: #999; font-size: 12px; }
            .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📬 Your Notification Summary</h1>
                <p>Hello ' . $firstName . ', you have ' . count($notifications) . ' notification(s) ' . $period . '</p>
            </div>
    ';
    
    foreach ($notifications as $notif) {
        $title = htmlspecialchars($notif['title']);
        $message = htmlspecialchars($notif['message']);
        $time = date('M d, Y g:i A', strtotime($notif['created_at']));
        
        $html .= '
            <div class="notification-item">
                <div class="notification-title">' . $title . '</div>
                <p>' . nl2br($message) . '</p>
                <div class="notification-time">' . $time . '</div>
            </div>
        ';
    }
    
    $html .= '
            <div class="footer">
                <p>Manage your notification preferences in <a href="' . Config::url('/views/settings.php') . '">Settings</a></p>
                <p>ADHD Dashboard &copy; 2026</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $html;
}

/**
 * Build plain text email digest
 */
function buildDigestPlainText($user, $notifications, $type = 'daily') {
    $firstName = $user['first_name'] ?? 'Friend';
    $period = $type === 'weekly' ? 'this week' : 'today';
    
    $text = "YOUR NOTIFICATION SUMMARY\n";
    $text .= str_repeat("=", 40) . "\n\n";
    $text .= "Hello " . $firstName . ",\n\n";
    $text .= "You have " . count($notifications) . " notification(s) " . $period . ":\n\n";
    
    foreach ($notifications as $notif) {
        $title = $notif['title'];
        $message = $notif['message'];
        $time = date('M d, Y g:i A', strtotime($notif['created_at']));
        
        $text .= "--- " . $title . " ---\n";
        $text .= $message . "\n";
        $text .= "[" . $time . "]\n\n";
    }
    
    $text .= str_repeat("=", 40) . "\n";
    $text .= "Manage your notification preferences in Settings\n";
    $text .= "ADHD Dashboard © 2026\n";
    
    return $text;
}
?>
