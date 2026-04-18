-- ADHD Dashboard - Profile & Achievement Tables Migration
-- Paste these SQL statements directly into your database to create missing tables
-- Updated: April 3, 2026

-- =====================================================
-- ALTER USERS TABLE - Add new columns
-- =====================================================
ALTER TABLE users ADD COLUMN username VARCHAR(100);
ALTER TABLE users ADD COLUMN phone_number VARCHAR(20);
ALTER TABLE users ADD COLUMN mobile_carrier VARCHAR(50);
ALTER TABLE users ADD COLUMN low_energy_mode BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN task_reschedule_mode VARCHAR(20) DEFAULT 'manual';
ALTER TABLE users ADD COLUMN daily_habits_reset_time TIME DEFAULT '00:00:00';
ALTER TABLE users ADD COLUMN role ENUM('developer', 'admin', 'user') DEFAULT 'user';
CREATE UNIQUE INDEX idx_username ON users(username);

-- =====================================================
-- STREAKS TABLE - User activity streaks
-- =====================================================
CREATE TABLE IF NOT EXISTS streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    streak_type ENUM('pomodoro', 'task_completion') NOT NULL,
    current_count INT DEFAULT 0,
    streak_start_date DATE,
    streak_end_date DATE,
    best_count INT DEFAULT 0,
    best_start_date DATE,
    best_end_date DATE,
    last_activity_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_streak_type (user_id, streak_type),
    INDEX idx_user_id (user_id),
    INDEX idx_current_count (current_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BADGES TABLE - User achievement badges
-- =====================================================
CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_type VARCHAR(50) NOT NULL,
    badge_emoji VARCHAR(10) DEFAULT '',
    badge_name VARCHAR(100) NOT NULL,
    badge_description TEXT,
    unlock_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_badge_type (badge_type),
    UNIQUE KEY unique_user_badge (user_id, badge_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- USER_PREFERENCES TABLE - Detailed notification & display preferences
-- =====================================================
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    email_notifications_enabled BOOLEAN DEFAULT TRUE,
    email_reminder_type VARCHAR(50) DEFAULT 'immediate',
    in_app_notifications_enabled BOOLEAN DEFAULT TRUE,
    sms_notifications_enabled BOOLEAN DEFAULT FALSE,
    quiet_hours_start TIME DEFAULT '21:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00',
    show_recent_badges_widget BOOLEAN DEFAULT FALSE,
    pomodoro_duration_minutes INT DEFAULT 25,
    pomodoro_break_duration_minutes INT DEFAULT 5,
    pomodoro_sound_enabled BOOLEAN DEFAULT TRUE,
    pomodoro_sound_type VARCHAR(50) DEFAULT 'bell',
    focus_planning_enabled BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- POMODORO_SESSIONS TABLE - Timer session tracking
-- =====================================================
CREATE TABLE IF NOT EXISTS pomodoro_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT,
    duration_minutes INT DEFAULT 25,
    actual_duration_minutes INT,
    task_focus VARCHAR(255),
    was_paused BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'completed',
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_task_id (task_id),
    INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DAILY_HABITS TABLE - User's daily habit checklist
-- =====================================================
CREATE TABLE IF NOT EXISTS daily_habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    habit_name VARCHAR(255) NOT NULL,
    habit_type VARCHAR(50) DEFAULT 'routine',
    is_morning BOOLEAN DEFAULT TRUE,
    is_evening BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATIONS TABLE - System notifications for users
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    notification_type VARCHAR(50),
    related_task_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INITIALIZE DEFAULT PREFERENCES FOR EXISTING USERS
-- =====================================================
INSERT IGNORE INTO user_preferences (user_id)
SELECT id FROM users
WHERE id NOT IN (SELECT DISTINCT user_id FROM user_preferences);
