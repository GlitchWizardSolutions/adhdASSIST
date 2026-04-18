<?php
/**
 * ADHD Dashboard - Emergency Contacts API: Create
 * POST /api/emergency-contacts/create.php
 * 
 * Body:
 * {
 *   "name": "Contact name (required)",
 *   "relationship": "Partner/Family/Friend/etc",
 *   "phone_number": "+1-555-0100",
 *   "email": "contact@example.com",
 *   "address": "Optional physical address",
 *   "notes": "Optional notes",
 *   "is_primary": false
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { contact object },
 *   "message": "Emergency contact created successfully"
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
$required = ['name'];
$missing = validateRequired($input, $required);
if (!empty($missing)) {
    jsonError('Missing required fields: ' . implode(', ', $missing), 400);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO emergency_contacts 
        (user_id, name, relationship, phone_number, email, address, notes, is_primary)
        VALUES (:user_id, :name, :relationship, :phone_number, :email, :address, :notes, :is_primary)
    ");

    $is_primary = isset($input['is_primary']) ? (bool)$input['is_primary'] : false;

    // If setting as primary, unset other primaries for this user
    if ($is_primary) {
        $pdo->prepare("UPDATE emergency_contacts SET is_primary = FALSE WHERE user_id = :user_id")
            ->execute([':user_id' => $user['id']]);
    }

    $stmt->execute([
        ':user_id' => $user['id'],
        ':name' => $input['name'] ?? null,
        ':relationship' => $input['relationship'] ?? null,
        ':phone_number' => $input['phone_number'] ?? null,
        ':email' => $input['email'] ?? null,
        ':address' => $input['address'] ?? null,
        ':notes' => $input['notes'] ?? null,
        ':is_primary' => (int)$is_primary
    ]);

    $contact_id = $pdo->lastInsertId();

    // Fetch the created contact
    $fetch = $pdo->prepare("SELECT * FROM emergency_contacts WHERE id = :id AND user_id = :user_id");
    $fetch->execute([':id' => $contact_id, ':user_id' => $user['id']]);
    $contact = $fetch->fetch(PDO::FETCH_ASSOC);

    jsonSuccess($contact, 'Emergency contact created successfully', 201);

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
