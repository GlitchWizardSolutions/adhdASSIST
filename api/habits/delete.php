<?php
/**
 * ADHD Dashboard - Habits API: Delete habit
 * DELETE /api/habits/delete.php
 * 
 * Body:
 * {
 *   "habit_id": 1
 * }
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only DELETE allowed
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
    
    // Verify habit belongs to user
    $checkStmt = $pdo->prepare('SELECT id FROM daily_habits WHERE id = ? AND user_id = ?');
    $checkStmt->execute([$habitId, $userId]);
    if (!$checkStmt->fetch()) {
        jsonError('Habit not found', 404);
    }
    
    // Delete habit (will cascade delete habit_completions)
    $stmt = $pdo->prepare('DELETE FROM daily_habits WHERE id = ? AND user_id = ?');
    $result = $stmt->execute([$habitId, $userId]);
    
    if ($result && $stmt->rowCount() > 0) {
        jsonSuccess([], 'Habit deleted successfully');
    }
    
    jsonError('Failed to delete habit', 500);
    
} catch (PDOException $e) {
    error_log('Habit Delete Error: ' . $e->getMessage());
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
