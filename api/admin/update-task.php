<?php
session_start();
/**
 * Admin API - Update Task
 * Updates a task (admin/developer only)
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
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['task_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'task_id required']);
        exit;
    }

    $taskId = $data['task_id'];

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

    // Verify task exists and is assigned by this admin
    $stmt = $db->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_by = ?");
    $stmt->execute([$taskId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
    }

    // Build update query
    $updates = [];
    $params = [];

    if (isset($data['title'])) {
        $updates[] = "title = ?";
        $params[] = $data['title'];
    }

    if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[] = $data['description'];
    }

    if (isset($data['priority'])) {
        $updates[] = "priority = ?";
        $params[] = $data['priority'];
    }

    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $params[] = $data['status'];
    }

    if (isset($data['due_date'])) {
        $updates[] = "due_date = ?";
        $params[] = $data['due_date'] ?: null;
    }

    if (isset($data['estimated_time'])) {
        $updates[] = "estimated_duration_minutes = ?";
        $params[] = $data['estimated_time'] ?: null;
    }

    if (isset($data['assigned_to'])) {
        $updates[] = "assigned_to = ?";
        $params[] = $data['assigned_to'] ?: null;
    }

    if (empty($updates)) {
        echo json_encode(['success' => true, 'data' => ['id' => $taskId]]);
        exit;
    }

    $params[] = $taskId;
    $query = "UPDATE tasks SET " . implode(", ", $updates) . " WHERE id = ?";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'data' => ['id' => $taskId]
    ]);

} catch (Exception $e) {
    error_log("Admin update-task.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
