<?php
/**
 * ADHD Dashboard - Habits API: Toggle habit completion
 * POST /api/habits/toggle.php
 * 
 * Body:
 * {
 *   "habit_id": 1,
 *   "date": "2026-04-03"  // optional, defaults to today
 * }
 * 
 * Returns: { completed: true/false }
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Require authentication
$user = requireAuthenticatedUser();

// Get input
$input = getJsonInput();

if (empty($input['habit_id'])) {
    jsonError('habit_id is required', 400);
}

try {
    $pdo = db();
    $userId = $user['id'];
    $habitId = (int) $input['habit_id'];
    $date = $input['date'] ?? date('Y-m-d');
    
    // Validate date
    if (!strtotime($date)) {
        jsonError('Invalid date format (use YYYY-MM-DD)', 400);
    }
    
    // Verify habit belongs to user
    $checkStmt = $pdo->prepare('SELECT id FROM daily_habits WHERE id = ? AND user_id = ?');
    $checkStmt->execute([$habitId, $userId]);
    if (!$checkStmt->fetch()) {
        jsonError('Habit not found', 404);
    }
    
    // Check if already completed today
    $existsStmt = $pdo->prepare('
        SELECT id FROM habit_completions 
        WHERE habit_id = ? AND user_id = ? AND completion_date = ?
    ');
    $existsStmt->execute([$habitId, $userId, $date]);
    $exists = $existsStmt->fetch();
    
    if ($exists) {
        // Remove completion (uncheck)
        $deleteStmt = $pdo->prepare('
            DELETE FROM habit_completions 
            WHERE habit_id = ? AND user_id = ? AND completion_date = ?
        ');
        $deleteStmt->execute([$habitId, $userId, $date]);
        $completed = false;
    } else {
        // Add completion (check)
        $insertStmt = $pdo->prepare('
            INSERT INTO habit_completions (user_id, habit_id, completion_date)
            VALUES (?, ?, ?)
        ');
        $insertStmt->execute([$userId, $habitId, $date]);
        $completed = true;
    }
    
    jsonSuccess(['completed' => $completed], 'Habit toggled successfully');
    
} catch (PDOException $e) {
    error_log('Habit Toggle Error: ' . $e->getMessage());
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
