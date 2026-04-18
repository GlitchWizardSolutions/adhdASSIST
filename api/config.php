<?php
/**
 * ADHD Dashboard - API Configuration & Response Utilities
 */

require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../lib/auth.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Enable CORS (adjust allowed origins as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Send success response
 */
function jsonSuccess($data, $message = 'Success', $statusCode = 200) {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], $statusCode);
}

/**
 * Send error response
 */
function jsonError($error, $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'error' => $error
    ], $statusCode);
}

/**
 * Get request body as JSON
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Get current authenticated user (exit if not authenticated)
 */
function requireAuthenticatedUser() {
    if (!Auth::isAuthenticated()) {
        jsonError('Unauthorized', 401);
    }

    $user = Auth::getCurrentUser();
    if (!$user) {
        jsonError('User not found', 401);
    }

    return $user;
}
?>
