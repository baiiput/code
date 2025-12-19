-- Migration: Add Kategori to Products & Auto Generate Kode Barang
-- File: database_migration_kategori_product.sql

USE koperasi_syariah;

-- Add kategori column to products table
ALTER TABLE products
ADD COLUMN kategori ENUM('Elektronik', 'Furniture', 'Kendaraan', 'Fashion', 'Peralatan Rumah Tangga', 'Gadget', 'Lainnya') DEFAULT 'Lainnya' AFTER nama_barang,
ADD INDEX idx_kategori (kategori);

-- Migration completed
SELECT 'Kategori Product migration completed successfully!' as message;
