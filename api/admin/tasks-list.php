<?php
session_start();
/**
 * Admin API - Get Delegated Tasks
 * Returns list of tasks assigned by the current admin
 */

require_once dirname(__DIR__, 4) . '/private/lib/config.php';
require_once dirname(__DIR__, 2) . '/lib/admin-db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $userId = $_SESSION['user_id'];
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
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    // Get filters from query params
    $filters = [
        'assigned_to' => $_GET['assigned_to'] ?? null,
        'status' => $_GET['status'] ?? null,
        'due_from' => $_GET['due_from'] ?? null,
        'due_to' => $_GET['due_to'] ?? null,
    ];

    $adminDb = new AdminDB($db);

    // Get tasks and stats
    $tasks = $adminDb->getDelegatedTasks($userId, $filters);
    $stats = $adminDb->getDelegatedTasksStats($userId);

    echo json_encode([
        'success' => true,
        'tasks' => $tasks,
        'stats' => $stats,
    ]);
} catch (Exception $e) {
    error_log("Admin API - Tasks List Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
