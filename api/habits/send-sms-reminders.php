<?php
/**
 * ADHD Dashboard - Send Habit Reminders via SMS
 * POST /api/habits/send-sms-reminders
 * 
 * Sends SMS text message reminders to users with incomplete habits
 * Uses email-to-SMS gateways (no SMS provider account needed)
 * 
 * Triggered by cron jobs at:
 * - 7:30 AM (Morning habits)
 * - 12:30 PM (Afternoon habits)
 * - 5:30 PM (Evening habits)
 * 
 * Usage:
 * POST /api/habits/send-sms-reminders
 * period: morning, afternoon, or evening
 * user_id: (optional) specific user or all users with SMS enabled
 * 
 * Returns: {success: true, sent_count: N, failed_count: N, errors: [...]}
 */

require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../../private/lib/easysend-sms-service.php';

header('Content-Type: application/json');

try {
    $pdo = db();
    $sent_count = 0;
    $failed_count = 0;
    $errors = [];

    // Get target period and user
    $period = strtolower($_POST['period'] ?? $_GET['period'] ?? 'morning');
    $user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

    // Validate period
    $valid_periods = ['morning', 'afternoon', 'evening'];
    if (!in_array($period, $valid_periods)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid period. Must be: morning, afternoon, or evening'
        ]);
        exit;
    }

    // Map period to database column and display name
    $periodMap = [
        'morning' => ['is_morning', 'Morning'],
        'afternoon' => ['is_afternoon', 'Afternoon'],
        'evening' => ['is_evening', 'Evening']
    ];

    list($dbColumn, $displayPeriod) = $periodMap[$period];

    // Get users to send reminders to
    if ($user_id) {
        // Specific user - must have phone number and SMS enabled
        $stmt = $pdo->prepare('
            SELECT u.id, u.email, u.first_name, u.notification_phone as phone_number, u.timezone,
                   up.quiet_hours_start, up.quiet_hours_end
            FROM users u
            LEFT JOIN user_preferences up ON u.id = up.user_id
            WHERE u.id = ? AND u.is_active = 1 
                AND u.notification_phone IS NOT NULL AND u.notification_phone != ""
                AND up.sms_notifications_enabled = 1
            LIMIT 1
        ');
        $stmt->execute([$user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // All active users with SMS enabled
        $stmt = $pdo->prepare('
            SELECT u.id, u.email, u.first_name, u.notification_phone as phone_number, u.timezone,
                   up.quiet_hours_start, up.quiet_hours_end
            FROM users u
            LEFT JOIN user_preferences up ON u.id = up.user_id
            WHERE u.is_active = 1 
                AND u.notification_phone IS NOT NULL AND u.notification_phone != ""
                AND up.sms_notifications_enabled = 1
            ORDER BY u.id
        ');
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($users)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'No users with SMS configured',
            'sent_count' => 0,
            'failed_count' => 0,
            'target' => ['period' => $period]
        ]);
        exit;
    }

    $smsService = new EasySendSMSService($pdo);

    // Send reminders to each user
    foreach ($users as $user) {
        try {
            // Get incomplete habits for this period
            $query = '
                SELECT dh.id, dh.habit_name
                FROM daily_habits dh
                LEFT JOIN habit_completions hc ON dh.id = hc.habit_id 
                    AND hc.user_id = ? 
                    AND hc.completion_date = CURDATE()
                WHERE dh.user_id = ? 
                    AND dh.is_active = 1
                    AND dh.' . $dbColumn . ' = 1
                    AND hc.id IS NULL
                ORDER BY dh.sort_order, dh.habit_name
            ';

            $stmt = $pdo->prepare($query);
            $stmt->execute([$user['id'], $user['id']]);
            $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Only send if there are incomplete habits
            if (!empty($habits)) {
                // Check quiet hours
                $current_time = new DateTime('now', new DateTimeZone($user['timezone'] ?? 'UTC'));
                $current_time_str = $current_time->format('H:i:s');
                
                $quiet_start = $user['quiet_hours_start'] ?? '21:00:00';
                $quiet_end = $user['quiet_hours_end'] ?? '08:00:00';
                
                // Check if current time is within quiet hours
                $in_quiet_hours = false;
                if ($quiet_start < $quiet_end) {
                    // Quiet hours don't cross midnight (e.g., 9 AM to 9 PM)
                    $in_quiet_hours = ($current_time_str >= $quiet_start && $current_time_str < $quiet_end);
                } else {
                    // Quiet hours cross midnight (e.g., 9 PM to 9 AM)
                    $in_quiet_hours = ($current_time_str >= $quiet_start || $current_time_str < $quiet_end);
                }
                
                // Skip sending if in quiet hours
                if ($in_quiet_hours) {
                    continue;
                }
                
                $result = $smsService->sendHabitReminder($user, $habits, $displayPeriod);

                if ($result['success']) {
                    $sent_count++;
                } else {
                    $failed_count++;
                    $errors[] = "User {$user['id']}: {$result['message']}";
                }
            }

        } catch (Exception $e) {
            $failed_count++;
            $errors[] = "User {$user['id']}: " . $e->getMessage();
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Sent $sent_count SMS reminders",
        'sent_count' => $sent_count,
        'failed_count' => $failed_count,
        'errors' => $errors,
        'target' => [
            'period' => $period,
            'total_users_processed' => count($users)
        ]
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