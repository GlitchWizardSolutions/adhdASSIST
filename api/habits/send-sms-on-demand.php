<?php
/**
 * ADHD Dashboard - Send Current Habits via SMS (On-Demand)
 * POST /api/habits/send-sms-on-demand
 * 
 * Sends SMS with unchecked habits for today to the authenticated user.
 * Used for on-demand manual sending (regardless of quiet hours or time).
 * 
 * Usage:
 * POST /api/habits/send-sms-on-demand
 * 
 * Returns: {success: true, message: "SMS sent", mobileCarrier: "...", count: N}
 */

// Set error handler to catch fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => "PHP Error: $errstr (Line $errline of $errfile)"
    ]);
    exit;
});

header('Content-Type: application/json');

try {
    // Load required libraries from public lib directory
    require_once __DIR__ . '/../../lib/auth.php';
    require_once __DIR__ . '/../../lib/config.php';
    require_once __DIR__ . '/../../lib/database.php';
    
    // Calculate path to private lib directory
    // Works in both dev (/xampp/.../adhd-dashboard/private) and prod (/home/user/private)
    $publicHtmlDir = dirname(dirname(dirname(__DIR__)));  // /public_html on prod, /public_html on dev
    $projectRoot = dirname($publicHtmlDir);  // /home/glitldgo on prod, /xampp/.../adhd-dashboard on dev
    $privatePath = $projectRoot . '/private/lib/easysend-sms-service.php';
    
    if (!file_exists($privatePath)) {
        throw new Exception("SMS Service not found at: $privatePath");
    }
    
    require_once $privatePath;

    // Check authentication
    if (!Auth::isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }

    $pdo = db();
    $user_id = Auth::getCurrentUser()['id'];

    // Get user info (phone, preferences)
    $stmt = $pdo->prepare('
        SELECT u.notification_phone, u.first_name,
               up.sms_notifications_enabled
        FROM users u
        LEFT JOIN user_preferences up ON u.id = up.user_id
        WHERE u.id = ?
        LIMIT 1
    ');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }

    // Check if SMS enabled in preferences
    if (!$user['sms_notifications_enabled']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'SMS notifications are disabled in your preferences'
        ]);
        exit;
    }

    // Check if phone number configured
    if (!$user['notification_phone']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Phone number not configured. Go to Settings to add it.'
        ]);
        exit;
    }

    // Get all incomplete habits for today (all periods)
    $stmt = $pdo->prepare('
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
    ');
    $stmt->execute([$user_id, $user_id]);
    $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($habits)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'No incomplete habits for today!',
            'status' => 'all-done',
            'count' => 0
        ]);
        exit;
    }

    // Send SMS with incomplete habits
    $smsService = new EasySendSMSService($pdo);
    
    // Build minimal SMS message - just habit names separated by commas
    $habit_names = array_map(function($h) {
        return substr($h['habit_name'], 0, 30);
    }, $habits);
    
    $message = implode(", ", $habit_names);
    
    // Truncate to SMS length if needed
    if (strlen($message) > 160) {
        $message = substr($message, 0, 155) . '...';
    }
    
    // Send directly
    $result = $smsService->send(
        $user['notification_phone'],
        $message,
        $user_id
    );

    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'SMS sent successfully!',
            'count' => count($habits)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['message'] ?? 'Failed to send SMS'
        ]);
    }

} catch (Exception $e) {
    restore_error_handler();
    http_response_code(500);
    
    // Log the detailed error if Logger available
    if (class_exists('Logger')) {
        Logger::error('SMS Send Error', [
            'endpoint' => 'habits/send-sms-on-demand.php',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

restore_error_handler();
