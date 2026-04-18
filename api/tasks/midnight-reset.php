<?php
/**
 * Midnight Task Reset - Auto-fill empty 1-3-5 slots
 * 
 * At midnight, this endpoint:
 * - Keeps uncompleted tasks in their current slots
 * - Auto-fills empty slots with available tasks
 * - Respects 1-3-5 priority structure
 */

session_start();
require_once __DIR__ . '/../config.php';

// Protect endpoint
header('Content-Type: application/json');
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    $userId = $user['id'];
    
    // Get all active tasks for this user
    $stmt = $pdo->prepare('
        SELECT id, title, status, status_today, is_completed
        FROM tasks
        WHERE user_id = ? AND status != "archived" AND status != "completed"
        ORDER BY created_at ASC
    ');
    $stmt->execute([$userId]);
    $allTasks = $stmt->fetchAll();
    
    // Group tasks by current status_today
    $currentAssignments = [
        'urgent' => [],
        'secondary' => [],
        'calm' => []
    ];
    
    $unassigned = [];
    
    foreach ($allTasks as $task) {
        if ($task['status_today'] && $task['status_today'] !== 'inbox') {
            $currentAssignments[$task['status_today']][] = $task;
        } else {
            $unassigned[] = $task;
        }
    }
    
    // Determine how many slots are empty in each priority
    $slots_needed = [
        'urgent' => 1,    // Need 1 big task
        'secondary' => 3, // Need 3 medium tasks
        'calm' => 5       // Need 5 quick wins
    ];
    
    $filled = [
        'urgent' => [],
        'secondary' => [],
        'calm' => []
    ];
    
    // Keep uncompleted tasks in their slots
    foreach ($currentAssignments as $priority => $tasks) {
        foreach ($tasks as $task) {
            if (!$task['is_completed']) {
                $filled[$priority][] = $task['id'];
            }
        }
    }
    
    // Collect tasks to fill empty slots, prioritizing by status first
    $toFill = [
        'urgent' => [],
        'secondary' => [],
        'calm' => []
    ];
    
    foreach ($unassigned as $task) {
        $priority = $task['status'] ?? 'calm';
        if ($priority === 'inbox') $priority = 'calm';
        
        // Only add to toFill if this priority needs more tasks
        if (count($filled[$priority]) + count($toFill[$priority]) < $slots_needed[$priority]) {
            $toFill[$priority][] = $task;
        }
    }
    
    // Also try to fill from unassigned tasks with unspecified priority
    foreach ($unassigned as $task) {
        if (!$task['status'] || $task['status'] === 'inbox') {
            foreach ($slots_needed as $priority => $needed) {
                if (count($filled[$priority]) + count($toFill[$priority]) < $needed) {
                    $toFill[$priority][] = $task;
                    break;
                }
            }
        }
    }
    
    // Update tasks to their new status_today
    $updates = [];
    $updateStmt = $pdo->prepare('UPDATE tasks SET status_today = ? WHERE id = ? AND user_id = ?');
    
    foreach ($toFill as $priority => $tasks) {
        foreach ($tasks as $task) {
            $updateStmt->execute([$priority, $task['id'], $userId]);
            $updates[] = ['id' => $task['id'], 'status_today' => $priority];
        }
    }
    
    // Return success with details of what was filled
    jsonSuccess([
        'filled' => $toFill,
        'total_slots_filled' => array_sum(array_map('count', $toFill)),
        'updated_tasks' => $updates
    ], 'Midnight reset completed');
    
} catch (PDOException $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}
