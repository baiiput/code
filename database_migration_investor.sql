-- Migration Script: Add Investor & Kas Management System
-- Run this after multi user level migration

USE koperasi_syariah;

-- Table: investors
CREATE TABLE investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_investor VARCHAR(50) NOT NULL UNIQUE,
    nama_investor VARCHAR(255) NOT NULL,
    email VARCHAR(100) NULL,
    telepon VARCHAR(20) NULL,
    alamat TEXT NULL,
    total_modal DECIMAL(15,2) NOT NULL DEFAULT 0,
    modal_tersedia DECIMAL(15,2) NOT NULL DEFAULT 0,
    modal_allocated DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_profit DECIMAL(15,2) NOT NULL DEFAULT 0,
    nisbah_investor INT NOT NULL DEFAULT 60 COMMENT 'Persentase untuk investor',
    nisbah_koperasi INT NOT NULL DEFAULT 40 COMMENT 'Persentase untuk koperasi',
    kontrak_mulai DATE NOT NULL,
    kontrak_selesai DATE NOT NULL,
    minimal_alokasi DECIMAL(15,2) DEFAULT 1000000 COMMENT 'Minimal alokasi per transaksi',
    status ENUM('aktif', 'selesai', 'nonaktif') DEFAULT 'aktif',
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode (kode_investor),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: transaction_investors (Many-to-Many: Transaksi <-> Investor)
CREATE TABLE transaction_investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    investor_id INT NOT NULL,
    modal_dialokasi DECIMAL(15,2) NOT NULL,
    proporsi DECIMAL(5,2) NOT NULL COMMENT 'Persentase dari total pool transaksi ini',
    profit_share DECIMAL(15,2) DEFAULT 0 COMMENT 'Profit yang didapat investor ini',
    modal_returned TINYINT(1) DEFAULT 0 COMMENT '0=belum kembali, 1=sudah kembali',
    profit_distributed TINYINT(1) DEFAULT 0 COMMENT '0=belum dibagi, 1=sudah dibagi',
    tanggal_return DATE NULL COMMENT 'Tanggal modal kembali + profit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE RESTRICT,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE RESTRICT,
    INDEX idx_transaction (transaction_id),
    INDEX idx_investor (investor_id),
    INDEX idx_status (modal_returned, profit_distributed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: investor_profit_history (Riwayat profit investor)
CREATE TABLE investor_profit_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    transaction_id INT NOT NULL,
    transaction_investor_id INT NOT NULL,
    modal_dialokasi DECIMAL(15,2) NOT NULL,
    profit_amount DECIMAL(15,2) NOT NULL,
    nisbah_investor INT NOT NULL,
    proporsi DECIMAL(5,2) NOT NULL,
    tanggal_profit DATE NOT NULL,
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE RESTRICT,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE RESTRICT,
    FOREIGN KEY (transaction_investor_id) REFERENCES transaction_investors(id) ON DELETE CASCADE,
    INDEX idx_investor (investor_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_tanggal (tanggal_profit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: investor_withdrawals (Penarikan modal investor)
CREATE TABLE investor_withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    tanggal_withdrawal DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL COMMENT 'User ID yang approve',
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE RESTRICT,
    INDEX idx_investor (investor_id),
    INDEX idx_status (status),
    INDEX idx_tanggal (tanggal_withdrawal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: kas_transactions (Transaksi kas koperasi)
CREATE TABLE kas_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipe ENUM('masuk', 'keluar') NOT NULL,
    kategori VARCHAR(50) NOT NULL COMMENT 'investor_in, investor_out, cicilan_in, pencairan_out, operational, etc',
    nominal DECIMAL(15,2) NOT NULL,
    saldo_before DECIMAL(15,2) NOT NULL,
    saldo_after DECIMAL(15,2) NOT NULL,
    referensi_type VARCHAR(50) NULL COMMENT 'investor, transaction, payment, etc',
    referensi_id INT NULL COMMENT 'ID dari referensi',
    keterangan TEXT NULL,
    tanggal_transaksi DATE NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipe (tipe),
    INDEX idx_kategori (kategori),
    INDEX idx_tanggal (tanggal_transaksi),
    INDEX idx_referensi (referensi_type, referensi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: kas_settings (Setting kas koperasi)
CREATE TABLE kas_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    keterangan TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default kas settings
INSERT INTO kas_settings (setting_key, setting_value, keterangan) VALUES
('kas_koperasi', '0', 'Saldo kas koperasi (non-investor)'),
('total_modal_investor', '0', 'Total modal dari semua investor'),
('modal_tersedia', '0', 'Modal yang bisa dipakai untuk transaksi baru'),
('modal_allocated', '0', 'Modal yang sedang dipakai di transaksi aktif');

-- Add column to transactions table for investor funding flag
ALTER TABLE transactions
ADD COLUMN funded_by_investor TINYINT(1) DEFAULT 1 COMMENT '1=dibiayai investor, 0=kas koperasi' AFTER created_by;

-- Sample data investor (untuk testing)
INSERT INTO investors (kode_investor, nama_investor, email, telepon, total_modal, modal_tersedia, nisbah_investor, nisbah_koperasi, kontrak_mulai, kontrak_selesai, minimal_alokasi) VALUES
('INV-001', 'Investor A', 'investora@email.com', '081234567001', 110000000, 110000000, 60, 40, '2025-01-01', '2026-01-01', 1000000),
('INV-002', 'Investor B', 'investorb@email.com', '081234567002', 35000000, 35000000, 60, 40, '2025-01-01', '2026-01-01', 1000000),
('INV-003', 'Investor C', 'investorc@email.com', '081234567003', 15000000, 15000000, 60, 40, '2025-01-01', '2026-01-01', 500000),
('INV-004', 'Investor D', 'investord@email.com', '081234567004', 10000000, 10000000, 55, 45, '2025-01-01', '2026-01-01', 500000),
('INV-005', 'Investor E', 'investore@email.com', '081234567005', 5000000, 5000000, 55, 45, '2025-01-01', '2026-01-01', 500000);

-- Update kas settings with sample data
UPDATE kas_settings SET setting_value = '175000000' WHERE setting_key = 'total_modal_investor';
UPDATE kas_settings SET setting_value = '175000000' WHERE setting_key = 'modal_tersedia';
