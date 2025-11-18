-- Starlink Customer Management System
-- Simple PHP + MySQL Version

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- --------------------------------------------------------
-- Database: `starlink_db`
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `starlink_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `starlink_db`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','viewer') NOT NULL DEFAULT 'viewer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `customers`
-- --------------------------------------------------------

CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `gmail_email` varchar(255) DEFAULT NULL,
  `gmail_password` varchar(255) DEFAULT NULL,
  `starlink_email` varchar(255) DEFAULT NULL,
  `starlink_password` varchar(255) DEFAULT NULL,
  `login_alternatif` text DEFAULT NULL,
  `acc_no` varchar(255) DEFAULT NULL,
  `email_client` varchar(255) DEFAULT NULL,
  `nomor_cs` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kit_number` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `tanggal_jatuh_tempo` date DEFAULT NULL,
  `kode` varchar(255) DEFAULT NULL,
  `status_langganan` enum('aktif','nonaktif','lunas','belum_bayar') NOT NULL DEFAULT 'aktif',
  `paket` varchar(255) DEFAULT NULL,
  `last_4_digit` varchar(4) DEFAULT NULL,
  `no_aktivasi` varchar(255) DEFAULT NULL,
  `koordinat_lokasi` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `periode_bulan` varchar(255) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default Super Admin
-- Password: admin123
-- --------------------------------------------------------

INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@starlink.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

SET FOREIGN_KEY_CHECKS=1;
