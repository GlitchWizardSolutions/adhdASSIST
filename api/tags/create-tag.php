<?php
/**
 * Create a new tag for the user
 * Can be called while editing a task
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
    $name = trim($data['name'] ?? '');
    $color_hex = $data['color_hex'] ?? '#3B82F6'; // Default blue

    if (!$name) {
        jsonError('Tag name required', 400);
    }

    if (strlen($name) > 50) {
        jsonError('Tag name too long (max 50 characters)', 400);
    }

    // Check if tag already exists for this user
    $stmt = $pdo->prepare('SELECT id FROM tags WHERE user_id = ? AND name = ?');
    $stmt->execute([$user['id'], $name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Return existing tag
        $tag_data = [
            'id' => intval($existing['id']),
            'name' => $name,
            'color_hex' => $color_hex
        ];
        jsonSuccess($tag_data, 'Tag already exists', 200);
    }

    // Create new tag
    $stmt = $pdo->prepare('
        INSERT INTO tags (user_id, name, color_hex) VALUES (?, ?, ?)
    ');
    $stmt->execute([$user['id'], $name, $color_hex]);
    $tag_id = $pdo->lastInsertId();

    $tag_data = [
        'id' => intval($tag_id),
        'name' => $name,
        'color_hex' => $color_hex
    ];

    jsonSuccess($tag_data, 'Tag created successfully', 201);

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
