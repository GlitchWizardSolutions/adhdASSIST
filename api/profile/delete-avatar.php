<?php
/**
 * ADHD Dashboard - Profile Avatar Delete API
 * POST /api/profile/delete-avatar.php
 * 
 * Removes user's avatar image
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "Avatar deleted successfully"
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

try {
    $pdo = db();

    // Get current avatar URL
    $stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Delete file if exists
    if (!empty($current_user['avatar_url'])) {
        // Extract filename from URL (handles both API URL and legacy paths)
        $filename = basename(parse_url($current_user['avatar_url'], PHP_URL_PATH));
        $file_path = Config::path('private_uploads') . $filename;
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }

    // Clear avatar URL in database
    $stmt = $pdo->prepare('UPDATE users SET avatar_url = NULL WHERE id = ?');
    $stmt->execute([$user['id']]);

    jsonSuccess([], 'Avatar deleted successfully');

} catch (Exception $e) {
    jsonError('Server error: ' . $e->getMessage(), 500);
}
?>
