<?php
/**
 * Read Tasks with Advanced Filtering
 * GET /api/tasks/read.php
 * 
 * Query Parameters:
 * - status: inbox, backlog, scheduled, active, completed (comma-separated)
 * - priority: high, medium, low, someday (comma-separated)
 * - is_low_effort: true/false (filter quick wins)
 * - due_date_from: YYYY-MM-DD
 * - due_date_to: YYYY-MM-DD
 * - sort_by: due_date, priority, created_at, updated_at (default: created_at DESC)
 * - limit: max results (default: 100)
 * - offset: pagination offset (default: 0)
 */

session_start();
require_once __DIR__ . '/../../lib/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/database.php';
require_once __DIR__ . '/../config.php';

try {
    $user = requireAuthenticatedUser();
    $user_id = $user['id'];
    
    // Parse filters
    $status = isset($_GET['status']) ? array_filter(explode(',', $_GET['status'])) : [];
    $priority = isset($_GET['priority']) ? array_filter(explode(',', $_GET['priority'])) : [];
    $category = $_GET['category'] ?? null;
    $is_low_effort = isset($_GET['is_low_effort']) ? (bool)$_GET['is_low_effort'] : null;
    $due_date_from = $_GET['due_date_from'] ?? null;
    $due_date_to = $_GET['due_date_to'] ?? null;
    $sort_by = $_GET['sort_by'] ?? 'created_at DESC';
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Validate sort_by
    $allowed_sorts = ['due_date', 'priority', 'created_at', 'updated_at', 'title'];
    $sort_parts = explode(' ', trim($sort_by));
    if (!in_array($sort_parts[0], $allowed_sorts)) {
        $sort_by = 'created_at DESC';
    }
    
    // Build query
    $query = 'SELECT * FROM tasks WHERE user_id = ?';
    $params = [$user_id];
    
    // Status filter
    if (!empty($status)) {
        $placeholders = implode(',', array_fill(0, count($status), '?'));
        $query .= " AND status IN ($placeholders)";
        $params = array_merge($params, $status);
    }
    
    // Priority filter
    if (!empty($priority)) {
        $placeholders = implode(',', array_fill(0, count($priority), '?'));
        $query .= " AND priority IN ($placeholders)";
        $params = array_merge($params, $priority);
    }
    
    // Category filter
    if ($category) {
        $query .= ' AND category = ?';
        $params[] = $category;
    }
    
    // Low effort filter
    if ($is_low_effort !== null) {
        $query .= ' AND is_low_effort = ?';
        $params[] = $is_low_effort ? 1 : 0;
    }
    
    // Due date range
    if ($due_date_from) {
        $query .= ' AND (due_date >= ? OR due_date IS NULL)';
        $params[] = $due_date_from;
    }
    if ($due_date_to) {
        $query .= ' AND (due_date <= ? OR due_date IS NULL)';
        $params[] = $due_date_to;
    }
    
    // Get total count
    $count_query = str_replace('SELECT *', 'SELECT COUNT(*) as count', $query);
    $stmt = db()->prepare($count_query);
    $stmt->execute($params);
    $total = $stmt->fetch()['count'];
    
    // Add sorting and pagination
    $query .= " ORDER BY $sort_by LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'tasks' => $tasks,
            'total' => $total,
            'count' => count($tasks),
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

