-- =====================================================
-- DATABASE SCHEMA: Inventory & Finance System
-- Untuk Toko Barang Jaringan (CCTV, Mikrotik, dll)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table: users (Multi-role user management)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100),
    `phone` VARCHAR(20),
    `role` ENUM('admin', 'manager', 'kasir', 'gudang') NOT NULL DEFAULT 'kasir',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: suppliers
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `contact_person` VARCHAR(100),
    `phone` VARCHAR(20),
    `email` VARCHAR(100),
    `address` TEXT,
    `city` VARCHAR(50),
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: customers
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `company` VARCHAR(100),
    `phone` VARCHAR(20),
    `email` VARCHAR(100),
    `address` TEXT,
    `city` VARCHAR(50),
    `credit_limit` DECIMAL(15,2) DEFAULT 0,
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: categories
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: products (Master produk)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `barcode` VARCHAR(50),
    `name` VARCHAR(150) NOT NULL,
    `category_id` INT,
    `brand` VARCHAR(50),
    `model` VARCHAR(50),
    `description` TEXT,
    `unit` VARCHAR(20) DEFAULT 'pcs',
    `min_stock` INT DEFAULT 0,
    `default_buy_price` DECIMAL(15,2) DEFAULT 0,
    `default_sell_price` DECIMAL(15,2) DEFAULT 0,
    `warranty_months` INT DEFAULT 0,
    `has_serial_number` TINYINT(1) DEFAULT 1,
    `image` VARCHAR(255),
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: stock_in (Stok Masuk - Header)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_in` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_id` INT,
    `date` DATE NOT NULL,
    `total_amount` DECIMAL(15,2) DEFAULT 0,
    `discount` DECIMAL(15,2) DEFAULT 0,
    `tax` DECIMAL(15,2) DEFAULT 0,
    `grand_total` DECIMAL(15,2) DEFAULT 0,
    `payment_status` ENUM('paid', 'partial', 'unpaid') DEFAULT 'unpaid',
    `paid_amount` DECIMAL(15,2) DEFAULT 0,
    `due_date` DATE,
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: stock_in_items (Detail item stok masuk)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_in_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stock_in_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `buy_price` DECIMAL(15,2) NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`stock_in_id`) REFERENCES `stock_in`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: product_serials (Serial Number per item)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_serials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stock_in_item_id` INT,
    `product_id` INT NOT NULL,
    `serial_number` VARCHAR(100) NOT NULL,
    `mac_address` VARCHAR(50),
    `ip_default` VARCHAR(50),
    `condition` ENUM('new', 'used', 'refurbished') DEFAULT 'new',
    `buy_price` DECIMAL(15,2) NOT NULL,
    `warranty_start` DATE,
    `warranty_end` DATE,
    `status` ENUM('available', 'sold', 'returned', 'damaged', 'reserved') DEFAULT 'available',
    `location` VARCHAR(50),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`stock_in_item_id`) REFERENCES `stock_in_items`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_serial` (`product_id`, `serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: sales (Penjualan - Header)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sales` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `customer_id` INT,
    `date` DATE NOT NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `discount` DECIMAL(15,2) DEFAULT 0,
    `tax` DECIMAL(15,2) DEFAULT 0,
    `grand_total` DECIMAL(15,2) DEFAULT 0,
    `profit` DECIMAL(15,2) DEFAULT 0,
    `payment_method` ENUM('cash', 'transfer', 'tempo', 'marketplace') DEFAULT 'cash',
    `marketplace_name` VARCHAR(50),
    `payment_status` ENUM('paid', 'partial', 'unpaid') DEFAULT 'paid',
    `paid_amount` DECIMAL(15,2) DEFAULT 0,
    `due_date` DATE,
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: sale_items (Detail item penjualan)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sale_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sale_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `serial_id` INT,
    `quantity` INT NOT NULL DEFAULT 1,
    `buy_price` DECIMAL(15,2) NOT NULL,
    `sell_price` DECIMAL(15,2) NOT NULL,
    `discount` DECIMAL(15,2) DEFAULT 0,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `profit` DECIMAL(15,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`serial_id`) REFERENCES `product_serials`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: returns (Return/RMA)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `returns` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `return_number` VARCHAR(50) NOT NULL UNIQUE,
    `type` ENUM('customer', 'supplier') NOT NULL,
    `reference_id` INT,
    `customer_id` INT,
    `supplier_id` INT,
    `date` DATE NOT NULL,
    `reason` TEXT,
    `total_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'pending',
    `action` ENUM('refund', 'replace', 'repair', 'credit') DEFAULT 'refund',
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: return_items (Detail item return)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `return_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `return_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `serial_id` INT,
    `quantity` INT NOT NULL DEFAULT 1,
    `price` DECIMAL(15,2) NOT NULL,
    `subtotal` DECIMAL(15,2) NOT NULL,
    `condition_notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`return_id`) REFERENCES `returns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`serial_id`) REFERENCES `product_serials`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: expenses (Pengeluaran Operasional)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `expense_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT,
    `date` DATE NOT NULL,
    `description` TEXT NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` ENUM('cash', 'transfer') DEFAULT 'cash',
    `reference` VARCHAR(100),
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: payments (Pembayaran hutang/piutang)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('receivable', 'payable') NOT NULL,
    `reference_type` ENUM('sale', 'stock_in') NOT NULL,
    `reference_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` ENUM('cash', 'transfer') DEFAULT 'cash',
    `notes` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: settings
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(50) NOT NULL UNIQUE,
    `value` TEXT,
    `description` VARCHAR(255),
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: activity_logs
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `module` VARCHAR(50),
    `reference_id` INT,
    `description` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Default Admin User (password: admin123)
INSERT INTO `users` (`username`, `password`, `name`, `email`, `role`) VALUES
('admin', '$2y$12$GFSVRzGpmLSI3ZRw6QMb5ukuDOg.rrpUrAJvC0Oz9bK2HwBLTZw8y', 'Administrator', 'admin@example.com', 'admin');

-- Default Categories
INSERT INTO `categories` (`name`, `description`) VALUES
('CCTV', 'Kamera CCTV dan DVR/NVR'),
('Mikrotik', 'Router dan Switch Mikrotik'),
('Router', 'Router dan Access Point'),
('Switch', 'Switch Managed dan Unmanaged'),
('Kabel', 'Kabel UTP, Fiber Optic, dll'),
('Aksesoris', 'Connector, Crimping Tool, dll'),
('Wireless', 'Access Point, Antenna, dll'),
('Server', 'Server dan Storage'),
('UPS', 'UPS dan Stabilizer'),
('Lainnya', 'Produk lainnya');

-- Default Expense Categories
INSERT INTO `expense_categories` (`name`, `description`) VALUES
('Listrik', 'Biaya listrik bulanan'),
('Internet', 'Biaya internet/bandwidth'),
('Sewa', 'Biaya sewa tempat'),
('Gaji', 'Gaji karyawan'),
('Transport', 'Biaya transport/pengiriman'),
('Perlengkapan', 'ATK dan perlengkapan kantor'),
('Maintenance', 'Biaya perawatan'),
('Lainnya', 'Pengeluaran lainnya');

-- Default Settings
INSERT INTO `settings` (`key`, `value`, `description`) VALUES
('company_name', 'Toko Jaringan', 'Nama perusahaan'),
('company_address', 'Jl. Contoh No. 123', 'Alamat perusahaan'),
('company_phone', '021-1234567', 'Telepon perusahaan'),
('company_email', 'info@tokojaringan.com', 'Email perusahaan'),
('tax_percentage', '11', 'Persentase pajak default'),
('invoice_prefix', 'INV', 'Prefix nomor invoice'),
('stock_in_prefix', 'SI', 'Prefix nomor stok masuk'),
('return_prefix', 'RET', 'Prefix nomor return'),
('currency', 'Rp', 'Simbol mata uang'),
('low_stock_alert', '5', 'Batas notifikasi stok minimum');

SET FOREIGN_KEY_CHECKS = 1;
