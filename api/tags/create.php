<?php
/**
 * ADHD Dashboard - Tags API: Create
 * POST /api/tags/create.php
 * 
 * Body:
 * {
 *   "name": "Tag name (required)",
 *   "color_hex": "#3B82F6"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { tag object },
 *   "message": "Tag created successfully"
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
$required = ['name'];
$missing = validateRequired($input, $required);
if (!empty($missing)) {
    jsonError('Missing required fields: ' . implode(', ', $missing), 400);
}

try {
    $pdo = db();

    // Check if tag name already exists for this user
    $check = $pdo->prepare("SELECT id FROM tags WHERE user_id = :user_id AND name = :name");
    $check->execute([':user_id' => $user['id'], ':name' => $input['name']]);
    
    if ($check->fetch()) {
        jsonError('Tag with this name already exists', 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tags (user_id, name, color_hex)
        VALUES (:user_id, :name, :color_hex)
    ");

    $stmt->execute([
        ':user_id' => $user['id'],
        ':name' => $input['name'],
        ':color_hex' => $input['color_hex'] ?? '#3B82F6'
    ]);

    $tag_id = $pdo->lastInsertId();

    // Fetch the created tag
    $fetch = $pdo->prepare("SELECT * FROM tags WHERE id = :id");
    $fetch->execute([':id' => $tag_id]);
    $tag = $fetch->fetch(PDO::FETCH_ASSOC);

    jsonSuccess($tag, 'Tag created successfully', 201);

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
