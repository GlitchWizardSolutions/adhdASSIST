<?php
/**
 * Save/update tags for a specific task
 * Request: POST with task_id and tag_ids array
 * Replaces all existing tags for the task
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
    $tag_ids = $data['tag_ids'] ?? [];

    if (!$task_id) {
        jsonError('task_id required', 400);
    }

    // Verify task belongs to user
    $stmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
    $stmt->execute([$task_id, $user['id']]);
    $task = $stmt->fetch();

    if (!$task) {
        jsonError('Task not found or unauthorized', 403);
    }

    // Start transaction
    $pdo->beginTransaction();

    // Delete all existing tags for this task
    $stmt = $pdo->prepare('DELETE FROM task_tags WHERE task_id = ?');
    $stmt->execute([$task_id]);

    // Add new tags
    if (!empty($tag_ids)) {
        $stmt = $pdo->prepare('INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)');
        foreach ($tag_ids as $tag_id) {
            // Verify tag belongs to user
            $verify = $pdo->prepare('SELECT id FROM tags WHERE id = ? AND user_id = ?');
            $verify->execute([$tag_id, $user['id']]);
            if ($verify->fetch()) {
                $stmt->execute([$task_id, $tag_id]);
            }
        }
    }

    $pdo->commit();

    jsonSuccess(null, 'Tags updated successfully');

} catch (Exception $e) {
    $pdo->rollBack();
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
