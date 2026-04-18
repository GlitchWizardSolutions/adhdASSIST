<?php
/**
 * Admin Dashboard - Configuration Section
 * System settings and preferences
 */
?>

<!-- Header with Description -->
<div class="admin-card-header">
  <h2>Configuration</h2>
  <p>Manage system-wide settings and preferences</p>
</div>

<!-- Configuration Tabs -->
<ul class="nav nav-tabs mb-4" id="configTabs" role="tablist" style="border-bottom: 2px solid var(--color-border-light);">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-pane" type="button" role="tab" aria-controls="email-pane" aria-selected="true">
      <i class="bi bi-envelope me-2"></i>Email & Notifications
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates-pane" type="button" role="tab" aria-controls="templates-pane" aria-selected="false">
      <i class="bi bi-file-text me-2"></i>Task Templates
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-pane" type="button" role="tab" aria-controls="categories-pane" aria-selected="false">
      <i class="bi bi-tags me-2"></i>Categories & Tags
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-pane" type="button" role="tab" aria-controls="archive-pane" aria-selected="false">
      <i class="bi bi-archive me-2"></i>Auto-Archive
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system-pane" type="button" role="tab" aria-controls="system-pane" aria-selected="false">
      <i class="bi bi-info-circle me-2"></i>System Info
    </button>
  </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="configTabContent">
  <!-- Email & Notifications Tab -->
  <div class="tab-pane fade show active" id="email-pane" role="tabpanel" aria-labelledby="email-tab">
    <div class="admin-card">
      <h3>Email & Notifications</h3>
      <form style="max-width: 600px;">
        <div class="mb-3">
          <label for="sender-name" class="form-label">Sender Name</label>
          <input type="text" id="sender-name" class="form-control" value="ADHD Dashboard" placeholder="Display name for emails">
          <small class="form-text text-muted">How emails appear to users (e.g., "ADHD Dashboard" or "Barbara Moore")</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Enable Notifications</label>
          <div class="form-check">
            <input type="checkbox" id="task-assign-notify" class="form-check-input" checked>
            <label class="form-check-label" for="task-assign-notify">Task assignment notifications</label>
          </div>
          <div class="form-check">
            <input type="checkbox" id="task-complete-notify" class="form-check-input">
            <label class="form-check-label" for="task-complete-notify">Task completion notifications</label>
          </div>
          <div class="form-check">
            <input type="checkbox" id="task-overdue-notify" class="form-check-input" checked>
            <label class="form-check-label" for="task-overdue-notify">Task overdue notifications</label>
          </div>
          <div class="form-check">
            <input type="checkbox" id="inactive-user-notify" class="form-check-input">
            <label class="form-check-label" for="inactive-user-notify">Inactive user reminders</label>
          </div>
        </div>

        <div class="mb-3">
          <label for="due-soon-days" class="form-label">Notify when task due in (days)</label>
          <input type="number" id="due-soon-days" class="form-control" value="3" min="1" max="30">
        </div>

        <button type="submit" class="btn btn-primary" onclick="alert('Settings saved (placeholder)')">Save Email Settings</button>
      </form>
    </div>
  </div>

  <!-- Task Templates Tab -->
  <div class="tab-pane fade" id="templates-pane" role="tabpanel" aria-labelledby="templates-tab">
    <div class="admin-card">
      <h3>Default Task Templates</h3>
      <p class="text-muted small">These templates appear as quick-capture buttons for all users</p>
      
      <div style="margin-bottom: 1.5rem;">
        <h5>Current Templates</h5>
        <div style="display: grid; gap: 0.75rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background-color: var(--color-bg-secondary); border-radius: 6px;">
            <div>
              <strong>Weekly Review</strong>
              <div class="small text-muted">Reflect on tasks and plan the week</div>
            </div>
            <button class="btn btn-sm btn-danger" onclick="alert('Delete template')">Remove</button>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background-color: var(--color-bg-secondary); border-radius: 6px;">
            <div>
              <strong>Medication Refill</strong>
              <div class="small text-muted">Request prescription refill from pharmacy</div>
            </div>
            <button class="btn btn-sm btn-danger" onclick="alert('Delete template')">Remove</button>
          </div>
        </div>
      </div>

      <form style="max-width: 600px; border-top: 1px solid var(--color-border-light); padding-top: 1.5rem;">
        <h5>Add New Template</h5>
        <div class="mb-3">
          <label for="template-title" class="form-label">Template Title</label>
          <input type="text" id="template-title" class="form-control" placeholder="e.g., 'Daily Standup'">
        </div>
        <div class="mb-3">
          <label for="template-desc" class="form-label">Description</label>
          <textarea id="template-desc" class="form-control" rows="2" placeholder="Brief description of this template"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" onclick="alert('Template added (placeholder)')">Add Template</button>
      </form>
    </div>
  </div>

  <!-- Categories & Tags Tab -->
  <div class="tab-pane fade" id="categories-pane" role="tabpanel" aria-labelledby="categories-tab">
    <div class="admin-card">
      <h3>System Categories & Tags</h3>
      <p class="text-muted small">Available to all users; users can create personal categories too</p>
      
      <div style="margin-bottom: 1.5rem;">
        <h5>Current Categories</h5>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
          <span class="badge" style="background-color: var(--color-urgent); color: var(--color-text-on-urgent); padding: 0.5rem 1rem; border-radius: 20px;">
            Health
            <button class="btn btn-sm btn-link" onclick="alert('Delete category')" style="padding: 0; color: inherit; border: none;">✕</button>
          </span>
          <span class="badge" style="background-color: var(--color-secondary); color: var(--color-text-on-secondary); padding: 0.5rem 1rem; border-radius: 20px;">
            Work
            <button class="btn btn-sm btn-link" onclick="alert('Delete category')" style="padding: 0; color: inherit; border: none;">✕</button>
          </span>
          <span class="badge" style="background-color: var(--color-calm); color: var(--color-text-on-calm); padding: 0.5rem 1rem; border-radius: 20px;">
            House
            <button class="btn btn-sm btn-link" onclick="alert('Delete category')" style="padding: 0; color: inherit; border: none;">✕</button>
          </span>
          <span class="badge" style="background-color: var(--color-info); color: var(--color-text-on-info); padding: 0.5rem 1rem; border-radius: 20px;">
            Family
            <button class="btn btn-sm btn-link" onclick="alert('Delete category')" style="padding: 0; color: inherit; border: none;">✕</button>
          </span>
        </div>
      </div>

      <form style="max-width: 600px; border-top: 1px solid var(--color-border-light); padding-top: 1.5rem;">
        <h5>Add New Category</h5>
        <div style="display: flex; gap: 0.5rem;">
          <input type="text" id="new-category" class="form-control" placeholder="e.g., 'Shopping'" maxlength="30">
          <button type="submit" class="btn btn-primary" style="white-space: nowrap;" onclick="alert('Category added (placeholder)')">Add</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Auto-Archive Tab -->
  <div class="tab-pane fade" id="archive-pane" role="tabpanel" aria-labelledby="archive-tab">
    <div class="admin-card">
      <h3>Task Auto-Archive</h3>
      <p class="text-muted small">How long completed tasks are kept before archiving</p>
      
      <form style="max-width: 600px;">
        <div class="mb-3">
          <label class="form-label">Global Auto-Archive Policy</label>
          <div class="form-check">
            <input type="radio" name="archive-policy" id="archive-never" class="form-check-input" value="never">
            <label class="form-check-label" for="archive-never">Never auto-archive (user controls)</label>
          </div>
          <div class="form-check">
            <input type="radio" name="archive-policy" id="archive-30" class="form-check-input" value="30" checked>
            <label class="form-check-label" for="archive-30">Archive after 30 days</label>
          </div>
          <div class="form-check">
            <input type="radio" name="archive-policy" id="archive-90" class="form-check-input" value="90">
            <label class="form-check-label" for="archive-90">Archive after 90 days</label>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-check">
            <input type="checkbox" class="form-check-input" checked>
            <span class="form-check-label">Auto-archive recurring task instances after completion</span>
          </label>
          <small class="form-text text-muted">Prevents clutter from completed recurring tasks</small>
        </div>

        <button type="submit" class="btn btn-primary" onclick="alert('Settings saved (placeholder)')">Save Archive Settings</button>
      </form>
    </div>
  </div>

  <!-- System Information Tab -->
  <div class="tab-pane fade" id="system-pane" role="tabpanel" aria-labelledby="system-tab">
    <div class="admin-card" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(255, 179, 0, 0.05));">
      <h3>System Information</h3>
      <table class="table table-borderless">
        <tr>
          <td><strong>Application:</strong></td>
          <td>ADHD Dashboard</td>
        </tr>
        <tr>
          <td><strong>Version:</strong></td>
          <td>1.0 (Beta)</td>
        </tr>
        <tr>
          <td><strong>Installation Date:</strong></td>
          <td>March 15, 2026</td>
        </tr>
        <tr>
          <td><strong>Last Updated:</strong></td>
          <td>April 8, 2026</td>
        </tr>
        <tr>
          <td><strong>Database:</strong></td>
          <td>Connected ✓</td>
        </tr>
      </table>
    </div>
  </div>
</div>

<style>
  /* Configuration tabs - override admin.css sidebar nav-item styles */
  #configTabs {
    display: flex !important;
    flex-wrap: wrap;
    gap: 0;
  }
  
  #configTabs .nav-item {
    width: auto !important;
    padding: 0 !important;
    background: none !important;
  }
  
  #configTabs .nav-link {
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
  
  #configTabs .nav-link:hover {
    color: var(--color-primary);
    border-bottom-color: var(--color-primary-light);
    background-color: rgba(255, 179, 0, 0.05);
  }
  
  #configTabs .nav-link.active {
    color: var(--color-primary);
    background: none !important;
    border-bottom-color: var(--color-primary);
  }
</style>
