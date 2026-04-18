<?php
/**
 * ADHD Dashboard - Emergency Contacts CRUD API
 * POST   /api/habits/emergency-contacts-crud.php - Create new emergency contact
 * PUT    /api/habits/emergency-contacts-crud.php - Update emergency contact
 * DELETE /api/habits/emergency-contacts-crud.php - Delete emergency contact
 * GET    /api/habits/emergency-contacts-crud.php - List emergency contacts (used by profile.php)
 */

session_start();
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/database.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $pdo = db();
    $current_user = Auth::getCurrentUser();
    $user_id = $current_user['id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            createEmergencyContact($pdo, $user_id);
            break;
        case 'PUT':
            updateEmergencyContact($pdo, $user_id);
            break;
        case 'DELETE':
            deleteEmergencyContact($pdo, $user_id);
            break;
        case 'GET':
            listEmergencyContacts($pdo, $user_id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Create a new emergency contact
 */
function createEmergencyContact($pdo, $user_id) {
    // Get POST data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $relationship = isset($_POST['relationship']) ? trim($_POST['relationship']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $is_primary = isset($_POST['is_primary']) ? intval($_POST['is_primary']) : 0;
    
    // Validate required fields
    if (!$name || !$phone_number) {
        echo json_encode(['success' => false, 'error' => 'Name and phone number are required']);
        exit;
    }
    
    // If marking as primary, unmark all other primary contacts
    if ($is_primary) {
        $stmt = $pdo->prepare('UPDATE emergency_contacts SET is_primary = 0 WHERE user_id = ?');
        $stmt->execute([$user_id]);
    }
    
    // Insert new contact
    $stmt = $pdo->prepare('
        INSERT INTO emergency_contacts (user_id, name, phone_number, relationship, email, address, notes, is_primary)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $result = $stmt->execute([
        $user_id,
        $name,
        $phone_number,
        $relationship,
        $email,
        $address,
        $notes,
        $is_primary
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Emergency contact created successfully',
            'contact_id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create emergency contact']);
    }
}

/**
 * Update an existing emergency contact
 */
function updateEmergencyContact($pdo, $user_id) {
    // Get POST data (PUT data arrives as POST)
    $_POST = json_decode(file_get_contents('php://input'), true);
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
    $relationship = isset($_POST['relationship']) ? trim($_POST['relationship']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $is_primary = isset($_POST['is_primary']) ? intval($_POST['is_primary']) : 0;
    
    // Validate
    if (!$id || !$name || !$phone_number) {
        echo json_encode(['success' => false, 'error' => 'ID, name, and phone number are required']);
        exit;
    }
    
    // Verify contact belongs to user
    $stmt = $pdo->prepare('SELECT id FROM emergency_contacts WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Contact not found']);
        exit;
    }
    
    // If marking as primary, unmark all other primary contacts
    if ($is_primary) {
        $stmt = $pdo->prepare('UPDATE emergency_contacts SET is_primary = 0 WHERE user_id = ? AND id != ?');
        $stmt->execute([$user_id, $id]);
    }
    
    // Update contact
    $stmt = $pdo->prepare('
        UPDATE emergency_contacts 
        SET name = ?, phone_number = ?, relationship = ?, email = ?, address = ?, notes = ?, is_primary = ?, updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ');
    
    $result = $stmt->execute([
        $name,
        $phone_number,
        $relationship,
        $email,
        $address,
        $notes,
        $is_primary,
        $id,
        $user_id
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Emergency contact updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update emergency contact']);
    }
}

/**
 * Delete an emergency contact
 */
function deleteEmergencyContact($pdo, $user_id) {
    // Get ID from POST or query string
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Contact ID is required']);
        exit;
    }
    
    // Verify contact belongs to user
    $stmt = $pdo->prepare('SELECT id FROM emergency_contacts WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Contact not found']);
        exit;
    }
    
    // Delete contact
    $stmt = $pdo->prepare('DELETE FROM emergency_contacts WHERE id = ? AND user_id = ?');
    $result = $stmt->execute([$id, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Emergency contact deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete emergency contact']);
    }
}

/**
 * List all emergency contacts for the current user
 */
function listEmergencyContacts($pdo, $user_id) {
    $stmt = $pdo->prepare('
        SELECT id, name, phone_number, relationship, email, address, notes, is_primary, created_at
        FROM emergency_contacts
        WHERE user_id = ?
        ORDER BY is_primary DESC, name ASC
    ');
    $stmt->execute([$user_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'contacts' => $contacts
    ]);
}
?>
