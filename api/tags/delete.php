<?php
/**
 * ADHD Dashboard - Tags API: Delete
 * DELETE /api/tags/delete.php
 * 
 * Query params:
 * - id: Tag ID to delete (required)
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { "id": "deleted_id" },
 *   "message": "Tag deleted successfully"
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

// Get tag ID
$tag_id = $_GET['id'] ?? null;

if (!$tag_id) {
    jsonError('Tag ID required', 400);
}

try {
    $pdo = db();

    // Verify tag belongs to user
    $verify = $pdo->prepare("SELECT id FROM tags WHERE id = :id AND user_id = :user_id");
    $verify->execute([':id' => $tag_id, ':user_id' => $user['id']]);
    
    if (!$verify->fetch()) {
        jsonError('Tag not found', 404);
    }

    // Delete the tag (cascade will remove from task_tags)
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $tag_id, ':user_id' => $user['id']]);

    jsonSuccess(['id' => $tag_id], 'Tag deleted successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
