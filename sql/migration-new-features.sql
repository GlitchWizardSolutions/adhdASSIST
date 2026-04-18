-- =====================================================================
-- ADHD DASHBOARD: NEW FEATURES MIGRATION
-- Run this AFTER the main schema.sql has been executed
-- =======================================================================

USE adhd_dashboard;

-- =====================================================
-- TAGS TABLE - User-defined task tags for organization
-- =====================================================
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color_hex VARCHAR(7) DEFAULT '#3B82F6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tag (user_id, name),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TASK_TAGS TABLE - Many-to-many junction table
-- =====================================================
CREATE TABLE IF NOT EXISTS task_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_task_tag (task_id, tag_id),
    INDEX idx_task_id (task_id),
    INDEX idx_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EMERGENCY_CONTACTS TABLE - CRUD example and emergency contact storage
-- =====================================================
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    relationship VARCHAR(50),
    phone_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    notes TEXT,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- RECURRING_TASKS_INSTANCES TABLE - Track generated instances
-- =====================================================
CREATE TABLE IF NOT EXISTS recurring_tasks_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recurring_parent_task_id INT NOT NULL,
    instance_task_id INT,
    instance_due_date DATE NOT NULL,
    completed_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recurring_parent_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (instance_task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX idx_parent_task_id (recurring_parent_task_id),
    INDEX idx_instance_task_id (instance_task_id),
    INDEX idx_instance_due_date (instance_due_date),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA - Tags for demo user (user_id = 1)
-- =====================================================
INSERT IGNORE INTO tags (user_id, name, color_hex) VALUES
(1, 'urgent', '#EF4444'),
(1, 'work', '#3B82F6'),
(1, 'health', '#10B981'),
(1, 'personal', '#F59E0B'),
(1, 'learning', '#8B5CF6'),
(1, 'follow-up', '#EC4899');

-- =====================================================
-- SAMPLE DATA - Emergency Contacts for demo user (user_id = 1)
-- =====================================================
INSERT IGNORE INTO emergency_contacts (user_id, name, relationship, phone_number, email, is_primary) VALUES
(1, 'Emergency Primary Contact', 'Partner/Family', '+1-555-0100', 'primary@example.com', TRUE);

-- =====================================================
-- INDEXES for performance optimization
-- =====================================================
CREATE INDEX idx_tags_user_id ON tags(user_id);
CREATE INDEX idx_task_tags_task_id ON task_tags(task_id);
CREATE INDEX idx_task_tags_tag_id ON task_tags(tag_id);
CREATE INDEX idx_emergency_contacts_user_id ON emergency_contacts(user_id);
CREATE INDEX idx_recurring_instances_parent ON recurring_tasks_instances(recurring_parent_task_id);
CREATE INDEX idx_recurring_instances_date ON recurring_tasks_instances(instance_due_date);

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================
-- Migration complete! New features ready:
-- - Tags system (tags, task_tags tables)
-- - Emergency Contacts CRUD (emergency_contacts table)
-- - Recurring Tasks tracking (recurring_tasks_instances table)
-- Sample data has been inserted for user_id = 1
