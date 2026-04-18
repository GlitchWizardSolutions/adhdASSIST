<?php
/**
 * Admin Dashboard - CRUD Templates Section
 * Manage template permissions and access
 */
?>

<!-- Header with Description -->
<div class="admin-card-header">
  <h2>CRUD Templates</h2>
  <p>Configure which data collection templates users can access</p>
</div>

<!-- CRUD Tabs -->
<ul class="nav nav-tabs mb-4" id="crudTabs" role="tablist" style="border-bottom: 2px solid var(--color-border-light);">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="templates-list-tab" data-bs-toggle="tab" data-bs-target="#templates-list-pane" type="button" role="tab" aria-controls="templates-list-pane" aria-selected="true">
      <i class="bi bi-file-text me-2"></i>Available Templates
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="permissions-matrix-tab" data-bs-toggle="tab" data-bs-target="#permissions-matrix-pane" type="button" role="tab" aria-controls="permissions-matrix-pane" aria-selected="false">
      <i class="bi bi-lock me-2"></i>User Permissions
    </button>
  </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="crudTabContent">
  <!-- Available Templates Tab -->
  <div class="tab-pane fade show active" id="templates-list-pane" role="tabpanel" aria-labelledby="templates-list-tab">
    <div class="admin-card">
      <h3>Available Templates</h3>
      <table class="admin-table" id="templatesTable">
        <thead>
          <tr>
            <th>Template Name</th>
            <th>Type</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="3" style="text-align: center; padding: 2rem;">
              <i class="bi bi-hourglass-split" style="opacity: 0.5;"></i> Loading templates...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- User Permissions Matrix Tab -->
  <div class="tab-pane fade" id="permissions-matrix-pane" role="tabpanel" aria-labelledby="permissions-matrix-tab">
    <div class="admin-card">
      <h3>User Permissions Matrix</h3>
      <p class="text-muted small">Grant or revoke template access per user. Click checkboxes to update permissions.</p>
      <div style="overflow-x: auto;">
        <table class="admin-table" id="permissionsTable">
          <thead>
            <tr id="headerRow">
              <th>User</th>
            </tr>
          </thead>
          <tbody id="permissionsTbody">
            <tr>
              <td colspan="20" style="text-align: center; padding: 2rem;">
                <i class="bi bi-hourglass-split" style="opacity: 0.5;"></i> Loading permissions...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="small text-muted" style="margin-top: 1rem;">
        <strong>Note:</strong> Users can also create their own personal templates. Admin permissions control only system-wide templates.
      </p>
    </div>
  </div>
</div>

<style>
  /* CRUD tabs - override admin.css sidebar nav-item styles */
  #crudTabs {
    display: flex !important;
    flex-wrap: wrap;
    gap: 0;
  }
  
  #crudTabs .nav-item {
    width: auto !important;
    padding: 0 !important;
    background: none !important;
  }
  
  #crudTabs .nav-link {
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
  
  #crudTabs .nav-link:hover {
    color: var(--color-primary);
    border-bottom-color: var(--color-primary-light);
    background-color: rgba(255, 179, 0, 0.05);
  }
  
  #crudTabs .nav-link.active {
    color: var(--color-primary);
    background: none !important;
    border-bottom-color: var(--color-primary);
  }
</style>

<script>
let templates = [];
let activeUsers = [];
let currentUserId = <?php echo json_encode($user['id'] ?? 0); ?>;

document.addEventListener('DOMContentLoaded', function() {
  loadTemplatesAndPermissions();
});

async function loadTemplatesAndPermissions() {
  try {
    // Load templates
    const templatesResponse = await fetch('<?php echo Config::url('base'); ?>api/admin/crud-list.php?user_id=' + currentUserId, {
      credentials: 'include'
    });
    const templatesData = await templatesResponse.json();
    
    if (templatesData.success) {
      templates = templatesData.templates || [];
      
      // Update templates table
      const tbody = document.querySelector('#templatesTable tbody');
      if (templates.length > 0) {
        tbody.innerHTML = templates.map(t => `
          <tr>
            <td><strong>${t.name}</strong></td>
            <td><span style="background: #f0f0f0; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.875rem;">${t.type}</span></td>
            <td>${t.description}</td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem; color: #999;">No templates available</td></tr>';
      }
    } else {
      const tbody = document.querySelector('#templatesTable tbody');
      tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem; color: #d9534f;">Error: ' + (templatesData.error || 'Failed to load templates') + '</td></tr>';
    }
    
    // Load active users for permissions matrix
    const usersResponse = await fetch('<?php echo Config::url('base'); ?>api/admin/users-list.php?status=active&perPage=100', {
      credentials: 'include'
    });
    const usersData = await usersResponse.json();
    
    if (usersData.success) {
      activeUsers = usersData.data || [];
      loadPermissionsMatrix();
    } else {
      const tbody = document.getElementById('permissionsTbody');
      tbody.innerHTML = '<tr><td colspan="20" style="text-align: center; padding: 2rem; color: #d9534f;">Error: ' + (usersData.error || 'Failed to load users') + '</td></tr>';
    }
  } catch (error) {
    console.error('Error loading data:', error);
    const templatesBody = document.querySelector('#templatesTable tbody');
    const permissionsBody = document.getElementById('permissionsTbody');
    templatesBody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem; color: #d9534f;">Error: ' + error.message + '</td></tr>';
    permissionsBody.innerHTML = '<tr><td colspan="20" style="text-align: center; padding: 2rem; color: #d9534f;">Error: ' + error.message + '</td></tr>';
  }
}

async function loadPermissionsMatrix() {
  try {
    // Build header row with template names
    const headerRow = document.getElementById('headerRow');
    // Remove any existing template headers (keep only the first User header)
    const existingHeaders = headerRow.querySelectorAll('th:not(:first-child)');
    existingHeaders.forEach(th => th.remove());
    
    // Add template headers as siblings
    templates.forEach(t => {
      const th = document.createElement('th');
      th.style.textAlign = 'center';
      th.style.fontSize = '0.875rem';
      th.textContent = t.name;
      headerRow.appendChild(th);
    });
    
    // Build permission rows for each user
    const tbody = document.getElementById('permissionsTbody');
    const rows = [];
    
    for (const user of activeUsers) {
      // Get user's current permissions
      const permResponse = await fetch('<?php echo Config::url('base'); ?>api/admin/crud-list.php?user_id=' + user.id, {
        credentials: 'include'
      });
      const permData = await permResponse.json();
      const userPermissions = permData.userPermissions || [];
      
      let cells = `<td><strong>${user.full_name || user.email}</strong></td>`;
      
      for (const template of templates) {
        const isChecked = userPermissions.includes(template.id);
        cells += `
          <td style="text-align: center;">
            <input type="checkbox" 
              ${isChecked ? 'checked' : ''} 
              onchange="updatePermission(${user.id}, '${template.id}', this.checked)"
              style="cursor: pointer; width: 18px; height: 18px;">
          </td>
        `;
      }
      
      rows.push(`<tr>${cells}</tr>`);
    }
    
    tbody.innerHTML = rows.join('');
  } catch (error) {
    console.error('Error loading permissions matrix:', error);
  }
}

async function updatePermission(userId, templateId, isChecked) {
  try {
    const action = isChecked ? 'grant' : 'revoke';
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/crud-permissions.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId, template_id: templateId, action })
    });
    
    const data = await response.json();
    if (!data.success) {
      alert('Error: ' + (data.error || 'Failed to update permission'));
      // Revert checkbox
      event.target.checked = !event.target.checked;
    }
  } catch (error) {
    console.error('Error updating permission:', error);
    alert('Failed to update permission');
    event.target.checked = !event.target.checked;
  }
}
</script>

<!-- User-Defined Templates (TODO: Implement dynamic loading from API)
<div class="admin-card">
  <h3>User-Defined Templates</h3>
  <p class="text-muted small">Custom templates created by users (always available to creator)</p>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Template Name</th>
        <th>Creator</th>
        <th>Created</th>
        <th>Fields</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Book Tracker</td>
        <td>John Smith</td>
        <td>March 2026</td>
        <td>Title, Author, Genre, Rating, Notes</td>
      </tr>
      <tr>
        <td>Project Ideas</td>
        <td>Sarah Johnson</td>
        <td>February 2026</td>
        <td>Project Name, Status, Due Date, Deadline</td>
      </tr>
    </tbody>
  </table>
</div>
-->
