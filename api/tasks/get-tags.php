<?php
/**
 * Get all tags for the current user
 * Returns: Array of tag objects with id, name, color_hex, and usage count
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    
    // Get all tags for this user
    $stmt = $pdo->prepare('
        SELECT id, name, color_hex
        FROM tags
        WHERE user_id = ?
        ORDER BY name ASC
    ');
    $stmt->execute([$user['id']]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess($tags, 'Tags retrieved successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
