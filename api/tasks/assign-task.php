<?php
/**
 * Assign a task to a user (admin only)
 * POST with task_id and assigned_to user_id
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    $data = getJsonInput();
    
    $task_id = $data['task_id'] ?? null;
    $assigned_to_id = $data['assigned_to'] ?? null;

    if (!$task_id) {
        jsonError('task_id required', 400);
    }

    // Verify task exists
    $stmt = $pdo->prepare('SELECT id, user_id FROM tasks WHERE id = ?');
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        jsonError('Task not found', 404);
    }

    // If assigned_to is provided, verify the user exists
    if ($assigned_to_id) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$assigned_to_id]);
        if (!$stmt->fetch()) {
            jsonError('Assigned user not found', 404);
        }
    }

    // Update task with assignment
    $stmt = $pdo->prepare('
        UPDATE tasks 
        SET assigned_to = ?, assigned_by = ?, assignment_date = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$assigned_to_id, $user['id'], $task_id]);

    jsonSuccess(null, 'Task assigned successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
