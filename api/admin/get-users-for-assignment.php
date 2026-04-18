<?php
/**
 * Get users for task assignment dropdown
 * Only available to admin/developer users
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication and admin role
$user = requireAuthenticatedUser();
if (!in_array($user['role'], ['admin', 'developer'])) {
    jsonError('Unauthorized', 403);
    exit;
}

try {
    $pdo = db();
    
    // Get all active users except the current admin
    $stmt = $pdo->prepare('
        SELECT id, first_name, last_name, email
        FROM users
        WHERE is_active = 1
        AND id != ?
        ORDER BY first_name, last_name
    ');
    $stmt->execute([$user['id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess($users, 'Users retrieved successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
