<?php
/**
 * ADHD Dashboard - User Settings Page
 * User preferences, notifications, password, timezone, etc.
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

// Fetch user preferences
$preferences = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM user_preferences WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    // Table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - ADHD Dashboard</title>
  
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

    .settings-container { max-width: 900px; margin: 0 auto; }
    
    .nav-tabs {
      border-bottom: 2px solid var(--color-border-light);
      margin-bottom: 2rem;
      gap: 0.5rem;
    }

    .nav-tabs .nav-link {
      color: var(--color-text-muted);
      border: none;
      padding: 1rem 1.5rem;
      font-weight: 600;
      position: relative;
      transition: all 0.2s;
    }

    .nav-tabs .nav-link:hover {
      color: var(--color-urgent);
    }

    .nav-tabs .nav-link.active {
      color: var(--color-urgent);
      background: none;
      border-bottom: 3px solid var(--color-urgent);
    }

    .tab-content {
      background: var(--color-bg-primary);
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    }

    .form-group { margin-bottom: 1.5rem; }
    .form-group:last-child { margin-bottom: 0; }

    .form-label {
      font-weight: 600;
      color: var(--color-text-dark);
      margin-bottom: 0.5rem;
      display: block;
    }

    .form-control, .form-select {
      border: 2px solid var(--color-border-light);
      border-radius: 8px;
      padding: 0.7rem;
      font-family: 'Nunito Sans', sans-serif;
      transition: border-color 0.2s;
      background-color: var(--color-bg-primary);
      color: var(--color-text-dark);
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--color-urgent);
      box-shadow: 0 0 0 3px rgba(255, 179, 0, 0.1);
      outline: none;
    }

    .form-text { color: var(--color-text-muted); font-size: 0.85rem; margin-top: 0.3rem; }

    .checkbox-group {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .checkbox-item {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      padding: 1rem;
      background: var(--color-bg-secondary);
      border-radius: 8px;
      transition: background 0.2s;
      color: var(--color-text-dark);
    }

    .checkbox-item:hover {
      background: var(--color-bg-calm);
    }

    .checkbox-item input[type="checkbox"] { margin-top: 0.3rem; cursor: pointer; flex-shrink: 0; }
    .checkbox-item label { margin: 0; cursor: pointer; flex: 1; }

    .btn-save {
      background-color: var(--color-calm);
      border-color: var(--color-calm);
      color: white;
      padding: 0.9rem 2rem;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.2s;
      border: none;
      cursor: pointer;
      font-size: 1rem;
    }

    .btn-save:hover {
      background-color: var(--color-secondary);
      border-color: var(--color-secondary);
      color: white;
    }

    .time-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .avatar-section {
      display: flex;
      align-items: flex-start;
      gap: 2rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }

    .avatar-preview {
      flex-shrink: 0;
      text-align: center;
    }

    .avatar-preview img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #dee2e6;
    }

    .avatar-controls {
      flex: 1;
      min-width: 250px;
    }

    .section-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: #333;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .section-divider {
      border-top: 1px solid #e5e7eb;
      margin: 2rem 0;
    }

    @media (max-width: 600px) {
      .nav-tabs .nav-link {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
      }
      
      .avatar-section {
        flex-direction: column;
        gap: 1rem;
      }
      
      .time-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body <?php echo isset($user['theme_preference']) ? "data-theme='{$user['theme_preference']}'" : "data-theme='light'"; ?>>
  <?php $current_page = 'settings'; require_once __DIR__ . '/header.php'; ?>

  <!-- Page Header -->
  <div class="bg-light border-bottom py-4" style="margin-bottom: 2rem;">
    <div class="container">
      <h1 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h1>
      <p class="text-muted mt-2">Manage your preferences, notifications, and account settings</p>
    </div>
  </div>

  <!-- Main Content -->
  <div id="main-content" class="container">
    <div class="settings-container">

      <!-- Navigation Tabs -->
      <ul class="nav nav-tabs" role="tablist" aria-label="Settings sections">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-selected="true" aria-controls="profile">
            <i class="bi bi-person-circle me-2" aria-hidden="true"></i>Profile
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences" type="button" role="tab" aria-selected="false" aria-controls="preferences">
            <i class="bi bi-sliders me-2" aria-hidden="true"></i>Preferences
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab" aria-selected="false" aria-controls="notifications">
            <i class="bi bi-bell me-2" aria-hidden="true"></i>Notifications
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button" role="tab" aria-selected="false" aria-controls="account">
            <i class="bi bi-lock me-2" aria-hidden="true"></i>Account
          </button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content">

        <!-- PROFILE TAB -->
        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
          <h3 class="section-title"><i class="bi bi-image"></i>Your Profile</h3>
          
          <!-- Avatar Upload Section -->
          <div class="avatar-section">
            <div class="avatar-preview">
              <img id="avatar_preview" src="<?php echo !empty($user['avatar_url']) ? APP_SUBDIR . '/' . htmlspecialchars($user['avatar_url']) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22%3E%3Crect fill=%22%23ddd%22 width=%22120%22 height=%22120%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-family=%22Arial%22 font-size=%2216%22 fill=%22%23999%22%3ENo Image%3C/text%3E%3C/svg%3E'; ?>" alt="Avatar preview for your profile">
              <p style="margin-top: 1rem; font-weight: 600; color: #333;">
                <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?>
              </p>
            </div>
            
            <div class="avatar-controls">
              <div class="form-group">
                <label for="avatar_upload" class="form-label">Upload New Picture</label>
                <input 
                  type="file" 
                  class="form-control" 
                  id="avatar_upload" 
                  accept="image/*"
                  aria-label="Upload profile picture"
                  aria-describedby="avatar-help"
                >
                <small id="avatar-help" class="form-text">
                  <i class="bi bi-info-circle me-1"></i>Max 2MB • Formats: JPG, PNG, GIF, WebP • Auto-resized to 200×200px
                </small>
              </div>
              
              <?php if (!empty($user['avatar_url'])): ?>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAvatar()" aria-label="Remove profile picture">
                  <i class="bi bi-trash me-1"></i>Remove Picture
                </button>
              <?php endif; ?>
            </div>
          </div>

          <div class="section-divider"></div>

          <!-- Contact Information Section -->
          <h5 style="margin-bottom: 1rem; color: #333;">Personal Information</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" 
                  value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" placeholder="First Name">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" 
                  value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" placeholder="Last Name">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" data-current-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="your@email.com">
            <small class="form-text"><i class="bi bi-info-circle me-1"></i>Changing your email requires verification - a confirmation link will be sent</small>
          </div>

          <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="your_username">
            <small class="form-text"><i class="bi bi-info-circle me-1"></i>Usernames must be unique and can contain letters, numbers, underscores, and hyphens</small>
          </div>

          <div class="form-group">
            <label for="phone_number_edit" class="form-label">Phone Number</label>
            <input type="tel" class="form-control" id="phone_number_edit" name="phone_number_edit" 
              value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="(123) 456-7890" oninput="formatPhoneInputRealtime(this)">
            <small class="form-text">Format: (123) 456-7890</small>
          </div>

          <div class="form-group">
            <label for="mailing_address" class="form-label">Mailing Address</label>
            <textarea class="form-control" id="mailing_address" name="mailing_address" placeholder="123 Main St, City, State 12345" style="resize: vertical; min-height: 80px;"><?php echo htmlspecialchars($user['mailing_address'] ?? ''); ?></textarea>
            <small class="form-text">Your complete mailing address (appears on printed profile)</small>
          </div>

          <div class="form-group">
            <label for="timezone" class="form-label"><i class="bi bi-globe me-1"></i>Timezone</label>
            <select class="form-select" id="timezone" name="timezone">
              <option value="UTC" <?php echo ($user['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC (Coordinated Universal Time)</option>
              <option value="America/New_York" <?php echo ($user['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time (ET)</option>
              <option value="America/Chicago" <?php echo ($user['timezone'] ?? '') === 'America/Chicago' ? 'selected' : ''; ?>>Central Time (CT)</option>
              <option value="America/Denver" <?php echo ($user['timezone'] ?? '') === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time (MT)</option>
              <option value="America/Los_Angeles" <?php echo ($user['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time (PT)</option>
              <option value="Europe/London" <?php echo ($user['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
              <option value="Europe/Paris" <?php echo ($user['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : ''; ?>>Paris (CET)</option>
              <option value="Asia/Tokyo" <?php echo ($user['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo (JST)</option>
              <option value="Australia/Sydney" <?php echo ($user['timezone'] ?? '') === 'Australia/Sydney' ? 'selected' : ''; ?>>Sydney (AEDT)</option>
            </select>
            <small class="form-text">Used for scheduling tasks and reminders</small>
          </div>
        </div>

        <!-- PREFERENCES TAB -->
        <div class="tab-pane fade" id="preferences" role="tabpanel">
          <h3 class="section-title"><i class="bi bi-sliders"></i>Display Preferences</h3>
          
          <h5 style="margin-bottom: 1rem; color: #333;">Color Theme</h5>
          <div class="form-group">
            <div class="row">
              <div class="col-md-6">
                <select class="form-select" id="theme_preference" name="theme_preference">
                  <option value="light" <?php echo ($user['theme_preference'] ?? 'light') === 'light' ? 'selected' : ''; ?>>☀️ Light Theme</option>
                  <option value="dark" <?php echo ($user['theme_preference'] ?? 'light') === 'dark' ? 'selected' : ''; ?>>🌙 Dark Theme</option>
                  <option value="blue" <?php echo ($user['theme_preference'] ?? 'light') === 'blue' ? 'selected' : ''; ?>>💙 Blue Theme</option>
                  <option value="green" <?php echo ($user['theme_preference'] ?? 'light') === 'green' ? 'selected' : ''; ?>>💚 Green Theme</option>
                  <option value="purple" <?php echo ($user['theme_preference'] ?? 'light') === 'purple' ? 'selected' : ''; ?>>💜 Purple Theme</option>
                </select>
                <small class="form-text">Choose your preferred color theme for the dashboard</small>
              </div>
            </div>
          </div>

          <div class="section-divider"></div>

          <h5 style="margin-bottom: 1rem; color: #333;">Pomodoro Settings</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="pomodoro_duration" class="form-label">Pomodoro Duration (minutes)</label>
                <select class="form-select" id="pomodoro_duration" name="pomodoro_duration">
                  <option value="15" <?php echo ($preferences['pomodoro_duration_minutes'] ?? 25) == 15 ? 'selected' : ''; ?>>15 minutes</option>
                  <option value="20" <?php echo ($preferences['pomodoro_duration_minutes'] ?? 25) == 20 ? 'selected' : ''; ?>>20 minutes</option>
                  <option value="25" <?php echo ($preferences['pomodoro_duration_minutes'] ?? 25) == 25 ? 'selected' : ''; ?>>25 minutes (standard)</option>
                  <option value="30" <?php echo ($preferences['pomodoro_duration_minutes'] ?? 25) == 30 ? 'selected' : ''; ?>>30 minutes</option>
                  <option value="45" <?php echo ($preferences['pomodoro_duration_minutes'] ?? 25) == 45 ? 'selected' : ''; ?>>45 minutes</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="break_duration" class="form-label">Break Duration (minutes)</label>
                <select class="form-select" id="break_duration" name="break_duration">
                  <option value="1" <?php echo ($preferences['pomodoro_break_duration_minutes'] ?? 5) == 1 ? 'selected' : ''; ?>>1 minute</option>
                  <option value="3" <?php echo ($preferences['pomodoro_break_duration_minutes'] ?? 5) == 3 ? 'selected' : ''; ?>>3 minutes</option>
                  <option value="5" <?php echo ($preferences['pomodoro_break_duration_minutes'] ?? 5) == 5 ? 'selected' : ''; ?>>5 minutes (standard)</option>
                  <option value="10" <?php echo ($preferences['pomodoro_break_duration_minutes'] ?? 5) == 10 ? 'selected' : ''; ?>>10 minutes</option>
                  <option value="15" <?php echo ($preferences['pomodoro_break_duration_minutes'] ?? 5) == 15 ? 'selected' : ''; ?>>15 minutes</option>
                </select>
              </div>
            </div>
          </div>

          <div class="checkbox-group" style="margin-top: 1rem;">
            <div class="checkbox-item">
              <input type="checkbox" id="pomodoro_sound" name="pomodoro_sound" 
                <?php echo ($preferences['pomodoro_sound_enabled'] ?? true) ? 'checked' : ''; ?>>
              <div>
                <label for="pomodoro_sound" style="font-weight: 600;">Enable Pomodoro Sound</label>
                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">Play a sound when pomodoro timer completes</p>
              </div>
            </div>
            <div class="checkbox-item">
              <input type="checkbox" id="show_badges" name="show_badges" 
                <?php echo ($preferences['show_recent_badges_widget'] ?? false) ? 'checked' : ''; ?>>
              <div>
                <label for="show_badges" style="font-weight: 600;">Show Recent Badges Widget</label>
                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">Display recently earned badges on dashboard</p>
              </div>
            </div>
            <div class="checkbox-item">
              <input type="checkbox" id="focus_planning" name="focus_planning" 
                <?php echo ($preferences['focus_planning_enabled'] ?? false) ? 'checked' : ''; ?>>
              <div>
                <label for="focus_planning" style="font-weight: 600;">Enable Focus Planning</label>
                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">Show planning view before starting pomodoro</p>
              </div>
            </div>
          </div>

          <div class="section-divider" style="margin: 2rem 0;"></div>

          <h5 style="margin-bottom: 1rem; color: #333;">Other Preferences</h5>
          <p style="color: #666; font-size: 0.95rem;">Additional preferences managed here</p>
        </div>

        <!-- NOTIFICATIONS TAB -->
        <div class="tab-pane fade" id="notifications" role="tabpanel">
          <h3 class="section-title"><i class="bi bi-bell"></i>Notification Settings</h3>

          <h5 style="margin-bottom: 1rem; color: #333;">Which channels?</h5>
          <div class="checkbox-group">
            <div class="checkbox-item">
              <input type="checkbox" id="email_notifications" name="email_notifications" 
                <?php echo ($preferences['email_notifications_enabled'] ?? true) ? 'checked' : ''; ?>>
              <div>
                <label for="email_notifications" style="font-weight: 600;">Email Notifications</label>
                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">Receive notifications via email</p>
              </div>
            </div>
            <div class="checkbox-item">
              <input type="checkbox" id="in_app_notifications" name="in_app_notifications" 
                <?php echo ($preferences['in_app_notifications_enabled'] ?? true) ? 'checked' : ''; ?>>
              <div>
                <label for="in_app_notifications" style="font-weight: 600;">In-App Notifications</label>
                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">See notifications in the bell icon on dashbard</p>
              </div>
            </div>
            <div class="checkbox-item">
              <input type="checkbox" id="sms_notifications" name="sms_notifications" 
                <?php echo ($preferences['sms_notifications_enabled'] ?? false) ? 'checked' : ''; ?>>
              <div>
                <label for="sms_notifications" style="font-weight: 600;">SMS Notifications</label>
                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">Receive text messages (requires phone & carrier)</p>
              </div>
            </div>
          </div>

          <div class="section-divider"></div>

          <h5 style="margin-bottom: 1rem; color: #333;">SMS Phone Number</h5>
          <p style="color: #666; font-size: 0.95rem; margin-bottom: 1rem;">Phone number for SMS reminders (separate from your main contact number)</p>
          
          <div class="form-group">
            <label for="notification_country_code" class="form-label">Country Code</label>
            <select class="form-select" id="notification_country_code" name="notification_country_code">
              <option value="1" selected>United States (+1)</option>
              <option value="1">Canada (+1)</option>
              <option value="44">United Kingdom (+44)</option>
              <option value="61">Australia (+61)</option>
              <option value="1">Caribbean region (+1)</option>
              <option value="33">France (+33)</option>
              <option value="49">Germany (+49)</option>
              <option value="39">Italy (+39)</option>
              <option value="34">Spain (+34)</option>
              <option value="31">Netherlands (+31)</option>
              <option value="46">Sweden (+46)</option>
              <option value="358">Finland (+358)</option>
              <option value="91">India (+91)</option>
              <option value="65">Singapore (+65)</option>
              <option value="886">Taiwan (+886)</option>
              <option value="81">Japan (+81)</option>
              <option value="86">China (+86)</option>
              <option value="82">South Korea (+82)</option>
            </select>
            <small class="text-muted">Select your country code. Default is USA.</small>
          </div>

          <div class="form-group">
            <label for="notification_phone" class="form-label">Phone Number</label>
            <input type="tel" class="form-control" id="notification_phone" name="notification_phone" 
              placeholder="Enter phone number (without country code)" oninput="formatPhoneInputRealtime(this)">
            <small class="text-muted">Enter your phone number without the country code. Full international format will be used for SMS delivery.</small>
          </div>

          <div class="section-divider"></div>

          <h5 style="margin-bottom: 1rem; color: #333;">Email Frequency</h5>
          <div class="form-group">
            <select class="form-select" id="email_reminder_type" name="email_reminder_type">
              <option value="immediate" <?php echo ($preferences['email_reminder_type'] ?? 'immediate') === 'immediate' ? 'selected' : ''; ?>>Immediate (as they happen)</option>
              <option value="daily_digest" <?php echo ($preferences['email_reminder_type'] ?? 'immediate') === 'daily_digest' ? 'selected' : ''; ?>>Daily Digest (once per day)</option>
              <option value="per_item" <?php echo ($preferences['email_reminder_type'] ?? 'immediate') === 'per_item' ? 'selected' : ''; ?>>Per Item (grouped)</option>
            </select>
          </div>

          <div class="section-divider"></div>

          <h5 style="margin-bottom: 1rem; color: #333;">Quiet Hours</h5>
          <p style="color: #666; font-size: 0.95rem; margin-bottom: 1rem;">No notifications will be sent outside these hours</p>
          <div class="time-row">
            <div class="form-group">
              <label for="quiet_hours_start" class="form-label">Start Time</label>
              <input type="time" class="form-control" id="quiet_hours_start" name="quiet_hours_start" 
                value="<?php echo htmlspecialchars($preferences['quiet_hours_start'] ?? '21:00'); ?>">
            </div>
            <div class="form-group">
              <label for="quiet_hours_end" class="form-label">End Time</label>
              <input type="time" class="form-control" id="quiet_hours_end" name="quiet_hours_end" 
                value="<?php echo htmlspecialchars($preferences['quiet_hours_end'] ?? '08:00'); ?>">
            </div>
          </div>
        </div>

        <!-- ACCOUNT TAB -->
        <div class="tab-pane fade" id="account" role="tabpanel">
          <h3 class="section-title"><i class="bi bi-lock"></i>Account & Security</h3>
          
          <div class="form-group">
            <a href="<?php echo Config::redirectUrl('/views/profile.php'); ?>" class="btn btn-outline-primary" style="text-decoration: none;">
              <i class="bi bi-eye me-1"></i>View Your Profile
            </a>
          </div>

          <div class="section-divider"></div>

          <h5 style="margin-bottom: 1rem; color: #333;">Password</h5>
          <div class="form-group">
            <button type="button" class="btn btn-primary" onclick="showChangePasswordModal()">
              <i class="bi bi-key me-1"></i>Change Password
            </button>
          </div>
        </div>

      </div>

      <!-- Save Button -->
      <div style="text-align: center; margin-top: 2rem;">
        <button type="button" class="btn btn-save" onclick="saveSettings()">
          <i class="bi bi-check-circle me-2"></i>Save All Settings
        </button>
      </div>

    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Format phone number as (123) 456-7890
    function formatPhoneNumber(phone) {
      const digits = phone.replace(/\D/g, '');
      if (digits.length === 10) {
        return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
      }
      return phone;
    }

    // Real-time phone formatting as user types (maintains cursor position)
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
      
      // Only update if different (preserves active typing)
      if (input.value !== formatted) {
        const prevLength = input.value.length;
        input.value = formatted;
        
        // Adjust cursor position to keep it in logical position
        let newPos = cursorPos;
        if (prevLength < formatted.length) {
          newPos = cursorPos + 1; // Account for added formatting char
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

    // Show change password modal
    function showChangePasswordModal() {
      alert('Password change feature coming soon');
    }
    // Avatar preview
    document.getElementById('avatar_upload')?.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        if (file.size > 2 * 1024 * 1024) {
          alert('File size must be less than 2MB');
          e.target.value = '';
          return;
        }
        
        const reader = new FileReader();
        reader.onload = (event) => {
          document.getElementById('avatar_preview').src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    function removeAvatar() {
      if (confirm('Remove your profile picture?')) {
        fetch('../api/profile/delete-avatar.php', { method: 'POST' })
          .then(r => r.json())
          .then(json => {
            if (json.success) {
              document.getElementById('avatar_preview').src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22%3E%3Crect fill=%22%23ddd%22 width=%22120%22 height=%22120%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-family=%22Arial%22 font-size=%2216%22 fill=%22%23999%22%3ENo Image%3C/text%3E%3C/svg%3E';
              document.querySelector('[onclick="removeAvatar()"]')?.remove();
              alert('Profile picture removed');
            }
          });
      }
    }

    function saveSettings() {
      const avatarFile = document.getElementById('avatar_upload');
      const formData = new FormData();
      const currentEmail = document.getElementById('email').dataset.currentEmail || '<?php echo htmlspecialchars($user['email'] ?? ''); ?>';
      const newEmail = document.getElementById('email').value;
      const emailChanged = currentEmail !== newEmail;
      
      // Add file if selected
      if (avatarFile && avatarFile.files.length > 0) {
        formData.append('avatar_upload', avatarFile.files[0]);
      }

      // Add all settings
      // Profile info (exclude email if it changed - we'll handle it separately)
      if (!emailChanged) {
        formData.append('email', newEmail);
      }
      formData.append('username', document.getElementById('username').value);
      formData.append('first_name', document.getElementById('first_name').value);
      formData.append('last_name', document.getElementById('last_name').value);
      formData.append('phone_number', formatPhoneNumber(document.getElementById('phone_number_edit').value));
      // Save notification_phone with country code for SMS international format
      const countryCode = document.getElementById('notification_country_code').value;
      const phoneNumber = document.getElementById('notification_phone').value.replace(/\D/g, '');
      formData.append('notification_phone', countryCode + phoneNumber);
      formData.append('mailing_address', document.getElementById('mailing_address').value);
      formData.append('timezone', document.getElementById('timezone').value);
      formData.append('theme', document.getElementById('theme_preference').value);
      
      // Preferences - Pomodoro
      formData.append('pomodoro_duration', document.getElementById('pomodoro_duration').value);
      formData.append('break_duration', document.getElementById('break_duration').value);
      formData.append('pomodoro_sound', document.getElementById('pomodoro_sound').checked ? 1 : 0);
      formData.append('show_badges_widget', document.getElementById('show_badges').checked ? 1 : 0);
      formData.append('focus_planning', document.getElementById('focus_planning').checked ? 1 : 0);
      
      // Notifications
      formData.append('email_notifications', document.getElementById('email_notifications').checked ? 1 : 0);
      formData.append('in_app_notifications', document.getElementById('in_app_notifications').checked ? 1 : 0);
      formData.append('sms_notifications', document.getElementById('sms_notifications').checked ? 1 : 0);
      formData.append('email_reminder_type', document.getElementById('email_reminder_type').value);
      formData.append('quiet_hours_start', document.getElementById('quiet_hours_start').value);
      formData.append('quiet_hours_end', document.getElementById('quiet_hours_end').value);

      // Upload avatar if present
      if (avatarFile && avatarFile.files.length > 0) {
        const avatarFormData = new FormData();
        avatarFormData.append('avatar_upload', avatarFile.files[0]);

        fetch('../api/profile/upload-avatar.php', { method: 'POST', body: avatarFormData })
          .then(r => r.json())
          .then(json => {
            if (!json.success) {
              alert('Avatar upload failed: ' + (json.error || 'Unknown error'));
              return;
            }
            // Continue with profile update
            updatePreferences(formData, newEmail, emailChanged);
          })
          .catch(e => {
            alert('Error uploading avatar');
            console.error(e);
          });
      } else {
        updatePreferences(formData, newEmail, emailChanged);
      }
    }

    function updatePreferences(formData, newEmail, emailChanged) {
      // If email changed, request verification first
      if (emailChanged) {
        const emailVerifyData = new FormData();
        emailVerifyData.append('new_email', newEmail);
        
        fetch('../api/profile/request-email-verification.php', { method: 'POST', body: emailVerifyData })
          .then(r => r.json())
          .then(json => {
            if (json.success) {
              // Now save other preferences
              fetch('../api/profile/preferences.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(json => {
                  if (json.success) {
                    alert('Settings saved successfully!\n\n✉️ Verification email sent to ' + newEmail + '\nPlease check your inbox to confirm the email change.');
                    setTimeout(() => location.reload(), 2000);
                  } else {
                    alert('Error saving preferences: ' + (json.error || 'Failed to save settings'));
                  }
                })
                .catch(e => {
                  alert('Error saving preferences');
                  console.error(e);
                });
            } else {
              alert('Error requesting email verification: ' + (json.error || 'Failed to send verification email'));
            }
          })
          .catch(e => {
            alert('Error requesting email verification');
            console.error(e);
          });
      } else {
        // No email change, just save preferences normally
        fetch('../api/profile/preferences.php', { method: 'POST', body: formData })
          .then(r => r.json())
          .then(json => {
            if (json.success) {
              alert('Settings saved successfully!');
              setTimeout(() => location.reload(), 500);
            } else {
              alert('Error: ' + (json.error || 'Failed to save settings'));
            }
          })
          .catch(e => {
            alert('Error saving settings');
            console.error(e);
          });
      }
    }

    // Initialize country code and phone number from stored value
    window.addEventListener('load', function() {
      const storedPhone = '<?php echo htmlspecialchars($user['notification_phone'] ?? ''); ?>';
      if (storedPhone) {
        // Common country codes sorted by length (3-digit, 2-digit, then 1-digit)
        const countryCodeMap = {
          '358': 'Finland', '886': 'Taiwan',
          '31': 'Netherlands', '33': 'France', '34': 'Spain', '39': 'Italy', 
          '44': 'United Kingdom', '46': 'Sweden', '49': 'Germany', '61': 'Australia', 
          '65': 'Singapore', '81': 'Japan', '82': 'South Korea', '86': 'China', '91': 'India',
          '1': 'US/Canada' // Must be last (1-digit fallback)
        };
        
        let countryCode = '';
        let phoneNumber = storedPhone;
        
        // Try 3-digit codes first
        if (storedPhone.substring(0, 3) in countryCodeMap) {
          countryCode = storedPhone.substring(0, 3);
          phoneNumber = storedPhone.substring(3);
        }
        // Try 2-digit codes
        else if (storedPhone.substring(0, 2) in countryCodeMap) {
          countryCode = storedPhone.substring(0, 2);
          phoneNumber = storedPhone.substring(2);
        }
        // Try 1-digit code (must be '1')
        else if (storedPhone.substring(0, 1) === '1' && storedPhone.length > 10) {
          countryCode = '1';
          phoneNumber = storedPhone.substring(1);
        }
        
        // Set the form fields
        if (countryCode && document.getElementById('notification_country_code')) {
          document.getElementById('notification_country_code').value = countryCode;
        }
        if (document.getElementById('notification_phone')) {
          document.getElementById('notification_phone').value = phoneNumber;
        }
      }
    });
  </script>

  <!-- Scroll to Top Component -->
  <script src="<?php echo Config::url('js'); ?>scroll-to-top.js"></script>
</body>
</html>
