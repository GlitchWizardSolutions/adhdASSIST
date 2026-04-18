<?php
/**
 * Get list of users that can be assigned tasks
 * Returns all users for admin task delegation
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    
    // Get all users (for task assignment dropdown)
    $stmt = $pdo->prepare('
        SELECT id, first_name, last_name, email
        FROM users
        ORDER BY first_name ASC, last_name ASC
    ');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for display
    $formattedUsers = array_map(function($u) {
        return [
            'id' => intval($u['id']),
            'name' => trim($u['first_name'] . ' ' . $u['last_name']) ?: $u['email'],
            'email' => $u['email']
        ];
    }, $users);

    jsonSuccess($formattedUsers, 'Users retrieved successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
