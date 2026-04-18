<?php
/**
 * API - Get incomplete daily habits list for today
 * Returns JSON with list of habits not yet completed for today
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

    // Get incomplete habits for today with their period info
    $stmt = $pdo->prepare('
        SELECT 
            dh.id,
            dh.habit_name,
            CASE 
                WHEN dh.is_morning = TRUE THEN "Morning"
                WHEN dh.is_afternoon = TRUE THEN "Afternoon"
                WHEN dh.is_evening = TRUE THEN "Evening"
                ELSE "Daily"
            END as period
        FROM daily_habits dh
        WHERE dh.user_id = ? 
        AND dh.is_active = TRUE
        AND NOT EXISTS (
            SELECT 1 FROM habit_completions hc
            WHERE hc.habit_id = dh.id
            AND DATE(hc.completed_at) = CURDATE()
        )
        ORDER BY 
            FIELD(period, "Morning", "Afternoon", "Evening", "Daily"),
            dh.sort_order ASC
    ');
    $stmt->execute([$user_id]);
    $incomplete_habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'habits' => $incomplete_habits,
        'count' => count($incomplete_habits)
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
