<?php
/**
 * Get Task Details
 * Used by header notification dropdown to fetch full task information
 * GET /api/tasks/get-task-details.php?id=taskId
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication
$user = requireAuthenticatedUser();

// Get task ID from query parameter
$taskId = $_GET['id'] ?? null;

if (!$taskId) {
    jsonError('Task ID is required', 400);
    exit;
}

try {
    $pdo = db();
    
    // Get task details - ensure user is either the owner or assignee
    $stmt = $pdo->prepare('
        SELECT 
            t.id,
            t.title,
            t.description,
            t.priority,
            t.due_date,
            t.status,
            t.user_id,
            t.assigned_to,
            t.assigned_by,
            u.first_name as assigned_by_first,
            u.last_name as assigned_by_last
        FROM tasks t
        LEFT JOIN users u ON t.assigned_by = u.id
        WHERE t.id = ?
        AND (t.user_id = ? OR t.assigned_to = ?)
    ');
    $stmt->execute([$taskId, $user['id'], $user['id']]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        jsonError('Task not found or access denied', 404);
        exit;
    }
    
    jsonSuccess($task, 'Task details retrieved successfully');
    
} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
