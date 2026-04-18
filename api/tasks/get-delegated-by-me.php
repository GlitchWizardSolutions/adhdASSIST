<?php
/**
 * Get tasks delegated by current user
 * Returns tasks that I created and assigned to someone
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    
    // Get tasks delegated by current user (assigned_by = current user)
    $stmt = $pdo->prepare('
        SELECT 
            t.*,
            u.first_name as assigned_to_first,
            u.last_name as assigned_to_last,
            u.email as assigned_to_email
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.assigned_by = ?
        AND t.assigned_to IS NOT NULL
        AND t.status != "archived"
        ORDER BY t.assignment_date DESC, t.due_date ASC
    ');
    $stmt->execute([$user['id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess($tasks, 'Delegated tasks retrieved successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
