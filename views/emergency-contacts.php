<?php
/**
 * ADHD Dashboard - Emergency Contacts
 * Displays and manages emergency contacts
 * Requires authentication
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/database.php';

// Redirect to login if not authenticated
if (!Auth::isAuthenticated()) {
    header('Location: ' . Config::redirectUrl('/views/login.php'));
    exit;
}

// Get current user
$user = Auth::getCurrentUser();
$user_id = $user['id'];
$pdo = db();

// Fetch emergency contacts
$contacts = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC');
    $stmt->execute([$user_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your emergency contacts">
    <title>Emergency Contacts - ADHD Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/adhd-dashboard.css" rel="stylesheet">
    <link href="../css/adhd-theme.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--color-bg-secondary);
            color: var(--color-text-dark);
        }

        .container {
            background-color: var(--color-bg-primary);
            border-radius: 8px;
            padding: 2rem;
        }

        h1 {
            color: var(--color-text-dark);
        }

        .contact-card {
            border-left: 4px solid var(--color-urgent);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            background-color: var(--color-bg-primary);
            color: var(--color-text-dark);
            border: 1px solid var(--color-border-light);
        }
        .contact-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .contact-card.primary {
            border-left-color: var(--color-secondary);
            background: linear-gradient(to right, rgba(255, 215, 0, 0.05), transparent);
        }
        .primary-badge {
            background: var(--color-secondary);
            color: var(--color-text-on-secondary);
            font-weight: bold;
        }
        .contact-info {
            font-size: 0.95rem;
            margin: 0.5rem 0;
            color: var(--color-text-dark);
        }
        .contact-info-label {
            font-weight: 600;
            color: var(--color-text-muted);
            display: inline-block;
            width: 100px;
        }
        .contact-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--color-text-muted);
        }
        .modal-form-group {
            margin-bottom: 1rem;
        }
        
        .card-body {
            color: var(--color-text-dark);
        }
        
        .card-title {
            color: var(--color-text-dark);
        }

        .text-muted {
            color: var(--color-text-muted) !important;
        }

        @media (max-width: 768px) {
            .contact-info-label {
                width: 80px;
                font-size: 0.90rem;
            }
            .contact-card {
                margin-bottom: 0.75rem;
            }
        }
    </style>
</head>
<body <?php echo isset($user['theme_preference']) ? "data-theme='{$user['theme_preference']}'" : "data-theme='light'"; ?>>
    <?php $current_page = 'emergency-contacts'; require_once __DIR__ . '/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="mb-0">🚨 Emergency Contacts</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                        + Add Contact
                    </button>
                </div>

                <div id="contactsContainer">
                    <?php if (empty($contacts)): ?>
                        <div class="empty-state card border-0" style="background-color: var(--color-bg-secondary);">
                            <p class="mb-0">No emergency contacts saved yet.</p>
                            <small class="text-muted">Add your first contact using the button above.</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <div class="card contact-card <?php echo $contact['is_primary'] ? 'primary' : ''; ?>" data-contact-id="<?php echo $contact['id']; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="card-title mb-1">
                                                <?php echo htmlspecialchars($contact['name']); ?>
                                                <?php if ($contact['is_primary']): ?>
                                                    <span class="badge primary-badge ms-2">Primary</span>
                                                <?php endif; ?>
                                            </h5>
                                            <?php if ($contact['relationship']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($contact['relationship']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($contact['phone_number']): ?>
                                        <div class="contact-info">
                                            <span class="contact-info-label">Phone:</span>
                                            <a href="tel:<?php echo htmlspecialchars($contact['phone_number']); ?>" style="color: #0052CC; text-decoration: none;">
                                                <?php echo htmlspecialchars($contact['phone_number']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($contact['email']): ?>
                                        <div class="contact-info">
                                            <span class="contact-info-label">Email:</span>
                                            <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" style="color: #0052CC; text-decoration: none;">
                                                <?php echo htmlspecialchars($contact['email']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($contact['address']): ?>
                                        <div class="contact-info">
                                            <span class="contact-info-label">Address:</span>
                                            <a href="https://www.google.com/maps/search/<?php echo urlencode($contact['address']); ?>" target="_blank" style="color: #0052CC; text-decoration: none;">
                                                <span style="white-space: pre-line;"><?php echo htmlspecialchars($contact['address']); ?></span>
                                                <i class="bi bi-box-arrow-up-right" style="font-size: 0.8rem; margin-left: 0.25rem;"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($contact['notes']): ?>
                                        <div class="contact-info">
                                            <span class="contact-info-label">Notes:</span>
                                            <span><?php echo nl2br(htmlspecialchars($contact['notes'])); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="contact-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editContact(<?php echo $contact['id']; ?>)">
                                            ✏️ Edit
                                        </button>
                                        <?php if (!$contact['is_primary']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="setAsPrimary(<?php echo $contact['id']; ?>)">
                                                ⭐ Set Primary
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteContact(<?php echo $contact['id']; ?>)">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Contact Modal -->
    <div class="modal fade" id="addContactModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Emergency Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="contactForm">
                        <div class="modal-form-group">
                            <label for="contactName" class="form-label">Contact Name *</label>
                            <input type="text" class="form-control" id="contactName" required>
                        </div>
                        <div class="modal-form-group">
                            <label for="contactRelationship" class="form-label">Relationship</label>
                            <input type="text" class="form-control" id="contactRelationship" placeholder="e.g., Partner, Family, Friend">
                        </div>
                        <div class="modal-form-group">
                            <label for="contactPhone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="contactPhone" placeholder="+1-555-0100">
                        </div>
                        <div class="modal-form-group">
                            <label for="contactEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="contactEmail">
                        </div>
                        <div class="modal-form-group">
                            <label for="contactAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="contactAddress" rows="2"></textarea>
                        </div>
                        <div class="modal-form-group">
                            <label for="contactNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="contactNotes" rows="2" placeholder="Any additional information..."></textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="contactPrimary">
                            <label class="form-check-label" for="contactPrimary">
                                Set as primary emergency contact
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveContact()">Save Contact</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Construct API base URL - handles both localhost and production
        const protocol = window.location.protocol;
        const host = window.location.host;
        const API_BASE = `${protocol}//${host}/api`;
        
        let currentContactId = null;
        let addContactModal;

        document.addEventListener('DOMContentLoaded', function() {
            addContactModal = new bootstrap.Modal(document.getElementById('addContactModal'));
        });

        function resetForm() {
            document.getElementById('contactForm').reset();
            document.getElementById('contactPrimary').checked = false;
            currentContactId = null;
        }

        function editContact(contactId) {
            currentContactId = contactId;
            
            fetch(`${API_BASE}/emergency-contacts/read.php?id=${contactId}`)
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        const contact = json.data;
                        document.getElementById('contactName').value = contact.name || '';
                        document.getElementById('contactRelationship').value = contact.relationship || '';
                        document.getElementById('contactPhone').value = contact.phone_number || '';
                        document.getElementById('contactEmail').value = contact.email || '';
                        document.getElementById('contactAddress').value = contact.address || '';
                        document.getElementById('contactNotes').value = contact.notes || '';
                        document.getElementById('contactPrimary').checked = contact.is_primary === 1 || contact.is_primary === true;
                        
                        document.querySelector('#addContactModal .modal-title').textContent = 'Edit Emergency Contact';
                        addContactModal.show();
                    }
                })
                .catch(err => console.error('Error:', err));
        }

        function saveContact() {
            const name = document.getElementById('contactName').value.trim();
            if (!name) {
                alert('Please enter contact name');
                return;
            }

            const payload = {
                name: name,
                relationship: document.getElementById('contactRelationship').value || null,
                phone_number: document.getElementById('contactPhone').value || null,
                email: document.getElementById('contactEmail').value || null,
                address: document.getElementById('contactAddress').value || null,
                notes: document.getElementById('contactNotes').value || null,
                is_primary: document.getElementById('contactPrimary').checked
            };

            const endpoint = currentContactId 
                ? `${API_BASE}/emergency-contacts/update.php`
                : `${API_BASE}/emergency-contacts/create.php`;

            const method = currentContactId ? 'PUT' : 'POST';

            if (currentContactId) {
                payload.id = currentContactId;
            }

            const options = {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            };

            fetch(endpoint, options)
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        addContactModal.hide();
                        resetForm();
                        location.reload();
                    } else {
                        alert('Error: ' + (json.error || json.message));
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Failed to save contact');
                });
        }

        function deleteContact(contactId) {
            if (!confirm('Are you sure you want to delete this emergency contact?')) {
                return;
            }

            fetch(`${API_BASE}/emergency-contacts/delete.php?id=${contactId}`, { method: 'DELETE' })
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (json.error || json.message));
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Failed to delete contact');
                });
        }

        function setAsPrimary(contactId) {
            fetch(`${API_BASE}/emergency-contacts/update.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: contactId, is_primary: true })
            })
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (json.error || json.message));
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Failed to update contact');
                });
        }

        // Open modal for new contact
        document.getElementById('addContactModal').addEventListener('hidden.bs.modal', function() {
            if (!currentContactId) {
                document.querySelector('#addContactModal .modal-title').textContent = 'Add Emergency Contact';
            }
        });
    </script>
</body>
</html>
