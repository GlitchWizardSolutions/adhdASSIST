<?php
session_start();
/**
 * Admin API - Update Task
 * Admin can reassign, reschedule, mark complete, or change priority
 */

require_once dirname(__DIR__, 4) . '/private/lib/config.php';
require_once dirname(__DIR__, 2) . '/lib/admin-db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $adminId = $_SESSION['user_id'];
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify admin/developer role
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current || !in_array($current['role'], ['admin', 'developer'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Task ID required']);
        exit;
    }

    // Verify task exists and was created by this admin
    $stmt = $db->prepare("SELECT id FROM tasks WHERE id = ? AND created_by = ?");
    $stmt->execute([$data['id'], $adminId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Task not found or not authorized']);
        exit;
    }

    // Build update data (only allow specific fields)
    $updateData = [];
    $allowedFields = ['assigned_to', 'due_date', 'status', 'priority', 'description'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateData[$field] = $data[$field];
        }
    }

    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }

    // Validate status values
    if (isset($updateData['status']) && !in_array($updateData['status'], ['not_started', 'in_progress', 'completed'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status value']);
        exit;
    }

    $adminDb = new AdminDB($db);

    if ($adminDb->updateTask($data['id'], $updateData)) {
        // Fetch updated task
        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$data['id']]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Task updated successfully',
            'task' => $task,
        ]);
    } else {
        throw new Exception('Failed to update task');
    }
} catch (Exception $e) {
    error_log("Admin API - Update Task Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
