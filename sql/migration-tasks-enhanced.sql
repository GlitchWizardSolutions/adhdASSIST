-- ADHD Dashboard - Enhanced Tasks Table Migration
-- Adds priority levels, categories, and low-effort tracking
-- Updated: April 4, 2026

-- =====================================================
-- ALTER TASKS TABLE - Add new task management columns
-- =====================================================
ALTER TABLE tasks ADD COLUMN category VARCHAR(100) AFTER priority;
ALTER TABLE tasks ADD COLUMN is_low_effort BOOLEAN DEFAULT FALSE AFTER priority_slot;
ALTER TABLE tasks ADD COLUMN estimated_duration_minutes INT AFTER is_low_effort;
ALTER TABLE tasks ADD COLUMN is_recurring BOOLEAN DEFAULT FALSE AFTER completed_date;
ALTER TABLE tasks ADD COLUMN recurrence_pattern VARCHAR(100) AFTER is_recurring;

-- Update existing priority values to match spec (high/medium/low/someday)
UPDATE tasks SET priority = 'high' WHERE priority = 'urgent';
UPDATE tasks SET priority = 'medium' WHERE priority IN ('secondary', 'neutral');
UPDATE tasks SET priority = 'low' WHERE priority = 'calm';

-- Create indexes for new columns
CREATE INDEX idx_category ON tasks(category);
CREATE INDEX idx_is_low_effort ON tasks(is_low_effort);

-- =====================================================
-- TASK_CATEGORIES TABLE - Optional: store user-defined categories
-- =====================================================
CREATE TABLE IF NOT EXISTS task_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, name),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
