-- ============================================
-- RESET SEMUA DATA (TOTAL RESET)
-- Menghapus SEMUA data termasuk Master Data
-- Hanya menyisakan struktur database kosong + admin default
-- ============================================

-- ⚠️ PERINGATAN KERAS! ⚠️
-- Script ini akan menghapus SEMUA DATA di database!
-- Master Data (Barang, Kategori, Supplier, dll) akan HILANG!
-- Pastikan Anda SUDAH BACKUP DATABASE!

-- Disable foreign key checks untuk menghindari error constraint
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Reset Activity Logs
TRUNCATE TABLE activity_logs;

-- 2. Reset Financial
TRUNCATE TABLE financial_transactions;

-- 3. Reset Stock Transfers
TRUNCATE TABLE stock_transfer_detail;
TRUNCATE TABLE stock_transfers;

-- 4. Reset Stock Adjustments
TRUNCATE TABLE stock_adjustment;

-- 5. Reset Stock Out
TRUNCATE TABLE stock_out_detail;
TRUNCATE TABLE stock_out;

-- 6. Reset Stock In
TRUNCATE TABLE stock_in_detail;
TRUNCATE TABLE stock_in;

-- 7. Reset Warehouse Items
TRUNCATE TABLE warehouse_items;

-- 8. Reset Master Data - Items
TRUNCATE TABLE items;

-- 9. Reset Master Data - Categories
TRUNCATE TABLE categories;

-- 10. Reset Master Data - Suppliers
TRUNCATE TABLE suppliers;

-- 11. Reset Master Data - Branches
TRUNCATE TABLE branches;

-- 12. Reset Master Data - Warehouses
TRUNCATE TABLE warehouses;

-- 13. Reset Users (akan di-insert ulang admin default)
TRUNCATE TABLE users;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Reset balance
UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role, is_active)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1);

-- ============================================
-- SELESAI! Database sudah dalam kondisi FRESH
--
-- Yang TERSISA:
-- ✓ User admin (username: admin, password: admin123)
-- ✓ Struktur database (tabel-tabel)
--
-- Yang TERHAPUS:
-- ✗ Semua transaksi
-- ✗ Semua master data (barang, kategori, supplier, dll)
-- ✗ Semua user (kecuali admin default)
-- ✗ Semua activity logs
-- ============================================
