<?php
/**
 * Admin Dashboard - Overview Section
 * At-a-glance system status and quick actions
 */
?>

<div class="admin-card">
  <h2>System Overview</h2>
  <p class="text-muted">Quick status summary and actionable insights</p>
</div>

<!-- Status Cards Grid -->
<div class="admin-status-grid" id="statusGrid">
  <div style="grid-column: 1/-1; text-align: center; padding: 2rem;">
    <i class="bi bi-hourglass-split" style="font-size: 1.5rem; opacity: 0.5; animation: spin 1s linear infinite;"></i>
    <p style="margin-top: 1rem; color: var(--color-text-secondary);">Loading statistics...</p>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
  loadAdminStats();
});

async function loadAdminStats() {
  try {
    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/stats.php', {
      credentials: 'include'
    });
    const data = await response.json();

    if (!data.success) {
      console.error('Failed to load stats:', data.error);
      return;
    }

    const stats = data.stats;
    const grid = document.getElementById('statusGrid');

    grid.innerHTML = `
      <!-- Active Users Card -->
      <div class="admin-status-card">
        <div style="display: flex; align-items: baseline; gap: 0.5rem;">
          <div class="status-card-icon">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="status-card-value">${stats.active_users || 0}</div>
        </div>
        <div class="status-card-label">Active Users</div>
      </div>

      <!-- Pending Invites Card -->
      <div class="admin-status-card">
        <div style="display: flex; align-items: baseline; gap: 0.5rem;">
          <div class="status-card-icon">
            <i class="bi bi-envelope-check"></i>
          </div>
          <div class="status-card-value">${stats.pending_invites || 0}</div>
        </div>
        <div class="status-card-label">Invites Pending</div>
      </div>

      <!-- Assigned Tasks Card -->
      <div class="admin-status-card">
        <div style="display: flex; align-items: baseline; gap: 0.5rem;">
          <div class="status-card-icon">
            <i class="bi bi-check2-square"></i>
          </div>
          <div class="status-card-value">${stats.total_tasks || 0}</div>
        </div>
        <div class="status-card-label">Tasks Assigned</div>
        <div class="status-card-subtitle">${stats.completed_tasks || 0} done</div>
      </div>

      <!-- System Health Card -->
      <div class="admin-status-card">
        <div style="display: flex; align-items: baseline; gap: 0.5rem;">
          <div class="status-card-icon">
            <i class="bi bi-heart-pulse" style="color: #27AE60;"></i>
          </div>
          <div class="status-card-value" style="color: #27AE60;">✓</div>
        </div>
        <div class="status-card-label">System Status</div>
      </div>
    `;
  } catch (error) {
    console.error('Error loading stats:', error);
  }
}
</script>
