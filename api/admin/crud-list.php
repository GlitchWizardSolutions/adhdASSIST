<?php
session_start();
/**
 * Admin API - Get CRUD Templates & Permissions
 * Returns list of templates and current user permissions
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

    // Get target user ID (for viewing/managing permissions)
    $targetUserId = $_GET['user_id'] ?? null;
    if (!$targetUserId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }

    $adminDb = new AdminDB($db);

    // Get all templates
    $templates = $adminDb->getCRUDTemplates();

    // Get user's current permissions
    $userPermissions = $adminDb->getUserTemplatePermissions($targetUserId);

    // Add permission status to each template
    $templatesWithStatus = array_map(function ($template) use ($userPermissions) {
        $template['granted'] = in_array($template['id'], $userPermissions);
        return $template;
    }, $templates);

    echo json_encode([
        'success' => true,
        'templates' => $templatesWithStatus,
        'userPermissions' => $userPermissions,
    ]);
} catch (Exception $e) {
    error_log("Admin API - CRUD Templates Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
