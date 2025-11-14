-- Database Schema untuk Sistem Laporan Keuangan Bisnis Teknologi
-- Created: 2025-11-14

CREATE DATABASE IF NOT EXISTS financial_report CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE financial_report;

-- Tabel Users untuk autentikasi
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Kategori Produk
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Produk (Alat Jaringan, Mikrotik, Starlink, dll)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    unit VARCHAR(20) DEFAULT 'pcs',
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_sku (sku),
    INDEX idx_name (name),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Customers
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    tax_id VARCHAR(50),
    customer_type ENUM('individual', 'company') DEFAULT 'individual',
    credit_limit DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Suppliers
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    tax_id VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Sales (Penjualan)
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    user_id INT NOT NULL,
    sale_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_percentage DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    shipping_cost DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    payment_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_invoice (invoice_number),
    INDEX idx_sale_date (sale_date),
    INDEX idx_customer (customer_id),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Detail Penjualan
CREATE TABLE IF NOT EXISTS sales_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_sale (sale_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Purchases (Pembelian)
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_number VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    user_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_percentage DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    shipping_cost DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    payment_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_purchase_number (purchase_number),
    INDEX idx_purchase_date (purchase_date),
    INDEX idx_supplier (supplier_id),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Detail Pembelian
CREATE TABLE IF NOT EXISTS purchases_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_purchase (purchase_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Expenses (Pengeluaran/Biaya Operasional)
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method VARCHAR(50),
    recipient VARCHAR(200),
    notes TEXT,
    attachment_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_expense_number (expense_number),
    INDEX idx_expense_date (expense_date),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Payments (Pembayaran dari Customer)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(50) UNIQUE NOT NULL,
    sale_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_payment_number (payment_number),
    INDEX idx_sale (sale_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Supplier Payments (Pembayaran ke Supplier)
CREATE TABLE IF NOT EXISTS supplier_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(50) UNIQUE NOT NULL,
    purchase_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    reference_number VARCHAR(100),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_payment_number (payment_number),
    INDEX idx_purchase (purchase_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Cash Flow untuk tracking arus kas
CREATE TABLE IF NOT EXISTS cash_flow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    type ENUM('inflow', 'outflow') NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (transaction_date),
    INDEX idx_type (type),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert data kategori produk default
INSERT INTO categories (name, description) VALUES
('Router & Access Point', 'Router, Access Point, dan perangkat wireless'),
('Switch & Hub', 'Network Switch, Hub, dan perangkat switching'),
('Mikrotik', 'Produk Mikrotik RouterBoard dan perangkat'),
('Starlink', 'Starlink Internet Satellite dan accessories'),
('Kabel & Connector', 'Kabel jaringan, connector, dan accessories'),
('Security & Surveillance', 'CCTV, NVR, dan perangkat keamanan'),
('Server & Storage', 'Server, NAS, dan perangkat penyimpanan'),
('Jasa Instalasi', 'Layanan instalasi dan konfigurasi'),
('Jasa Maintenance', 'Layanan maintenance dan support'),
('Lainnya', 'Produk dan jasa lainnya');

-- Insert user default (password: admin123 - hashed dengan bcrypt)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Views untuk Laporan

-- View untuk Laporan Penjualan
CREATE OR REPLACE VIEW v_sales_report AS
SELECT
    s.id,
    s.invoice_number,
    s.sale_date,
    c.name AS customer_name,
    c.code AS customer_code,
    u.full_name AS sales_person,
    s.subtotal,
    s.tax_amount,
    s.discount_amount,
    s.shipping_cost,
    s.total_amount,
    s.paid_amount,
    (s.total_amount - s.paid_amount) AS remaining_amount,
    s.payment_status,
    s.payment_method,
    YEAR(s.sale_date) AS year,
    MONTH(s.sale_date) AS month,
    QUARTER(s.sale_date) AS quarter
FROM sales s
JOIN customers c ON s.customer_id = c.id
JOIN users u ON s.user_id = u.id;

-- View untuk Laporan Pembelian
CREATE OR REPLACE VIEW v_purchases_report AS
SELECT
    p.id,
    p.purchase_number,
    p.purchase_date,
    sp.name AS supplier_name,
    sp.code AS supplier_code,
    u.full_name AS buyer,
    p.subtotal,
    p.tax_amount,
    p.discount_amount,
    p.shipping_cost,
    p.total_amount,
    p.paid_amount,
    (p.total_amount - p.paid_amount) AS remaining_amount,
    p.payment_status,
    p.payment_method,
    YEAR(p.purchase_date) AS year,
    MONTH(p.purchase_date) AS month,
    QUARTER(p.purchase_date) AS quarter
FROM purchases p
JOIN suppliers sp ON p.supplier_id = sp.id
JOIN users u ON p.user_id = u.id;

-- View untuk Laporan Produk Terlaris
CREATE OR REPLACE VIEW v_top_selling_products AS
SELECT
    p.id,
    p.sku,
    p.name,
    c.name AS category_name,
    SUM(sd.quantity) AS total_sold,
    SUM(sd.subtotal) AS total_revenue,
    COUNT(DISTINCT sd.sale_id) AS transaction_count,
    AVG(sd.unit_price) AS avg_selling_price
FROM products p
JOIN categories c ON p.category_id = c.id
JOIN sales_details sd ON p.id = sd.product_id
JOIN sales s ON sd.sale_id = s.id
GROUP BY p.id, p.sku, p.name, c.name
ORDER BY total_sold DESC;

-- View untuk Stock Alert (produk di bawah minimum stock)
CREATE OR REPLACE VIEW v_stock_alert AS
SELECT
    p.id,
    p.sku,
    p.name,
    c.name AS category_name,
    p.stock AS current_stock,
    p.min_stock AS minimum_stock,
    (p.min_stock - p.stock) AS stock_needed,
    p.purchase_price,
    ((p.min_stock - p.stock) * p.purchase_price) AS estimated_cost
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE p.stock <= p.min_stock AND p.is_active = TRUE
ORDER BY stock_needed DESC;

-- View untuk Profit Analysis per Produk
CREATE OR REPLACE VIEW v_product_profit AS
SELECT
    p.id,
    p.sku,
    p.name,
    c.name AS category_name,
    p.purchase_price,
    p.selling_price,
    (p.selling_price - p.purchase_price) AS profit_per_unit,
    ROUND(((p.selling_price - p.purchase_price) / p.purchase_price * 100), 2) AS profit_margin_percentage,
    p.stock,
    (p.stock * p.purchase_price) AS stock_value_cost,
    (p.stock * p.selling_price) AS stock_value_selling
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE p.is_active = TRUE;
