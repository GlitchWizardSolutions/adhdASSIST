<?php
/**
 * ADHD Dashboard - Recurring Tasks API: Generate instances
 * POST /api/tasks/generate-recurring.php
 * 
 * Body:
 * {
 *   "parent_task_id": "ID of the recurring parent task (required)",
 *   "next_due_date": "YYYY-MM-DD for next instance (optional, auto-calculated if not provided)"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { new task instance object },
 *   "message": "Next recurring task instance created successfully"
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
$required = ['parent_task_id'];
$missing = validateRequired($input, $required);
if (!empty($missing)) {
    jsonError('Missing required fields: ' . implode(', ', $missing), 400);
}

try {
    $pdo = db();

    // Fetch parent task
    $stmt = $pdo->prepare("
        SELECT * FROM tasks 
        WHERE id = :id AND user_id = :user_id AND is_recurring = TRUE
    ");
    $stmt->execute([':id' => $input['parent_task_id'], ':user_id' => $user['id']]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        jsonError('Recurring task not found', 404);
    }

    // Calculate next due date if not provided
    $next_due_date = $input['next_due_date'] ?? null;
    
    if (!$next_due_date) {
        $recurrence = $parent['recurrence_pattern'];
        $last_instance = $parent['due_date'] ? new DateTime($parent['due_date']) : new DateTime();
        
        $next = clone $last_instance;
        
        switch ($recurrence) {
            case 'daily':
                $next->modify('+1 day');
                break;
            case 'weekly':
                $next->modify('+1 week');
                break;
            case 'biweekly':
                $next->modify('+2 weeks');
                break;
            case 'monthly':
                $next->modify('+1 month');
                break;
            default:
                $next->modify('+1 day');
        }
        
        $next_due_date = $next->format('Y-m-d');
    }

    // Create new instance task
    $stmt = $pdo->prepare("
        INSERT INTO tasks 
        (user_id, title, description, priority, due_date, status, created_at)
        VALUES (:user_id, :title, :description, :priority, :due_date, 'scheduled', NOW())
    ");

    $stmt->execute([
        ':user_id' => $user['id'],
        ':title' => $parent['title'],
        ':description' => $parent['description'],
        ':priority' => $parent['priority'],
        ':due_date' => $next_due_date
    ]);

    $new_task_id = $pdo->lastInsertId();

    // Copy estimated duration if exists
    if ($parent['estimated_duration_minutes']) {
        $stmt = $pdo->prepare("UPDATE tasks SET estimated_duration_minutes = ? WHERE id = ?");
        $stmt->execute([$parent['estimated_duration_minutes'], $new_task_id]);
    }

    // Record in recurring_tasks_instances for tracking
    $stmt = $pdo->prepare("
        INSERT INTO recurring_tasks_instances 
        (recurring_parent_task_id, instance_task_id, instance_due_date, is_active)
        VALUES (:parent_id, :task_id, :due_date, TRUE)
    ");

    $stmt->execute([
        ':parent_id' => $input['parent_task_id'],
        ':task_id' => $new_task_id,
        ':due_date' => $next_due_date
    ]);

    // Fetch the created task
    $fetch = $pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $fetch->execute([':id' => $new_task_id]);
    $new_task = $fetch->fetch(PDO::FETCH_ASSOC);

    jsonSuccess($new_task, 'Next recurring task instance created successfully', 201);

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
