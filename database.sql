-- Database untuk Sistem Jimpitan RT
-- Dibuat untuk PHP 8.2 dan MySQL

CREATE DATABASE IF NOT EXISTS jimpitan_rt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jimpitan_rt;

-- Tabel Dawis
CREATE TABLE IF NOT EXISTS dawis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_dawis VARCHAR(100) NOT NULL,
    ketua_dawis VARCHAR(100) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Warga
CREATE TABLE IF NOT EXISTS warga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dawis_id INT NOT NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    nomor_kk VARCHAR(50),
    alamat TEXT,
    no_telepon VARCHAR(20),
    status ENUM('aktif', 'tidak_aktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dawis_id) REFERENCES dawis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Transaksi Jimpitan
CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warga_id INT NOT NULL,
    tanggal_transaksi DATE NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    jenis_transaksi ENUM('setoran', 'penarikan') NOT NULL,
    keterangan TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (warga_id) REFERENCES warga(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Pengeluaran (untuk keperluan RT)
CREATE TABLE IF NOT EXISTS pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_pengeluaran DATE NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    keterangan TEXT NOT NULL,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel User (untuk login admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'bendahara') DEFAULT 'bendahara',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data sample
INSERT INTO dawis (nama_dawis, ketua_dawis, keterangan) VALUES
('Dawis 1', 'Ibu Siti Aminah', 'Dawis wilayah utara'),
('Dawis 2', 'Ibu Rani Kusuma', 'Dawis wilayah tengah'),
('Dawis 3', 'Ibu Dewi Lestari', 'Dawis wilayah selatan');

-- Insert user admin default (password: admin123)
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert sample warga
INSERT INTO warga (dawis_id, nama_lengkap, nomor_kk, alamat, no_telepon) VALUES
(1, 'Bapak Ahmad Santoso', '3201011234567890', 'Jl. Mawar No. 1', '081234567890'),
(1, 'Ibu Siti Rahayu', '3201011234567891', 'Jl. Mawar No. 2', '081234567891'),
(2, 'Bapak Budi Hartono', '3201011234567892', 'Jl. Melati No. 1', '081234567892'),
(2, 'Ibu Ani Wijaya', '3201011234567893', 'Jl. Melati No. 2', '081234567893'),
(3, 'Bapak Candra Kusuma', '3201011234567894', 'Jl. Kenanga No. 1', '081234567894'),
(3, 'Ibu Dewi Anggraini', '3201011234567895', 'Jl. Kenanga No. 2', '081234567895');

-- Insert sample transaksi
INSERT INTO transaksi (warga_id, tanggal_transaksi, jumlah, jenis_transaksi, keterangan) VALUES
(1, '2025-01-01', 50000, 'setoran', 'Jimpitan Januari minggu 1'),
(2, '2025-01-01', 50000, 'setoran', 'Jimpitan Januari minggu 1'),
(3, '2025-01-01', 50000, 'setoran', 'Jimpitan Januari minggu 1'),
(4, '2025-01-01', 50000, 'setoran', 'Jimpitan Januari minggu 1'),
(5, '2025-01-01', 50000, 'setoran', 'Jimpitan Januari minggu 1'),
(6, '2025-01-01', 50000, 'setoran', 'Jimpitan Januari minggu 1');

-- Insert sample pengeluaran
INSERT INTO pengeluaran (tanggal_pengeluaran, kategori, jumlah, keterangan) VALUES
('2025-01-05', 'Kebersihan', 100000, 'Beli alat kebersihan RT'),
('2025-01-10', 'Keamanan', 150000, 'Uang keamanan bulan Januari');

-- View untuk laporan per dawis
CREATE OR REPLACE VIEW laporan_per_dawis AS
SELECT
    d.id as dawis_id,
    d.nama_dawis,
    COUNT(DISTINCT w.id) as jumlah_warga,
    COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE 0 END), 0) as total_setoran,
    COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'penarikan' THEN t.jumlah ELSE 0 END), 0) as total_penarikan,
    COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END), 0) as saldo
FROM dawis d
LEFT JOIN warga w ON d.id = w.dawis_id
LEFT JOIN transaksi t ON w.id = t.warga_id
GROUP BY d.id, d.nama_dawis;

-- View untuk saldo total RT
CREATE OR REPLACE VIEW saldo_total_rt AS
SELECT
    COALESCE(SUM(CASE WHEN jenis_transaksi = 'setoran' THEN jumlah ELSE -jumlah END), 0) as total_jimpitan,
    (SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran) as total_pengeluaran,
    COALESCE(SUM(CASE WHEN jenis_transaksi = 'setoran' THEN jumlah ELSE -jumlah END), 0) -
    (SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran) as saldo_akhir
FROM transaksi;
