-- Add is_active column to customers table for soft delete functionality
-- This allows customers to be "deleted" (deactivated) while preserving all transaction history

-- Add the column if it doesn't exist
ALTER TABLE customers
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 NOT NULL COMMENT 'Customer active status: 1=active, 0=deactivated'
AFTER keterangan;

-- Create index for better query performance
CREATE INDEX IF NOT EXISTS idx_customers_is_active ON customers(is_active);

-- Set all existing customers to active
UPDATE customers SET is_active = 1 WHERE is_active IS NULL;

-- Verify the changes
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_DEFAULT,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'customers'
AND COLUMN_NAME = 'is_active'
AND TABLE_SCHEMA = DATABASE();
