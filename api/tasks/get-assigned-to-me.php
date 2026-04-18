<?php
/**
 * Get tasks assigned to current user
 * Returns tasks where assigned_to = current user
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    
    // Get tasks assigned to current user
    $stmt = $pdo->prepare('
        SELECT 
            t.*,
            u.first_name as assigned_by_first,
            u.last_name as assigned_by_last,
            u.email as assigned_by_email
        FROM tasks t
        LEFT JOIN users u ON t.assigned_by = u.id
        WHERE t.assigned_to = ?
        AND t.status != "completed"
        AND t.status != "archived"
        ORDER BY t.assignment_date DESC, t.due_date ASC
    ');
    $stmt->execute([$user['id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess($tasks, 'Assigned tasks retrieved successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
