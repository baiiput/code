-- Migration: Add created_by column to transactions table
-- Date: 2025-11-25
-- Description: Add user tracking to transactions for audit trail

USE dimsum_financial;

-- Add created_by column to transactions table
ALTER TABLE transactions
ADD COLUMN created_by INT NULL AFTER expense_description,
ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update existing records to set created_by to first admin user (if exists)
UPDATE transactions
SET created_by = (SELECT id FROM users WHERE role = 'admin' LIMIT 1)
WHERE created_by IS NULL;
