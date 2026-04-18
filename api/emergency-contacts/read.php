<?php
/**
 * ADHD Dashboard - Emergency Contacts API: Read
 * GET /api/emergency-contacts/read.php
 * 
 * Query params (optional):
 * - id: Get specific contact by ID
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": [array of contact objects],
 *   "message": "Contacts retrieved successfully"
 * }
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

    $contact_id = $_GET['id'] ?? null;

    if ($contact_id) {
        // Get specific contact
        $stmt = $pdo->prepare("
            SELECT * FROM emergency_contacts 
            WHERE id = :id AND user_id = :user_id
        ");
        $stmt->execute([
            ':id' => $contact_id,
            ':user_id' => $user['id']
        ]);
        
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$contact) {
            jsonError('Contact not found', 404);
        }
        
        jsonSuccess($contact, 'Contact retrieved successfully');
    } else {
        // Get all contacts for user, ordered by is_primary DESC, then by created_at DESC
        $stmt = $pdo->prepare("
            SELECT * FROM emergency_contacts 
            WHERE user_id = :user_id
            ORDER BY is_primary DESC, created_at DESC
        ");
        $stmt->execute([':user_id' => $user['id']]);
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonSuccess($contacts, 'Contacts retrieved successfully');
    }

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
