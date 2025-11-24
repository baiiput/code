-- ============================================
-- WAREHOUSE MANAGEMENT SYSTEM - COMPLETE DATABASE SCHEMA
-- Multi-Warehouse with Activity Logs & Manager Role
-- Version: 2.0
-- Last Update: 2024
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- 1. MASTER DATA TABLES
-- ============================================

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT PRIMARY KEY AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Items Table (Master data barang)
CREATE TABLE IF NOT EXISTS `items` (
  `item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `item_code` VARCHAR(50) NOT NULL,
  `item_name` VARCHAR(200) NOT NULL,
  `category_id` INT,
  `unit` VARCHAR(20) NOT NULL,
  `min_stock` DECIMAL(10,2) DEFAULT 0,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `item_code` (`item_code`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL,
  INDEX `idx_item_code` (`item_code`),
  INDEX `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Suppliers Table
CREATE TABLE IF NOT EXISTS `suppliers` (
  `supplier_id` INT PRIMARY KEY AUTO_INCREMENT,
  `supplier_name` VARCHAR(200) NOT NULL,
  `contact_person` VARCHAR(100),
  `phone` VARCHAR(20),
  `email` VARCHAR(100),
  `address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_supplier_name` (`supplier_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Branches Table (Cabang)
CREATE TABLE IF NOT EXISTS `branches` (
  `branch_id` INT PRIMARY KEY AUTO_INCREMENT,
  `branch_name` VARCHAR(200) NOT NULL,
  `address` TEXT,
  `phone` VARCHAR(20),
  `manager_name` VARCHAR(100),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_branch_name` (`branch_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Warehouses Table (Multi-Warehouse)
CREATE TABLE IF NOT EXISTS `warehouses` (
  `warehouse_id` INT PRIMARY KEY AUTO_INCREMENT,
  `warehouse_code` VARCHAR(20) NOT NULL,
  `warehouse_name` VARCHAR(200) NOT NULL,
  `address` TEXT,
  `phone` VARCHAR(20),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `warehouse_code` (`warehouse_code`),
  INDEX `idx_warehouse_code` (`warehouse_code`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. INVENTORY TABLES (Multi-Warehouse)
-- ============================================

-- Warehouse Items (Pivot table for multi-warehouse stock)
CREATE TABLE IF NOT EXISTS `warehouse_items` (
  `warehouse_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `warehouse_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `current_stock` DECIMAL(10,2) DEFAULT 0,
  `average_cost` DECIMAL(15,2) DEFAULT 0,
  `min_stock` DECIMAL(10,2) DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`warehouse_id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE CASCADE,
  UNIQUE KEY `warehouse_item_unique` (`warehouse_id`, `item_id`),
  INDEX `idx_warehouse` (`warehouse_id`),
  INDEX `idx_item` (`item_id`),
  INDEX `idx_stock` (`current_stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. TRANSACTION TABLES
-- ============================================

-- Stock In (Pembelian dari Supplier)
CREATE TABLE IF NOT EXISTS `stock_in` (
  `stock_in_id` INT PRIMARY KEY AUTO_INCREMENT,
  `transaction_code` VARCHAR(50) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `supplier_id` INT,
  `warehouse_id` INT NOT NULL,
  `total_amount` DECIMAL(15,2) DEFAULT 0,
  `notes` TEXT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `transaction_code` (`transaction_code`),
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`supplier_id`) ON DELETE SET NULL,
  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`warehouse_id`) ON DELETE RESTRICT,
  INDEX `idx_transaction_date` (`transaction_date`),
  INDEX `idx_warehouse` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_in_detail` (
  `detail_id` INT PRIMARY KEY AUTO_INCREMENT,
  `stock_in_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_price` DECIMAL(15,2) NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (`stock_in_id`) REFERENCES `stock_in`(`stock_in_id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE RESTRICT,
  INDEX `idx_stock_in` (`stock_in_id`),
  INDEX `idx_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock Out (Distribusi ke Cabang)
CREATE TABLE IF NOT EXISTS `stock_out` (
  `stock_out_id` INT PRIMARY KEY AUTO_INCREMENT,
  `transaction_code` VARCHAR(50) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `branch_id` INT,
  `warehouse_id` INT NOT NULL,
  `total_amount` DECIMAL(15,2) DEFAULT 0,
  `notes` TEXT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `transaction_code` (`transaction_code`),
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`branch_id`) ON DELETE SET NULL,
  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`warehouse_id`) ON DELETE RESTRICT,
  INDEX `idx_transaction_date` (`transaction_date`),
  INDEX `idx_warehouse` (`warehouse_id`),
  INDEX `idx_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_out_detail` (
  `detail_id` INT PRIMARY KEY AUTO_INCREMENT,
  `stock_out_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_price` DECIMAL(15,2) NOT NULL,
  `subtotal` DECIMAL(15,2) NOT NULL,
  FOREIGN KEY (`stock_out_id`) REFERENCES `stock_out`(`stock_out_id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE RESTRICT,
  INDEX `idx_stock_out` (`stock_out_id`),
  INDEX `idx_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock Adjustment (Opname/Koreksi Stok)
CREATE TABLE IF NOT EXISTS `stock_adjustment` (
  `adjustment_id` INT PRIMARY KEY AUTO_INCREMENT,
  `transaction_code` VARCHAR(50) NOT NULL,
  `adjustment_date` DATE NOT NULL,
  `warehouse_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `old_stock` DECIMAL(10,2) NOT NULL,
  `new_stock` DECIMAL(10,2) NOT NULL,
  `difference` DECIMAL(10,2) NOT NULL,
  `reason` TEXT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `transaction_code` (`transaction_code`),
  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`warehouse_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE RESTRICT,
  INDEX `idx_adjustment_date` (`adjustment_date`),
  INDEX `idx_warehouse` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stock Transfers (Transfer antar Warehouse)
CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `transfer_id` INT PRIMARY KEY AUTO_INCREMENT,
  `transaction_code` VARCHAR(50) NOT NULL,
  `transfer_date` DATE NOT NULL,
  `from_warehouse_id` INT NOT NULL,
  `to_warehouse_id` INT NOT NULL,
  `notes` TEXT,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `transaction_code` (`transaction_code`),
  FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses`(`warehouse_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses`(`warehouse_id`) ON DELETE RESTRICT,
  INDEX `idx_transfer_date` (`transfer_date`),
  INDEX `idx_from_warehouse` (`from_warehouse_id`),
  INDEX `idx_to_warehouse` (`to_warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_transfer_detail` (
  `detail_id` INT PRIMARY KEY AUTO_INCREMENT,
  `transfer_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers`(`transfer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE RESTRICT,
  INDEX `idx_transfer` (`transfer_id`),
  INDEX `idx_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. FINANCIAL TABLES
-- ============================================

-- Warehouse Balance
CREATE TABLE IF NOT EXISTS `warehouse_balance` (
  `balance_id` INT PRIMARY KEY AUTO_INCREMENT,
  `balance_amount` DECIMAL(15,2) DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Financial Transactions
CREATE TABLE IF NOT EXISTS `financial_transactions` (
  `transaction_id` INT PRIMARY KEY AUTO_INCREMENT,
  `transaction_date` DATE NOT NULL,
  `transaction_type` ENUM('stock_in', 'stock_out', 'adjustment', 'other') NOT NULL,
  `reference_code` VARCHAR(50),
  `description` TEXT,
  `debit` DECIMAL(15,2) DEFAULT 0,
  `credit` DECIMAL(15,2) DEFAULT 0,
  `balance_after` DECIMAL(15,2) DEFAULT 0,
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_transaction_date` (`transaction_date`),
  INDEX `idx_transaction_type` (`transaction_type`),
  INDEX `idx_reference_code` (`reference_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. USER & ACCESS CONTROL TABLES
-- ============================================

-- Users Table (dengan Manager role)
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100),
  `phone` VARCHAR(20),
  `role` ENUM('admin', 'manager', 'staff_warehouse', 'staff_keuangan', 'cabang') NOT NULL,
  `cabang_id` INT NULL,
  `warehouse_id` INT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `username` (`username`),
  FOREIGN KEY (`cabang_id`) REFERENCES `branches`(`branch_id`) ON DELETE SET NULL,
  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`warehouse_id`) ON DELETE SET NULL,
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_module` (`module`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. INITIAL DATA
-- ============================================

-- Insert default warehouse balance
INSERT INTO `warehouse_balance` (`balance_id`, `balance_amount`) VALUES (1, 0)
ON DUPLICATE KEY UPDATE balance_id = balance_id;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `is_active`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1)
ON DUPLICATE KEY UPDATE username = username;

-- ============================================
-- 7. SAMPLE DATA (Optional - Comment out if not needed)
-- ============================================

-- Sample Categories
INSERT INTO `categories` (`category_name`, `description`) VALUES
('Elektronik', 'Barang-barang elektronik'),
('Makanan & Minuman', 'Produk makanan dan minuman'),
('Pakaian', 'Pakaian dan aksesoris'),
('Alat Tulis', 'Perlengkapan kantor dan alat tulis')
ON DUPLICATE KEY UPDATE category_name = category_name;

-- Sample Warehouse
INSERT INTO `warehouses` (`warehouse_code`, `warehouse_name`, `address`, `phone`, `is_active`) VALUES
('WH001', 'Gudang Pusat Jakarta', 'Jl. Raya Jakarta No. 123', '021-12345678', 1)
ON DUPLICATE KEY UPDATE warehouse_code = warehouse_code;

-- Sample Branch
INSERT INTO `branches` (`branch_name`, `address`, `phone`, `manager_name`, `is_active`) VALUES
('Cabang Bandung', 'Jl. Raya Bandung No. 456', '022-87654321', 'John Doe', 1)
ON DUPLICATE KEY UPDATE branch_name = branch_name;

-- ============================================
-- END OF SCHEMA
-- ============================================
