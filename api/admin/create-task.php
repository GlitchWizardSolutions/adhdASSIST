<?php
/**
 * Create task with optional assignment (Admin only)
 * Allows admins to create tasks and assign them to users
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
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['title'])) {
        jsonError('Task title is required', 400);
    }

    $pdo = db();
    
    // Determine user_id for the task
    // If assigned_to is specified, that's who gets the task
    // Otherwise, create it for the admin with no assignment
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $priority = $data['priority'] ?? 'medium';
    $due_date = !empty($data['due_date']) ? $data['due_date'] : null;
    $assigned_to = !empty($data['assigned_to']) ? intval($data['assigned_to']) : null;
    
    // The task is created for the person it's assigned to
    // If no assignment, create it for the admin (current user)
    $task_user_id = $assigned_to ?? $user['id'];
    
    // Create the task
    $stmt = $pdo->prepare('
        INSERT INTO tasks (
            user_id,
            title,
            description,
            priority,
            due_date,
            assigned_to,
            assigned_by,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ');

    $stmt->execute([
        $task_user_id,
        $title,
        $description,
        $priority,
        $due_date,
        $assigned_to,
        $assigned_to ? $user['id'] : null,  // Only set assigned_by if actually assigning to someone
        'inbox'
    ]);

    $task_id = $pdo->lastInsertId();

    jsonSuccess([
        'id' => $task_id,
        'title' => $title,
        'assigned_to' => $assigned_to,
        'assigned_by' => $user['id']
    ], 'Task created successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
