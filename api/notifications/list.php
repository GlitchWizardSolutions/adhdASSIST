<?php
/**
 * Get User Notifications
 * GET /api/notifications/list
 * 
 * Retrieves unread notifications for the current user
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/database.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = db();
    $user_id = Auth::getCurrentUser()['id'];
    $limit = $_GET['limit'] ?? 20;
    $offset = $_GET['offset'] ?? 0;

    // Get total unread count
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 AND expires_at > NOW()
    ');
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get notifications
    $stmt = $pdo->prepare('
        SELECT id, title, message, notification_type, related_task_id, 
               is_read, created_at, expires_at
        FROM notifications 
        WHERE user_id = ? AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$user_id, $limit, $offset]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $notifications,
        'unread_count' => $unread_count,
        'total' => count($notifications)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
