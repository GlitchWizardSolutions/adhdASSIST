<?php
/**
 * ADHD Dashboard - Emergency Contacts API: Update
 * PUT /api/emergency-contacts/update.php
 * 
 * Body:
 * {
 *   "id": "Contact ID (required)",
 *   "name": "Updated name",
 *   "relationship": "Updated relationship",
 *   "phone_number": "Updated phone",
 *   "email": "Updated email",
 *   "address": "Updated address",
 *   "notes": "Updated notes",
 *   "is_primary": true/false
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "data": { updated contact object },
 *   "message": "Emergency contact updated successfully"
 * }
 */

session_start();
require_once __DIR__ . '/../config.php';

// Only PUT allowed
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonError('Method not allowed', 405);
}

// Require authentication
$user = requireAuthenticatedUser();

// Get input
$input = getJsonInput();

// Validate required fields
$required = ['id'];
$missing = validateRequired($input, $required);
if (!empty($missing)) {
    jsonError('Missing required fields: ' . implode(', ', $missing), 400);
}

try {
    $pdo = db();

    // Verify contact belongs to user
    $verify = $pdo->prepare("SELECT id FROM emergency_contacts WHERE id = :id AND user_id = :user_id");
    $verify->execute([':id' => $input['id'], ':user_id' => $user['id']]);
    
    if (!$verify->fetch()) {
        jsonError('Contact not found', 404);
    }

    // If setting as primary, unset other primaries
    if (isset($input['is_primary']) && $input['is_primary']) {
        $pdo->prepare("UPDATE emergency_contacts SET is_primary = FALSE WHERE user_id = :user_id AND id != :id")
            ->execute([':user_id' => $user['id'], ':id' => $input['id']]);
    }

    // Build update query dynamically
    $fields = [];
    $values = [];
    $allowed_fields = ['name', 'relationship', 'phone_number', 'email', 'address', 'notes', 'is_primary'];
    
    foreach ($allowed_fields as $field) {
        if (array_key_exists($field, $input)) {
            if ($field === 'is_primary') {
                $fields[] = "$field = :$field";
                $values[":$field"] = (int)(bool)$input[$field];
            } else {
                $fields[] = "$field = :$field";
                $values[":$field"] = $input[$field];
            }
        }
    }

    if (empty($fields)) {
        jsonError('No fields to update', 400);
    }

    $fields[] = "updated_at = NOW()";
    $values[':id'] = $input['id'];

    $sql = "UPDATE emergency_contacts SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    // Fetch updated contact
    $fetch = $pdo->prepare("SELECT * FROM emergency_contacts WHERE id = :id");
    $fetch->execute([':id' => $input['id']]);
    $contact = $fetch->fetch(PDO::FETCH_ASSOC);

    jsonSuccess($contact, 'Emergency contact updated successfully');

} catch (Exception $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>
