<?php
/**
 * ADHD Dashboard - Habits API: Update habit
 * PUT /api/habits/update.php
 * 
 * Body:
 * {
 *   "habit_id": 1,
 *   "habit_name": "Updated name",
 *   "habit_type": "routine|health|work|personal",
 *   "is_morning": true,
 *   "is_evening": false,
 *   "sort_order": 0
 * }
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only PUT allowed
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
    
    // Build update query
    $updates = [];
    $params = [];
    
    // Habit name
    if (array_key_exists('habit_name', $input)) {
        $name = trim($input['habit_name']);
        if (strlen($name) > 255) {
            jsonError('Habit name too long (max 255 characters)', 400);
        }
        $updates[] = 'habit_name = ?';
        $params[] = $name;
    }
    
    // Habit type
    if (array_key_exists('habit_type', $input)) {
        $type = $input['habit_type'];
        $validTypes = ['routine', 'health', 'work', 'personal'];
        if (!in_array($type, $validTypes)) {
            jsonError('Invalid habit_type', 400);
        }
        $updates[] = 'habit_type = ?';
        $params[] = $type;
    }
    
    // Morning flag
    if (array_key_exists('is_morning', $input)) {
        $updates[] = 'is_morning = ?';
        $params[] = (int) $input['is_morning'];
    }
    
    // Afternoon flag
    if (array_key_exists('is_afternoon', $input)) {
        $updates[] = 'is_afternoon = ?';
        $params[] = (int) $input['is_afternoon'];
    }
    
    // Evening flag
    if (array_key_exists('is_evening', $input)) {
        $updates[] = 'is_evening = ?';
        $params[] = (int) $input['is_evening'];
    }
    
    // Sort order
    if (array_key_exists('sort_order', $input)) {
        $updates[] = 'sort_order = ?';
        $params[] = (int) $input['sort_order'];
    }
    
    if (empty($updates)) {
        jsonError('No updates provided', 400);
    }
    
    // Add habit ID to params
    $params[] = $habitId;
    $params[] = $userId;
    
    // Execute update
    $query = 'UPDATE daily_habits SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ? AND user_id = ?';
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Fetch updated habit
        $fetchStmt = $pdo->prepare('SELECT * FROM daily_habits WHERE id = ? AND user_id = ?');
        $fetchStmt->execute([$habitId, $userId]);
        $habit = $fetchStmt->fetch();
        
        jsonSuccess($habit, 'Habit updated successfully');
    }
    
    jsonError('Failed to update habit', 500);
    
} catch (PDOException $e) {
    error_log('Habit Update Error: ' . $e->getMessage());
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
