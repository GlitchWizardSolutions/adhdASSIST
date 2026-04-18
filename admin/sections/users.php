<?php
/**
 * Admin Dashboard - Users Management Section
 * Manage user accounts, invitations, and permissions
 */
?>

<div class="admin-card-header">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2>Users</h2>
      <p>Manage user accounts and send invitations</p>
    </div>
    <button class="btn btn-primary" onclick="openInviteModal()">
      <i class="bi bi-person-plus" aria-hidden="true"></i> Invite User
    </button>
  </div>
</div>

<!-- Users Tabs -->
<ul class="nav nav-tabs mb-4" id="usersTabs" role="tablist" style="border-bottom: 2px solid var(--color-border-light);">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="active-users-tab" data-bs-toggle="tab" data-bs-target="#active-users-pane" type="button" role="tab" aria-controls="active-users-pane" aria-selected="true">
      <i class="bi bi-person-check me-2"></i>Active Users
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="pending-invitations-tab" data-bs-toggle="tab" data-bs-target="#pending-invitations-pane" type="button" role="tab" aria-controls="pending-invitations-pane" aria-selected="false">
      <i class="bi bi-envelope me-2"></i>Pending Invitations
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="inactive-users-tab" data-bs-toggle="tab" data-bs-target="#inactive-users-pane" type="button" role="tab" aria-controls="inactive-users-pane" aria-selected="false">
      <i class="bi bi-person-x me-2"></i>Inactive Users
    </button>
  </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="usersTabContent">
  <!-- Active Users Tab -->
  <div class="tab-pane fade show active" id="active-users-pane" role="tabpanel" aria-labelledby="active-users-tab">
    <div class="admin-card">
      <h3>Active Users</h3>
      <table class="admin-table" id="activeUsersTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Last Login</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="5" style="text-align: center; padding: 2rem;">
              <i class="bi bi-hourglass-split" style="opacity: 0.5;"></i> Loading users...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pending Invitations Tab -->
  <div class="tab-pane fade" id="pending-invitations-pane" role="tabpanel" aria-labelledby="pending-invitations-tab">
    <div class="admin-card">
      <h3>Pending Invitations</h3>
      <table class="admin-table" id="invitationsTable">
        <thead>
          <tr>
            <th>Email</th>
            <th>Date Sent</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="4" style="text-align: center; padding: 2rem;">
              <i class="bi bi-hourglass-split" style="opacity: 0.5;"></i> Loading invitations...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Inactive Users Tab -->
  <div class="tab-pane fade" id="inactive-users-pane" role="tabpanel" aria-labelledby="inactive-users-tab">
    <div class="admin-card">
      <h3>Inactive Users</h3>
      <table class="admin-table" id="inactiveUsersTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Last Login</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="4" style="text-align: center; padding: 2rem;">
              <i class="bi bi-hourglass-split" style="opacity: 0.5;"></i> Loading users...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
  /* Users tabs - override admin.css sidebar nav-item styles */
  #usersTabs {
    display: flex !important;
    flex-wrap: wrap;
    gap: 0;
  }
  
  #usersTabs .nav-item {
    width: auto !important;
    padding: 0 !important;
    background: none !important;
  }
  
  #usersTabs .nav-link {
    color: var(--color-text-secondary);
    border: none;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    padding: 0.75rem 1.25rem !important;
    font-weight: 500;
    white-space: nowrap;
  }
  
  #usersTabs .nav-link:hover {
    color: var(--color-primary);
    border-bottom-color: var(--color-primary-light);
    background-color: rgba(255, 179, 0, 0.05);
  }
  
  #usersTabs .nav-link.active {
    color: var(--color-primary);
    background: none !important;
    border-bottom-color: var(--color-primary);
  }
</style>

<!-- Invite User Modal -->
<div id="inviteModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
  <div class="modal-content" style="background: white; border-radius: 8px; padding: 2rem; max-width: 400px; margin: auto; margin-top: 10vh;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
      <h3 style="margin: 0;">Invite User</h3>
      <button onclick="closeInviteModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
    </div>
    
    <form id="inviteForm" onsubmit="handleInvite(event)">
      <div style="margin-bottom: 1rem;">
        <label for="inviteEmail" style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Email Address</label>
        <input type="email" id="inviteEmail" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
      </div>
      
      <div style="margin-bottom: 1rem;">
        <label for="inviteName" style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Full Name (Optional)</label>
        <input type="text" id="inviteName" name="full_name" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
      </div>
      
      <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
        <button type="button" onclick="closeInviteModal()" class="btn btn-light">Cancel</button>
        <button type="submit" class="btn btn-primary">Send Invitation</button>
      </div>
    </form>
  </div>
</div>

<script>
// Format date helper
function formatDate(dateString) {
  if (!dateString) return 'Never';
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return diffMins + ' min' + (diffMins > 1 ? 's' : '') + ' ago';
  if (diffHours < 24) return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ' ago';
  if (diffDays < 7) return diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' ago';
  
  return date.toLocaleDateString();
}

// Modal functions
function openInviteModal() {
  document.getElementById('inviteModal').style.display = 'flex';
}

function closeInviteModal() {
  document.getElementById('inviteModal').style.display = 'none';
  document.getElementById('inviteForm').reset();
}

// Handle invite submission
async function handleInvite(e) {
  e.preventDefault();
  
  const email = document.getElementById('inviteEmail').value;
  const fullName = document.getElementById('inviteName').value;
  
  try {
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/users-invite.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, full_name: fullName })
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert('Invitation sent to ' + email);
      closeInviteModal();
      loadUsersData();
    } else {
      alert('Error: ' + (data.error || 'Failed to send invitation'));
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Failed to send invitation');
  }
}

// Load all user data
document.addEventListener('DOMContentLoaded', function() {
  loadUsersData();
});

async function loadUsersData() {
  try {
    // Load active users
    const activeResponse = await fetch('<?php echo Config::url('base'); ?>api/admin/users-list.php?status=active', {
      credentials: 'include'
    });
    const activeData = await activeResponse.json();
    
    if (activeData.success && activeData.data.length > 0) {
      const tbody = document.querySelector('#activeUsersTable tbody');
      tbody.innerHTML = activeData.data.map(user => `
        <tr>
          <td>${user.full_name || 'N/A'}</td>
          <td>${user.email}</td>
          <td><span class="badge-status badge-active">Active</span></td>
          <td>${formatDate(user.last_login)}</td>
          <td>
            <div class="dropdown">
              <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="viewUser(${user.id})"><i class="bi bi-eye"></i> View</a></li>
                <li><a class="dropdown-item" href="#" onclick="editUser(${user.id})"><i class="bi bi-pencil"></i> Edit</a></li>
                <li><a class="dropdown-item" href="#" onclick="resetUserPassword(${user.id})"><i class="bi bi-key"></i> Reset Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="deactivateUser(${user.id})"><i class="bi bi-exclamation-circle"></i> Deactivate</a></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(${user.id})"><i class="bi bi-trash"></i> Delete</a></li>
              </ul>
            </div>
          </td>
        </tr>
      `).join('');
    } else {
      document.querySelector('#activeUsersTable tbody').innerHTML = 
        '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No active users</td></tr>';
    }
    
    // Load pending invitations
    const invitationsResponse = await fetch('<?php echo Config::url('base'); ?>api/admin/users-invitations.php?status=pending', {
      credentials: 'include'
    });
    const invitationsData = await invitationsResponse.json();
    
    if (invitationsData.success && invitationsData.data && invitationsData.data.length > 0) {
      const tbody = document.querySelector('#invitationsTable tbody');
      tbody.innerHTML = invitationsData.data.map(invitation => `
        <tr>
          <td>${invitation.email}</td>
          <td>${formatDate(invitation.created_at)}</td>
          <td><span class="badge bg-warning text-dark">Pending</span></td>
          <td>
            <div class="dropdown">
              <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="resendInvitation(${invitation.id})"><i class="bi bi-arrow-repeat"></i> Resend</a></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="cancelInvitation(${invitation.id})"><i class="bi bi-trash"></i> Cancel</a></li>
              </ul>
            </div>
          </td>
        </tr>
      `).join('');
    } else {
      document.querySelector('#invitationsTable tbody').innerHTML = 
        '<tr><td colspan="4" style="text-align: center; padding: 2rem;">No pending invitations</td></tr>';
    }
    
    // Load inactive users
    const inactiveResponse = await fetch('<?php echo Config::url('base'); ?>api/admin/users-list.php?status=inactive', {
      credentials: 'include'
    });
    const inactiveData = await inactiveResponse.json();
    
    if (inactiveData.success && inactiveData.data.length > 0) {
      const tbody = document.querySelector('#inactiveUsersTable tbody');
      tbody.innerHTML = inactiveData.data.map(user => `
        <tr>
          <td>${user.full_name || 'N/A'}</td>
          <td>${user.email}</td>
          <td>${formatDate(user.last_login)}</td>
          <td>
            <div class="dropdown">
              <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="viewUser(${user.id})"><i class="bi bi-eye"></i> View</a></li>
                <li><a class="dropdown-item" href="#" onclick="editUser(${user.id})"><i class="bi bi-pencil"></i> Edit</a></li>
                <li><a class="dropdown-item" href="#" onclick="resetUserPassword(${user.id})"><i class="bi bi-key"></i> Reset Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" onclick="reactivateUser(${user.id})"><i class="bi bi-check-circle"></i> Reactivate</a></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(${user.id})"><i class="bi bi-trash"></i> Delete</a></li>
              </ul>
            </div>
          </td>
        </tr>
      `).join('');
    } else {
      document.querySelector('#inactiveUsersTable tbody').innerHTML = 
        '<tr><td colspan="4" style="text-align: center; padding: 2rem;">No inactive users</td></tr>';
    }
  } catch (error) {
    console.error('Error loading users:', error);
  }
}

async function reactivateUser(userId) {
  const confirmed = await showConfirmDialog('Reactivate User', 'Reactivate this user so they can log in again?');
  if (!confirmed) return;
  
  try {
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/users-deactivate.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: userId, action: 'reactivate' })
    });
    
    const data = await response.json();
    if (data.success) {
      showMessage('User reactivated successfully', 'success');
      loadUsersData();
    } else {
      showMessage('Error: ' + (data.error || 'Failed to reactivate user'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showMessage('Failed to reactivate user', 'error');
  }
}

function viewUser(userId) {
  showMessage('View user profile - feature coming soon', 'info');
}

function editUser(userId) {
  showMessage('Edit user - feature coming soon', 'info');
}

async function resetUserPassword(userId) {
  const confirmed = await showConfirmDialog('Reset Password', 'Send a password reset email to this user?');
  if (!confirmed) return;
  
  try {
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/users-reset-password.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: userId })
    });
    
    const data = await response.json();
    if (data.success) {
      showMessage('Password reset email sent', 'success');
    } else {
      showMessage('Error: ' + (data.error || 'Failed to send reset email'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showMessage('Failed to send password reset email', 'error');
  }
}

async function deactivateUser(userId) {
  const confirmed = await showConfirmDialog('Deactivate User', 'Deactivate this user? They will not be able to log in.');
  if (!confirmed) return;
  
  try {
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/users-deactivate.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: userId, action: 'deactivate' })
    });
    
    const data = await response.json();
    if (data.success) {
      showMessage('User deactivated', 'success');
      loadUsersData();
    } else {
      showMessage('Error: ' + (data.error || 'Failed to deactivate user'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showMessage('Failed to deactivate user', 'error');
  }
}

async function deleteUser(userId) {
  const confirmed = await showConfirmDialog('Delete User', 'Are you sure you want to delete this user? This action cannot be undone.');
  if (!confirmed) return;
  
  const doubleConfirmed = await showConfirmDialog('Confirm Deletion', 'This will permanently delete the user account and all associated data. Continue?', true);
  if (!doubleConfirmed) return;
  
  try {
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/users-delete.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: userId })
    });
    
    const data = await response.json();
    if (data.success) {
      showMessage('User deleted successfully', 'success');
      loadUsersData();
    } else {
      showMessage('Error: ' + (data.error || 'Failed to delete user'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showMessage('Failed to delete user', 'error');
  }
}

function showConfirmDialog(title, message, isDangerous = false) {
  return new Promise((resolve) => {
    // Create overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9998; display: flex; align-items: center; justify-content: center;';
    
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = 'background: white; border-radius: 12px; padding: 2rem; max-width: 450px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease-out;';
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }`;
    document.head.appendChild(style);
    
    const titleEl = document.createElement('h2');
    titleEl.style.cssText = 'margin: 0 0 1rem 0; font-size: 1.5rem; color: #2D3A4E; font-weight: 600;';
    titleEl.textContent = title;
    
    const msgEl = document.createElement('p');
    msgEl.style.cssText = 'margin: 0 0 1.5rem 0; color: #666; font-size: 0.95rem; line-height: 1.5;';
    msgEl.textContent = message;
    
    const buttonsDiv = document.createElement('div');
    buttonsDiv.style.cssText = 'display: flex; gap: 0.75rem; justify-content: flex-end;';
    
    const cancelBtn = document.createElement('button');
    cancelBtn.textContent = 'Cancel';
    cancelBtn.style.cssText = 'padding: 0.75rem 1.5rem; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer; font-weight: 500; color: #666; transition: all 0.2s;';
    cancelBtn.onmouseover = () => cancelBtn.style.background = '#f5f5f5';
    cancelBtn.onmouseout = () => cancelBtn.style.background = 'white';
    cancelBtn.onclick = () => {
      overlay.remove();
      resolve(false);
    };
    
    const confirmBtn = document.createElement('button');
    confirmBtn.textContent = 'Confirm';
    confirmBtn.style.cssText = `padding: 0.75rem 1.5rem; border: none; background: ${isDangerous ? '#dc2626' : '#3b82f6'}; color: white; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;`;
    confirmBtn.onmouseover = () => confirmBtn.style.background = isDangerous ? '#b91c1c' : '#2563eb';
    confirmBtn.onmouseout = () => confirmBtn.style.background = isDangerous ? '#dc2626' : '#3b82f6';
    confirmBtn.onclick = () => {
      overlay.remove();
      resolve(true);
    };
    
    buttonsDiv.appendChild(cancelBtn);
    buttonsDiv.appendChild(confirmBtn);
    
    modal.appendChild(titleEl);
    modal.appendChild(msgEl);
    modal.appendChild(buttonsDiv);
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
  });
}

function showMessage(message, type = 'info') {
  const msgDiv = document.createElement('div');
  
  // Determine styling based on type
  let bgColor, borderColor, textColor, icon;
  switch(type) {
    case 'success':
      bgColor = '#d1fae5';
      borderColor = '#6ee7b7';
      textColor = '#065f46';
      icon = '✓';
      break;
    case 'error':
      bgColor = '#fee2e2';
      borderColor = '#fca5a5';
      textColor = '#7f1d1d';
      icon = '✕';
      break;
    case 'warning':
      bgColor = '#fef3c7';
      borderColor = '#fcd34d';
      textColor = '#92400e';
      icon = '⚠';
      break;
    default:
      bgColor = '#dbeafe';
      borderColor = '#93c5fd';
      textColor = '#1e40af';
      icon = 'ℹ';
  }
  
  msgDiv.style.cssText = `position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 8px; border: 1px solid ${borderColor}; background: ${bgColor}; padding: 1rem; animation: slideInRight 0.3s ease-out;`;
  
  const style = document.createElement('style');
  style.textContent = `@keyframes slideInRight { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }`;
  document.head.appendChild(style);
  
  msgDiv.innerHTML = `
    <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
      <span style="color: ${textColor}; font-weight: bold; font-size: 1.2rem; flex-shrink: 0;">${icon}</span>
      <div style="flex: 1;">
        <p style="margin: 0; color: ${textColor}; font-weight: 500; word-wrap: break-word;">${escapeHtml(message)}</p>
      </div>
      <button type="button" style="background: none; border: none; color: ${textColor}; font-size: 1.5rem; cursor: pointer; padding: 0; margin: -0.5rem; flex-shrink: 0;" onclick="this.parentElement.parentElement.remove()">×</button>
    </div>
  `;
  
  document.body.appendChild(msgDiv);
  
  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    if (msgDiv.parentNode) {
      msgDiv.remove();
    }
  }, 5000);
}

async function resendInvitation(invitationId) {
  try {
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/users-resend-invitation.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ invitation_id: invitationId })
    });
    
    const data = await response.json();
    if (data.success) {
      showMessage('Invitation resent successfully', 'success');
      loadUsersData();
    } else {
      showMessage('Error: ' + (data.error || 'Failed to resend invitation'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showMessage('Failed to resend invitation', 'error');
  }
}

async function cancelInvitation(invitationId) {
  const confirmed = await showConfirmDialog('Cancel Invitation', 'Are you sure you want to cancel this invitation?');
  if (!confirmed) return;
  
  try {
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/users-cancel-invitation.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ invitation_id: invitationId })
    });
    
    const data = await response.json();
    if (data.success) {
      showMessage('Invitation cancelled', 'success');
      loadUsersData();
    } else {
      showMessage('Error: ' + (data.error || 'Failed to cancel invitation'), 'error');
    }
  } catch (error) {
    console.error('Error:', error);
    showMessage('Failed to cancel invitation', 'error');
  }
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}
</script>
