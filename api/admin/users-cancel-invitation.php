<?php
session_start();
/**
 * Admin API - Cancel User Invitation
 * Deletes/cancels a pending invitation
 */

require_once dirname(__DIR__, 2) . '/lib/config.php';
require_once dirname(__DIR__, 2) . '/lib/admin-db.php';

header('Content-Type: application/json');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Check authorization
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

    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['invitation_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invitation ID required']);
        exit;
    }

    // Cancel the invitation (mark as cancelled instead of deleting)
    $stmt = $db->prepare("UPDATE invitations SET cancelled_at = NOW() WHERE id = ? AND accepted_at IS NULL");
    $stmt->execute([$data['invitation_id']]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invitation not found or already accepted']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Admin API - Cancel Invitation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    error_log("Admin API - Cancel Invitation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
