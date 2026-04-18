<?php
/**
 * ADHD Dashboard - User Profile (View-Only & Print-Friendly)
 * Displays user information for viewing, printing, and sharing
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

// Fetch emergency contacts (optional for export)
$emergency_contacts = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM emergency_contacts WHERE user_id = ? ORDER BY name ASC');
    $stmt->execute([$user_id]);
    $emergency_contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - ADHD Dashboard</title>
  
  <!-- Bootstrap 5.3.8 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  
  <link href="../css/adhd-theme.css" rel="stylesheet">
  <link href="../css/adhd-dashboard.css" rel="stylesheet">
  
  <style>
    body { font-family: 'Nunito Sans', sans-serif; background-color: var(--color-bg-secondary); }
    h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }

    .profile-header {
      background: var(--color-bg-primary);
      border-bottom: 1px solid var(--color-border-light);
      padding: 3rem 0;
      margin-bottom: 2rem;
      text-align: center;
    }

    .profile-card {
      background: var(--color-bg-primary);
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
      margin-bottom: 1.5rem;
    }

    .profile-avatar {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--color-calm) 0%, var(--color-secondary) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      color: white;
      border: 4px solid var(--color-border-light);
      margin: 0 auto 1.5rem auto;
      object-fit: cover;
    }

    .profile-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
    }

    .info-item {
      padding-bottom: 1rem;
    }

    .info-label {
      font-size: 0.75rem;
      color: var(--color-text-muted);
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .info-value {
      font-size: 1rem;
      color: var(--color-text-dark);
      word-break: break-word;
    }

    .info-value a {
      color: var(--color-calm);
      text-decoration: none;
    }

    .info-value a:hover {
      text-decoration: underline;
    }

    .export-options {
      background: var(--color-bg-secondary);
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }

    .checkbox-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 0.75rem;
    }

    .checkbox-item input[type="checkbox"] {
      cursor: pointer;
    }

    .checkbox-item label {
      margin: 0;
      cursor: pointer;
      flex: 1;
      color: var(--color-text-dark);
    }

    @media print {
      .no-print { display: none !important; }
      body { background: white; margin: 0; padding: 0; }
      .container { max-width: 100%; margin: 0; padding: 0; }
      .profile-card { box-shadow: none; border: 1px solid var(--color-border-light); page-break-inside: avoid; }
      .profile-header { padding: 1rem 0; display: none; }
      header, nav { display: none !important; }
      .profile-avatar { border: 2px solid #333; }
      .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
      @page { size: letter; margin: 0.5in; }
    }
  </style>
</head>
<body <?php echo isset($user['theme_preference']) ? "data-theme='{$user['theme_preference']}'" : "data-theme='light'"; ?> style="padding: 0; margin: 0;">
  <?php $current_page = 'profile'; require_once __DIR__ . '/header.php'; ?>

  <!-- Main Content -->
  <div id="main-content" class="container" style="max-width: 900px; margin: 0 auto; padding: 2rem 1rem;">
    
    <!-- Profile Card - Print Friendly -->
    <div class="profile-card" role="main" aria-label="User profile information">
      <!-- Avatar & Name Section -->
      <div style="text-align: center; margin-bottom: 2rem;">
        <div class="profile-avatar">
          <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?php echo APP_SUBDIR . '/' . htmlspecialchars($user['avatar_url']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
          <?php else: ?>
            <i class="bi bi-person-fill"></i>
          <?php endif; ?>
        </div>
        
        <h1 style="margin-top: 1rem; margin-bottom: 0.5rem; font-size: 2rem;">
          <?php echo htmlspecialchars($user['first_name'] ? $user['first_name'] . ' ' . $user['last_name'] : $user['email']); ?>
        </h1>
        
        <?php if (!empty($user['created_at'])): ?>
          <small style="color: #999;">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></small>
        <?php endif; ?>
      </div>

      <!-- Contact Information - Simple Display -->
      <div style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: #333;">Contact Information</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
          <?php if (!empty($user['email'])): ?>
            <div>
              <strong style="color: #666; font-size: 0.9rem; text-transform: uppercase;">Email</strong><br>
              <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" style="color: #0052CC; text-decoration: none; font-weight: 500;">
                <?php echo htmlspecialchars($user['email']); ?>
              </a>
            </div>
          <?php endif; ?>

          <?php if (!empty($user['phone_number'])): ?>
            <div>
              <strong style="color: #666; font-size: 0.9rem; text-transform: uppercase;">Phone</strong><br>
              <a href="tel:<?php echo htmlspecialchars($user['phone_number']); ?>" style="color: #0052CC; text-decoration: none; font-weight: 500;">
                <?php echo htmlspecialchars($user['phone_number']); ?>
              </a>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($user['mailing_address'])): ?>
          <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
            <strong style="color: #666; font-size: 0.9rem; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Mailing Address</strong>
            <a href="https://www.google.com/maps/search/<?php echo urlencode($user['mailing_address']); ?>" target="_blank" style="color: #0052CC; text-decoration: none; white-space: pre-line; line-height: 1.6; display: block;">
              <?php echo htmlspecialchars($user['mailing_address']); ?>
              <i class="bi bi-box-arrow-up-right" style="font-size: 0.8rem; margin-left: 0.25rem;"></i>
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Print/Export Options (hidden on print) -->
      <div class="no-print" style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: #333;">Print/Export Options</h3>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
          <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
            <input type="checkbox" id="include_emergency" name="include_emergency" checked>
            <span>Include Emergency Contacts</span>
          </label>
          
          <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
            <input type="checkbox" id="include_medications" name="include_medications">
            <span>Include Medications</span>
          </label>
        </div>
      </div>

      <!-- Emergency Contacts -->
      <div id="emergency-section" style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-bottom: 1.5rem;">
        <?php if (!empty($emergency_contacts)): ?>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; color: #333; margin: 0;">Emergency Contacts</h3>
            <div class="no-print" style="display: flex; gap: 0.5rem;">
              <a href="emergency-contacts.php" class="btn-action" style="padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none; display: inline-block;">
                <i class="bi bi-pencil-square me-1"></i>Manage Contacts
              </a>
              <button type="button" class="btn-action" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="openAddContactModal()">
                <i class="bi bi-plus-circle me-1"></i>Add Contact
              </button>
            </div>
          </div>
          
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
            <?php foreach ($emergency_contacts as $contact): ?>
              <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; background-color: #f9fafb;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                  <div>
                    <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.25rem;">
                      <?php echo htmlspecialchars($contact['name']); ?>
                      <?php if ($contact['is_primary']): ?>
                        <span style="background-color: #FFB300; color: #fff; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 4px; margin-left: 0.5rem; font-weight: 500;">Primary</span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($contact['relationship'])): ?>
                      <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.25rem;">
                        <?php echo htmlspecialchars($contact['relationship']); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <div style="font-size: 0.9rem; line-height: 1.5;">
                  <div style="margin-bottom: 0.5rem;">
                    <a href="tel:<?php echo htmlspecialchars($contact['phone_number']); ?>" style="color: #0052CC; text-decoration: none;">
                      <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($contact['phone_number']); ?>
                    </a>
                  </div>
                  
                  <?php if (!empty($contact['email'])): ?>
                    <div style="margin-bottom: 0.5rem;">
                      <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" style="color: #0052CC; text-decoration: none;">
                        <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($contact['email']); ?>
                      </a>
                    </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($contact['address'])): ?>
                    <div style="margin-bottom: 0.5rem;">
                      <a href="https://www.google.com/maps/search/<?php echo urlencode($contact['address']); ?>" target="_blank" style="color: #0052CC; text-decoration: none;">
                        <i class="bi bi-geo-alt me-1"></i>
                        <span style="white-space: pre-line;"><?php echo htmlspecialchars($contact['address']); ?></span>
                        <i class="bi bi-box-arrow-up-right" style="font-size: 0.8rem; margin-left: 0.25rem;"></i>
                      </a>
                    </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($contact['notes'])): ?>
                    <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; color: #666; font-style: italic;">
                      <p style="margin: 0; font-size: 0.85rem;">📝 <?php echo htmlspecialchars($contact['notes']); ?></p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <p style="color: #999; margin: 0;">No emergency contacts saved yet</p>
            <button type="button" class="btn-action" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="openAddContactModal()">
              <i class="bi bi-plus-circle me-1"></i>Add Contact
            </button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Medications Section -->
      <div id="medications-section" style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-bottom: 1.5rem; display: none;">
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: #333;">Medications</h3>
        
        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; color: #999;">
          <p style="margin: 0;"><i class="bi bi-info-circle me-2"></i>Medication information coming soon</p>
        </div>
      </div>

      <!-- Print Button (hidden on print) -->
      <div class="no-print" style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; text-align: center;">
        <button type="button" class="btn-action" style="background-color: #27ae60;" onclick="window.print()">
          <i class="bi bi-printer me-2"></i>Print Profile
        </button>

        <a href="<?php echo Config::redirectUrl('/views/settings.php'); ?>" class="btn-action" style="padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none; display: inline-block;">
          <i class="bi bi-gear me-2"></i>Edit Settings
        </a>
      </div>
    </div>

  </div>

  <!-- Add Emergency Contact Modal -->
  <div class="modal fade" id="addContactModal" tabindex="-1" style="display: none;">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Emergency Contact</h5>
          <button type="button" class="btn-close" onclick="closeAddContactModal()" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="contact_name" class="form-label">Name *</label>
            <input type="text" class="form-control" id="contact_name" placeholder="Full Name" required>
          </div>
          <div class="form-group">
            <label for="contact_phone" class="form-label">Phone Number *</label>
            <input type="tel" class="form-control" id="contact_phone" placeholder="(123) 456-7890" oninput="formatPhoneInputRealtime(this)" required>
          </div>
          <div class="form-group">
            <label for="contact_relationship" class="form-label">Relationship</label>
            <input type="text" class="form-control" id="contact_relationship" placeholder="e.g., Sister, Doctor, Partner">
          </div>
          <div class="form-group">
            <label for="contact_email" class="form-label">Email (Optional)</label>
            <input type="email" class="form-control" id="contact_email" placeholder="email@example.com">
          </div>
          <div class="form-group">
            <label for="contact_address" class="form-label">Address (Optional)</label>
            <textarea class="form-control" id="contact_address" placeholder="Street, City, State, ZIP" style="resize: vertical; min-height: 60px;"></textarea>
          </div>
          <div class="form-group">
            <label for="contact_notes" class="form-label">Notes (Optional)</label>
            <textarea class="form-control" id="contact_notes" placeholder="Any additional notes or medical info" style="resize: vertical; min-height: 60px;"></textarea>
          </div>
          <div class="form-group">
            <div class="checkbox-item">
              <input type="checkbox" id="contact_is_primary" name="contact_is_primary">
              <label for="contact_is_primary" style="font-weight: 600;">Mark as Primary Contact</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" onclick="closeAddContactModal()">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="saveEmergencyContact()">Save Contact</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS (minimal) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Modal management for Add Emergency Contact
    function openAddContactModal() {
      document.getElementById('addContactModal').style.display = 'block';
      document.getElementById('addContactModal').classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function closeAddContactModal() {
      document.getElementById('addContactModal').style.display = 'none';
      document.getElementById('addContactModal').classList.remove('show');
      document.body.style.overflow = 'auto';
      // Clear form
      document.getElementById('contact_name').value = '';
      document.getElementById('contact_phone').value = '';
      document.getElementById('contact_relationship').value = '';
      document.getElementById('contact_email').value = '';
      document.getElementById('contact_address').value = '';
      document.getElementById('contact_notes').value = '';
      document.getElementById('contact_is_primary').checked = false;
    }

    function formatPhoneInputRealtime(input) {
      let value = input.value;
      const cursorPos = input.selectionStart;
      
      // Get only digits
      const digits = value.replace(/\D/g, '');
      
      // Format based on digit count
      let formatted = '';
      if (digits.length === 0) {
        formatted = '';
      } else if (digits.length <= 3) {
        formatted = digits.length > 0 ? `(${digits}` : '';
      } else if (digits.length <= 6) {
        formatted = `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
      } else if (digits.length <= 10) {
        formatted = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
      } else {
        formatted = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
      }
      
      // Only update if different
      if (input.value !== formatted) {
        const prevLength = input.value.length;
        input.value = formatted;
        
        let newPos = cursorPos;
        if (prevLength < formatted.length) {
          newPos = cursorPos + 1;
        } else if (prevLength > formatted.length) {
          newPos = Math.max(0, cursorPos - 1);
        }
        
        try {
          input.setSelectionRange(newPos, newPos);
        } catch (e) {
          // Cursor positioning failed silently
        }
      }
    }

    function saveEmergencyContact() {
      const name = document.getElementById('contact_name').value.trim();
      const phone = document.getElementById('contact_phone').value.trim();
      const relationship = document.getElementById('contact_relationship').value.trim();
      const email = document.getElementById('contact_email').value.trim();
      const address = document.getElementById('contact_address').value.trim();
      const notes = document.getElementById('contact_notes').value.trim();
      const is_primary = document.getElementById('contact_is_primary').checked ? 1 : 0;

      if (!name || !phone) {
        alert('Name and phone number are required');
        return;
      }

      const formData = new FormData();
      formData.append('name', name);
      formData.append('phone_number', phone);
      formData.append('relationship', relationship);
      formData.append('email', email);
      formData.append('address', address);
      formData.append('notes', notes);
      formData.append('is_primary', is_primary);

      fetch('../api/habits/emergency-contacts-crud.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(json => {
          if (json.success) {
            alert('Emergency contact added successfully');
            closeAddContactModal();
            setTimeout(() => location.reload(), 500);
          } else {
            alert('Error: ' + (json.error || 'Failed to add contact'));
          }
        })
        .catch(e => {
          alert('Error saving contact');
          console.error(e);
        });
    }

    // Format phone input in real-time
    function formatPhoneInputRealtime(input) {
      let value = input.value;
      
      // Get only digits
      const digits = value.replace(/\D/g, '');
      
      // Format based on digit count
      let formatted = '';
      if (digits.length === 0) {
        formatted = '';
      } else if (digits.length <= 3) {
        formatted = digits.length > 0 ? `(${digits}` : '';
      } else if (digits.length <= 6) {
        formatted = `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
      } else if (digits.length <= 10) {
        formatted = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
      } else {
        formatted = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
      }
      
      // Update the field
      input.value = formatted;
      
      // Set cursor to end of input to prevent rearrangement issues
      input.setSelectionRange(formatted.length, formatted.length);
    }

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
      const modal = document.getElementById('addContactModal');
      if (e.target === modal) {
        closeAddContactModal();
      }
    });

    // Toggle emergency contacts visibility
    document.getElementById('include_emergency')?.addEventListener('change', (e) => {
      const section = document.getElementById('emergency-section');
      if (section) {
        section.style.display = e.target.checked ? 'block' : 'none';
      }
    });

    // Toggle medications visibility
    document.getElementById('include_medications')?.addEventListener('change', (e) => {
      const section = document.getElementById('medications-section');
      if (section) {
        section.style.display = e.target.checked ? 'block' : 'none';
      }
    });

    // Initialize display state on page load
    document.addEventListener('DOMContentLoaded', () => {
      const emergencyCheckbox = document.getElementById('include_emergency');
      const medicationsCheckbox = document.getElementById('include_medications');
      
      if (emergencyCheckbox && !emergencyCheckbox.checked) {
        document.getElementById('emergency-section')?.style.display === 'none';
      }
      
      if (medicationsCheckbox && !medicationsCheckbox.checked) {
        document.getElementById('medications-section')?.style.display === 'none';
      }
    });
  </script>

  <!-- Scroll to Top Component -->
  <script src="<?php echo Config::url('js'); ?>scroll-to-top.js"></script>

</body>
</html>
