<?php
/**
 * Mark Notification as Read
 * POST /api/notifications/mark-read
 * 
 * Marks a notification as read
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = db();
    $user_id = Auth::getCurrentUser()['id'];
    $notification_id = $_POST['notification_id'] ?? null;
    $mark_all = $_POST['mark_all'] ?? false;

    if ($mark_all) {
        // Mark all as read
        $stmt = $pdo->prepare('
            UPDATE notifications 
            SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ');
        $stmt->execute([$user_id]);
        $count = $stmt->rowCount();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Marked ' . $count . ' notifications as read'
        ]);
    } else if ($notification_id) {
        // Mark single as read
        $stmt = $pdo->prepare('
            UPDATE notifications 
            SET is_read = 1, read_at = NOW()
            WHERE id = ? AND user_id = ?
        ');
        $stmt->execute([$notification_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Notification not found']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'notification_id or mark_all required']);
    }

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
