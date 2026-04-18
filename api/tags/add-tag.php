<?php
/**
 * ADHD Dashboard - Tags API: Add tag to task
 * POST /api/tags/add-tag.php
 * 
 * Body:
 * {
 *   "task_id": "Task ID (required)",
 *   "tag_id": "Tag ID (required)"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { "task_id": "x", "tag_id": "y" },
 *   "message": "Tag added to task successfully"
 * }
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Require authentication
$user = requireAuthenticatedUser();

// Get input
$input = getJsonInput();

// Validate required fields
$required = ['task_id', 'tag_id'];
$missing = validateRequired($input, $required);
if (!empty($missing)) {
    jsonError('Missing required fields: ' . implode(', ', $missing), 400);
}

try {
    $pdo = db();

    // Verify task belongs to user
    $verify_task = $pdo->prepare("SELECT id FROM tasks WHERE id = :id AND user_id = :user_id");
    $verify_task->execute([':id' => $input['task_id'], ':user_id' => $user['id']]);
    if (!$verify_task->fetch()) {
        jsonError('Task not found', 404);
    }

    // Verify tag belongs to user
    $verify_tag = $pdo->prepare("SELECT id FROM tags WHERE id = :id AND user_id = :user_id");
    $verify_tag->execute([':id' => $input['tag_id'], ':user_id' => $user['id']]);
    if (!$verify_tag->fetch()) {
        jsonError('Tag not found', 404);
    }

    // Check if tag already added to task
    $check = $pdo->prepare("SELECT id FROM task_tags WHERE task_id = :task_id AND tag_id = :tag_id");
    $check->execute([':task_id' => $input['task_id'], ':tag_id' => $input['tag_id']]);
    if ($check->fetch()) {
        jsonError('Tag already added to this task', 400);
    }

    // Add tag to task
    $stmt = $pdo->prepare("INSERT INTO task_tags (task_id, tag_id) VALUES (:task_id, :tag_id)");
    $stmt->execute([
        ':task_id' => $input['task_id'],
        ':tag_id' => $input['tag_id']
    ]);

    jsonSuccess([
        'task_id' => $input['task_id'],
        'tag_id' => $input['tag_id']
    ], 'Tag added to task successfully', 201);

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
