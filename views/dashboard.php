<?php
/**
 * ADHD Dashboard - Main Application
 * Requires authentication
 */

session_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/config.php';

// Redirect to login if not authenticated
if (!Auth::isAuthenticated()) {
    header('Location: ' . Config::redirectUrl('/views/login.php'));
    exit;
}

// Get current user
$user = Auth::getCurrentUser();

// Get user preferences (including SMS notifications setting)
require_once __DIR__ . '/../lib/database.php';
$pdo = db();
$stmt = $pdo->prepare('SELECT sms_notifications_enabled FROM user_preferences WHERE user_id = ? LIMIT 1');
$stmt->execute([$user['id']]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC);
$smsEnabled = $prefs['sms_notifications_enabled'] ?? 0;

// DEBUG
error_log("📱 Dashboard.php DEBUG - User ID: {$user['id']}, SMS Enabled: " . var_export($smsEnabled, true) . ", Raw prefs: " . json_encode($prefs));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ADHD Dashboard</title>
  
  <!-- Bootstrap 5.3.8 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.0/font/bootstrap-icons.css" rel="stylesheet">
  
  <!-- Google Fonts - Nunito Sans + Poppins + JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&family=Poppins:wght@600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  
  <!-- ADHD Custom Theme - Using Config::url() for environment-aware paths -->
  <link href="<?php echo Config::url('css'); ?>adhd-theme.css" rel="stylesheet">
  <link href="<?php echo Config::url('css'); ?>adhd-dashboard.css" rel="stylesheet">
  
  <!-- Minified CSS Bundle (Production) -->
  <link href="<?php echo Config::url('base'); ?>dist/dashboard.min.css" rel="stylesheet">
  
  <style>
    :root {
      --default-font: 'Nunito Sans', sans-serif;
      --heading-font: 'Poppins', sans-serif;
      --mono-font: 'JetBrains Mono', monospace;
    }
    
    body {
      font-family: var(--default-font);
      background-color: #F9FAFB;
    }
    
    h1, h2, h3, h4, h5, h6 {
      font-family: var(--heading-font);
    }

    /* Fix Send SMS button hover contrast */
    .btn-outline-success:hover,
    .btn-outline-success:focus {
      color: #fff !important;
      background-color: #198754;
      border-color: #198754;
    }

    .btn-outline-success:active,
    .btn-outline-success.active {
      color: #fff !important;
      background-color: #198754;
      border-color: #198754;
    }
  </style>
</head>
<body <?php echo isset($user['theme_preference']) ? "data-theme='{$user['theme_preference']}'" : "data-theme='light'"; ?>>
  <?php $current_page = 'dashboard'; require_once __DIR__ . '/header.php'; ?>

  <!-- Main Content -->
  <main id="main-content" class="container-fluid py-4" role="main" aria-label="Dashboard content">
    <!-- Main Page Heading -->
    <h1 class="visually-hidden">ADHD Dashboard</h1>
    
    <!-- Dashboard Grid -->
    <div class="row g-4">
      
      <!-- LEFT SIDEBAR: Navigation Menu -->
      <nav class="col-lg-1" aria-label="Dashboard navigation" style="position: sticky; top: 80px;">
        <div class="d-flex flex-column gap-2">
          <!-- Dashboard -->
          <a href="<?php echo Config::redirectUrl('/views/dashboard.php'); ?>" 
             class="nav-menu-item" 
             style="padding: 0.75rem 0.5rem; text-align: center; border-radius: 6px; 
                     background: #667eea; color: white; text-decoration: none; font-size: 0.85rem;
                     display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
                     transition: all 0.2s ease;"
             onmouseover="this.style.background='#5568d3';" 
             onmouseout="this.style.background='#667eea';"
             aria-label="Dashboard">
            <i class="bi bi-speedometer2" style="font-size: 1.2rem;" aria-hidden="true"></i>
            <span>Dashboard</span>
          </a>
            
            <!-- Task Planner -->
            <a href="<?php echo Config::redirectUrl('/views/task-planner.php'); ?>" 
               class="nav-menu-item" 
               style="padding: 0.75rem 0.5rem; text-align: center; border-radius: 6px; 
                       background: transparent; color: #667eea; text-decoration: none; font-size: 0.85rem;
                       border: 1px solid #667eea; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
                       transition: all 0.2s ease;"
               onmouseover="this.style.background='#f0f2ff'; this.style.borderColor='#5568d3'; this.style.color='#5568d3';" 
               onmouseout="this.style.background='transparent'; this.style.borderColor='#667eea'; this.style.color='#667eea';">
              <i class="bi bi-list-check" style="font-size: 1.2rem;"></i>
              <span>Planner</span>
            </a>
            
            <!-- Assigned Tasks -->
            <a href="<?php echo Config::redirectUrl('/views/delegated-tasks.php'); ?>" 
               class="nav-menu-item" 
               style="padding: 0.75rem 0.5rem; text-align: center; border-radius: 6px; 
                       background: transparent; color: #667eea; text-decoration: none; font-size: 0.85rem;
                       border: 1px solid #667eea; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
                       transition: all 0.2s ease;"
               onmouseover="this.style.background='#f0f2ff'; this.style.borderColor='#5568d3'; this.style.color='#5568d3';" 
               onmouseout="this.style.background='transparent'; this.style.borderColor='#667eea'; this.style.color='#667eea';">
              <i class="bi bi-list-task" style="font-size: 1.2rem;"></i>
              <span>Assigned</span>
            </a>
            
            <!-- Routines -->
            <a href="<?php echo Config::redirectUrl('/views/habits.php'); ?>" 
               class="nav-menu-item" 
               style="padding: 0.75rem 0.5rem; text-align: center; border-radius: 6px; 
                       background: transparent; color: #667eea; text-decoration: none; font-size: 0.85rem;
                       border: 1px solid #667eea; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
                       transition: all 0.2s ease;"
               onmouseover="this.style.background='#f0f2ff'; this.style.borderColor='#5568d3'; this.style.color='#5568d3';" 
               onmouseout="this.style.background='transparent'; this.style.borderColor='#667eea'; this.style.color='#667eea';">
              <i class="bi bi-calendar-check" style="font-size: 1.2rem;"></i>
              <span>Routines</span>
            </a>
            
            <!-- Settings (Future) -->
            <a href="<?php echo Config::redirectUrl('/views/settings.php'); ?>" 
               class="nav-menu-item" 
               style="padding: 0.75rem 0.5rem; text-align: center; border-radius: 6px; 
                       background: transparent; color: #667eea; text-decoration: none; font-size: 0.85rem;
                       border: 1px solid #667eea; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
                       transition: all 0.2s ease;"
               onmouseover="this.style.background='#f0f2ff'; this.style.borderColor='#5568d3'; this.style.color='#5568d3';" 
               onmouseout="this.style.background='transparent'; this.style.borderColor='#667eea'; this.style.color='#667eea';">
              <i class="bi bi-gear" style="font-size: 1.2rem;"></i>
              <span>Settings</span>
            </a>
          </div>
      </nav>
      
      <!-- MAIN COLUMN: Tabbed Interface -->
      <div class="col-lg-10">
        
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4 border-0" id="dashboardTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="today-focus-tab" data-bs-toggle="tab" data-bs-target="#today-focus-pane" type="button" role="tab" aria-controls="today-focus-pane" aria-selected="true">
              <i class="bi bi-star-fill text-warning me-2"></i>
              Today's Focus
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="inbox-capture-tab" data-bs-toggle="tab" data-bs-target="#inbox-capture-pane" type="button" role="tab" aria-controls="inbox-capture-pane" aria-selected="false">
              <i class="bi bi-inbox text-info me-2"></i>
              Inbox & Capture
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="routines-tab" data-bs-toggle="tab" data-bs-target="#routines-pane" type="button" role="tab" aria-controls="routines-pane" aria-selected="false">
              <i class="bi bi-clock-history text-warning me-2"></i>
              Daily Routines
            </button>
          </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="dashboardTabContent">
          
          <!-- Tab 1: Today's Focus (1-3-5 Priority) -->
          <div class="tab-pane fade show active" id="today-focus-pane" role="tabpanel" aria-labelledby="today-focus-tab">
            <div class="card card-spacious border-0 shadow-sm">
              <div class="card-header bg-transparent border-0 pb-2">
                <div class="d-flex justify-content-between align-items-start gap-3">
                  <div style="flex: 1;">
                    <h2 class="h5 mb-1">
                      <i class="bi bi-star-fill text-warning me-2"></i>
                      Today's Focus - 1-3-5 Priority
                    </h2>
                    <!-- Energy Level Selector - Compact -->
                    <div id="energy-selector-card" style="padding: 0.25rem 0; margin-top: 0.5rem;">
                      <div class="small fw-bold mb-1">⚡ How's your day?</div>
                      <div class="d-flex gap-2" id="energy-levels" style="flex-wrap: wrap;">
                        <!-- Energy levels will be inserted here by JavaScript -->
                      </div>
                    </div>
                  </div>
                    <button id="send-tasks-sms-btn" class="btn btn-sm btn-outline-success" title="Send today's tasks via SMS" style="flex-shrink: 0;">
                    <i class="bi bi-chat-left-text"></i> Send SMS
                  </button>
                </div>
              </div>
              
              <div class="card-body">
                <div class="row g-3" id="priority-slots-container">
                  <!-- 1 Big Task -->
                  <div class="col-md-12">
                    <div class="priority-slot urgency-today">
                      <h3 class="h6 fw-bold text-dark mb-2">
                        <i class="bi bi-exclamation-circle text-warning"></i> 1 Big Task
                      </h3>
                      <div id="priority-urgent" class="list-group">
                      </div>
                    </div>
                  </div>
                  
                  <!-- 3 Medium Tasks -->
                  <div class="col-md-12">
                    <div class="priority-slot urgency-soon">
                      <h3 class="h6 fw-bold text-dark mb-2">
                        <i class="bi bi-list-check text-warning"></i> 3 Medium Tasks
                      </h3>
                      <div class="list-group">
                        <div class="priority-secondary list-group-item d-flex gap-3 p-3"></div>
                        <div class="priority-secondary list-group-item d-flex gap-3 p-3"></div>
                        <div class="priority-secondary list-group-item d-flex gap-3 p-3"></div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- 5 Small Tasks -->
                  <div class="col-md-12">
                    <div class="priority-slot urgency-calm">
                      <h3 class="h6 fw-bold text-dark mb-2">
                        <i class="bi bi-lightning text-warning"></i> 5 Quick Wins
                      </h3>
                      <div class="list-group">
                        <div class="priority-calm list-group-item d-flex gap-3 p-2"></div>
                        <div class="priority-calm list-group-item d-flex gap-3 p-2"></div>
                        <div class="priority-calm list-group-item d-flex gap-3 p-2"></div>
                        <div class="priority-calm list-group-item d-flex gap-3 p-2"></div>
                        <div class="priority-calm list-group-item d-flex gap-3 p-2"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Permission Slip (shown when empty battery selected) -->
              <div id="permission-slip" style="display: none;">
                <!-- Centered Container -->
                <div style="display: flex; justify-content: center; padding: 0 1rem;">
                  <!-- Notebook Paper Styled Note -->
                  <div style="
                    width: 100%;
                    max-width: 520px;
                    border: 2px dashed #8B7355;
                    padding: 2rem 1.5rem 1.5rem 3rem;
                    text-align: left;
                    font-family: 'Segoe Print', 'Comic Sans MS', cursive, sans-serif;
                    background-color: #FFF8DC;
                    background-image: 
                      linear-gradient(90deg, #D4A574 2px, transparent 2px),
                      repeating-linear-gradient(
                        0deg,
                        #E8D7C9,
                        #E8D7C9 1px,
                        transparent 1px,
                        transparent 28px
                      ),
                      repeating-linear-gradient(
                        0deg,
                        #4A90E2,
                        #4A90E2 1px,
                        transparent 1px,
                        transparent 28px
                      );
                    background-size: 100% 100%, 100% 28px, 100% 28px;
                    background-position: 0 0, 0 2rem, 0 2rem;
                    background-repeat: repeat;
                    border-radius: 4px;
                    box-shadow: inset -2px -2px 4px rgba(0,0,0,0.05);
                    position: relative;
                    margin-top: 0rem;
                  ">
                    <!-- Red margin line -->
                    <div style="
                      position: absolute;
                      left: 18px;
                      top: 0;
                      bottom: 0;
                      width: 2px;
                      background-color: rgba(220, 85, 85, 0.3);
                    "></div>
                    
                    <!-- Content -->
                    <div style="font-size: 0.9rem; line-height: 1.8; color: #333; position: relative; z-index: 1;">
                      <div style="margin-bottom: 0.8rem;">To Whom It May Concern:</div>
                      <div style="margin-bottom: 1rem;">
                        On <span id="permission-slip-date"></span>, <span id="permission-slip-name"></span> has my permission to postpone all tasks, to reset and recharge.
                      </div>
                      <div style="margin-bottom: 1rem; font-size: 0.85rem;">Tasks can resume tomorrow.</div>
                      <div style="margin-top: 1.5rem;">Thank you,</div>
                      <div style="font-family: 'Lucida Handwriting', cursive; font-size: 1.2rem; margin-top: 0.5rem;">
                        <span id="permission-slip-signature"></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 2: Inbox & Brain Dump -->
          <div class="tab-pane fade" id="inbox-capture-pane" role="tabpanel" aria-labelledby="inbox-capture-tab">
            <!-- Brain Dump / Capture Form -->
            <div class="card card-spacious border-0 shadow-sm mb-4 bg-capture">
              <div class="card-body">
                <h2 class="card-title h5 mb-3">
                  <i class="bi bi-inbox text-warning me-2"></i>
                  Brain Dump - Capture Your Thoughts
                </h2>
                <p class="text-muted small mb-3">
                  Everything goes here first. No judgment, no organization yet. 
                  Just add what's on your mind.
                </p>
                
                <!-- Quick Capture Form -->
                <form id="brain-dump-form" class="d-flex gap-2">
                  <input 
                    id="brain-dump-input"
                    type="text" 
                    class="form-control form-control-lg" 
                    placeholder="What do you need to do? Add it here..."
                    aria-label="Capture new task"
                  >
                  <button id="submit-capture-btn" type="submit" class="btn btn-primary btn-lg" aria-label="Capture task">
                    <i class="bi bi-plus-lg"></i>
                  </button>
                </form>
              </div>
            </div>

            <!-- Inbox (Unorganized Captures) -->
            <div class="card card-spacious border-0 shadow-sm">
              <div class="card-header bg-transparent border-0 pb-3">
                <h2 class="h5 mb-0">
                  <i class="bi bi-inbox text-info me-2"></i>
                  Inbox - Need to Review & Sort
                </h2>
                <p class="text-muted small mt-2 mb-0">
                  Items captured but not yet organized into your priority list.
                </p>
              </div>
              
              <div class="card-body">
                <ul id="inbox-list" class="list-group list-group-flush">
                  <li class="list-group-item text-center text-muted">
                    📭 Inbox is empty. Capture something to get started!
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Tab 3: Daily Routines -->
          <div class="tab-pane fade" id="routines-pane" role="tabpanel" aria-labelledby="routines-tab">
            <div class="card card-spacious border-0 shadow-sm">
              <div class="card-header bg-transparent border-0 pb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <h2 class="h5 mb-0">
                    <i class="bi bi-clock-history text-warning me-2"></i>
                    Daily Routines
                  </h2>
                  <div class="d-flex gap-2">
                  <button id="refresh-habits-btn" class="btn btn-sm btn-outline-primary" title="Refresh habits to check for daily reset">
                      <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button id="send-habits-sms-btn" class="btn btn-sm btn-outline-success" title="Send unchecked habits via SMS">
                      <i class="bi bi-chat-left-text"></i> Send SMS
                    </button>
                    <a href="habits.php" class="btn btn-sm btn-outline-secondary">Manage</a>
                  </div>
                </div>
              </div>
              
              <div class="card-body">
                <!-- Morning Habits -->
                <div class="mb-3">
                  <h6 class="fw-bold mb-2">
                    <i class="bi bi-sunrise text-warning"></i> Morning
                  </h6>
                  <div id="morning-habits-list" class="list-group">
                    <div class="text-center text-muted small py-2">Loading...</div>
                  </div>
                </div>

                <!-- Afternoon Habits -->
                <hr>
                <div class="mb-3">
                  <h6 class="fw-bold mb-2">
                    <i class="bi bi-sun text-warning"></i> Afternoon
                  </h6>
                  <div id="afternoon-habits-list" class="list-group">
                    <div class="text-center text-muted small py-2">Loading...</div>
                  </div>
                </div>

                <!-- Evening Habits -->
                <hr>
                <div>
                  <h6 class="fw-bold mb-2">
                    <i class="bi bi-moon-stars text-warning"></i> Evening
                  </h6>
                  <div id="evening-habits-list" class="list-group">
                    <div class="text-center text-muted small py-2">Loading...</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-light border-top mt-5 py-4">
    <div class="container-fluid text-center text-muted small">
      <p class="mb-0">ADHD Dashboard • Focus on what matters • Zero judgment</p>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Set correct API base and user info for dashboard.js -->
  <script>
    window._apiBase = "<?php echo Config::url('api'); ?>";
    window._currentUser = {
      name: "<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>",
      firstName: "<?php echo htmlspecialchars($user['first_name']); ?>",
      lastName: "<?php echo htmlspecialchars($user['last_name']); ?>",
      email: "<?php echo htmlspecialchars($user['email']); ?>",
      smsNotificationsEnabled: <?php echo $smsEnabled ? 'true' : 'false'; ?>
    };
    
    // DEBUG: Log SMS notifications enabled status
    console.log('🔔 SMS Notifications Enabled:', window._currentUser.smsNotificationsEnabled);

    // Handle tab activation via URL hash (e.g., #routines-pane)
    document.addEventListener('DOMContentLoaded', function() {
      const hash = window.location.hash;
      if (hash === '#routines-pane') {
        const routinesTab = document.getElementById('routines-tab');
        if (routinesTab) {
          const tab = new bootstrap.Tab(routinesTab);
          tab.show();
          // Scroll to the tab area
          routinesTab.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  </script>

  <!-- Utility Modules (Load first, before API wrappers and dashboard) -->
  <script src="<?php echo Config::url('js'); ?>utils/api-helper.js"></script>
  <script src="<?php echo Config::url('js'); ?>utils/dom-helper.js"></script>
  <script src="<?php echo Config::url('js'); ?>utils/theme-manager.js"></script>
  <script src="<?php echo Config::url('js'); ?>utils/notification-handler.js"></script>
  <script src="<?php echo Config::url('js'); ?>utils/form-validator.js"></script>
  <script src="<?php echo Config::url('js'); ?>utils/preferences.js"></script>
  <script src="<?php echo Config::url('js'); ?>components/modal-manager.js"></script>
  
  <!-- API Wrappers (Load after utilities) -->
  <script src="<?php echo Config::url('js'); ?>api/dashboard-api.js"></script>
  <script src="<?php echo Config::url('js'); ?>api/admin-api.js"></script>

  <!-- Unified Task Modal - Using Config::url() for environment-aware paths -->
  <script src="<?php echo Config::url('js'); ?>unified-task-modal.js"></script>
  
  <!-- Dashboard Minified Bundle (Production) -->
  <!-- Falls back to individual files if bundle not available -->
  <script src="<?php echo Config::url('base'); ?>dist/dashboard.min.js"></script>
  <script>
    // Fallback: Load individual files if minified bundle fails to load Dashboard
    if (typeof Dashboard === 'undefined') {
      console.warn('Dashboard bundle not loaded, using individual files');
      const script = document.createElement('script');
      script.src = '<?php echo Config::url('js'); ?>dashboard.js';
      document.head.appendChild(script);
    }
  </script>

  <!-- Scroll to Top Component -->
  <script src="<?php echo Config::url('js'); ?>scroll-to-top.js"></script>
</body>
</html>
