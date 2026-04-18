<?php
/**
 * ADHD Dashboard - Send Today's Tasks via SMS (On-Demand)
 * POST /api/tasks/send-sms-on-demand
 * 
 * Sends SMS with incomplete tasks for today to the authenticated user.
 * Used for on-demand manual sending (regardless of quiet hours or time).
 * Includes tasks that are: active, scheduled for today, or high priority.
 * 
 * Usage:
 * POST /api/tasks/send-sms-on-demand
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

    // Get incomplete tasks (active or scheduled for today, excluding completed/cancelled)
    $stmt = $pdo->prepare('
        SELECT id, title, priority, status
        FROM tasks 
        WHERE user_id = ? 
            AND status != "completed" 
            AND status != "cancelled"
            AND (
                status = "active" 
                OR (due_date IS NOT NULL AND DATE(due_date) = CURDATE())
                OR priority = "high"
            )
        ORDER BY 
            CASE priority 
                WHEN "high" THEN 1 
                WHEN "medium" THEN 2 
                WHEN "low" THEN 3 
                ELSE 4 
            END,
            due_date ASC,
            created_at DESC
        LIMIT 20
    ');
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tasks)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'No tasks to complete today!',
            'status' => 'all-done',
            'count' => 0
        ]);
        exit;
    }

    // Format tasks for SMS - minimal text to save characters
    $smsService = new EasySendSMSService($pdo);
    
    // Build SMS message with tasks only (comma-separated, no headers)
    $task_names = array_map(function($task) {
        return substr($task['title'], 0, 35);
    }, $tasks);
    
    $message = implode(", ", $task_names);
    
    // Truncate to SMS length limit if needed
    if (strlen($message) > 160) {
        $message = substr($message, 0, 155) . '...';
    }

    // Send via SMS service
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
            'count' => $count,
            'preview' => $message
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
            'endpoint' => 'tasks/send-sms-on-demand.php',
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
