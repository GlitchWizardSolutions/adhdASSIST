<?php
/**
 * Get recently completed delegated tasks for admin notification bell
 * Returns tasks completed by users that were delegated by admins
 */

session_start();

// Check authentication and admin/developer role
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once dirname(__DIR__, 4) . '/private/lib/config.php';

// Verify admin/developer role
$user = Auth::getCurrentUser();
if (!$user || !in_array($user['role'], ['admin', 'developer'])) {
    http_response_code(403);
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
    
    // Get current user ID
    $currentUserId = $user['id'];
    
    // Get recently completed delegated tasks (last 7 days, limit to 10)
    // Tasks delegated by the current admin that have been completed
    $query = "
        SELECT 
            t.id,
            t.title,
            t.completed_date,
            u.username as user_name,
            u.first_name,
            u.last_name
        FROM tasks t
        JOIN users u ON t.assigned_to = u.id
        WHERE t.assigned_by = :admin_id
        AND t.status = 'completed' 
        AND t.completed_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY t.completed_date DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':admin_id' => $currentUserId]);
    $tasks = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tasks[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'user_name' => $row['first_name'] && $row['last_name'] 
                ? $row['first_name'] . ' ' . $row['last_name']
                : $row['username'],
            'completed_at' => $row['completed_date']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tasks,
        'total' => count($tasks)
    ]);
    
} catch (Exception $e) {
    // Return error with details for debugging
    error_log('delegated-tasks-completed error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => 'Failed to load completed tasks',
        'message' => $e->getMessage()
    ]);
}
?>
