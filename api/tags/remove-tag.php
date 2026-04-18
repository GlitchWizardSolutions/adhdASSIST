<?php
/**
 * ADHD Dashboard - Tags API: Remove tag from task
 * DELETE /api/tags/remove-tag.php
 * 
 * Query params:
 * - task_id: Task ID (required)
 * - tag_id: Tag ID (required)
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { "task_id": "x", "tag_id": "y" },
 *   "message": "Tag removed from task successfully"
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

// Get params
$task_id = $_GET['task_id'] ?? null;
$tag_id = $_GET['tag_id'] ?? null;

if (!$task_id || !$tag_id) {
    jsonError('task_id and tag_id required', 400);
}

try {
    $pdo = db();

    // Verify task belongs to user
    $verify_task = $pdo->prepare("SELECT id FROM tasks WHERE id = :id AND user_id = :user_id");
    $verify_task->execute([':id' => $task_id, ':user_id' => $user['id']]);
    if (!$verify_task->fetch()) {
        jsonError('Task not found', 404);
    }

    // Verify tag belongs to user
    $verify_tag = $pdo->prepare("SELECT id FROM tags WHERE id = :id AND user_id = :user_id");
    $verify_tag->execute([':id' => $tag_id, ':user_id' => $user['id']]);
    if (!$verify_tag->fetch()) {
        jsonError('Tag not found', 404);
    }

    // Remove tag from task
    $stmt = $pdo->prepare("DELETE FROM task_tags WHERE task_id = :task_id AND tag_id = :tag_id");
    $stmt->execute([':task_id' => $task_id, ':tag_id' => $tag_id]);

    jsonSuccess([
        'task_id' => $task_id,
        'tag_id' => $tag_id
    ], 'Tag removed from task successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
