-- Migration: Add Manager role to users table
-- Run this migration to enable Manager role functionality

-- Update users table role column to include 'manager'
ALTER TABLE `users`
MODIFY COLUMN `role` ENUM('admin', 'manager', 'staff_warehouse', 'staff_keuangan', 'cabang') NOT NULL;

-- Verify the change
SELECT COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'users'
AND COLUMN_NAME = 'role'
AND TABLE_SCHEMA = 'warehouse';
