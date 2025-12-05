-- =====================================================
-- Database Migration for Penawaran Website Enhancement
-- Date: 2025-12-05
-- Features: Customer DB, Company Profiles, History, Revisions, User Management
-- =====================================================

-- 1. TABEL CUSTOMERS
-- Menyimpan data pelanggan untuk auto-fill
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
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL COMPANY PROFILES
-- Menyimpan multiple company profiles/header information
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

-- 3. TABEL USERS
-- User management untuk multi-user access
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

-- 4. TABEL PENAWARAN HISTORY
-- Menyimpan semua penawaran yang pernah dibuat (terpisah dari template)
CREATE TABLE IF NOT EXISTS `penawaran_history` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nomor_penawaran` varchar(100) NOT NULL,
  `customer_id` bigint DEFAULT NULL,
  `company_profile_id` bigint DEFAULT NULL,
  `

  -- Data Pelanggan (snapshot saat penawaran dibuat)
  `nama_pelanggan` varchar(255) NOT NULL,
  `alamat_pelanggan` text,
  `telepon_pelanggan` varchar(50),
  `email_pelanggan` varchar(255),

  -- Data Perusahaan (snapshot saat penawaran dibuat)
  `nama_perusahaan` varchar(255),
  `alamat_perusahaan` text,
  `telepon_perusahaan` varchar(50),
  `email_perusahaan` varchar(255),

  -- Data Penawaran
  `tanggal` date NOT NULL,
  `berlaku_hingga` date,
  `data` json NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0,
  `diskon_persen` decimal(5,2) DEFAULT 0,
  `diskon_nominal` decimal(15,2) DEFAULT 0,
  `ppn_persen` decimal(5,2) DEFAULT 0,
  `ppn_nominal` decimal(15,2) DEFAULT 0,
  `total` decimal(15,2) DEFAULT 0,

  -- Status & Workflow
  `status` enum('draft','sent','approved','rejected','revised') DEFAULT 'draft',
  `sent_at` timestamp NULL,
  `approved_at` timestamp NULL,
  `rejected_at` timestamp NULL,
  `rejection_reason` text,

  -- Version & Revision
  `version` int DEFAULT 1,
  `parent_id` bigint DEFAULT NULL COMMENT 'ID dari penawaran parent jika ini adalah revisi',

  -- User Tracking
  `created_by` bigint DEFAULT NULL,
  `sent_by` bigint DEFAULT NULL,
  `approved_by` bigint DEFAULT NULL,

  -- Timestamps
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

  CONSTRAINT `fk_penawaran_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_penawaran_company` FOREIGN KEY (`company_profile_id`) REFERENCES `company_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_penawaran_parent` FOREIGN KEY (`parent_id`) REFERENCES `penawaran_history` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_penawaran_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_penawaran_sent_by` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_penawaran_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL PENAWARAN REVISIONS
-- Menyimpan log perubahan/revisi untuk audit trail
CREATE TABLE IF NOT EXISTS `penawaran_revisions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `penawaran_id` bigint NOT NULL,
  `version` int NOT NULL,
  `data_snapshot` json NOT NULL COMMENT 'Snapshot lengkap data penawaran',
  `changes_summary` text COMMENT 'Ringkasan perubahan yang dilakukan',
  `revised_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_penawaran_id` (`penawaran_id`),
  KEY `idx_version` (`version`),
  KEY `idx_revised_by` (`revised_by`),
  CONSTRAINT `fk_revision_penawaran` FOREIGN KEY (`penawaran_id`) REFERENCES `penawaran_history` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_revision_user` FOREIGN KEY (`revised_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABEL USER SESSIONS
-- Menyimpan session untuk authentication
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
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABEL ACTIVITY LOGS
-- Audit trail untuk semua aktivitas penting
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `action` varchar(100) NOT NULL COMMENT 'create, update, delete, send, approve, etc',
  `entity_type` varchar(50) NOT NULL COMMENT 'penawaran, customer, template, etc',
  `entity_id` bigint DEFAULT NULL,
  `description` text,
  `old_data` json COMMENT 'Data sebelum perubahan',
  `new_data` json COMMENT 'Data setelah perubahan',
  `ip_address` varchar(50),
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT SAMPLE DATA
-- =====================================================

-- Insert default admin user (password: admin123)
-- Password hash menggunakan PHP password_hash() dengan PASSWORD_DEFAULT
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `is_active`) VALUES
('admin', 'admin@octolink.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1)
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Insert default company profile dari data yang sudah ada
INSERT INTO `company_profiles` (`nama_perusahaan`, `alamat`, `telepon`, `email`, `is_default`, `is_active`) VALUES
('Octolink.ID', 'Kembangarum Area Semarang Barat', '08567890902', 'info.octolink@gmail.com', 1, 1),
('OCTOLINK.ID', 'Kembangarum Area Semarang Barat', '08567890902', 'id.octolink@gmail.com', 0, 1)
ON DUPLICATE KEY UPDATE `nama_perusahaan` = `nama_perusahaan`;

-- Insert sample customers dari data templates yang sudah ada
INSERT INTO `customers` (`nama`, `alamat`, `telepon`, `email`, `company_type`) VALUES
('Hideout', '-', '-', '-', 'company'),
('PT. Multi Instrumentasi Mandiri', 'Jl. Perintis Kemerdekaan No.178 A dan E, Srondol Wetan, Kec. Banyumanik, Kota Semarang, Jawa Tengah 50263', '-', '-', 'company'),
('POS RT 7 RW 3', 'Jl. Taman Condrokusumo IV', '-', '-', 'company'),
('PT. Global Energi Lestari', 'Jl. SM Amin No No 8 A-B Pekanbaru', '081268121398', '', 'company'),
('Deni Wijaya', '', '081385200389', '', 'individual'),
('PT. Samudera Inti Pasifik (Site BSPC)', 'Pasar B2 Bero Jaya, Tungkal Jaya Musi Banyuasin Sumatera Selatan', '081268121398', '', 'company'),
('PT. Yangtze River Indonesia', 'Kawasan Industri Kendal Wonorejo Kec. Kaliwungu, Kabupaten Kendal Jawa Tengah 51372', '-', '-', 'company'),
('Faisal Agustino', '', '087738331027', '', 'individual'),
('Pelanggan Starlink', '', '081398765001', '-', 'individual')
ON DUPLICATE KEY UPDATE `nama` = `nama`;

-- =====================================================
-- CREATE VIEWS untuk Query yang Sering Digunakan
-- =====================================================

-- View untuk daftar penawaran dengan informasi lengkap
CREATE OR REPLACE VIEW `v_penawaran_list` AS
SELECT
    ph.id,
    ph.nomor_penawaran,
    ph.nama_pelanggan,
    ph.nama_perusahaan,
    ph.tanggal,
    ph.berlaku_hingga,
    ph.total,
    ph.status,
    ph.version,
    c.nama as customer_name,
    cp.nama_perusahaan as company_name,
    u.full_name as created_by_name,
    ph.created_at,
    ph.updated_at,
    JSON_LENGTH(JSON_EXTRACT(ph.data, '$.items')) as item_count
FROM penawaran_history ph
LEFT JOIN customers c ON ph.customer_id = c.id
LEFT JOIN company_profiles cp ON ph.company_profile_id = cp.id
LEFT JOIN users u ON ph.created_by = u.id
ORDER BY ph.created_at DESC;

-- View untuk revisi history
CREATE OR REPLACE VIEW `v_penawaran_revisions_list` AS
SELECT
    pr.id,
    pr.penawaran_id,
    pr.version,
    pr.changes_summary,
    u.full_name as revised_by_name,
    pr.created_at
FROM penawaran_revisions pr
LEFT JOIN users u ON pr.revised_by = u.id
ORDER BY pr.penawaran_id, pr.version DESC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure untuk auto-generate nomor penawaran
DELIMITER $$

CREATE PROCEDURE `sp_generate_nomor_penawaran`(
    IN p_tanggal DATE,
    OUT p_nomor_penawaran VARCHAR(100)
)
BEGIN
    DECLARE v_count INT;
    DECLARE v_year VARCHAR(4);
    DECLARE v_month VARCHAR(2);
    DECLARE v_day VARCHAR(2);
    DECLARE v_sequence VARCHAR(3);

    SET v_year = YEAR(p_tanggal);
    SET v_month = LPAD(MONTH(p_tanggal), 2, '0');
    SET v_day = LPAD(DAY(p_tanggal), 2, '0');

    -- Hitung jumlah penawaran pada tanggal yang sama
    SELECT COUNT(*) + 1 INTO v_count
    FROM penawaran_history
    WHERE DATE(tanggal) = p_tanggal;

    SET v_sequence = LPAD(v_count, 3, '0');
    SET p_nomor_penawaran = CONCAT('PNW-', v_year, v_month, v_day, '-', v_sequence);
END$$

DELIMITER ;

-- =====================================================
-- INDEXES untuk Performance Optimization
-- =====================================================

-- Additional composite indexes untuk query yang kompleks
ALTER TABLE `penawaran_history`
ADD INDEX `idx_customer_status` (`customer_id`, `status`),
ADD INDEX `idx_tanggal_status` (`tanggal`, `status`),
ADD INDEX `idx_status_created` (`status`, `created_at`);

ALTER TABLE `customers`
ADD FULLTEXT INDEX `ft_nama_contact` (`nama`, `contact_person`);

-- =====================================================
-- TRIGGERS untuk Audit & Validation
-- =====================================================

DELIMITER $$

-- Trigger untuk log activity saat penawaran dibuat
CREATE TRIGGER `tr_penawaran_after_insert`
AFTER INSERT ON `penawaran_history`
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, new_data)
    VALUES (
        NEW.created_by,
        'create',
        'penawaran',
        NEW.id,
        CONCAT('Penawaran baru dibuat: ', NEW.nomor_penawaran),
        JSON_OBJECT(
            'nomor_penawaran', NEW.nomor_penawaran,
            'customer', NEW.nama_pelanggan,
            'total', NEW.total,
            'status', NEW.status
        )
    );
END$$

-- Trigger untuk log activity saat penawaran diupdate
CREATE TRIGGER `tr_penawaran_after_update`
AFTER UPDATE ON `penawaran_history`
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, old_data, new_data)
    VALUES (
        NEW.created_by,
        'update',
        'penawaran',
        NEW.id,
        CONCAT('Penawaran diupdate: ', NEW.nomor_penawaran),
        JSON_OBJECT(
            'status', OLD.status,
            'total', OLD.total,
            'version', OLD.version
        ),
        JSON_OBJECT(
            'status', NEW.status,
            'total', NEW.total,
            'version', NEW.version
        )
    );
END$$

-- Trigger untuk ensure only one default company profile
CREATE TRIGGER `tr_company_profile_before_insert`
BEFORE INSERT ON `company_profiles`
FOR EACH ROW
BEGIN
    IF NEW.is_default = 1 THEN
        UPDATE company_profiles SET is_default = 0 WHERE is_default = 1;
    END IF;
END$$

CREATE TRIGGER `tr_company_profile_before_update`
BEFORE UPDATE ON `company_profiles`
FOR EACH ROW
BEGIN
    IF NEW.is_default = 1 AND OLD.is_default = 0 THEN
        UPDATE company_profiles SET is_default = 0 WHERE id != NEW.id AND is_default = 1;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- GRANT PERMISSIONS (jika diperlukan)
-- =====================================================

-- GRANT ALL PRIVILEGES ON db_offer.* TO 'db_offer'@'localhost';
-- FLUSH PRIVILEGES;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
