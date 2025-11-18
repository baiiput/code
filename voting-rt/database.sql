-- ========================================
-- Database Schema untuk Sistem Voting RT
-- Kompatibel dengan MySQL 5.7+ / MariaDB
-- ========================================

-- Buat database
CREATE DATABASE IF NOT EXISTS voting_rt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE voting_rt;

-- ========================================
-- Tabel Users (Pemilih/Voter)
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    no_kk VARCHAR(16) NOT NULL,
    no_hp VARCHAR(15),
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    foto_ktp VARCHAR(255),
    status_verifikasi ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    sudah_memilih TINYINT(1) DEFAULT 0,
    waktu_memilih DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nik (nik),
    INDEX idx_status (status_verifikasi)
) ENGINE=InnoDB;

-- ========================================
-- Tabel Admin
-- ========================================
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('superadmin', 'admin', 'operator') DEFAULT 'admin',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- Tabel Kandidat
-- ========================================
CREATE TABLE IF NOT EXISTS kandidat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_urut INT NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    alamat TEXT,
    pekerjaan VARCHAR(100),
    pendidikan VARCHAR(100),
    visi TEXT,
    misi TEXT,
    foto VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_no_urut (no_urut),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ========================================
-- Tabel Voting/Suara
-- ========================================
CREATE TABLE IF NOT EXISTS voting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kandidat_id INT NOT NULL,
    waktu_voting DATETIME NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kandidat_id) REFERENCES kandidat(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (user_id),
    INDEX idx_kandidat (kandidat_id),
    INDEX idx_waktu (waktu_voting)
) ENGINE=InnoDB;

-- ========================================
-- Tabel Pengaturan Pemilihan
-- ========================================
CREATE TABLE IF NOT EXISTS pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pemilihan VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    tanggal_mulai DATETIME NOT NULL,
    tanggal_selesai DATETIME NOT NULL,
    status_pemilihan ENUM('belum_mulai', 'berlangsung', 'selesai') DEFAULT 'belum_mulai',
    tampilkan_hasil TINYINT(1) DEFAULT 0,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- Tabel Log Aktivitas
-- ========================================
CREATE TABLE IF NOT EXISTS log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('user', 'admin') NOT NULL,
    user_id INT NOT NULL,
    aktivitas VARCHAR(255) NOT NULL,
    detail TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_type, user_id),
    INDEX idx_waktu (created_at)
) ENGINE=InnoDB;

-- ========================================
-- Insert Data Default
-- ========================================

-- Admin default (password: admin123)
INSERT INTO admin (username, password, nama_lengkap, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@votingrt.com', 'superadmin');

-- Pengaturan default
INSERT INTO pengaturan (nama_pemilihan, deskripsi, tanggal_mulai, tanggal_selesai, status_pemilihan) VALUES
('Pemilihan Ketua RT 001 Tahun 2024', 'Pemilihan Ketua RT 001 RW 005 Kelurahan Sukamaju periode 2024-2027',
DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 8 DAY), 'belum_mulai');

-- Contoh kandidat
INSERT INTO kandidat (no_urut, nama_lengkap, tempat_lahir, tanggal_lahir, alamat, pekerjaan, pendidikan, visi, misi) VALUES
(1, 'Budi Santoso', 'Jakarta', '1975-05-15', 'Jl. Melati No. 10 RT 001/005', 'Wiraswasta', 'S1 Manajemen',
'Mewujudkan RT yang aman, nyaman, dan sejahtera untuk seluruh warga',
'1. Meningkatkan keamanan lingkungan 24 jam\n2. Mengadakan kerja bakti rutin setiap minggu\n3. Membuat program bank sampah\n4. Meningkatkan solidaritas antar warga'),
(2, 'Siti Aminah', 'Bandung', '1980-08-20', 'Jl. Mawar No. 5 RT 001/005', 'Guru', 'S1 Pendidikan',
'Membangun RT yang cerdas, bersih, dan harmonis',
'1. Membuka perpustakaan mini untuk anak-anak\n2. Program kebersihan lingkungan\n3. Mengadakan posyandu rutin\n4. Mediasi konflik antar warga'),
(3, 'Ahmad Hidayat', 'Surabaya', '1978-03-10', 'Jl. Anggrek No. 8 RT 001/005', 'PNS', 'S2 Administrasi Publik',
'RT yang transparan, partisipatif, dan berkeadilan',
'1. Digitalisasi administrasi RT\n2. Laporan keuangan transparan\n3. Rapat warga rutin setiap bulan\n4. Program bantuan untuk warga kurang mampu');
