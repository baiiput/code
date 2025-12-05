-- =====================================================
-- ENHANCED DB OFFER - Complete Database Export
-- Includes: Original + New Enhancement Tables
-- Date: 2025-12-05
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_offer`
--

-- --------------------------------------------------------
-- ORIGINAL TABLE (tetap ada)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `penawaran_templates` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- NEW ENHANCEMENT TABLES
-- --------------------------------------------------------

-- Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `alamat` text,
  `telepon` varchar(50),
  `email` varchar(255),
  `contact_person` varchar(255),
  `company_type` enum('individual','company') DEFAULT 'company',
  `notes` text,
  `created_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nama` (`nama`),
  KEY `idx_email` (`email`),
  KEY `idx_created_by` (`created_by`),
  FULLTEXT KEY `ft_nama_contact` (`nama`,`contact_person`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company Profiles Table
CREATE TABLE IF NOT EXISTS `company_profiles` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nama_perusahaan` varchar(255) NOT NULL,
  `alamat` text,
  `telepon` varchar(50),
  `email` varchar(255),
  `website` varchar(255),
  `logo_url` varchar(500),
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('admin','manager','sales','viewer') DEFAULT 'sales',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Penawaran History Table
CREATE TABLE IF NOT EXISTS `penawaran_history` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nomor_penawaran` varchar(100) NOT NULL,
  `customer_id` bigint DEFAULT NULL,
  `company_profile_id` bigint DEFAULT NULL,
  `nama_pelanggan` varchar(255) NOT NULL,
  `alamat_pelanggan` text,
  `telepon_pelanggan` varchar(50),
  `email_pelanggan` varchar(255),
  `nama_perusahaan` varchar(255),
  `alamat_perusahaan` text,
  `telepon_perusahaan` varchar(50),
  `email_perusahaan` varchar(255),
  `tanggal` date NOT NULL,
  `berlaku_hingga` date,
  `data` json NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0,
  `diskon_persen` decimal(5,2) DEFAULT 0,
  `diskon_nominal` decimal(15,2) DEFAULT 0,
  `ppn_persen` decimal(5,2) DEFAULT 0,
  `ppn_nominal` decimal(15,2) DEFAULT 0,
  `total` decimal(15,2) DEFAULT 0,
  `status` enum('draft','sent','approved','rejected','revised') DEFAULT 'draft',
  `sent_at` timestamp NULL,
  `approved_at` timestamp NULL,
  `rejected_at` timestamp NULL,
  `rejection_reason` text,
  `version` int DEFAULT 1,
  `parent_id` bigint DEFAULT NULL,
  `created_by` bigint DEFAULT NULL,
  `sent_by` bigint DEFAULT NULL,
  `approved_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomor_penawaran` (`nomor_penawaran`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_company_profile_id` (`company_profile_id`),
  KEY `idx_status` (`status`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_customer_status` (`customer_id`,`status`),
  KEY `idx_tanggal_status` (`tanggal`,`status`),
  KEY `idx_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Penawaran Revisions Table
CREATE TABLE IF NOT EXISTS `penawaran_revisions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `penawaran_id` bigint NOT NULL,
  `version` int NOT NULL,
  `data_snapshot` json NOT NULL,
  `changes_summary` text,
  `revised_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_penawaran_id` (`penawaran_id`),
  KEY `idx_version` (`version`),
  KEY `idx_revised_by` (`revised_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Sessions Table
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(50),
  `user_agent` text,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_token` (`session_token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` bigint DEFAULT NULL,
  `description` text,
  `old_data` json,
  `new_data` json,
  `ip_address` varchar(50),
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SAMPLE DATA
-- --------------------------------------------------------

-- Default Admin User (password: admin123)
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('admin', 'admin@octolink.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1)
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- Default Company Profiles
INSERT INTO `company_profiles` (`nama_perusahaan`, `alamat`, `telepon`, `email`, `is_default`, `is_active`) VALUES
('Octolink.ID', 'Kembangarum Area Semarang Barat', '08567890902', 'info.octolink@gmail.com', 1, 1),
('OCTOLINK.ID', 'Kembangarum Area Semarang Barat', '08567890902', 'id.octolink@gmail.com', 0, 1)
ON DUPLICATE KEY UPDATE `nama_perusahaan` = VALUES(`nama_perusahaan`);

-- Sample Customers
INSERT INTO `customers` (`nama`, `alamat`, `telepon`, `email`, `company_type`) VALUES
('Hideout', '-', '-', '-', 'company'),
('PT. Multi Instrumentasi Mandiri', 'Jl. Perintis Kemerdekaan No.178 A dan E, Srondol Wetan, Kec. Banyumanik, Kota Semarang, Jawa Tengah 50263', '-', '-', 'company'),
('POS RT 7 RW 3', 'Jl. Taman Condrokusumo IV', '-', '-', 'company'),
('PT. Global Energi Lestari', 'Jl. SM Amin No No 8 A-B Pekanbaru', '081268121398', '', 'company'),
('Deni Wijaya', '', '081385200389', '', 'individual'),
('PT. Samudera Inti Pasifik', 'Pasar B2 Bero Jaya, Tungkal Jaya Musi Banyuasin Sumatera Selatan', '081268121398', '', 'company'),
('PT. Yangtze River Indonesia', 'Kawasan Industri Kendal Wonorejo Kec. Kaliwungu, Kabupaten Kendal Jawa Tengah 51372', '-', '-', 'company'),
('Faisal Agustino', '', '087738331027', '', 'individual')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
