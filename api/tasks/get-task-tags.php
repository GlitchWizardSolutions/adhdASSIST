<?php
/**
 * Get tags for a specific task
 * Returns: Array of tag IDs already assigned to this task
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    
    $task_id = $_GET['task_id'] ?? null;

    if (!$task_id) {
        jsonError('task_id parameter required', 400);
    }

    // Verify task belongs to user
    $stmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
    $stmt->execute([$task_id, $user['id']]);
    if (!$stmt->fetch()) {
        jsonError('Task not found or unauthorized', 403);
    }

    // Get all tag IDs for this task
    $stmt = $pdo->prepare('
        SELECT tag_id FROM task_tags WHERE task_id = ?
    ');
    $stmt->execute([$task_id]);
    $tag_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    jsonSuccess(array_map('intval', $tag_ids), 'Tags retrieved successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
