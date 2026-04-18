<?php
/**
 * ADHD Dashboard - Task API: Create task
 * POST /api/tasks/create.php
 * 
 * Body:
 * {
 *   "title": "Task title (required)",
 *   "description": "Optional description",
 *   "priority": "high|medium|low|someday",
 *   "estimated_duration_minutes": 25,
 *   "due_date": "YYYY-MM-DD",
 *   "is_recurring": false,
 *   "recurrence_pattern": "daily|weekly|biweekly|monthly"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { Task object with all fields },
 *   "message": "Task created successfully"
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
$required = ['title'];
$missing = validateRequired($input, $required);
if (!empty($missing)) {
    jsonError('Missing required fields: ' . implode(', ', $missing), 400);
}

try {
    $pdo = db();

    // Extract and validate inputs
    $title = trim($input['title']);
    $description = isset($input['description']) ? trim($input['description']) : null;
    $priority = $input['priority'] ?? 'medium';
    $category = isset($input['category']) ? trim($input['category']) : null;
    $dueDate = $input['due_date'] ?? null;
    $estimatedDurationMinutes = isset($input['estimated_duration_minutes']) ? (int)$input['estimated_duration_minutes'] : null;
    $isRecurring = isset($input['is_recurring']) ? (bool)$input['is_recurring'] : false;
    $recurrencePattern = $isRecurring && isset($input['recurrence_pattern']) ? trim($input['recurrence_pattern']) : null;
    $userId = $user['id'];

    // Validate title length
    if (strlen($title) > 500) {
        jsonError('Title too long (max 500 characters)', 400);
    }

    if (empty($title)) {
        jsonError('Title cannot be empty', 400);
    }

    // Validate priority
    $validPriorities = ['high', 'medium', 'low', 'someday'];
    if (!in_array($priority, $validPriorities)) {
        jsonError('Invalid priority value. Valid: high, medium, low, someday', 400);
    }

    // Validate due date format if provided
    if ($dueDate && !strtotime($dueDate)) {
        jsonError('Invalid due date format (use YYYY-MM-DD)', 400);
    }

    // Validate estimated duration
    if ($estimatedDurationMinutes !== null && $estimatedDurationMinutes < 0) {
        jsonError('Estimated duration must be non-negative', 400);
    }

    // Validate recurrence pattern if recurring
    if ($isRecurring) {
        $validPatterns = ['daily', 'weekly', 'biweekly', 'monthly'];
        if (!$recurrencePattern || !in_array($recurrencePattern, $validPatterns)) {
            jsonError('Invalid recurrence pattern. Valid: daily, weekly, biweekly, monthly', 400);
        }
    }

    // Auto-calculate is_low_effort (task takes < 15 minutes)
    $isLowEffort = $estimatedDurationMinutes !== null && $estimatedDurationMinutes < 15 ? 1 : 0;

    // Insert task
    $stmt = $pdo->prepare('
        INSERT INTO tasks (
            user_id,
            title,
            description,
            priority,
            category,
            status,
            is_low_effort,
            estimated_duration_minutes,
            is_recurring,
            recurrence_pattern,
            brain_dump_text,
            capture_date,
            due_date
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, NOW(), ?
        )
    ');

    $result = $stmt->execute([
        $userId,              // user_id
        $title,               // title
        $description,         // description
        $priority,            // priority
        $category,            // category
        'inbox',              // status - new tasks start in inbox
        $isLowEffort,         // is_low_effort (auto-calculated)
        $estimatedDurationMinutes,  // estimated_duration_minutes
        $isRecurring ? 1 : 0, // is_recurring
        $recurrencePattern,   // recurrence_pattern
        $title,               // brain_dump_text (original capture)
        $dueDate              // due_date
    ]);

    if (!$result) {
        jsonError('Failed to create task', 500);
    }

    $taskId = $pdo->lastInsertId();

    // Fetch created task to return
    $fetchStmt = $pdo->prepare('
        SELECT 
            id, user_id, title, description, priority, category,
            status, priority_slot, is_low_effort, estimated_duration_minutes,
            urgency_score, brain_dump_text, capture_date, due_date,
            completed_date, is_recurring, recurrence_pattern,
            created_at, updated_at
        FROM tasks 
        WHERE id = ? AND user_id = ?
    ');
    $fetchStmt->execute([$taskId, $userId]);
    $task = $fetchStmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        jsonError('Failed to retrieve created task', 500);
    }

    jsonSuccess($task, 'Task created successfully', 201);

} catch (PDOException $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
