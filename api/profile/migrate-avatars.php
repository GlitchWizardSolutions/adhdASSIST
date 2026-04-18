<?php
/**
 * ADHD Dashboard - Avatar Migration Script
 * Migrates avatars from /public_html/adhdASSIST/uploads/ to /private/uploads/
 * and updates database URLs to use new API endpoint.
 * 
 * Usage: Run once after deploying the new file structure
 * Run from: http://localhost/adhdASSIST/api/profile/migrate-avatars.php
 */

session_start();
require_once __DIR__ . '/../config.php';

// Require admin authentication
$user = requireAuthenticatedUser();
if ($user['role'] !== 'admin' && $user['role'] !== 'developer') {
    jsonError('Only admins can run migrations', 403);
}

// Ensure /private/uploads/ exists
$private_uploads = __DIR__ . '/../../../private/uploads';
if (!file_exists($private_uploads)) {
    mkdir($private_uploads, 0755, true);
}

$pdo = db();
$migrated = 0;
$errors = [];

try {
    // Get all users with avatars
    $stmt = $pdo->query('SELECT id, avatar_url FROM users WHERE avatar_url IS NOT NULL AND avatar_url != ""');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user_row) {
        $user_id = $user_row['id'];
        $old_url = $user_row['avatar_url'];
        
        // Skip if already migrated to API URL
        if (strpos($old_url, '/api/files/serve.php') !== false) {
            continue;
        }
        
        // Extract filename
        $filename = basename($old_url);
        
        // Try to move file from public uploads to private uploads
        $old_path = __DIR__ . '/../../uploads/' . $filename;
        $new_path = $private_uploads . '/' . $filename;
        
        if (file_exists($old_path)) {
            // Move file to private directory
            if (@rename($old_path, $new_path)) {
                // Update database with new API URL
                $api_url = Config::url('api') . '/files/serve.php?type=avatar&file=' . $filename;
                $update_stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
                $update_stmt->execute([$api_url, $user_id]);
                $migrated++;
            } else {
                $errors[] = "Failed to move file for user $user_id: $filename";
            }
        } else {
            // File doesn't exist in old location, just update URL
            $api_url = Config::url('api') . '/files/serve.php?type=avatar&file=' . $filename;
            $update_stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
            $update_stmt->execute([$api_url, $user_id]);
            $migrated++;
        }
    }
    
    jsonSuccess([
        'migrated' => $migrated,
        'errors' => $errors,
        'total_errors' => count($errors)
    ], "Migration complete. $migrated avatars migrated.");
    
} catch (Exception $e) {
    jsonError('Migration error: ' . $e->getMessage(), 500);
}
?>
