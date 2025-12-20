-- Migration: Add Manual Payment Support with Proof Upload
-- File: database_migration_manual_payment.sql

USE koperasi_syariah;

-- Add columns to payments table for manual payment
ALTER TABLE payments
ADD COLUMN payment_type ENUM('xendit', 'manual') DEFAULT 'xendit' AFTER metode_pembayaran,
ADD COLUMN bukti_transfer VARCHAR(255) NULL AFTER xendit_payment_id,
ADD COLUMN verified_by INT NULL COMMENT 'User ID yang memverifikasi' AFTER status,
ADD COLUMN verified_at DATETIME NULL AFTER verified_by,
ADD COLUMN rejection_reason TEXT NULL COMMENT 'Alasan jika ditolak' AFTER verified_at,
ADD INDEX idx_payment_type (payment_type),
ADD INDEX idx_verified_by (verified_by);

-- Update status enum to include 'waiting_verification' and 'rejected'
ALTER TABLE payments
MODIFY COLUMN status ENUM('pending', 'waiting_verification', 'success', 'failed', 'rejected') DEFAULT 'pending';

-- Create uploads directory structure (manual step after migration)
-- mkdir -p uploads/bukti_transfer/
-- chmod 755 uploads/
-- chmod 755 uploads/bukti_transfer/

-- Migration completed
SELECT 'Manual Payment Support migration completed successfully!' as message;
