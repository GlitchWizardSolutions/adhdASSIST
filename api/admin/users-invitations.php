<?php
session_start();
/**
 * Admin API - Get Pending Invitations
 * Returns list of pending user invitations
 */

require_once dirname(__DIR__, 4) . '/private/lib/config.php';
require_once dirname(__DIR__, 2) . '/lib/config.php';
require_once dirname(__DIR__, 2) . '/lib/auth.php';
require_once dirname(__DIR__, 2) . '/lib/admin-db.php';

header('Content-Type: application/json');

try {
    // Check authentication using modern Auth class
    $user = Auth::getCurrentUser();
    if (!$user || !in_array($user['role'], ['admin', 'developer'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized', 'success' => false]);
        exit;
    }

    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get status filter (default to pending)
    $status = $_GET['status'] ?? 'pending';

    // Query invitations
    $invitations = [];
    if ($status === 'pending') {
        $query = "
            SELECT 
                id,
                email,
                token,
                created_at,
                expires_at
            FROM invitations 
            WHERE accepted_at IS NULL AND expires_at > NOW()
            ORDER BY created_at DESC
        ";
    } else {
        // Expired or accepted invitations
        $query = "
            SELECT 
                id,
                email,
                token,
                created_at,
                accepted_at
            FROM invitations 
            WHERE (accepted_at IS NOT NULL OR expires_at <= NOW())
            ORDER BY created_at DESC
        ";
    }

    $stmt = $db->prepare($query);
    $stmt->execute();
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $invitations
    ]);

} catch (PDOException $e) {
    error_log("Admin API - Invitations DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("Admin API - Invitations Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'success' => false, 'message' => $e->getMessage()]);
}
