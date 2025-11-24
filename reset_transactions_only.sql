-- ============================================
-- RESET TRANSAKSI ONLY
-- Menghapus semua transaksi tapi tetap simpan Master Data
-- (Barang, Kategori, Supplier, Cabang, Warehouse, Users)
-- ============================================

-- PERINGATAN: Script ini akan menghapus SEMUA transaksi!
-- Pastikan Anda sudah backup database terlebih dahulu!

-- 1. Reset Activity Logs
TRUNCATE TABLE activity_logs;

-- 2. Reset Financial Transactions
TRUNCATE TABLE financial_transactions;

-- 3. Reset Stock Transfers
TRUNCATE TABLE stock_transfer_detail;
TRUNCATE TABLE stock_transfers;

-- 4. Reset Stock Adjustments
TRUNCATE TABLE stock_adjustment;

-- 5. Reset Stock Out (Distribusi)
TRUNCATE TABLE stock_out_detail;
TRUNCATE TABLE stock_out;

-- 6. Reset Stock In (Pembelian)
TRUNCATE TABLE stock_in_detail;
TRUNCATE TABLE stock_in;

-- 7. Reset Warehouse Items (Stok akan menjadi 0 semua)
TRUNCATE TABLE warehouse_items;

-- 8. Reset Balance ke 0
UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1;

-- ============================================
-- SELESAI!
--
-- Yang di-RESET:
-- ✓ Semua transaksi (stock in, out, adjustment, transfer)
-- ✓ Stok semua warehouse (menjadi 0)
-- ✓ Activity logs
-- ✓ Financial transactions
-- ✓ Balance
--
-- Yang TETAP ADA:
-- ✓ Master Barang
-- ✓ Kategori
-- ✓ Supplier
-- ✓ Cabang
-- ✓ Warehouse
-- ✓ Users
-- ============================================
