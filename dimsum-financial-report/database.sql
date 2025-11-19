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

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert sample branch
INSERT INTO branches (name, address, phone) VALUES
('Cabang Pusat', 'Jl. Contoh No. 1', '081234567890');

-- Insert default admin user (password: admin123)
-- Generate new hash with: php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
INSERT INTO users (username, password, name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert sample editor (password: editor123)
INSERT INTO users (username, password, name, role) VALUES
('editor', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQkLAi0LW0bXL0jnFlVbOk2qe7u.vW', 'Editor User', 'editor');
