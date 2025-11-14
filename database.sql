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

-- Tabel Settings (untuk konfigurasi target tahunan)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
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

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('target_tahunan', '120000', 'Target jimpitan tahunan per KK (dalam Rupiah)'),
('tahun_berjalan', '2025', 'Tahun yang sedang berjalan untuk perhitungan target');

-- Insert sample warga
INSERT INTO warga (dawis_id, nama_lengkap, nomor_kk, alamat, no_telepon) VALUES
(1, 'Bapak Ahmad Santoso', '3201011234567890', 'Jl. Mawar No. 1', '081234567890'),
(1, 'Ibu Siti Rahayu', '3201011234567891', 'Jl. Mawar No. 2', '081234567891'),
(2, 'Bapak Budi Hartono', '3201011234567892', 'Jl. Melati No. 1', '081234567892'),
(2, 'Ibu Ani Wijaya', '3201011234567893', 'Jl. Melati No. 2', '081234567893'),
(3, 'Bapak Candra Kusuma', '3201011234567894', 'Jl. Kenanga No. 1', '081234567894'),
(3, 'Ibu Dewi Anggraini', '3201011234567895', 'Jl. Kenanga No. 2', '081234567895');

-- Insert sample transaksi (varied amounts to show different payment statuses)
-- Warga 1: Bayar tepat (110rb untuk 11 bulan)
INSERT INTO transaksi (warga_id, tanggal_transaksi, jumlah, jenis_transaksi, keterangan) VALUES
(1, '2025-01-15', 10000, 'setoran', 'Jimpitan Januari'),
(1, '2025-02-15', 10000, 'setoran', 'Jimpitan Februari'),
(1, '2025-03-15', 10000, 'setoran', 'Jimpitan Maret'),
(1, '2025-04-15', 10000, 'setoran', 'Jimpitan April'),
(1, '2025-05-15', 10000, 'setoran', 'Jimpitan Mei'),
(1, '2025-06-15', 10000, 'setoran', 'Jimpitan Juni'),
(1, '2025-07-15', 10000, 'setoran', 'Jimpitan Juli'),
(1, '2025-08-15', 10000, 'setoran', 'Jimpitan Agustus'),
(1, '2025-09-15', 10000, 'setoran', 'Jimpitan September'),
(1, '2025-10-15', 10000, 'setoran', 'Jimpitan Oktober'),
(1, '2025-11-15', 10000, 'setoran', 'Jimpitan November'),

-- Warga 2: Bayar lebih dari target (130rb untuk 11 bulan)
(2, '2025-01-20', 15000, 'setoran', 'Jimpitan Januari'),
(2, '2025-02-20', 10000, 'setoran', 'Jimpitan Februari'),
(2, '2025-03-20', 10000, 'setoran', 'Jimpitan Maret'),
(2, '2025-04-20', 15000, 'setoran', 'Jimpitan April'),
(2, '2025-05-20', 10000, 'setoran', 'Jimpitan Mei'),
(2, '2025-06-20', 10000, 'setoran', 'Jimpitan Juni'),
(2, '2025-07-20', 15000, 'setoran', 'Jimpitan Juli'),
(2, '2025-08-20', 10000, 'setoran', 'Jimpitan Agustus'),
(2, '2025-09-20', 10000, 'setoran', 'Jimpitan September'),
(2, '2025-10-20', 10000, 'setoran', 'Jimpitan Oktober'),
(2, '2025-11-20', 15000, 'setoran', 'Jimpitan November'),

-- Warga 3: Kurang bayar (70rb untuk 11 bulan, target harusnya 110rb) - ALERT!
(3, '2025-01-10', 5000, 'setoran', 'Jimpitan Januari'),
(3, '2025-02-10', 5000, 'setoran', 'Jimpitan Februari'),
(3, '2025-03-10', 5000, 'setoran', 'Jimpitan Maret'),
(3, '2025-04-10', 10000, 'setoran', 'Jimpitan April'),
(3, '2025-05-10', 5000, 'setoran', 'Jimpitan Mei'),
(3, '2025-06-10', 5000, 'setoran', 'Jimpitan Juni'),
(3, '2025-07-10', 5000, 'setoran', 'Jimpitan Juli'),
(3, '2025-08-10', 10000, 'setoran', 'Jimpitan Agustus'),
(3, '2025-09-10', 5000, 'setoran', 'Jimpitan September'),
(3, '2025-10-10', 5000, 'setoran', 'Jimpitan Oktober'),
(3, '2025-11-10', 10000, 'setoran', 'Jimpitan November'),

-- Warga 4: Bayar normal (95rb untuk 11 bulan)
(4, '2025-01-15', 8000, 'setoran', 'Jimpitan Januari'),
(4, '2025-02-15', 8000, 'setoran', 'Jimpitan Februari'),
(4, '2025-03-15', 10000, 'setoran', 'Jimpitan Maret'),
(4, '2025-04-15', 8000, 'setoran', 'Jimpitan April'),
(4, '2025-05-15', 8000, 'setoran', 'Jimpitan Mei'),
(4, '2025-06-15', 10000, 'setoran', 'Jimpitan Juni'),
(4, '2025-07-15', 8000, 'setoran', 'Jimpitan Juli'),
(4, '2025-08-15', 10000, 'setoran', 'Jimpitan Agustus'),
(4, '2025-09-15', 10000, 'setoran', 'Jimpitan September'),
(4, '2025-10-15', 8000, 'setoran', 'Jimpitan Oktober'),
(4, '2025-11-15', 7000, 'setoran', 'Jimpitan November'),

-- Warga 5: Sangat kurang bayar (40rb untuk 11 bulan) - ALERT!
(5, '2025-01-20', 3000, 'setoran', 'Jimpitan Januari'),
(5, '2025-03-20', 5000, 'setoran', 'Jimpitan Maret'),
(5, '2025-05-20', 3000, 'setoran', 'Jimpitan Mei'),
(5, '2025-06-20', 5000, 'setoran', 'Jimpitan Juni'),
(5, '2025-07-20', 4000, 'setoran', 'Jimpitan Juli'),
(5, '2025-08-20', 5000, 'setoran', 'Jimpitan Agustus'),
(5, '2025-09-20', 5000, 'setoran', 'Jimpitan September'),
(5, '2025-11-20', 10000, 'setoran', 'Jimpitan November'),

-- Warga 6: Bayar sempurna (120rb untuk 11 bulan, lebih dari target)
(6, '2025-01-10', 10000, 'setoran', 'Jimpitan Januari'),
(6, '2025-02-10', 10000, 'setoran', 'Jimpitan Februari'),
(6, '2025-03-10', 12000, 'setoran', 'Jimpitan Maret'),
(6, '2025-04-10', 10000, 'setoran', 'Jimpitan April'),
(6, '2025-05-10', 10000, 'setoran', 'Jimpitan Mei'),
(6, '2025-06-10', 12000, 'setoran', 'Jimpitan Juni'),
(6, '2025-07-10', 10000, 'setoran', 'Jimpitan Juli'),
(6, '2025-08-10', 12000, 'setoran', 'Jimpitan Agustus'),
(6, '2025-09-10', 10000, 'setoran', 'Jimpitan September'),
(6, '2025-10-10', 12000, 'setoran', 'Jimpitan Oktober'),
(6, '2025-11-10', 12000, 'setoran', 'Jimpitan November');

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

-- View untuk progress pembayaran warga (dengan target tahunan)
CREATE OR REPLACE VIEW progress_pembayaran_warga AS
SELECT
    w.id as warga_id,
    w.nama_lengkap,
    w.nomor_kk,
    w.alamat,
    w.no_telepon,
    w.status,
    w.dawis_id,
    d.nama_dawis,

    -- Get target tahunan dari settings
    (SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') as target_tahunan,

    -- Get tahun berjalan
    (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan') as tahun_berjalan,

    -- Hitung bulan berjalan (1-12)
    MONTH(CURRENT_DATE()) as bulan_berjalan,

    -- Target sampai bulan berjalan (target_tahunan * bulan_berjalan / 12)
    ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
          MONTH(CURRENT_DATE()) / 12, 0) as target_sampai_bulan_ini,

    -- Total setoran tahun ini
    COALESCE(SUM(CASE
        WHEN t.jenis_transaksi = 'setoran'
        AND YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
        THEN t.jumlah
        ELSE 0
    END), 0) as total_bayar_tahun_ini,

    -- Total penarikan tahun ini
    COALESCE(SUM(CASE
        WHEN t.jenis_transaksi = 'penarikan'
        AND YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
        THEN t.jumlah
        ELSE 0
    END), 0) as total_penarikan_tahun_ini,

    -- Saldo tahun ini (setoran - penarikan)
    COALESCE(SUM(CASE
        WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
        THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
        ELSE 0
    END), 0) as saldo_tahun_ini,

    -- Selisih dari target bulan ini
    COALESCE(SUM(CASE
        WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
        THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
        ELSE 0
    END), 0) - ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
          MONTH(CURRENT_DATE()) / 12, 0) as selisih_dari_target,

    -- Persentase pencapaian (saldo_tahun_ini / target_sampai_bulan_ini * 100)
    CASE
        WHEN ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
          MONTH(CURRENT_DATE()) / 12, 0) > 0
        THEN ROUND(
            (COALESCE(SUM(CASE
                WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
                THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
                ELSE 0
            END), 0) /
            ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
              MONTH(CURRENT_DATE()) / 12, 0)) * 100, 2)
        ELSE 0
    END as persentase_pencapaian,

    -- Status pembayaran (OK, Warning, Alert)
    CASE
        WHEN ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
          MONTH(CURRENT_DATE()) / 12, 0) = 0 THEN 'OK'
        WHEN (COALESCE(SUM(CASE
                WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
                THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
                ELSE 0
            END), 0) /
            ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
              MONTH(CURRENT_DATE()) / 12, 0)) < 0.75 THEN 'alert'
        WHEN (COALESCE(SUM(CASE
                WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
                THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
                ELSE 0
            END), 0) /
            ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
              MONTH(CURRENT_DATE()) / 12, 0)) < 0.90 THEN 'warning'
        ELSE 'ok'
    END as status_pembayaran

FROM warga w
LEFT JOIN dawis d ON w.dawis_id = d.id
LEFT JOIN transaksi t ON w.id = t.warga_id
WHERE w.status = 'aktif'
GROUP BY w.id, w.nama_lengkap, w.nomor_kk, w.alamat, w.no_telepon, w.status, w.dawis_id, d.nama_dawis;
