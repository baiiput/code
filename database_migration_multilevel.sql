-- Migration Script: Add Multi User Level System
-- Run this if you already have existing database

USE koperasi_syariah;

-- Add new columns to users table
ALTER TABLE users
ADD COLUMN user_level TINYINT(1) NOT NULL DEFAULT 4 COMMENT '1=Super Admin, 2=Manager, 3=Staff, 4=Customer' AFTER role,
ADD COLUMN full_name VARCHAR(255) NULL AFTER user_level,
ADD INDEX idx_user_level (user_level);

-- Add created_by column to transactions table
ALTER TABLE transactions
ADD COLUMN created_by INT NULL COMMENT 'User ID yang membuat transaksi' AFTER keterangan,
ADD INDEX idx_created_by (created_by);

-- Update existing admin user to super admin level
UPDATE users SET user_level = 1, full_name = 'Super Administrator' WHERE username = 'admin';

-- Update existing customers to level 4
UPDATE users SET user_level = 4 WHERE role = 'customer';

-- Insert sample admin users (if not exists)
INSERT IGNORE INTO users (username, password, role, user_level, full_name, customer_id) VALUES
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 2, 'Manager User', NULL),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 3, 'Staff User', NULL);

-- Note: Password untuk semua user adalah 'admin123'
