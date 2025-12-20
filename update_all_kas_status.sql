-- =====================================================================
-- COMPREHENSIVE FIX: Update all kas_transactions status
-- This fixes ALL kas_transactions that should be marked as 'batal'
-- =====================================================================

-- Case 1: Mark kas_transactions as 'batal' for DELETED investors
UPDATE kas_transactions kt
SET kt.status = 'batal'
WHERE kt.referensi_type = 'investor'
AND kt.referensi_id NOT IN (SELECT id FROM investors);

-- Case 2: Mark kas_transactions as 'batal' for CANCELLED transactions
-- (transactions with status 'batal' in transactions table)
UPDATE kas_transactions kt
SET kt.status = 'batal'
WHERE kt.referensi_type = 'transaction'
AND kt.referensi_id IN (
    SELECT id FROM transactions WHERE status = 'batal'
);

-- Case 3: Mark investor allocation/return as 'batal' if related transaction is cancelled
UPDATE kas_transactions kt
SET kt.status = 'batal'
WHERE kt.kategori IN ('investor_allocation', 'investor_return')
AND kt.referensi_type = 'transaction'
AND kt.referensi_id IN (
    SELECT id FROM transactions WHERE status = 'batal'
);

-- Verification: Show results
SELECT '=== STATUS DISTRIBUTION ===' AS '';

SELECT
    status,
    COUNT(*) as jumlah,
    SUM(CASE WHEN tipe = 'masuk' THEN nominal ELSE 0 END) as total_masuk,
    SUM(CASE WHEN tipe = 'keluar' THEN nominal ELSE 0 END) as total_keluar
FROM kas_transactions
GROUP BY status;

SELECT '=== TRANSAKSI BATAL (Sample 10) ===' AS '';

SELECT
    id,
    tanggal_transaksi,
    kategori,
    tipe,
    nominal,
    status,
    keterangan
FROM kas_transactions
WHERE status = 'batal'
ORDER BY tanggal_transaksi DESC
LIMIT 10;

SELECT '=== SUMMARY ===' AS '';

SELECT
    'SUKSES!' AS Message,
    (SELECT COUNT(*) FROM kas_transactions WHERE status = 'aktif') as transaksi_aktif,
    (SELECT COUNT(*) FROM kas_transactions WHERE status = 'batal') as transaksi_batal,
    'Silakan refresh halaman kas_transactions.php' as next_step;
