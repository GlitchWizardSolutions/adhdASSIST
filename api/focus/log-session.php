<?php
/**
 * Log Focus Timer Session
 * POST /api/focus/log-session.php
 * 
 * Logs a completed focus session with optional task association.
 * 
 * Request Body:
 * {
 *   "duration_minutes": 25,
 *   "actual_duration_minutes": 25,
 *   "task_id": null,
 *   "task_focus": "Write section 3.3 spec",
 *   "distraction_backup": "Write it down and return",
 *   "was_paused": false
 * }
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/database.php';

header('Content-Type: application/json');

try {
    $user = requireAuthenticatedUser();
    $user_id = $user['id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON');
    }
    
    $duration_minutes = (int)($input['duration_minutes'] ?? 25);
    $actual_duration_minutes = (int)($input['actual_duration_minutes'] ?? $duration_minutes);
    $task_id = !empty($input['task_id']) ? (int)$input['task_id'] : null;
    $task_focus = $input['task_focus'] ?? null;
    $was_paused = (bool)($input['was_paused'] ?? false);
    
    // Validate task ownership if task_id provided
    if ($task_id) {
        $stmt = db()->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
        $stmt->execute([$task_id, $user_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Task not found or unauthorized');
        }
    }
    
    // Insert session record
    $stmt = db()->prepare('
        INSERT INTO pomodoro_sessions 
        (user_id, task_id, duration_minutes, actual_duration_minutes, status, started_at, completed_at, task_focus, was_paused)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)
    ');
    
    $stmt->execute([
        $user_id,
        $task_id,
        $duration_minutes,
        $actual_duration_minutes,
        'completed',
        $task_focus,
        $was_paused ? 1 : 0
    ]);
    
    $session_id = db()->lastInsertId();
    
    // Update user streak if applicable
    updateFocusStreak($user_id);
    
    // Check for badge unlocks
    checkFocusBadges($user_id);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'session_id' => $session_id,
            'duration_minutes' => $actual_duration_minutes,
            'message' => "Session logged! You focused for $actual_duration_minutes minutes."
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function updateFocusStreak($user_id) {
    // Check if user has session today
    $stmt = db()->prepare('
        SELECT COUNT(*) as today_count FROM pomodoro_sessions 
        WHERE user_id = ? AND DATE(completed_at) = CURDATE()
    ');
    $stmt->execute([$user_id]);
    $today_count = $stmt->fetch()['today_count'];
    
    if ($today_count == 1) {
        // First session today - update streak
        $stmt = db()->prepare('
            SELECT current_count, streak_start_date FROM streaks 
            WHERE user_id = ? AND streak_type = "pomodoro"
        ');
        $stmt->execute([$user_id]);
        $streak = $stmt->fetch();
        
        if ($streak) {
            $last_date = new DateTime($streak['streak_start_date']);
            $today = new DateTime('now');
            $yesterday = clone $today;
            $yesterday->modify('-1 day');
            
            if ($last_date->format('Y-m-d') == $yesterday->format('Y-m-d')) {
                // Streak continues
                $new_count = $streak['current_count'] + 1;
            } else if ($last_date->format('Y-m-d') == $today->format('Y-m-d')) {
                // Already counted today
                $new_count = $streak['current_count'];
            } else {
                // Streak broken, restart
                $new_count = 1;
            }
            
            $stmt = db()->prepare('
                UPDATE streaks 
                SET current_count = ?, streak_start_date = CURDATE(),
                    best_count = GREATEST(best_count, ?),
                    last_activity_date = CURDATE()
                WHERE user_id = ? AND streak_type = "pomodoro"
            ');
            $stmt->execute([$new_count, $new_count, $user_id]);
        } else {
            // Create new streak
            $stmt = db()->prepare('
                INSERT INTO streaks (user_id, streak_type, current_count, streak_start_date, best_count, last_activity_date)
                VALUES (?, "pomodoro", 1, CURDATE(), 1, CURDATE())
            ');
            $stmt->execute([$user_id]);
        }
    }
}

function checkFocusBadges($user_id) {
    // TODO: Implement badge unlock logic for focus streaks
    // Examples:
    // - "First Focus" (1 session logged)
    // - "Focus Master" (50 sessions logged)
    // - "Week of Focus" (7-day streak)
}
?>
