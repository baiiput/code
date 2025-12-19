-- Fix Investor Modal Allocation
-- File: fix_investor_allocation.sql
--
-- Script ini memperbaiki data investor yang modal_allocated-nya tidak sesuai
-- karena transaksi dibatalkan tetapi modal tidak dikembalikan

USE koperasi_syariah;

-- Step 1: Recalculate modal_allocated for each investor based on active transactions
UPDATE investors i
SET modal_allocated = COALESCE((
    SELECT SUM(ti.alokasi)
    FROM transaction_investors ti
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE ti.investor_id = i.id
    AND t.status NOT IN ('dibatalkan', 'lunas')
), 0);

-- Step 2: Recalculate modal_tersedia (= total_modal - modal_allocated)
UPDATE investors
SET modal_tersedia = total_modal - modal_allocated;

-- Step 3: Clean up transaction_investors for cancelled transactions
DELETE ti FROM transaction_investors ti
JOIN transactions t ON ti.transaction_id = t.id
WHERE t.status = 'dibatalkan';

-- Verification: Show investor summary
SELECT
    kode_investor,
    nama_investor,
    total_modal,
    modal_allocated,
    modal_tersedia,
    (total_modal - modal_allocated - modal_tersedia) as difference
FROM investors
ORDER BY kode_investor;

-- If difference column shows 0 for all rows, data is now consistent
SELECT 'Fix completed successfully! Check the investor summary above.' as message;
