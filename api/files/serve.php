<?php
/**
 * ADHD Dashboard - Secure File Serving API
 * GET /api/files/serve.php?type=avatar&file=avatar_1_123456.jpg
 * 
 * Serves files from the /private/ directory securely:
 * - Prevents directory traversal attacks
 * - Validates file existence
 * - Sets proper cache headers
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die('Method not allowed');
}

// Get file type and name from query parameters
$type = $_GET['type'] ?? null;
$filename = $_GET['file'] ?? null;

if (!$type || !$filename) {
    http_response_code(400);
    die('Missing parameters');
}

// Validate type is in allowed list
$allowed_types = ['avatar'];
if (!in_array($type, $allowed_types)) {
    http_response_code(400);
    die('Invalid file type');
}

// Prevent directory traversal attacks
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    http_response_code(400);
    die('Invalid filename');
}

// Build full path
$base_path = __DIR__ . '/../../..';
$file_path = null;

if ($type === 'avatar') {
    $file_path = $base_path . '/private/uploads/' . $filename;
}

if (!$file_path || !file_exists($file_path)) {
    http_response_code(404);
    die('File not found');
}

// SECURITY: Optional ownership check for avatars (if user is logged in)
// This allows public access but respects privacy if needed
if ($type === 'avatar') {
    // Extract user_id from filename (avatar_ID_timestamp.jpg)
    if (preg_match('/^avatar_(\d+)_/', $filename, $matches)) {
        $file_user_id = (int)$matches[1];
        
        // If user is authenticated, check ownership
        if (isset($_SESSION['user_id'])) {
            if ($file_user_id !== $_SESSION['user_id']) {
                // User is logged in but trying to access someone else's avatar
                // Still allow it (avatars are public-friendly), but could restrict if needed
                // For now, we allow public access to avatars
            }
        }
        // If not authenticated, still allow access (avatars are not sensitive)
    }
}

// Get MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

// Set cache headers (1 week for avatars)
header('Cache-Control: public, max-age=604800');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 604800));

// Set content headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');

// Prevent caching issues on file updates
header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', filemtime($file_path)));

// Send file
readfile($file_path);
exit;
?>
