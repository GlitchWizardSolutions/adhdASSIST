<?php
/**
 * ADHD Dashboard - Tags API: Read
 * GET /api/tags/read.php
 * 
 * Query params (optional):
 * - task_id: Get tags for specific task
 * - id: Get specific tag by ID
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": [array of tag objects],
 *   "message": "Tags retrieved successfully"
 * }
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

// Require authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();

    $tag_id = $_GET['id'] ?? null;
    $task_id = $_GET['task_id'] ?? null;

    if ($tag_id) {
        // Get specific tag
        $stmt = $pdo->prepare("
            SELECT * FROM tags 
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([
            ':id' => $tag_id,
            ':user_id' => $user['id']
        ]);
        
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tag) {
            jsonError('Tag not found', 404);
        }
        
        jsonSuccess($tag, 'Tag retrieved successfully');
    } elseif ($task_id) {
        // Get tags for specific task
        $stmt = $pdo->prepare("
            SELECT t.* FROM tags t
            INNER JOIN task_tags tt ON t.id = tt.tag_id
            INNER JOIN tasks tk ON tt.task_id = tk.id
            WHERE tt.task_id = :task_id AND tk.user_id = :user_id AND t.user_id = :user_id
            ORDER BY t.name ASC
        ");
        $stmt->execute([
            ':task_id' => $task_id,
            ':user_id' => $user['id']
        ]);
        
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonSuccess($tags, 'Tags retrieved successfully');
    } else {
        // Get all tags for user
        $stmt = $pdo->prepare("
            SELECT * FROM tags 
            WHERE user_id = :user_id
            ORDER BY name ASC
        ");
        $stmt->execute([':user_id' => $user['id']]);
        
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonSuccess($tags, 'Tags retrieved successfully');
    }

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
