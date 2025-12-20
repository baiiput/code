-- Diagnostic Query: Check Investor Data Consistency
-- File: check_investor_data.sql

USE koperasi_syariah;

-- 1. Show current investor status
SELECT
    '=== CURRENT INVESTOR STATUS ===' as info;

SELECT
    i.id,
    i.kode_investor,
    i.nama_investor,
    i.total_modal,
    i.modal_allocated,
    i.modal_tersedia,
    (i.total_modal - i.modal_allocated - i.modal_tersedia) as difference,
    CASE
        WHEN (i.total_modal - i.modal_allocated - i.modal_tersedia) = 0 THEN 'OK'
        ELSE 'INCONSISTENT'
    END as status
FROM investors i
ORDER BY i.id;

-- 2. Show transaction_investors with transaction status
SELECT
    '=== TRANSACTION_INVESTORS RECORDS ===' as info;

SELECT
    ti.id,
    ti.transaction_id,
    ti.investor_id,
    i.kode_investor,
    ti.modal_dialokasi,
    t.nomor_kontrak,
    t.status as transaction_status,
    t.total_harga
FROM transaction_investors ti
JOIN investors i ON ti.investor_id = i.id
JOIN transactions t ON ti.transaction_id = t.id
ORDER BY ti.investor_id, t.status;

-- 3. Calculate what modal_allocated SHOULD be for each investor
SELECT
    '=== WHAT MODAL_ALLOCATED SHOULD BE ===' as info;

SELECT
    i.id as investor_id,
    i.kode_investor,
    i.modal_allocated as current_allocated,
    COALESCE((
        SELECT SUM(ti.modal_dialokasi)
        FROM transaction_investors ti
        JOIN transactions t ON ti.transaction_id = t.id
        WHERE ti.investor_id = i.id
        AND t.status NOT IN ('dibatalkan', 'lunas')
    ), 0) as should_be_allocated,
    (i.modal_allocated - COALESCE((
        SELECT SUM(ti.modal_dialokasi)
        FROM transaction_investors ti
        JOIN transactions t ON ti.transaction_id = t.id
        WHERE ti.investor_id = i.id
        AND t.status NOT IN ('dibatalkan', 'lunas')
    ), 0)) as difference
FROM investors i
ORDER BY i.id;

-- 4. Show transaction_investors for CANCELLED transactions
SELECT
    '=== CANCELLED TRANSACTIONS WITH INVESTOR RECORDS ===' as info;

SELECT
    ti.id as ti_id,
    ti.transaction_id,
    ti.investor_id,
    i.kode_investor,
    ti.modal_dialokasi,
    t.nomor_kontrak,
    t.status
FROM transaction_investors ti
JOIN investors i ON ti.investor_id = i.id
JOIN transactions t ON ti.transaction_id = t.id
WHERE t.status = 'dibatalkan'
ORDER BY ti.investor_id;

-- 5. Summary: Count by transaction status
SELECT
    '=== SUMMARY: TRANSACTIONS BY STATUS ===' as info;

SELECT
    t.status,
    COUNT(*) as total_transactions,
    COUNT(DISTINCT ti.investor_id) as investors_involved,
    SUM(ti.modal_dialokasi) as total_modal_allocated
FROM transactions t
LEFT JOIN transaction_investors ti ON t.id = ti.transaction_id
GROUP BY t.status
ORDER BY t.status;
