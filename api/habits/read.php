<?php
/**
 * ADHD Dashboard - Habits API: Get all habits
 * GET /api/habits/read.php?date=2026-04-03
 * 
 * Returns user's habits with today's completion status
 * Optional: date parameter to get completions for specific date
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

// Require authentication
$user = requireAuthenticatedUser();

try {
    $pdo = db();
    $userId = $user['id'];
    
    // Get the date (default to today)
    $date = $_GET['date'] ?? date('Y-m-d');
    
    // Validate date format
    if (!strtotime($date)) {
        jsonError('Invalid date format (use YYYY-MM-DD)', 400);
    }
    
    // Get all active habits for user
    $stmt = $pdo->prepare('
        SELECT 
            h.id,
            h.habit_name,
            h.habit_type,
            h.is_morning,
            h.is_afternoon,
            h.is_evening,
            h.sort_order,
            h.is_active,
            CASE WHEN hc.id IS NOT NULL THEN 1 ELSE 0 END as completed
        FROM daily_habits h
        LEFT JOIN habit_completions hc 
            ON h.id = hc.habit_id 
            AND hc.user_id = ? 
            AND hc.completion_date = ?
        WHERE h.user_id = ? AND h.is_active = TRUE
        ORDER BY h.is_morning DESC, h.is_afternoon DESC, h.is_evening DESC, h.sort_order ASC
    ');
    
    $stmt->execute([$userId, $date, $userId]);
    $habits = $stmt->fetchAll();
    
    // Separate into morning, afternoon, and evening
    $morning_habits = array_filter($habits, fn($h) => $h['is_morning']);
    $afternoon_habits = array_filter($habits, fn($h) => $h['is_afternoon']);
    $evening_habits = array_filter($habits, fn($h) => $h['is_evening']);
    
    jsonSuccess([
        'date' => $date,
        'morning' => array_values($morning_habits),
        'afternoon' => array_values($afternoon_habits),
        'evening' => array_values($evening_habits),
        'all' => $habits
    ], 'Habits retrieved successfully');
    
} catch (PDOException $e) {
    error_log('Habits Read Error: ' . $e->getMessage());
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
