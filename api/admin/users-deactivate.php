<?php
session_start();
/**
 * Admin API - Deactivate/Reactivate User
 * Admin toggles user status (active/inactive)
 */

require_once dirname(__DIR__, 4) . '/private/lib/config.php';
require_once dirname(__DIR__, 2) . '/lib/admin-db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    if (empty($data['id']) || empty($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID and action required']);
        exit;
    }

    if (!in_array($data['action'], ['deactivate', 'reactivate'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    // Cannot deactivate developer account
    if ($data['action'] === 'deactivate' && $data['id'] == $adminId && $current['role'] === 'developer') {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot deactivate your own account']);
        exit;
    }

    $adminDb = new AdminDB($db);

    if ($data['action'] === 'deactivate') {
        $result = $adminDb->deactivateUser($data['id']);
        $message = 'User deactivated successfully';
    } else {
        $result = $adminDb->reactivateUser($data['id']);
        $message = 'User reactivated successfully';
    }

    if ($result) {
        $user = $adminDb->getUserById($data['id']);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'user' => $user,
        ]);
    } else {
        throw new Exception('Failed to update user status');
    }
} catch (Exception $e) {
    error_log("Admin API - Deactivate User Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
