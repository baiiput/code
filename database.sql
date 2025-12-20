-- Database: koperasi_syariah
-- Charset: utf8mb4

CREATE DATABASE IF NOT EXISTS koperasi_syariah DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE koperasi_syariah;

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

-- Insert default users with levels
-- Password: admin123 (hashed dengan password_hash)
INSERT INTO users (username, password, role, user_level, full_name, customer_id) VALUES
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 'Super Administrator', NULL),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 2, 'Manager User', NULL),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 3, 'Staff User', NULL);

-- Sample data untuk testing (optional)
INSERT INTO customers (nama_lengkap, nik, alamat, telepon, email) VALUES
('Ahmad Hidayat', '3201012345678901', 'Jl. Merdeka No. 123, Jakarta', '081234567890', 'ahmad@email.com'),
('Siti Nurhaliza', '3201012345678902', 'Jl. Sudirman No. 456, Bandung', '081234567891', 'siti@email.com');

INSERT INTO products (kode_barang, nama_barang, deskripsi, harga_modal) VALUES
('HP001', 'Samsung Galaxy A54', 'Smartphone 8GB RAM', 4500000),
('MTR001', 'Honda Beat 2023', 'Motor Matic', 18000000),
('LPT001', 'Laptop ASUS VivoBook', 'Laptop Core i5 8GB', 7000000);
