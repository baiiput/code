-- Migration: Add status column to kas_transactions table
-- Purpose: Track cancelled transactions for better audit trail and reporting
-- Date: 2025-12-20

-- Add status column to kas_transactions
ALTER TABLE kas_transactions
ADD COLUMN status ENUM('aktif', 'batal') DEFAULT 'aktif' NOT NULL
AFTER keterangan;

-- Add index for better query performance
ALTER TABLE kas_transactions
ADD INDEX idx_status (status);

-- Set all existing transactions to 'aktif' (already done by DEFAULT but being explicit)
UPDATE kas_transactions SET status = 'aktif' WHERE status IS NULL;

-- Verification query
SELECT
    status,
    COUNT(*) as total_transaksi,
    SUM(CASE WHEN tipe = 'masuk' THEN nominal ELSE 0 END) as total_masuk,
    SUM(CASE WHEN tipe = 'keluar' THEN nominal ELSE 0 END) as total_keluar
FROM kas_transactions
GROUP BY status;
