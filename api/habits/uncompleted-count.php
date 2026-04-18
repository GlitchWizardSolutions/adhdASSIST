<?php
/**
 * API - Get uncompleted daily habits count for current user
 * Returns JSON with count of habits not yet completed for today
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/database.php';

// Require authentication
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $user = Auth::getCurrentUser();
    $user_id = $user['id'];
    $pdo = Database::getInstance();

    // Get uncompleted habits for today
    // Habits reset at user's daily_habits_reset_time (default midnight)
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as uncompleted
        FROM daily_habits dh
        WHERE dh.user_id = ? AND dh.is_active = TRUE
        AND NOT EXISTS (
            SELECT 1 FROM habit_completions hc
            WHERE hc.habit_id = dh.id
            AND DATE(hc.completed_at) = CURDATE()
        )
    ');
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $uncompleted_count = $result['uncompleted'] ?? 0;

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'uncompleted' => $uncompleted_count
    ]);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
