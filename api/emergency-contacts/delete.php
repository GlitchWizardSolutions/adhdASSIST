<?php
/**
 * ADHD Dashboard - Emergency Contacts API: Delete
 * DELETE /api/emergency-contacts/delete.php
 * 
 * Query params:
 * - id: Contact ID to delete (required)
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { "id": "deleted_id" },
 *   "message": "Emergency contact deleted successfully"
 * }
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only DELETE allowed
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonError('Method not allowed', 405);
}

// Require authentication
$user = requireAuthenticatedUser();

// Get contact ID
$contact_id = $_GET['id'] ?? null;

if (!$contact_id) {
    jsonError('Contact ID required', 400);
}

try {
    $pdo = db();

    // Verify contact belongs to user
    $verify = $pdo->prepare("SELECT id FROM emergency_contacts WHERE id = :id AND user_id = :user_id");
    $verify->execute([':id' => $contact_id, ':user_id' => $user['id']]);
    
    if (!$verify->fetch()) {
        jsonError('Contact not found', 404);
    }

    // Delete the contact
    $stmt = $pdo->prepare("DELETE FROM emergency_contacts WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $contact_id, ':user_id' => $user['id']]);

    jsonSuccess(['id' => $contact_id], 'Emergency contact deleted successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
