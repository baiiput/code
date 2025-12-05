-- Database schema for Starlink Financial Report
-- Run this SQL to create the database and tables

CREATE DATABASE IF NOT EXISTS starlink_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE starlink_finance;

-- Main transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    deskripsi VARCHAR(500) NOT NULL,
    pemasukan DECIMAL(15, 2) DEFAULT 0.00,
    pengeluaran DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tanggal (tanggal),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table for better organization (optional feature)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tipe ENUM('pemasukan', 'pengeluaran') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO categories (nama, tipe) VALUES
('Langganan Starlink', 'pemasukan'),
('Pemasangan Baru', 'pemasukan'),
('Service/Maintenance', 'pemasukan'),
('Penjualan Perangkat', 'pemasukan'),
('Lainnya', 'pemasukan'),
('Biaya Internet', 'pengeluaran'),
('Gaji Karyawan', 'pengeluaran'),
('Peralatan', 'pengeluaran'),
('Transportasi', 'pengeluaran'),
('Operasional', 'pengeluaran'),
('Lainnya', 'pengeluaran');

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default users (password: admin123) - CHANGE IN PRODUCTION!
INSERT INTO users (username, password, nama, role) VALUES
('admin', '$2y$12$eEBVEDY8nFJyJkim1JQqKO8Bl1TCmV3iwlL0i6XGdwkS7J2sC5bDC', 'Administrator', 'admin'),
('editor', '$2y$12$eEBVEDY8nFJyJkim1JQqKO8Bl1TCmV3iwlL0i6XGdwkS7J2sC5bDC', 'Editor User', 'editor');

-- Sample data (optional - remove in production)
INSERT INTO transactions (tanggal, deskripsi, pemasukan, pengeluaran) VALUES
('2025-11-01', 'Langganan bulanan - Pelanggan A', 750000, 0),
('2025-11-02', 'Pemasangan baru - Pelanggan B', 1500000, 0),
('2025-11-03', 'Biaya langganan Starlink ke provider', 0, 500000),
('2025-11-05', 'Langganan bulanan - Pelanggan C', 750000, 0),
('2025-11-07', 'Pembelian kabel dan konektor', 0, 150000),
('2025-11-10', 'Service antenna - Pelanggan A', 200000, 0),
('2025-11-12', 'Biaya transportasi teknisi', 0, 100000),
('2025-11-15', 'Langganan bulanan - Pelanggan D', 750000, 0);
