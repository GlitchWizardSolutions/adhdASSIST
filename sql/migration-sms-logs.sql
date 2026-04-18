-- Migration: Create SMS Logs Table
-- Purpose: Track all SMS sends with delivery status
-- Created: 2024
-- Status: Idempotent (can run multiple times safely)

CREATE TABLE IF NOT EXISTS `sms_logs` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `message_text` TEXT NOT NULL,
    `message_id` VARCHAR(255) UNIQUE,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `error_code` INT,
    `error_message` VARCHAR(255),
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `delivered_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_message_id` (`message_id`),
    INDEX `idx_sent_at` (`sent_at`),
    INDEX `idx_delivered_at` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment to table explaining its purpose
ALTER TABLE `sms_logs` 
    COMMENT = 'Tracks all SMS sends with delivery status from EasySendSMS API';
