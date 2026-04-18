<?php
session_start();
/**
 * Admin API - Delete User
 * Permanently delete user account (prevents developer account deletion)
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

    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }

    // Cannot delete own account
    if ($data['id'] == $adminId) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete your own account']);
        exit;
    }

    // Cannot delete developer accounts
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$data['id']]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    if ($targetUser['role'] === 'developer') {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot delete developer accounts']);
        exit;
    }

    $adminDb = new AdminDB($db);
    
    if ($adminDb->deleteUser($data['id'])) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete user');
    }
} catch (Exception $e) {
    error_log("Admin API - Delete User Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
