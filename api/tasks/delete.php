<?php
/**
 * ADHD Dashboard - Task API: Delete task
 * DELETE /api/tasks/delete.php
 * 
 * Body:
 * {
 *   "task_id": 1
 * }
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only DELETE allowed
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonError('Method not allowed', 405);
}

// Require authentication
$user = requireAuthenticatedUser();

// Get input
$input = getJsonInput();

// Validate required fields
if (empty($input['task_id'])) {
    jsonError('task_id is required', 400);
}

try {
    $pdo = db();
    $taskId = (int) $input['task_id'];
    $userId = $user['id'];

    // Verify task belongs to user
    $checkStmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
    $checkStmt->execute([$taskId, $userId]);
    if (!$checkStmt->fetch()) {
        jsonError('Task not found', 404);
    }

    // Delete task (cascade deletes related records)
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
    $result = $stmt->execute([$taskId, $userId]);

    if ($result && $stmt->rowCount() > 0) {
        jsonSuccess(['task_id' => $taskId], 'Task deleted successfully');
    }

    jsonError('Failed to delete task', 500);

} catch (PDOException $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
