/**
 * Unified Task Modal Utility
 * A single, consistent modal used across all sections:
 * - Dashboard Inbox
 * - Task Planner
 * - Admin Delegated Tasks
 * 
 * Features:
 * - Basic tab: Title, Priority, Tags, Estimated Time
 * - Advanced tab: Description, Due Date, Admin Assignment
 * - Responsive layout
 * - Consistent priority naming (High/Medium/Low/Someday removed from database values)
 */

const UnifiedTaskModal = {
  /**
   * Open the unified task edit modal
   * @param {Object} options Configuration object
   * @param {string} options.taskId - Task ID to edit
   * @param {Object} options.task - Task data object with properties: title, priority, due_date, description, estimated_time, tags, assigned_to
   * @param {function} options.onSave - Callback when task is saved
   * @param {array} options.availableTags - Array of available tags (optional)
   * @param {array} options.assignableUsers - Array of users available for assignment (optional, admin/developer only)
   * @param {string} options.userRole - Current user's role ('user', 'admin', 'developer')
   */
  open: function(options) {
    const { taskId, task, onSave, availableTags = [], assignableUsers = [], userRole = 'user' } = options;

    // Determine priority display names
    const priorityOptions = {
      'high': '⚡ Urgent',
      'medium': '⏱️ Secondary',
      'low': '☁️ Calm'
    };

    // Build assignable users dropdown (admin/developer only)
    const userOptionsHtml = assignableUsers.map(u => 
      `<option value="${u.id}" ${task.assigned_to === u.id ? 'selected' : ''}>${u.first_name} ${u.last_name} (${u.email})</option>`
    ).join('');

    // Build available tags markup
    const tagsHtml = availableTags.map(tag => {
      const isSelected = task.tags && task.tags.some(t => t.id === tag.id);
      return `
        <button type="button" 
                class="btn btn-sm ${isSelected ? 'btn-primary' : 'btn-outline-secondary'}"
                data-tag-id="${tag.id}"
                onclick="UnifiedTaskModal.toggleTag('${tag.id}', event)"
                style="background-color: ${isSelected && tag.color_hex ? tag.color_hex : 'transparent'}; 
                       border-color: ${tag.color_hex || '#ccc'}; 
                       color: ${isSelected && tag.color_hex ? 'white' : tag.color_hex || '#666'};">
          ${tag.name}
        </button>
      `;
    }).join('');

    // Create modal HTML
    const modalId = `unified-task-modal-${taskId}`;
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = modalId;
    modal.setAttribute('tabindex', '-1');
    modal.innerHTML = `
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Maintain Task</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <!-- Tabs Navigation -->
          <ul class="nav nav-tabs" role="tablist" style="border-bottom: 1px solid var(--color-border-light); padding: 0 1rem; flex-wrap: nowrap;">
            <li class="nav-item" role="presentation" style="white-space: nowrap;">
              <button class="nav-link active" id="basic-tab-${taskId}" 
                      data-bs-toggle="tab" 
                      data-bs-target="#basic-pane-${taskId}" 
                      type="button" role="tab" 
                      aria-controls="basic-pane-${taskId}" 
                      aria-selected="true">
                📝 Basics
              </button>
            </li>
            <li class="nav-item" role="presentation" style="white-space: nowrap;">
              <button class="nav-link" id="advanced-tab-${taskId}" 
                      data-bs-toggle="tab" 
                      data-bs-target="#advanced-pane-${taskId}" 
                      type="button" role="tab" 
                      aria-controls="advanced-pane-${taskId}" 
                      aria-selected="false">
                ⚙️ Advanced
              </button>
            </li>
          </ul>

          <!-- Tabs Content -->
          <div class="tab-content" style="padding: 1.5rem;">
            <!-- BASIC TAB -->
            <div class="tab-pane fade show active" id="basic-pane-${taskId}" role="tabpanel" aria-labelledby="basic-tab-${taskId}">
              <!-- Task Title -->
              <div class="mb-4">
                <label class="form-label fw-500">Task Title *</label>
                <input type="text" class="form-control form-control-lg" id="task-title-${taskId}" 
                       value="${UnifiedTaskModal.escapeHtml(task.title || task.text || '')}" 
                       placeholder="What needs to be done?">
              </div>

              <!-- Priority & Estimated Time (Side by Side) -->
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Priority -->
                <div>
                  <label class="form-label fw-500">Priority Level</label>
                  <select class="form-select" id="task-priority-${taskId}">
                    <option value="high" ${(task.priority === 'high' || task.priority === 'urgent') ? 'selected' : ''}>⚡ Urgent (High Priority)</option>
                    <option value="medium" ${(task.priority === 'medium' || task.priority === 'secondary') ? 'selected' : ''}>⏱️ Secondary (Medium Priority)</option>
                    <option value="low" ${(task.priority === 'low' || task.priority === 'calm') ? 'selected' : ''}>☁️ Calm (Low Priority)</option>
                  </select>
                </div>

                <!-- Estimated Time -->
                <div>
                  <label class="form-label fw-500">Estimated Time</label>
                  <select class="form-select" id="task-estimated-time-${taskId}">
                    <option value="" ${!task.estimated_time ? 'selected' : ''}>No estimate</option>
                    <option value="10" ${task.estimated_time == 10 ? 'selected' : ''}>10 minutes</option>
                    <option value="15" ${task.estimated_time == 15 ? 'selected' : ''}>15 minutes</option>
                    <option value="25" ${task.estimated_time == 25 ? 'selected' : ''}>25 minutes</option>
                    <option value="30" ${task.estimated_time == 30 ? 'selected' : ''}>30 minutes</option>
                    <option value="45" ${task.estimated_time == 45 ? 'selected' : ''}>45 minutes</option>
                    <option value="60" ${task.estimated_time == 60 ? 'selected' : ''}>60 minutes</option>
                  </select>
                </div>
              </div>

              <!-- Tags -->
              <div class="mb-4">
                <label class="form-label fw-500">Tags (Optional)</label>
                <div id="tags-container-${taskId}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                  ${tagsHtml || '<p class="text-muted small">No tags available</p>'}
                </div>
              </div>
            </div>

            <!-- ADVANCED TAB -->
            <div class="tab-pane fade" id="advanced-pane-${taskId}" role="tabpanel" aria-labelledby="advanced-tab-${taskId}">
              <!-- Description -->
              <div class="mb-4">
                <label class="form-label fw-500">Description (Optional)</label>
                <textarea class="form-control" id="task-description-${taskId}" 
                          placeholder="Add more details about this task..."
                          rows="4">${UnifiedTaskModal.escapeHtml(task.description || '')}</textarea>
              </div>

              <!-- Due Date -->
              <div class="mb-4">
                <label class="form-label fw-500">Due Date (Optional)</label>
                <input type="date" class="form-control" id="task-due-date-${taskId}" 
                       value="${task.due_date ? task.due_date.split(' ')[0] : ''}">
              </div>

              <!-- Admin Assignment Section -->
              ${(userRole === 'admin' || userRole === 'developer') ? `
                <div class="mb-4">
                  <label class="form-label fw-500">Assign To</label>
                  <select class="form-select" id="task-assigned-to-${taskId}">
                    <option value="">-- My account --</option>
                    <option value="me" ${task.assigned_to === 'me' ? 'selected' : ''}>-- Assign to myself --</option>
                    ${userOptionsHtml}
                  </select>
                </div>
              ` : ''}
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="UnifiedTaskModal.save('${taskId}')">Save Changes</button>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    // Store task data and callback for save operation
    UnifiedTaskModal._currentModalData = {
      taskId: taskId,
      onSave: onSave,
      selectedTags: new Set((task.tags || []).map(t => t.id))
    };

    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    // Cleanup on hide
    modal.addEventListener('hidden.bs.modal', () => {
      modal.remove();
      UnifiedTaskModal._currentModalData = null;
    });
  },

  /**
   * Toggle tag selection
   */
  toggleTag: function(tagId, event) {
    event.preventDefault();
    if (!UnifiedTaskModal._currentModalData) return;

    const selectedTags = UnifiedTaskModal._currentModalData.selectedTags;
    const button = event.target.closest('button');

    if (selectedTags.has(parseInt(tagId))) {
      selectedTags.delete(parseInt(tagId));
      button.classList.remove('btn-primary');
      button.classList.add('btn-outline-secondary');
      button.style.backgroundColor = 'transparent';
    } else {
      selectedTags.add(parseInt(tagId));
      button.classList.remove('btn-outline-secondary');
      button.classList.add('btn-primary');
      // Color might be applied by data
    }
  },

  /**
   * Save the edited task
   */
  save: function(taskId) {
    const title = document.querySelector(`#task-title-${taskId}`).value;
    const priority = document.querySelector(`#task-priority-${taskId}`).value;
    const description = document.querySelector(`#task-description-${taskId}`)?.value;
    const due_date = document.querySelector(`#task-due-date-${taskId}`)?.value;
    const estimated_time = document.querySelector(`#task-estimated-time-${taskId}`)?.value;
    const assigned_to = document.querySelector(`#task-assigned-to-${taskId}`)?.value;

    if (!title.trim()) {
      alert('Task title cannot be empty');
      return;
    }

    if (!UnifiedTaskModal._currentModalData) {
      alert('Modal data not found');
      return;
    }

    const taskData = {
      title: title,
      priority: priority,
      description: description || null,
      due_date: due_date || null,
      estimated_time: estimated_time ? parseInt(estimated_time) : null,
      assigned_to: assigned_to || null,
      tags: Array.from(UnifiedTaskModal._currentModalData.selectedTags)
    };

    // Call the provided callback
    if (UnifiedTaskModal._currentModalData.onSave) {
      UnifiedTaskModal._currentModalData.onSave(taskData);
    }

    // Close modal
    const modal = document.querySelector(`#unified-task-modal-${taskId}`);
    if (modal) {
      bootstrap.Modal.getInstance(modal).hide();
    }
  },

  /**
   * Escape HTML special characters
   */
  escapeHtml: function(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
};
