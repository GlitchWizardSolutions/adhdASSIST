<?php
/**
 * Get count and recent assigned tasks for current user
 * Returns count of unprocessed assigned tasks and list of recent ones
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../config.php';

// Check authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    
    // Get count of assigned tasks (not yet completed/archived)
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count
        FROM tasks
        WHERE assigned_to = ?
        AND status != "completed"
        AND status != "archived"
    ');
    $stmt->execute([$user['id']]);
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = intval($countResult['count']);
    
    // Get recent 5 assigned tasks with assigner info
    $stmt = $pdo->prepare('
        SELECT 
            t.id,
            t.title,
            t.priority,
            t.due_date,
            t.assignment_date,
            u.first_name as assigned_by_first,
            u.last_name as assigned_by_last
        FROM tasks t
        LEFT JOIN users u ON t.assigned_by = u.id
        WHERE t.assigned_to = ?
        AND t.status != "completed"
        AND t.status != "archived"
        ORDER BY t.assignment_date DESC
        LIMIT 5
    ');
    $stmt->execute([$user['id']]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess([
        'count' => $count,
        'recent' => $recent
    ], 'Assigned task info retrieved successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
