<?php
/**
 * ADHD Dashboard - Habits API: Create new habit
 * POST /api/habits/create.php
 * 
 * Body:
 * {
 *   "habit_name": "Morning meditation",
 *   "habit_type": "routine|health|work|personal",
 *   "is_morning": true,
 *   "is_evening": false
 * }
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

// Validate required fields
if (empty($input['habit_name'])) {
    jsonError('habit_name is required', 400);
}

try {
    $pdo = db();
    $userId = $user['id'];
    
    // Trim and validate name
    $habitName = trim($input['habit_name']);
    if (strlen($habitName) > 255) {
        jsonError('Habit name too long (max 255 characters)', 400);
    }
    
    $habitType = $input['habit_type'] ?? 'routine';
    $validTypes = ['routine', 'health', 'work', 'personal'];
    if (!in_array($habitType, $validTypes)) {
        $habitType = 'routine';
    }
    
    $isMorning = $input['is_morning'] ?? true;
    $isAfternoon = $input['is_afternoon'] ?? false;
    $isEvening = $input['is_evening'] ?? false;
    
    // At least one should be true
    if (!$isMorning && !$isAfternoon && !$isEvening) {
        jsonError('Habit must be selected for at least one time period (morning, afternoon, or evening)', 400);
    }
    
    // Get max sort order for user
    $maxStmt = $pdo->prepare('SELECT MAX(sort_order) as max_order FROM daily_habits WHERE user_id = ?');
    $maxStmt->execute([$userId]);
    $max = $maxStmt->fetch();
    $sortOrder = ($max['max_order'] ?? -1) + 1;
    
    // Create habit
    $stmt = $pdo->prepare('
        INSERT INTO daily_habits (user_id, habit_name, habit_type, is_morning, is_afternoon, is_evening, sort_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)
    ');
    
    $result = $stmt->execute([
        $userId,
        $habitName,
        $habitType,
        (int) $isMorning,
        (int) $isAfternoon,
        (int) $isEvening,
        $sortOrder
    ]);
    
    if ($result) {
        $habitId = $pdo->lastInsertId();
        
        // Fetch created habit
        $fetchStmt = $pdo->prepare('SELECT * FROM daily_habits WHERE id = ? AND user_id = ?');
        $fetchStmt->execute([$habitId, $userId]);
        $habit = $fetchStmt->fetch();
        
        jsonSuccess($habit, 'Habit created successfully');
    }
    
    jsonError('Failed to create habit', 500);
    
} catch (PDOException $e) {
    error_log('Habit Create Error: ' . $e->getMessage());
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
