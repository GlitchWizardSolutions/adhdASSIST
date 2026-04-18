<?php
/**
 * ADHD Dashboard - Task API: Update task
 * PUT /api/tasks/update.php
 * 
 * Body (all fields optional except task_id):
 * {
 *   "task_id": 1,
 *   "title": "New title",
 *   "description": "New description",
 *   "status": "inbox|backlog|scheduled|active|completed",
 *   "priority": "high|medium|low|someday",

 *   "priority_slot": 0,
 *   "due_date": "YYYY-MM-DD",
 *   "completed_date": "YYYY-MM-DD HH:MM:SS",
 *   "estimated_duration_minutes": 25,
 *   "is_recurring": false,
 *   "recurrence_pattern": "daily|weekly|biweekly|monthly"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { Updated task object },
 *   "message": "Task updated successfully"
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

// Validate required fields
if (empty($input['task_id'])) {
    jsonError('task_id is required', 400);
}

try {
    $pdo = db();
    $taskId = (int) $input['task_id'];
    $userId = $user['id'];

    // Verify task belongs to user
    $checkStmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
    $checkStmt->execute([$taskId, $userId]);
    if (!$checkStmt->fetch()) {
        jsonError('Task not found', 404);
    }

    // Build update query - only include fields that were provided
    $updates = [];
    $params = [];

    // Title
    if (array_key_exists('title', $input)) {
        if (strlen($input['title']) > 500) {
            jsonError('Title too long (max 500 characters)', 400);
        }
        if (empty(trim($input['title']))) {
            jsonError('Title cannot be empty', 400);
        }
        $updates[] = 'title = ?';
        $params[] = trim($input['title']);
    }

    // Description
    if (array_key_exists('description', $input)) {
        $updates[] = 'description = ?';
        $params[] = !empty($input['description']) ? trim($input['description']) : null;
    }

    // Status
    if (array_key_exists('status', $input)) {
        $status = $input['status'];
        $validStatuses = ['inbox', 'backlog', 'scheduled', 'active', 'completed'];
        if (!in_array($status, $validStatuses)) {
            jsonError('Invalid status value. Valid: inbox, backlog, scheduled, active, completed', 400);
        }
        $updates[] = 'status = ?';
        $params[] = $status;
    }

    // Priority
    if (array_key_exists('priority', $input)) {
        $priority = $input['priority'];
        $validPriorities = ['high', 'medium', 'low', 'someday'];
        if (!in_array($priority, $validPriorities)) {
            jsonError('Invalid priority value. Valid: high, medium, low, someday', 400);
        }
        $updates[] = 'priority = ?';
        $params[] = $priority;
    }

    // Category
    if (array_key_exists('category', $input)) {
        $updates[] = 'category = ?';
        $params[] = !empty($input['category']) ? trim($input['category']) : null;
    }

    // Priority slot (for 1-3-5 organization)
    if (array_key_exists('priority_slot', $input)) {
        $slot = $input['priority_slot'] !== null ? (int) $input['priority_slot'] : null;
        if ($slot !== null && ($slot < 0 || $slot > 8)) {
            jsonError('Priority slot must be 0-8 or null', 400);
        }
        $updates[] = 'priority_slot = ?';
        $params[] = $slot;
    }

    // Due date
    if (array_key_exists('due_date', $input)) {
        if ($input['due_date']) {
            $timestamp = strtotime($input['due_date']);
            if (!$timestamp) {
                jsonError('Invalid due date format (use YYYY-MM-DD)', 400);
            }
            $normalizedDate = date('Y-m-d', $timestamp);
            $updates[] = 'due_date = ?';
            $params[] = $normalizedDate;
        } else {
            $updates[] = 'due_date = NULL';
        }
    }

    // Completed date
    if (array_key_exists('completed_date', $input)) {
        if ($input['completed_date']) {
            $timestamp = strtotime($input['completed_date']);
            if (!$timestamp) {
                jsonError('Invalid completed date format', 400);
            }
            $normalizedDate = date('Y-m-d H:i:s', $timestamp);
            $updates[] = 'completed_date = ?';
            $params[] = $normalizedDate;
        } else {
            $updates[] = 'completed_date = NULL';
        }
    }

    // Estimated duration minutes
    if (array_key_exists('estimated_duration_minutes', $input)) {
        $duration = $input['estimated_duration_minutes'] !== null ? (int) $input['estimated_duration_minutes'] : null;
        if ($duration !== null && $duration < 0) {
            jsonError('Estimated duration must be non-negative', 400);
        }
        $updates[] = 'estimated_duration_minutes = ?';
        $params[] = $duration;

        // If duration provided, auto-calculate is_low_effort
        if ($duration !== null) {
            $updates[] = 'is_low_effort = ?';
            $params[] = $duration < 15 ? 1 : 0;
        }
    }

    // Is recurring
    if (array_key_exists('is_recurring', $input)) {
        $updates[] = 'is_recurring = ?';
        $params[] = (bool) $input['is_recurring'] ? 1 : 0;
    }

    // Recurrence pattern
    if (array_key_exists('recurrence_pattern', $input)) {
        if ($input['recurrence_pattern']) {
            $validPatterns = ['daily', 'weekly', 'biweekly', 'monthly'];
            if (!in_array($input['recurrence_pattern'], $validPatterns)) {
                jsonError('Invalid recurrence pattern. Valid: daily, weekly, biweekly, monthly', 400);
            }
            $updates[] = 'recurrence_pattern = ?';
            $params[] = $input['recurrence_pattern'];
        } else {
            $updates[] = 'recurrence_pattern = NULL';
        }
    }

    // Status Today - which slot is the task assigned to today (urgent/secondary/calm/inbox/null)
    if (array_key_exists('status_today', $input)) {
        if ($input['status_today']) {
            $statusToday = $input['status_today'];
            $validStatuses = ['urgent', 'secondary', 'calm', 'inbox'];
            if (!in_array($statusToday, $validStatuses)) {
                jsonError('Invalid status_today value. Valid: urgent, secondary, calm, inbox', 400);
            }
            $updates[] = 'status_today = ?';
            $params[] = $statusToday;
        } else {
            // Allow null to unassign from today
            $updates[] = 'status_today = NULL';
        }
    }

    // No updates provided
    if (empty($updates)) {
        jsonError('No updates provided', 400);
    }

    // Add task ID to params for WHERE clause
    $params[] = $taskId;

    // Execute update with updated_at timestamp
    $query = 'UPDATE tasks SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute($params);

    if (!$result) {
        jsonError('Failed to update task', 500);
    }

    // Fetch updated task
    $fetchStmt = $pdo->prepare('
        SELECT 
            id, user_id, title, description, priority, category,
            status, status_today, priority_slot, is_low_effort, estimated_duration_minutes,
            urgency_score, brain_dump_text, capture_date, due_date,
            completed_date, is_recurring, recurrence_pattern,
            created_at, updated_at
        FROM tasks 
        WHERE id = ? AND user_id = ?
    ');
    $fetchStmt->execute([$taskId, $userId]);
    $task = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        jsonError('Failed to retrieve updated task', 500);
    }

    jsonSuccess($task, 'Task updated successfully');

} catch (PDOException $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
