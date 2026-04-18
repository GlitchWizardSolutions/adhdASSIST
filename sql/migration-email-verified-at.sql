-- Migration: Add email verification support to users table
-- Add this to your database to track when users verify their email

ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL AFTER is_verified;

-- Add index for email verification tracking
ALTER TABLE users ADD INDEX   idx_email_verified_at (email_verified_at);