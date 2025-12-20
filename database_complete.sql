-- =====================================================
-- KOPERASI SYARIAH ONLINE - COMPLETE DATABASE SCHEMA
-- Version: 2.0 (Multi-level User + Investor System)
-- =====================================================
-- Fitur:
-- 1. Multi-level User System (Super Admin, Manager, Staff, Customer)
-- 2. Islamic Installment System (Murabahah)
-- 3. Investor & Capital Management
-- 4. Automatic Profit Distribution
-- 5. Cash Flow Tracking
-- =====================================================

CREATE DATABASE IF NOT EXISTS koperasi_syariah DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE koperasi_syariah;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Table: users (untuk login dengan multi level)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    user_level TINYINT(1) NOT NULL DEFAULT 4 COMMENT '1=Super Admin, 2=Manager, 3=Staff, 4=Customer',
    full_name VARCHAR(255) NULL,
    customer_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_customer_id (customer_id),
    INDEX idx_user_level (user_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: customers (data pelanggan lengkap)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(255) NOT NULL,
    nik VARCHAR(16) NULL,
    alamat TEXT NULL,
    telepon VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    pekerjaan VARCHAR(100) NULL,
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nama (nama_lengkap),
    INDEX idx_nik (nik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products (master barang)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(50) NULL,
    nama_barang VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    harga_modal DECIMAL(15,2) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nama_barang (nama_barang),
    INDEX idx_kode (kode_barang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transactions (akad cicilan murabahah)
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_kontrak VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    harga_modal DECIMAL(15,2) NOT NULL,
    margin DECIMAL(15,2) NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    tenor INT NOT NULL COMMENT 'Jumlah angsuran (bulan)',
    angsuran_perbulan DECIMAL(15,2) NOT NULL,
    total_dibayar DECIMAL(15,2) DEFAULT 0,
    sisa_hutang DECIMAL(15,2) NOT NULL,
    status ENUM('aktif', 'lunas', 'batal') DEFAULT 'aktif',
    tanggal_akad DATE NOT NULL,
    keterangan TEXT NULL,
    funded_by_investor TINYINT(1) DEFAULT 1 COMMENT '1=Investor, 0=Kas Koperasi',
    created_by INT NULL COMMENT 'User ID yang membuat transaksi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_nomor_kontrak (nomor_kontrak),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: payments (riwayat pembayaran)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    metode_pembayaran VARCHAR(50) DEFAULT 'xendit',
    xendit_payment_id INT NULL,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    tanggal_bayar DATETIME NULL,
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: xendit_payments (tracking Xendit payment link)
CREATE TABLE xendit_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    xendit_invoice_id VARCHAR(255) NULL,
    xendit_invoice_url TEXT NULL,
    xendit_external_id VARCHAR(255) NULL,
    amount DECIMAL(15,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'PENDING',
    paid_at DATETIME NULL,
    xendit_response JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_payment (payment_id),
    INDEX idx_external_id (xendit_external_id),
    INDEX idx_invoice_id (xendit_invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INVESTOR & KAS MANAGEMENT TABLES
-- =====================================================

-- Table: investors (data investor)
CREATE TABLE investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_investor VARCHAR(50) NOT NULL UNIQUE,
    nama_investor VARCHAR(255) NOT NULL,
    email VARCHAR(100) NULL,
    telepon VARCHAR(20) NULL,
    alamat TEXT NULL,
    total_modal DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Total modal investor',
    modal_tersedia DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Modal yang tersedia untuk dialokasikan',
    modal_allocated DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Modal yang sedang dialokasikan',
    total_profit DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Total profit yang sudah didapat',
    nisbah_investor INT NOT NULL DEFAULT 60 COMMENT 'Persentase nisbah investor (eg: 60)',
    nisbah_koperasi INT NOT NULL DEFAULT 40 COMMENT 'Persentase nisbah koperasi (eg: 40)',
    kontrak_mulai DATE NOT NULL,
    kontrak_selesai DATE NOT NULL,
    minimal_alokasi DECIMAL(15,2) DEFAULT 1000000 COMMENT 'Minimal alokasi per transaksi',
    status ENUM('aktif', 'selesai', 'nonaktif') DEFAULT 'aktif',
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode (kode_investor),
    INDEX idx_nama (nama_investor),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transaction_investors (relasi many-to-many transaksi-investor)
CREATE TABLE transaction_investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    investor_id INT NOT NULL,
    modal_dialokasi DECIMAL(15,2) NOT NULL COMMENT 'Jumlah modal yang dialokasikan',
    proporsi DECIMAL(5,2) NOT NULL COMMENT 'Persentase proporsi dari total modal transaksi',
    profit_share DECIMAL(15,2) DEFAULT 0 COMMENT 'Profit yang didapat investor',
    modal_returned TINYINT(1) DEFAULT 0 COMMENT 'Status modal sudah kembali',
    profit_distributed TINYINT(1) DEFAULT 0 COMMENT 'Status profit sudah dibagikan',
    tanggal_return DATE NULL COMMENT 'Tanggal modal+profit kembali',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE RESTRICT,
    INDEX idx_transaction (transaction_id),
    INDEX idx_investor (investor_id),
    INDEX idx_modal_returned (modal_returned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: investor_profit_history (riwayat profit investor)
CREATE TABLE investor_profit_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    transaction_id INT NOT NULL,
    transaction_investor_id INT NULL,
    modal_dialokasi DECIMAL(15,2) NOT NULL,
    profit_amount DECIMAL(15,2) NOT NULL,
    nisbah_investor INT NOT NULL,
    proporsi DECIMAL(5,2) NOT NULL,
    tanggal_profit DATE NOT NULL,
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_investor (investor_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_tanggal (tanggal_profit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: investor_withdrawals (penarikan modal investor)
CREATE TABLE investor_withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    tanggal_request DATE NOT NULL,
    tanggal_processed DATE NULL,
    processed_by INT NULL COMMENT 'User ID yang memproses',
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE,
    INDEX idx_investor (investor_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: kas_transactions (log semua transaksi kas)
CREATE TABLE kas_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipe ENUM('masuk', 'keluar') NOT NULL,
    kategori VARCHAR(50) NOT NULL COMMENT 'investor_in, investor_allocation, investor_return, investor_out, cicilan_in, etc',
    nominal DECIMAL(15,2) NOT NULL,
    saldo_before DECIMAL(15,2) NOT NULL,
    saldo_after DECIMAL(15,2) NOT NULL,
    referensi_type VARCHAR(50) NULL COMMENT 'investor, transaction, payment, withdrawal',
    referensi_id INT NULL,
    keterangan TEXT NULL,
    tanggal_transaksi DATE NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipe (tipe),
    INDEX idx_kategori (kategori),
    INDEX idx_tanggal (tanggal_transaksi),
    INDEX idx_referensi (referensi_type, referensi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: kas_settings (pengaturan kas global)
CREATE TABLE kas_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value DECIMAL(15,2) DEFAULT 0,
    keterangan TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Insert default users
-- Password: admin123 (hashed dengan password_hash)
INSERT INTO users (username, password, role, user_level, full_name, customer_id) VALUES
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 'Super Administrator', NULL),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 2, 'Manager User', NULL),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 3, 'Staff User', NULL);

-- Insert default kas settings
INSERT INTO kas_settings (setting_key, setting_value, keterangan) VALUES
('kas_koperasi', 0, 'Kas utama koperasi'),
('total_modal_investor', 0, 'Total modal dari semua investor'),
('modal_tersedia', 0, 'Modal investor yang tersedia untuk dialokasikan'),
('modal_allocated', 0, 'Modal investor yang sedang dialokasikan ke transaksi');

-- =====================================================
-- SAMPLE DATA (OPTIONAL - untuk testing)
-- =====================================================

-- Sample customers
INSERT INTO customers (nama_lengkap, nik, alamat, telepon, email) VALUES
('Ahmad Hidayat', '3201012345678901', 'Jl. Merdeka No. 123, Jakarta', '081234567890', 'ahmad@email.com'),
('Siti Nurhaliza', '3201012345678902', 'Jl. Sudirman No. 456, Bandung', '081234567891', 'siti@email.com');

-- Sample products
INSERT INTO products (kode_barang, nama_barang, deskripsi, harga_modal) VALUES
('HP001', 'Samsung Galaxy A54', 'Smartphone 8GB RAM', 4500000),
('MTR001', 'Honda Beat 2023', 'Motor Matic', 18000000),
('LPT001', 'Laptop ASUS VivoBook', 'Laptop Core i5 8GB', 7000000);

-- Sample investors (5 investors sesuai skenario user)
INSERT INTO investors (kode_investor, nama_investor, email, telepon, total_modal, modal_tersedia, nisbah_investor, nisbah_koperasi, kontrak_mulai, kontrak_selesai, minimal_alokasi, status, keterangan) VALUES
('INV-001', 'Investor A', 'investora@email.com', '081234567801', 110000000, 110000000, 60, 40, '2024-01-01', '2025-12-31', 1000000, 'aktif', 'Investor utama dengan modal terbesar'),
('INV-002', 'Investor B', 'investorb@email.com', '081234567802', 35000000, 35000000, 60, 40, '2024-01-01', '2025-12-31', 1000000, 'aktif', 'Investor menengah'),
('INV-003', 'Investor C', 'investorc@email.com', '081234567803', 15000000, 15000000, 60, 40, '2024-01-01', '2025-12-31', 500000, 'aktif', 'Investor kecil'),
('INV-004', 'Investor D', 'investord@email.com', '081234567804', 10000000, 10000000, 55, 45, '2024-01-01', '2025-12-31', 500000, 'aktif', 'Investor dengan nisbah berbeda'),
('INV-005', 'Investor E', 'investore@email.com', '081234567805', 5000000, 5000000, 55, 45, '2024-01-01', '2025-12-31', 500000, 'aktif', 'Investor terkecil');

-- Update kas_settings with investor totals
UPDATE kas_settings SET setting_value = 175000000 WHERE setting_key = 'total_modal_investor';
UPDATE kas_settings SET setting_value = 175000000 WHERE setting_key = 'modal_tersedia';

-- =====================================================
-- INFO
-- =====================================================
-- Database siap digunakan!
--
-- Login credentials:
-- - Super Admin: superadmin / admin123
-- - Manager: manager / admin123
-- - Staff: staff / admin123
--
-- Total Modal Investor (Sample): Rp 175.000.000
-- - Investor A: Rp 110.000.000 (60:40)
-- - Investor B: Rp  35.000.000 (60:40)
-- - Investor C: Rp  15.000.000 (60:40)
-- - Investor D: Rp  10.000.000 (55:45)
-- - Investor E: Rp   5.000.000 (55:45)
-- =====================================================
