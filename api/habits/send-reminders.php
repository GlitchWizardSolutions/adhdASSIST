<?php
/**
 * ADHD Dashboard - Send Habit Reminders
 * POST /api/habits/send-reminders
 * Sends email reminders to users with incomplete habits
 * 
 * Can be triggered by:
 * - Cron job at specific times
 * - Manual admin trigger
 * - User request (opt-in reminders)
 * 
 * Usage:
 * POST /api/habits/send-reminders
 * user_id: (optional, specific user) or all active users
 * period: (optional) morning, afternoon, evening, or all
 * 
 * Returns: {success: true, sent_count: N, failed_count: N}
 */

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../../../private/lib/email-service.php';

// Set error handling to return JSON on errors
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("💥 Email send-reminders error: [$errno] $errstr in $errfile:$errline");
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $errstr,
        'details' => "[$errno] $errfile:$errline"
    ]);
    exit;
});

header('Content-Type: application/json');

error_log('📧 Email reminders endpoint called - POST user_id: ' . ($_POST['user_id'] ?? 'none') . ', GET user_id: ' . ($_GET['user_id'] ?? 'none') . ', period: ' . ($_POST['period'] ?? $_GET['period'] ?? 'none'));

try {
    $pdo = db();
    $sent_count = 0;
    $failed_count = 0;
    $errors = [];

    // Get target user(s)
    $user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
    $period = $_POST['period'] ?? $_GET['period'] ?? null; // morning, afternoon, evening, or null for all
    
    error_log("📧 Email reminders - Fetching users. Period: $period, User ID: " . ($user_id ?? 'all'));

    if ($user_id) {
        // Specific user
        $stmt = $pdo->prepare('
            SELECT u.id, u.email, u.first_name, u.timezone,
                   COALESCE(up.email_notifications_enabled, 1) as email_notifications_enabled,
                   up.quiet_hours_start, up.quiet_hours_end
            FROM users u
            LEFT JOIN user_preferences up ON u.id = up.user_id
            WHERE u.id = ? AND u.is_active = 1 AND u.email IS NOT NULL
            LIMIT 1
        ');
        $stmt->execute([$user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // All active users (send to all by default, check preference during send)
        $stmt = $pdo->prepare('
            SELECT u.id, u.email, u.first_name, u.timezone,
                   COALESCE(up.email_notifications_enabled, 1) as email_notifications_enabled,
                   up.quiet_hours_start, up.quiet_hours_end
            FROM users u
            LEFT JOIN user_preferences up ON u.id = up.user_id
            WHERE u.is_active = 1 AND u.email IS NOT NULL
            ORDER BY u.id
        ');
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    error_log("📧 Email reminders - Found " . count($users) . " users");

    if (empty($users)) {
        error_log("📧 Email reminders - No active users found");
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'No active users found',
            'sent_count' => 0,
            'failed_count' => 0
        ]);
        exit;
    }

    $emailService = new EmailService();
    error_log("📧 Email reminders - EmailService initialized");

    // Send reminders to each user
    foreach ($users as $user) {
        try {
            // Get incomplete habits for user
            // Habits are tracked by is_morning, is_afternoon, is_evening booleans
            $query = '
                SELECT dh.id, dh.habit_name,
                       CASE 
                           WHEN dh.is_morning THEN "Morning"
                           WHEN dh.is_afternoon THEN "Afternoon"
                           WHEN dh.is_evening THEN "Evening"
                           ELSE "Daily"
                       END as period
                FROM daily_habits dh
                LEFT JOIN habit_completions hc ON dh.id = hc.habit_id 
                    AND hc.user_id = ? 
                    AND hc.completion_date = CURDATE()
                WHERE dh.user_id = ? 
                    AND dh.is_active = 1
                    AND hc.id IS NULL
                ORDER BY dh.sort_order, dh.habit_name
            ';

            $stmt = $pdo->prepare($query);
            $stmt->execute([$user['id'], $user['id']]);
            $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("📧 Email reminders - User {$user['id']} ({$user['email']}): Found " . count($habits) . " incomplete habits");

            // Filter by period if specified
            if ($period && !empty($habits)) {
                $period_name = ucfirst(strtolower($period));
                $habits = array_filter($habits, function($h) use ($period_name) {
                    return $h['period'] === $period_name;
                });
                error_log("📧 Email reminders - After period filter ($period): " . count($habits) . " habits");
            }

            // Only send if there are incomplete habits
            if (!empty($habits)) {
                error_log("📧 Email reminders - Checking quiet hours for user {$user['id']}");
                
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
                
                error_log("📧 Email reminders - Quiet hours check for user {$user['id']}: current time=$current_time_str, quiet hours=$quiet_start to $quiet_end, in_quiet=$in_quiet_hours");
                
                // Skip sending if in quiet hours
                if ($in_quiet_hours) {
                    error_log("📧 Email reminders - Skipping user {$user['id']} due to quiet hours");
                    continue;
                }
                
                error_log("📧 Email reminders - Sending email to {$user['email']} with " . count($habits) . " habits");
                
                $result = $emailService->sendHabitReminder(
                    $user['email'],
                    $habits,
                    Config::url('base')
                );
                
                error_log("📧 Email reminders - Result for {$user['email']}: " . json_encode($result));

                if ($result['success']) {
                    $sent_count++;
                    error_log("📧 Email reminders - ✅ Email sent successfully to {$user['email']}");
                } else {
                    $failed_count++;
                    $error_msg = $result['message'] ?? $result['error'] ?? 'Unknown error';
                    $errors[] = "User {$user['id']}: {$error_msg}";
                    error_log("📧 Email reminders - ❌ Failed to send email to {$user['email']}: $error_msg");
                }
            }

        } catch (Exception $e) {
            $failed_count++;
            $error_msg = $e->getMessage();
            $errors[] = "User {$user['id']}: " . $error_msg;
            error_log("💥 Email reminders - Exception for user {$user['id']}: $error_msg");
        }
    }
    
    error_log("📧 Email reminders - Complete: sent=$sent_count, failed=$failed_count, total_users=" . count($users));

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Sent $sent_count reminders",
        'sent_count' => $sent_count,
        'failed_count' => $failed_count,
        'errors' => $errors,
        'target' => [
            'user_id' => $user_id,
            'period' => $period,
            'total_users_processed' => count($users)
        ]
    ]);

} catch (PDOException $e) {
    error_log("💥 Email reminders - Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("💥 Email reminders - General exception: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}