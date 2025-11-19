-- Database Schema untuk Laporan Keuangan Dimsum
-- PHP 8.2 & MySQL

CREATE DATABASE IF NOT EXISTS dimsum_financial CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dimsum_financial;

-- Tabel Cabang
CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Transaksi
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    description VARCHAR(500) NOT NULL,
    -- Pemasukan
    cash DECIMAL(15,2) DEFAULT 0,
    qris DECIMAL(15,2) DEFAULT 0,
    transfer DECIMAL(15,2) DEFAULT 0,
    shopee_food DECIMAL(15,2) DEFAULT 0,
    grab_food DECIMAL(15,2) DEFAULT 0,
    go_food DECIMAL(15,2) DEFAULT 0,
    -- Pengeluaran
    expenses DECIMAL(15,2) DEFAULT 0,
    expense_description VARCHAR(500),
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    INDEX idx_branch_date (branch_id, transaction_date),
    INDEX idx_date (transaction_date)
) ENGINE=InnoDB;

-- Tabel Kategori Pengeluaran (opsional untuk tracking)
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default expense categories
INSERT INTO expense_categories (name) VALUES
('Bahan Baku'),
('Gaji Karyawan'),
('Listrik & Air'),
('Sewa Tempat'),
('Peralatan'),
('Transportasi'),
('Lain-lain');

-- Insert sample branch
INSERT INTO branches (name, address, phone) VALUES
('Cabang Pusat', 'Jl. Contoh No. 1', '081234567890');
