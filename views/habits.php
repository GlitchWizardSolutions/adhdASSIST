<?php
/**
 * ADHD Dashboard - Daily Habits Settings
 * Manage morning and evening routines
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';

// Redirect to login if not authenticated
if (!Auth::isAuthenticated()) {
    header('Location: ' . Config::redirectUrl('/views/login.php'));
    exit;
}

$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daily Habits - Settings</title>
  
  <!-- Bootstrap 5.3.8 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  
  <!-- ADHD Custom Theme - Using Config::url() for environment-aware paths -->
  <link href="<?php echo Config::url('css'); ?>adhd-theme.css" rel="stylesheet">
  <link href="<?php echo Config::url('css'); ?>adhd-dashboard.css" rel="stylesheet">
  
  <style>
    :root {
      --default-font: 'Nunito Sans', sans-serif;
      --heading-font: 'Poppins', sans-serif;
    }
    
    body {
      font-family: var(--default-font);
      background-color: #F9FAFB;
    }

    .habit-card {
      background: white;
      border: 1px solid #E0E4E8;
      border-radius: 6px;
      padding: 15px;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: all 0.2s ease;
    }

    .habit-card:hover {
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border-color: #FFB300;
    }

    .habit-type-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
    }

    .badge-routine { background: #D1ECF1; color: #0C5460; }
    .badge-health { background: #FCE4EC; color: #C2185B; }
    .badge-work { background: #E8F5E9; color: #2E7D32; }
    .badge-personal { background: #F3E5F5; color: #6A1B9A; }

    .habit-actions {
      display: flex;
      gap: 8px;
    }

    .habit-actions button {
      padding: 6px 10px;
      font-size: 14px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-edit {
      background: #E3F2FD;
      color: #1976D2;
    }

    .btn-edit:hover {
      background: #BBDEFB;
    }

    .btn-delete {
      background: #FFEBEE;
      color: #D32F2F;
    }

    .btn-delete:hover {
      background: #FFCDD2;
    }
  </style>
</head>
<body <?php echo isset($user['theme_preference']) ? "data-theme='{$user['theme_preference']}'" : "data-theme='light'"; ?>>
  <?php $current_page = 'habits'; require_once __DIR__ . '/header.php'; ?>

  <!-- Main Content -->
  <main class="container py-4">
    <div class="row">
      <div class="col-lg-8 mx-auto">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3 mb-0">
            <i class="bi bi-clock-history text-warning me-2"></i>
            Daily Habits
          </h1>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHabitModal">
            <i class="bi bi-plus-lg me-2"></i>Add Habit
          </button>
        </div>

        <!-- Description -->
        <p class="text-muted mb-4">
          Create your morning and evening routines. Habits reset daily at midnight.
        </p>

        <!-- Morning Habits Section -->
        <div class="card card-spacious border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pb-3">
            <h2 class="h5 mb-0">
              <i class="bi bi-sunrise text-warning me-2"></i>
              Morning Routine
            </h2>
          </div>
          <div class="card-body pt-0" id="morning-habits">
            <div class="text-center text-muted py-4">
              <p>No morning habits yet. Add one to get started!</p>
            </div>
          </div>
        </div>

        <!-- Afternoon Habits Section -->
        <div class="card card-spacious border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pb-3">
            <h2 class="h5 mb-0">
              <i class="bi bi-sun text-warning me-2"></i>
              Afternoon Routine
            </h2>
          </div>
          <div class="card-body pt-0" id="afternoon-habits">
            <div class="text-center text-muted py-4">
              <p>No afternoon habits yet. Add one to get started!</p>
            </div>
          </div>
        </div>

        <!-- Evening Habits Section -->
        <div class="card card-spacious border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pb-3">
            <h2 class="h5 mb-0">
              <i class="bi bi-moon-stars text-warning me-2"></i>
              Evening Routine
            </h2>
          </div>
          <div class="card-body pt-0" id="evening-habits">
            <div class="text-center text-muted py-4">
              <p>No evening habits yet. Add one to get started!</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <!-- Add Habit Modal -->
  <div class="modal fade" id="addHabitModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Habit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Habit Name</label>
            <input type="text" class="form-control" id="habitName" placeholder="e.g., Meditate, Stretch, Take medication">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" id="habitType">
              <option value="routine" selected>Routine</option>
              <option value="health">Health</option>
              <option value="work">Work</option>
              <option value="personal">Personal</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">When?</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="timePeriod" id="isMorning" value="morning" checked>
              <label class="form-check-label" for="isMorning">
                <i class="bi bi-sunrise"></i> Morning
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="timePeriod" id="isAfternoon" value="afternoon">
              <label class="form-check-label" for="isAfternoon">
                <i class="bi bi-sun"></i> Afternoon
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="timePeriod" id="isEvening" value="evening">
              <label class="form-check-label" for="isEvening">
                <i class="bi bi-moon-stars"></i> Evening
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="addHabitBtn">Add Habit</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Habit Modal -->
  <div class="modal fade" id="editHabitModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Habit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Habit Name</label>
            <input type="text" class="form-control" id="editHabitName">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" id="editHabitType">
              <option value="routine">Routine</option>
              <option value="health">Health</option>
              <option value="work">Work</option>
              <option value="personal">Personal</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">When?</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="editTimePeriod" id="editIsMorning" value="morning">
              <label class="form-check-label" for="editIsMorning">
                <i class="bi bi-sunrise"></i> Morning
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="editTimePeriod" id="editIsAfternoon" value="afternoon">
              <label class="form-check-label" for="editIsAfternoon">
                <i class="bi bi-sun"></i> Afternoon
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="editTimePeriod" id="editIsEvening" value="evening">
              <label class="form-check-label" for="editIsEvening">
                <i class="bi bi-moon-stars"></i> Evening
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="saveHabitBtn">Save Changes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Habits Management Script -->
  <script>
    const HabitsManager = {
      apiBase: API_BASE,
      editingHabitId: null,

      // Initialize
      init() {
        console.log('💪 Habits Manager initialized, API Base:', this.apiBase);
        this.loadHabits();
        this.attachEventListeners();
      },

      // Event listeners
      attachEventListeners() {
        document.getElementById('addHabitBtn').addEventListener('click', () => this.addHabit());
        document.getElementById('saveHabitBtn').addEventListener('click', () => this.saveHabit());
      },

      // Load habits
      async loadHabits() {
        try {
          const response = await fetch(this.apiBase + '/habits/read.php');
          const result = await response.json();

          if (result.success) {
            this.renderHabits(result.data);
          }
        } catch (error) {
          console.error('Failed to load habits:', error);
        }
      },

      // Render habits
      renderHabits(data) {
        const morningContainer = document.getElementById('morning-habits');
        const afternoonContainer = document.getElementById('afternoon-habits');
        const eveningContainer = document.getElementById('evening-habits');

        // Clear containers
        morningContainer.innerHTML = '';
        afternoonContainer.innerHTML = '';
        eveningContainer.innerHTML = '';

        if (data.morning.length === 0) {
          morningContainer.innerHTML = '<div class="text-center text-muted py-4"><p>No morning habits yet. Add one to get started!</p></div>';
        } else {
          data.morning.forEach(habit => {
            morningContainer.appendChild(this.createHabitCard(habit));
          });
        }

        if (data.afternoon.length === 0) {
          afternoonContainer.innerHTML = '<div class="text-center text-muted py-4"><p>No afternoon habits yet. Add one to get started!</p></div>';
        } else {
          data.afternoon.forEach(habit => {
            afternoonContainer.appendChild(this.createHabitCard(habit));
          });
        }

        if (data.evening.length === 0) {
          eveningContainer.innerHTML = '<div class="text-center text-muted py-4"><p>No evening habits yet. Add one to get started!</p></div>';
        } else {
          data.evening.forEach(habit => {
            eveningContainer.appendChild(this.createHabitCard(habit));
          });
        }
      },

      // Create habit card
      createHabitCard(habit) {
        const card = document.createElement('div');
        card.className = 'habit-card';
        
        const typeClass = `badge-${habit.habit_type}`;
        
        card.innerHTML = `
          <div class="flex-grow-1">
            <h6 class="mb-2">${this.escapeHtml(habit.habit_name)}</h6>
            <span class="habit-type-badge ${typeClass}">${habit.habit_type}</span>
          </div>
          <div class="habit-actions">
            <button class="btn-edit" onclick="HabitsManager.openEditModal(${habit.id}, '${this.escapeHtml(habit.habit_name)}', '${this.escapeHtml(habit.habit_type)}', ${habit.is_morning}, ${habit.is_afternoon}, ${habit.is_evening})">
              <i class="bi bi-pencil"></i> Edit
            </button>
            <button class="btn-delete" onclick="HabitsManager.deleteHabit(${habit.id})">
              <i class="bi bi-trash"></i> Delete
            </button>
          </div>
        `;
        
        return card;
      },

      // Add habit
      async addHabit() {
        const name = document.getElementById('habitName').value.trim();
        const type = document.getElementById('habitType').value;
        const timePeriod = document.querySelector('input[name="timePeriod"]:checked').value;

        if (!name) {
          alert('Please enter a habit name');
          return;
        }

        const isMorning = timePeriod === 'morning';
        const isAfternoon = timePeriod === 'afternoon';
        const isEvening = timePeriod === 'evening';

        try {
          const response = await fetch(this.apiBase + '/habits/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              habit_name: name,
              habit_type: type,
              is_morning: isMorning,
              is_afternoon: isAfternoon,
              is_evening: isEvening
            })
          });

          const result = await response.json();

          if (result.success) {
            // Clear form and close modal
            document.getElementById('habitName').value = '';
            document.getElementById('habitType').value = 'routine';
            document.getElementById('isMorning').checked = true;
            document.getElementById('isAfternoon').checked = false;
            document.getElementById('isEvening').checked = false;
            
            bootstrap.Modal.getInstance(document.getElementById('addHabitModal')).hide();
            
            // Reload habits
            this.loadHabits();
          } else {
            alert('Error: ' + (result.error || 'Failed to add habit'));
          }
        } catch (error) {
          console.error('Add habit failed:', error);
          alert('Failed to add habit');
        }
      },

      // Open edit modal
      openEditModal(habitId, name, type, isMorning, isAfternoon, isEvening) {
        this.editingHabitId = habitId;
        document.getElementById('editHabitName').value = name;
        document.getElementById('editHabitType').value = type;
        
        // Set the appropriate radio button based on the time period flags
        if (isMorning) {
          document.getElementById('editIsMorning').checked = true;
        } else if (isAfternoon) {
          document.getElementById('editIsAfternoon').checked = true;
        } else if (isEvening) {
          document.getElementById('editIsEvening').checked = true;
        }
        
        new bootstrap.Modal(document.getElementById('editHabitModal')).show();
      },

      // Save habit
      async saveHabit() {
        const habitId = this.editingHabitId;
        const name = document.getElementById('editHabitName').value.trim();
        const type = document.getElementById('editHabitType').value;
        const timePeriod = document.querySelector('input[name="editTimePeriod"]:checked').value;

        if (!name) {
          alert('Please enter a habit name');
          return;
        }

        const isMorning = timePeriod === 'morning';
        const isAfternoon = timePeriod === 'afternoon';
        const isEvening = timePeriod === 'evening';

        try {
          const response = await fetch(this.apiBase + '/habits/update.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              habit_id: habitId,
              habit_name: name,
              habit_type: type,
              is_morning: isMorning,
              is_afternoon: isAfternoon,
              is_evening: isEvening
            })
          });

          const result = await response.json();

          if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('editHabitModal')).hide();
            this.loadHabits();
          } else {
            alert('Error: ' + (result.error || 'Failed to save habit'));
          }
        } catch (error) {
          console.error('Save habit failed:', error);
          alert('Failed to save habit');
        }
      },

      // Delete habit
      async deleteHabit(habitId) {
        if (!confirm('Delete this habit? This cannot be undone.')) return;

        try {
          const response = await fetch(this.apiBase + '/habits/delete.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ habit_id: habitId })
          });

          const result = await response.json();

          if (result.success) {
            this.loadHabits();
          } else {
            alert('Error: ' + (result.error || 'Failed to delete habit'));
          }
        } catch (error) {
          console.error('Delete habit failed:', error);
          alert('Failed to delete habit');
        }
      },

      // Utility
      escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
      }
    };

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => HabitsManager.init());
  </script>
</body>
</html>
