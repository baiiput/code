-- ==================================================================
-- COMPREHENSIVE FIX SCRIPT
-- Purpose: Fix existing data and apply status column migration
-- Run this ONCE to fix all existing issues
-- ==================================================================

-- Step 1: Add status column if it doesn't exist
-- This will add the column safely without error if it already exists
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'kas_transactions'
    AND COLUMN_NAME = 'status'
);

SET @sql = IF(
    @column_exists = 0,
    'ALTER TABLE kas_transactions ADD COLUMN status ENUM(''aktif'', ''batal'') DEFAULT ''aktif'' NOT NULL AFTER keterangan',
    'SELECT ''Column status already exists'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Add index for status column if it doesn't exist
SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'kas_transactions'
    AND INDEX_NAME = 'idx_status'
);

SET @sql = IF(
    @index_exists = 0,
    'ALTER TABLE kas_transactions ADD INDEX idx_status (status)',
    'SELECT ''Index idx_status already exists'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Mark all existing kas_transactions as 'aktif' (already done by DEFAULT, but being explicit)
UPDATE kas_transactions
SET status = 'aktif'
WHERE status IS NULL;

-- Step 4: Mark kas_transactions as 'batal' for investors that no longer exist
UPDATE kas_transactions kt
SET kt.status = 'batal'
WHERE kt.referensi_type = 'investor'
AND kt.referensi_id NOT IN (SELECT id FROM investors);

-- Step 5: Verification queries
SELECT '=== VERIFICATION RESULTS ===' AS '---';

-- Show status distribution
SELECT
    'Status Distribution' AS 'Check',
    status,
    COUNT(*) as total_transaksi,
    SUM(CASE WHEN tipe = 'masuk' THEN nominal ELSE 0 END) as total_masuk,
    SUM(CASE WHEN tipe = 'keluar' THEN nominal ELSE 0 END) as total_keluar
FROM kas_transactions
GROUP BY status;

-- Show transactions for deleted investors (should all be 'batal' now)
SELECT
    'Deleted Investor Transactions' AS 'Check',
    kt.id,
    kt.kategori,
    kt.nominal,
    kt.status,
    kt.keterangan
FROM kas_transactions kt
WHERE kt.referensi_type = 'investor'
AND kt.referensi_id NOT IN (SELECT id FROM investors)
LIMIT 10;

-- Show count of transactions with no status (should be 0)
SELECT
    'Transactions with NULL status' AS 'Check',
    COUNT(*) as total
FROM kas_transactions
WHERE status IS NULL;

SELECT '=== FIX COMPLETE ===' AS '---';
