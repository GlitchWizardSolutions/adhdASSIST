<?php
/**
 * Get upcoming reminders for the user
 * Returns reminders that are scheduled for the future and haven't been sent yet
 */

session_start();

// Check authentication
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../../../private/lib/config.php';

// Verify user is authenticated
$user = Auth::getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get upcoming reminders (next 7 days, not yet sent)
    $query = "
        SELECT 
            r.id,
            r.reminder_type,
            r.remind_at,
            t.id as task_id,
            t.title,
            t.priority
        FROM reminders r
        LEFT JOIN tasks t ON r.task_id = t.id
        WHERE r.user_id = :user_id
        AND r.is_sent = 0
        AND r.remind_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        AND r.remind_at > NOW()
        ORDER BY r.remind_at ASC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $user['id']]);
    
    $reminders = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format reminder type
        $type_label = match($row['reminder_type']) {
            'due_date' => 'Task Due',
            'refill_reminder' => 'Medication Refill',
            'follow_up' => 'Follow-up',
            'inactive_user' => 'Account Reminder',
            'system' => 'System Notice',
            default => 'Reminder'
        };
        
        $reminders[] = [
            'id' => $row['id'],
            'type' => $row['reminder_type'],
            'type_label' => $type_label,
            'reminder_at' => $row['remind_at'],
            'task_id' => $row['task_id'],
            'title' => $row['title'],
            'priority' => $row['priority']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $reminders,
        'total' => count($reminders)
    ]);
    
} catch (Exception $e) {
    // Graceful degradation for new installations
    echo json_encode([
        'success' => true,
        'data' => [],
        'total' => 0
    ]);
}
?>
