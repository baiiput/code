-- =====================================================================
-- QUICK FIX: Add status column and mark deleted investor transactions
-- Run this script ONCE to fix the kas_transactions status issue
-- =====================================================================

-- Step 1: Add status column (will show error if already exists - that's OK!)
ALTER TABLE kas_transactions
ADD COLUMN status ENUM('aktif', 'batal') DEFAULT 'aktif' NOT NULL
AFTER keterangan;

-- Step 2: Add index for better performance
ALTER TABLE kas_transactions
ADD INDEX idx_status (status);

-- Step 3: Mark transactions from deleted investors as 'batal'
UPDATE kas_transactions kt
SET kt.status = 'batal'
WHERE kt.referensi_type = 'investor'
AND kt.referensi_id NOT IN (SELECT id FROM investors);

-- Step 4: Show results
SELECT
    'SUCCESS! Status column added and updated' AS Message,
    COUNT(*) as total_transaksi,
    SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as aktif,
    SUM(CASE WHEN status = 'batal' THEN 1 ELSE 0 END) as batal
FROM kas_transactions;
