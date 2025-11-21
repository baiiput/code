-- ========================================
-- MIGRATION: Multi-Warehouse System
-- ========================================
-- This migration adds multi-warehouse support to the existing system
-- Execute this SQL carefully, preferably on a backup first!

-- 1. Create warehouses table
CREATE TABLE IF NOT EXISTS warehouses (
    warehouse_id INT PRIMARY KEY AUTO_INCREMENT,
    warehouse_code VARCHAR(20) UNIQUE NOT NULL,
    warehouse_name VARCHAR(100) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_warehouse_code (warehouse_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create warehouse_items table (stores stock per warehouse)
CREATE TABLE IF NOT EXISTS warehouse_items (
    warehouse_item_id INT PRIMARY KEY AUTO_INCREMENT,
    warehouse_id INT NOT NULL,
    item_id INT NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    average_cost DECIMAL(15,2) DEFAULT 0,
    min_stock DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(warehouse_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    UNIQUE KEY unique_warehouse_item (warehouse_id, item_id),
    INDEX idx_warehouse (warehouse_id),
    INDEX idx_item (item_id),
    INDEX idx_low_stock (warehouse_id, current_stock, min_stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create stock_transfers table (for transfers between warehouses)
CREATE TABLE IF NOT EXISTS stock_transfers (
    transfer_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_code VARCHAR(50) UNIQUE NOT NULL,
    transaction_date DATETIME NOT NULL,
    from_warehouse_id INT NOT NULL,
    to_warehouse_id INT NOT NULL,
    total_amount DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(warehouse_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_transaction_code (transaction_code),
    INDEX idx_from_warehouse (from_warehouse_id),
    INDEX idx_to_warehouse (to_warehouse_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create stock_transfer_detail table
CREATE TABLE IF NOT EXISTS stock_transfer_detail (
    transfer_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    transfer_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (transfer_id) REFERENCES stock_transfers(transfer_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id),
    INDEX idx_transfer (transfer_id),
    INDEX idx_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Add warehouse_id to users table (for staff_warehouse role)
ALTER TABLE users
ADD COLUMN IF NOT EXISTS warehouse_id INT DEFAULT NULL AFTER cabang_id,
ADD INDEX idx_warehouse_id (warehouse_id);

-- 6. Add warehouse_id to stock_in table
ALTER TABLE stock_in
ADD COLUMN IF NOT EXISTS warehouse_id INT DEFAULT NULL AFTER supplier_id,
ADD INDEX idx_warehouse_id (warehouse_id);

-- 7. Add warehouse_id to stock_out table
ALTER TABLE stock_out
ADD COLUMN IF NOT EXISTS warehouse_id INT DEFAULT NULL AFTER branch_id,
ADD INDEX idx_warehouse_id (warehouse_id);

-- 8. Add warehouse_id to stock_adjustment table
ALTER TABLE stock_adjustment
ADD COLUMN IF NOT EXISTS warehouse_id INT DEFAULT NULL AFTER item_id,
ADD INDEX idx_warehouse_id (warehouse_id);

-- 9. Insert default warehouse (migrate existing data to this warehouse)
INSERT INTO warehouses (warehouse_code, warehouse_name, address, is_active)
VALUES ('WH-001', 'Warehouse Utama', 'Alamat Warehouse Utama', 1)
ON DUPLICATE KEY UPDATE warehouse_id = warehouse_id;

-- 10. Get the default warehouse_id
SET @default_warehouse_id = (SELECT warehouse_id FROM warehouses WHERE warehouse_code = 'WH-001' LIMIT 1);

-- 11. Migrate existing items stock to warehouse_items for default warehouse
INSERT INTO warehouse_items (warehouse_id, item_id, current_stock, average_cost, min_stock)
SELECT @default_warehouse_id, item_id, current_stock, average_cost, min_stock
FROM items
WHERE item_id NOT IN (SELECT item_id FROM warehouse_items WHERE warehouse_id = @default_warehouse_id);

-- 12. Update existing stock_in transactions to use default warehouse
UPDATE stock_in SET warehouse_id = @default_warehouse_id WHERE warehouse_id IS NULL;

-- 13. Update existing stock_out transactions to use default warehouse
UPDATE stock_out SET warehouse_id = @default_warehouse_id WHERE warehouse_id IS NULL;

-- 14. Update existing stock_adjustment to use default warehouse
UPDATE stock_adjustment SET warehouse_id = @default_warehouse_id WHERE warehouse_id IS NULL;

-- 15. Backup: Keep items table columns for now (can be removed later after verification)
-- ALTER TABLE items DROP COLUMN current_stock;
-- ALTER TABLE items DROP COLUMN average_cost;

-- ========================================
-- VERIFICATION QUERIES (run these to check)
-- ========================================
-- SELECT * FROM warehouses;
-- SELECT COUNT(*) FROM warehouse_items;
-- SELECT COUNT(*) FROM items;
-- SELECT warehouse_id, COUNT(*) FROM stock_in GROUP BY warehouse_id;
-- SELECT warehouse_id, COUNT(*) FROM stock_out GROUP BY warehouse_id;

-- ========================================
-- ROLLBACK (if needed - be careful!)
-- ========================================
-- DROP TABLE IF EXISTS stock_transfer_detail;
-- DROP TABLE IF EXISTS stock_transfers;
-- DROP TABLE IF EXISTS warehouse_items;
-- DROP TABLE IF EXISTS warehouses;
-- ALTER TABLE users DROP COLUMN warehouse_id;
-- ALTER TABLE stock_in DROP COLUMN warehouse_id;
-- ALTER TABLE stock_out DROP COLUMN warehouse_id;
-- ALTER TABLE stock_adjustment DROP COLUMN warehouse_id;
