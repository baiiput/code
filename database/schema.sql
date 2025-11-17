-- ========================================
-- STARLINK CUSTOMER MANAGEMENT SYSTEM
-- Database Schema for MySQL
-- ========================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================
-- 1. USERS TABLE (Multi-user dengan role)
-- ========================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','finance','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default user: admin / admin123
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `email`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'admin@starlink.local', 'super_admin', 'active');

-- ========================================
-- 2. PELANGGAN TABLE (Customer data)
-- ========================================
CREATE TABLE IF NOT EXISTS `pelanggan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(200) NOT NULL,
  `login_gmail` text DEFAULT NULL,
  `login_starlink` text DEFAULT NULL,
  `login_alternatif` text DEFAULT NULL,
  `acc_no` varchar(100) DEFAULT NULL,
  `email_client` varchar(200) DEFAULT NULL,
  `nomor_cs` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kit_number` text DEFAULT NULL,
  `serial_number` text DEFAULT NULL,
  `tanggal_jatuh_tempo` date DEFAULT NULL,
  `kode` varchar(100) DEFAULT NULL,
  `payment` varchar(100) DEFAULT NULL,
  `id_transaksi` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Belum',
  `paket` text DEFAULT NULL,
  `last_4_digit` varchar(10) DEFAULT NULL,
  `no_aktivasi` varchar(100) DEFAULT NULL,
  `status_client` enum('Client Aktif','Client Non Aktif','Client Lepas') NOT NULL DEFAULT 'Client Aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_client` (`status_client`),
  KEY `status` (`status`),
  KEY `tanggal_jatuh_tempo` (`tanggal_jatuh_tempo`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_pelanggan_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. PAKET TABLE (Package types)
-- ========================================
CREATE TABLE IF NOT EXISTS `paket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_paket` varchar(100) NOT NULL,
  `harga` decimal(15,2) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default paket
INSERT INTO `paket` (`nama_paket`, `harga`, `keterangan`) VALUES
('Reguler', 750000.00, 'Paket Starlink Reguler'),
('Lite', 500000.00, 'Paket Starlink Lite'),
('Roam', 1500000.00, 'Paket Starlink Roam');

-- ========================================
-- 4. PEMBAYARAN TABLE (Payment records)
-- ========================================
CREATE TABLE IF NOT EXISTS `pembayaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pelanggan_id` int(11) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) GENERATED ALWAYS AS (`nominal` + `fee`) STORED,
  `metode_bayar` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status` enum('pending','lunas','dibatalkan') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pelanggan_id` (`pelanggan_id`),
  KEY `tanggal_bayar` (`tanggal_bayar`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_pembayaran_pelanggan` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pembayaran_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 5. FEE_SETTINGS TABLE (Fee configuration)
-- ========================================
CREATE TABLE IF NOT EXISTS `fee_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_setting` varchar(100) NOT NULL,
  `tipe` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `nilai` decimal(15,2) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default fee setting
INSERT INTO `fee_settings` (`nama_setting`, `tipe`, `nilai`, `status`) VALUES
('Fee Per Transaksi', 'fixed', 50000.00, 'active');

-- ========================================
-- 6. ACTIVITY_LOG TABLE (Audit trail)
-- ========================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `table_name` (`table_name`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 7. SYSTEM_SETTINGS TABLE (App settings)
-- ========================================
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('app_name', 'Starlink Customer Management', 'text', 'Nama Aplikasi'),
('app_version', '1.0.0', 'text', 'Versi Aplikasi'),
('timezone', 'Asia/Jakarta', 'text', 'Timezone'),
('date_format', 'd/m/Y', 'text', 'Format Tanggal'),
('currency_symbol', 'Rp', 'text', 'Simbol Mata Uang'),
('reminder_h3_enabled', '1', 'boolean', 'Enable Reminder H-3'),
('reminder_h2_enabled', '1', 'boolean', 'Enable Reminder H-2'),
('reminder_h1_enabled', '1', 'boolean', 'Enable Reminder H-1'),
('reminder_warning_enabled', '1', 'boolean', 'Enable Warning Reminder');

-- ========================================
-- 8. VIEWS untuk Dashboard
-- ========================================

-- View: Pelanggan Jatuh Tempo (besok)
CREATE OR REPLACE VIEW `v_jatuh_tempo` AS
SELECT
  p.*,
  DATEDIFF(p.tanggal_jatuh_tempo, CURDATE()) as hari_tersisa
FROM pelanggan p
WHERE p.tanggal_jatuh_tempo = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
  AND p.status_client = 'Client Aktif'
ORDER BY p.nama;

-- View: Pelanggan Proses (jatuh tempo kemarin)
CREATE OR REPLACE VIEW `v_proses` AS
SELECT
  p.*,
  DATEDIFF(CURDATE(), p.tanggal_jatuh_tempo) as hari_lewat
FROM pelanggan p
WHERE p.tanggal_jatuh_tempo = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
  AND p.status_client = 'Client Aktif'
ORDER BY p.nama;

-- View: Pelanggan Segera (sudah lunas tapi lewat tanggal)
CREATE OR REPLACE VIEW `v_segera` AS
SELECT
  p.*,
  DATEDIFF(CURDATE(), p.tanggal_jatuh_tempo) as hari_lewat
FROM pelanggan p
WHERE p.tanggal_jatuh_tempo < CURDATE()
  AND p.status IN ('Lunas', 'Rencana Bayarkan')
  AND p.status_client = 'Client Aktif'
ORDER BY p.tanggal_jatuh_tempo ASC;

-- View: Pelanggan Observasi (status pending)
CREATE OR REPLACE VIEW `v_observasi` AS
SELECT p.*
FROM pelanggan p
WHERE p.status = 'Pending'
  AND p.status_client = 'Client Aktif'
ORDER BY p.nama;

-- View: Laporan Pembayaran dengan Fee
CREATE OR REPLACE VIEW `v_laporan_pembayaran` AS
SELECT
  pb.id,
  pb.tanggal_bayar,
  p.nama as nama_pelanggan,
  p.kit_number,
  p.paket,
  pb.nominal,
  pb.fee,
  pb.total,
  pb.metode_bayar,
  pb.status,
  u.nama_lengkap as input_by,
  pb.created_at
FROM pembayaran pb
LEFT JOIN pelanggan p ON pb.pelanggan_id = p.id
LEFT JOIN users u ON pb.created_by = u.id
ORDER BY pb.tanggal_bayar DESC, pb.id DESC;

COMMIT;

-- ========================================
-- END OF SCHEMA
-- ========================================
