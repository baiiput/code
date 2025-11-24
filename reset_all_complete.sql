-- ====================================================
-- COMPLETE RESET SCRIPT
-- Reset semua transaksi DAN warehouse_items
-- ====================================================

-- 1. Hapus semua transaksi detail
DELETE FROM stock_in_detail;
DELETE FROM stock_out_detail;
DELETE FROM stock_transfer_detail;

-- 2. Hapus semua transaksi header
DELETE FROM stock_in;
DELETE FROM stock_out;
DELETE FROM stock_transfers;
DELETE FROM stock_adjustment;

-- 3. Hapus financial transactions
DELETE FROM financial_transactions;

-- 4. Hapus activity logs (optional - hapus kalau mau clean)
DELETE FROM activity_logs WHERE module IN ('stock_in', 'stock_out', 'stock_transfer', 'stock_adjustment', 'finance');

-- 5. RESET warehouse_items (INI YANG PENTING!)
DELETE FROM warehouse_items;

-- 6. Reset warehouse balance ke 0
UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1;

-- 7. Reset auto increment
ALTER TABLE stock_in AUTO_INCREMENT = 1;
ALTER TABLE stock_out AUTO_INCREMENT = 1;
ALTER TABLE stock_transfers AUTO_INCREMENT = 1;
ALTER TABLE stock_adjustment AUTO_INCREMENT = 1;
ALTER TABLE financial_transactions AUTO_INCREMENT = 1;

-- ====================================================
-- SELESAI - Semua data transaksi dan stok sudah bersih!
-- ====================================================
