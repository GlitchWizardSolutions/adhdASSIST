<?php
session_start();
/**
 * Admin API - Get Single Task
 * Returns details of a single task by ID
 */

require_once dirname(__DIR__, 4) . '/private/lib/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $taskId = $_GET['task_id'] ?? null;

    if (!$taskId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'task_id parameter required']);
        exit;
    }

    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify admin/developer role
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current || !in_array($current['role'], ['admin', 'developer'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    // Fetch task details
    $stmt = $db->prepare("
        SELECT 
            t.id,
            t.title,
            t.description,
            t.priority,
            t.status,
            t.due_date,
            t.assigned_to,
            t.assigned_by,
            t.assignment_date,
            t.estimated_duration_minutes,
            CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assignee_name,
            u.email as assignee_email
        FROM tasks t
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.id = ? AND (t.assigned_by = ? OR t.user_id = ?)
    ");
    $stmt->execute([$taskId, $userId, $userId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
    }

    // Fetch tags for this task
    $stmt = $db->prepare("
        SELECT t.id, t.name, t.color_hex
        FROM tags t
        JOIN task_tags tt ON t.id = tt.tag_id
        WHERE tt.task_id = ?
        ORDER BY t.name
    ");
    $stmt->execute([$taskId]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $task['tags'] = $tags;

    echo json_encode([
        'success' => true,
        'data' => $task
    ]);

} catch (Exception $e) {
    error_log("Admin get-task.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
