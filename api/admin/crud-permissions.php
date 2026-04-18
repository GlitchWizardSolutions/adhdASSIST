<?php
session_start();
/**
 * Admin API - Update CRUD Template Permissions
 * Grant or revoke template access for a user
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

    if (empty($data['user_id']) || empty($data['template_id']) || empty($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID, Template ID, and action are required']);
        exit;
    }

    if (!in_array($data['action'], ['grant', 'revoke'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    $adminDb = new AdminDB($db);

    if ($data['action'] === 'grant') {
        $result = $adminDb->grantTemplateAccess($data['user_id'], $data['template_id']);
        $message = 'Template access granted';
    } else {
        $result = $adminDb->revokeTemplateAccess($data['user_id'], $data['template_id']);
        $message = 'Template access revoked';
    }

    if ($result) {
        // Get updated permissions
        $permissions = $adminDb->getUserTemplatePermissions($data['user_id']);
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'permissions' => $permissions,
        ]);
    } else {
        throw new Exception('Failed to update permissions');
    }
} catch (Exception $e) {
    error_log("Admin API - CRUD Permissions Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
