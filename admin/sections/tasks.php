<?php
/**
 * Admin Dashboard - Delegated Tasks Section
 * View and manage tasks assigned to others
 */
?>

<!-- Header with Description and Filter -->
<div class="admin-card-header">
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2>Delegated Tasks</h2>
      <p>Track progress on tasks you've assigned to other users</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openCreateTaskModal()">
      <i class="bi bi-plus-circle me-2"></i> Create Task
    </button>
  </div>
</div>

<!-- Set API base for this section -->
<script>
  window._apiAdmin = "<?php echo Config::url('api'); ?>admin/";
</script>

<!-- Filter Options (Compact) -->
<div class="admin-card">
  <form id="taskFilterForm" class="admin-filter-compact">
    <div>
      <label for="filter-status">Filter by Status:</label>
      <select id="filter-status" class="form-select" onchange="loadTasksData()">
        <option value="">All Statuses</option>
        <option value="not_started">Not Started</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
      </select>
    </div>
    <button type="button" onclick="document.getElementById('filter-status').value=''; loadTasksData();" class="btn btn-sm btn-secondary">Clear Filters</button>
  </form>
</div>

<!-- Tasks Table -->
<div class="admin-card">
  <h3>All Delegated Tasks</h3>
  <table class="admin-table" id="tasksTable">
    <thead>
      <tr>
        <th>Task Title</th>
        <th>Assigned To</th>
        <th>Due Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td colspan="5" style="text-align: center; padding: 2rem;">
          <i class="bi bi-hourglass-split" style="opacity: 0.5;"></i> Loading tasks...
        </td>
      </tr>
    </tbody>
  </table>
</div>

<script>
function formatDate(dateString) {
  if (!dateString) return 'No date';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function getStatusBadgeClass(status) {
  switch(status) {
    case 'completed': return 'badge-completed';
    case 'in_progress': return 'badge-active';
    case 'not_started': return 'badge-inactive';
    default: return 'badge-inactive';
  }
}

function getStatusLabel(status) {
  switch(status) {
    case 'completed': return 'Completed';
    case 'in_progress': return 'In Progress';
    case 'not_started': return 'Not Started';
    default: return status;
  }
}

document.addEventListener('DOMContentLoaded', function() {
  loadTasksData();
});

async function loadTasksData() {
  try {
    const status = document.getElementById('filter-status').value;
    const params = new URLSearchParams();
    if (status) params.append('status', status);

    const response = await fetch('<?php echo Config::url('base'); ?>api/admin/tasks-list.php?' + params, {
      credentials: 'include'
    });
    const data = await response.json();

    if (!data.success) {
      console.error('Failed to load tasks:', data.error);
      return;
    }

    // Update table
    const tbody = document.querySelector('#tasksTable tbody');
    if (!data.tasks || data.tasks.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">No tasks found</td></tr>';
      return;
    }

    tbody.innerHTML = data.tasks.map(task => `
      <tr>
        <td>
          <div style="font-weight: 500;">${task.description || task.title || 'Untitled'}</div>
          <div style="font-size: 0.875rem; color: var(--color-text-secondary);">${task.title || ''}</div>
        </td>
        <td>${task.assignee_name || 'Unknown'}</td>
        <td>${formatDate(task.due_date)}</td>
        <td><span class="badge-status ${getStatusBadgeClass(task.status)}">${getStatusLabel(task.status)}</span></td>
        <td>
          <button class="btn btn-sm btn-light" onclick="openAdminEditTaskModal(${task.id})">Edit</button>
        </td>
      </tr>
    `).join('');
  } catch (error) {
    console.error('Error loading tasks:', error);
  }
}

let availableUsers = [];

async function openCreateTaskModal() {
  // Load users list for assignment
  try {
    const response = await fetch(window._apiAdmin + 'get-users-for-assignment.php', {
      credentials: 'include'
    });
    const data = await response.json();
    availableUsers = data.data || [];
  } catch (error) {
    console.error('Error loading users:', error);
    availableUsers = [];
  }

  // Create empty task for new task creation
  const newTask = {
    title: '',
    priority: 'medium',
    description: '',
    due_date: '',
    estimated_time: '',
    tags: [],
    assigned_to: ''
  };

  // Use unified modal
  UnifiedTaskModal.open({
    taskId: 0, // 0 indicates new task
    task: newTask,
    availableTags: [],
    assignableUsers: availableUsers,
    userRole: window._currentUserRole,
    onSave: async (taskData) => {
      try {
        const response = await fetch(window._apiAdmin + 'create-task.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({
            title: taskData.title,
            description: taskData.description || null,
            priority: taskData.priority,
            due_date: taskData.due_date || null,
            estimated_time: taskData.estimated_time || null,
            assigned_to: taskData.assigned_to ? (taskData.assigned_to === 'me' ? null : parseInt(taskData.assigned_to)) : null
          })
        });

        const data = await response.json();

        if (data.success) {
          alert('Task created successfully!');
          loadTasksData(); // Refresh the task list
        } else {
          alert('Error creating task: ' + (data.message || 'Unknown error'));
        }
      } catch (error) {
        console.error('Error creating task:', error);
        alert('Failed to create task. Please try again.');
      }
    }
  });
}

async function openAdminEditTaskModal(taskId) {
  // Fetch task details and users list
  try {
    const [taskResponse, usersResponse] = await Promise.all([
      fetch(window._apiAdmin + `get-task.php?task_id=${taskId}`, { credentials: 'include' }),
      fetch(window._apiAdmin + 'get-users-for-assignment.php', { credentials: 'include' })
    ]);

    const taskData = await taskResponse.json();
    const usersData = await usersResponse.json();

    if (!taskData.success) {
      alert('Failed to load task');
      return;
    }

    const task = taskData.data;
    const users = usersData.data || [];

    // Use unified modal
    UnifiedTaskModal.open({
      taskId: taskId,
      task: {
        title: task.title,
        priority: task.priority,
        description: task.description || '',
        due_date: task.due_date || '',
        estimated_time: task.estimated_duration_minutes || '',
        tags: task.tags || [],
        assigned_to: task.assigned_to || ''
      },
      availableTags: [],
      assignableUsers: users,
      userRole: window._currentUserRole,
      onSave: async (taskData) => {
        try {
          const response = await fetch(window._apiAdmin + 'update-task.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
              task_id: taskId,
              title: taskData.title,
              description: taskData.description || null,
              priority: taskData.priority,
              due_date: taskData.due_date || null,
              estimated_time: taskData.estimated_time || null,
              assigned_to: taskData.assigned_to ? (taskData.assigned_to === 'me' ? null : parseInt(taskData.assigned_to)) : null
            })
          });

          const data = await response.json();

          if (data.success) {
            alert('Task updated successfully!');
            loadTasksData(); // Refresh the task list
          } else {
            alert('Error updating task: ' + (data.message || 'Unknown error'));
          }
        } catch (error) {
          console.error('Error updating task:', error);
          alert('Failed to update task. Please try again.');
        }
      }
    });
  } catch (error) {
    console.error('Error loading task:', error);
    alert('Failed to load task details');
  }
}
</script>
