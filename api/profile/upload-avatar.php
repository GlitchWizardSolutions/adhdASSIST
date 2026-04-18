<?php
/**
 * ADHD Dashboard - Profile Avatar Upload API
 * POST /api/profile/upload-avatar.php
 * 
 * Handles avatar image upload with automatic resizing to 200x200px
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { "avatar_url": "path/to/avatar.jpg" },
 *   "message": "Avatar uploaded successfully"
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

// Check if file is uploaded
if (!isset($_FILES['avatar_upload']) || $_FILES['avatar_upload']['error'] !== UPLOAD_ERR_OK) {
    jsonError('No file uploaded or upload error', 400);
}

$file = $_FILES['avatar_upload'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    jsonError('Invalid file type. Allowed: JPG, PNG, GIF, WebP', 400);
}

// Validate file size (2MB max)
$max_size = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $max_size) {
    jsonError('File too large. Maximum size: 2MB', 400);
}

try {
    // Create uploads directory in /private/ (survives GitHub pulls)
    $upload_dir = __DIR__ . '/../../../private/uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $user_id = $user['id'];
    $extension = mime2ext($file['type']);
    $filename = "avatar_{$user_id}_" . time() . ".{$extension}";
    $filepath = $upload_dir . '/' . $filename;

    // Create image resource
    $image = null;
    switch ($file['type']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            break;
    }

    if (!$image) {
        jsonError('Failed to process image', 400);
    }

    // Resize to 200x200px
    $resized = imagecreatetruecolor(200, 200);
    
    // Handle transparency for PNG/GIF/WebP
    if ($file['type'] === 'image/png' || $file['type'] === 'image/gif' || $file['type'] === 'image/webp') {
        imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagesavealpha($resized, true);
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    $src_x = 0;
    $src_y = 0;
    
    // Calculate crop to make square, centered
    if ($width > $height) {
        $src_x = ($width - $height) / 2;
        $size = $height;
    } else {
        $src_y = ($height - $width) / 2;
        $size = $width;
    }
    
    imagecopyresampled($resized, $image, 0, 0, $src_x, $src_y, 200, 200, $size, $size);

    // Save resized image as JPEG for consistency
    imagejpeg($resized, $filepath, 90);
    imagedestroy($image);
    imagedestroy($resized);

    // Delete old avatar if exists
    $pdo = db();
    $stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $old_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($old_user['avatar_url'])) {
        // Extract filename from old URL (could be API URL or legacy path)
        $old_filename = basename($old_user['avatar_url']);
        $old_file = __DIR__ . '/../../../private/uploads/' . $old_filename;
        if (file_exists($old_file)) {
            @unlink($old_file);
        }
    }

    // Save to database as API URL (environment-aware)
    $avatar_url = Config::url('api') . '/files/serve.php?type=avatar&file=' . $filename;
    $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
    $stmt->execute([$avatar_url, $user_id]);

    jsonSuccess(['avatar_url' => $avatar_url], 'Avatar uploaded successfully', 201);

} catch (Exception $e) {
    jsonError('Server error: ' . $e->getMessage(), 500);
}

/**
 * Convert MIME type to file extension
 */
function mime2ext($mime) {
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    return $extensions[$mime] ?? 'jpg';
}
?>
