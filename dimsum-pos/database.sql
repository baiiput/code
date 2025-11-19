-- =====================================================
-- DATABASE SCHEMA - DIMSUM UMAMI POS SYSTEM
-- =====================================================

CREATE DATABASE IF NOT EXISTS dimsum_pos;
USE dimsum_pos;

-- -----------------------------------------------------
-- Table: cabang (Branches)
-- -----------------------------------------------------
CREATE TABLE cabang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    alamat TEXT,
    telepon VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: users
-- -----------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'owner', 'admin_cabang', 'kasir') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: kategori
-- -----------------------------------------------------
CREATE TABLE kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    urutan INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: menu
-- -----------------------------------------------------
CREATE TABLE menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    gambar VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: menu_variasi (Variations like isi 3, isi 5, etc)
-- -----------------------------------------------------
CREATE TABLE menu_variasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    nama_variasi VARCHAR(50) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: diskon
-- -----------------------------------------------------
CREATE TABLE diskon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tipe ENUM('persen', 'nominal', 'beli_x_gratis_y', 'member', 'happy_hour') NOT NULL,
    nilai DECIMAL(12,2) NOT NULL,
    min_pembelian DECIMAL(12,2) DEFAULT 0,
    beli_qty INT DEFAULT 0,
    gratis_qty INT DEFAULT 0,
    jam_mulai TIME NULL,
    jam_selesai TIME NULL,
    tanggal_mulai DATE NULL,
    tanggal_selesai DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: pengaturan_pajak
-- -----------------------------------------------------
CREATE TABLE pengaturan_pajak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL,
    persentase DECIMAL(5,2) NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: metode_pembayaran
-- -----------------------------------------------------
CREATE TABLE metode_pembayaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL,
    keterangan TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: transaksi
-- -----------------------------------------------------
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT NOT NULL,
    user_id INT NOT NULL,
    no_transaksi VARCHAR(50) NOT NULL UNIQUE,
    tipe_order ENUM('dine_in', 'take_away') NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    diskon_id INT NULL,
    diskon_nominal DECIMAL(12,2) DEFAULT 0,
    pajak_persen DECIMAL(5,2) DEFAULT 0,
    pajak_nominal DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    metode_pembayaran_id INT NOT NULL,
    uang_diterima DECIMAL(12,2) DEFAULT 0,
    kembalian DECIMAL(12,2) DEFAULT 0,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (diskon_id) REFERENCES diskon(id) ON DELETE SET NULL,
    FOREIGN KEY (metode_pembayaran_id) REFERENCES metode_pembayaran(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: transaksi_detail
-- -----------------------------------------------------
CREATE TABLE transaksi_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    menu_variasi_id INT NOT NULL,
    nama_menu VARCHAR(100) NOT NULL,
    nama_variasi VARCHAR(50) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    qty INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_variasi_id) REFERENCES menu_variasi(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: member (untuk diskon member)
-- -----------------------------------------------------
CREATE TABLE member (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telepon VARCHAR(20) UNIQUE,
    email VARCHAR(100),
    poin INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table: pengaturan
-- -----------------------------------------------------
CREATE TABLE pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_key VARCHAR(50) NOT NULL UNIQUE,
    nilai TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Default cabang
INSERT INTO cabang (nama, alamat, telepon) VALUES
('Pusat', 'Jl. Contoh No. 1', '021-1234567');

-- Default super admin (password: admin123)
INSERT INTO users (cabang_id, username, password, nama_lengkap, role) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'super_admin');

-- Default metode pembayaran
INSERT INTO metode_pembayaran (nama, keterangan) VALUES
('Tunai', 'Pembayaran tunai'),
('QRIS', 'Pembayaran via QRIS'),
('Transfer BCA', 'Transfer ke rekening BCA');

-- Default pajak (non-active)
INSERT INTO pengaturan_pajak (nama, persentase, is_active) VALUES
('PPN', 11.00, 0);

-- Default pengaturan
INSERT INTO pengaturan (nama_key, nilai) VALUES
('nama_toko', 'Dimsum Umami'),
('alamat_toko', 'Jl. Contoh No. 1'),
('telepon_toko', '021-1234567'),
('footer_struk', 'Terima kasih atas kunjungan Anda!');

-- Default kategori
INSERT INTO kategori (nama, deskripsi, urutan) VALUES
('Dimsum Kukus', 'Dimsum yang dikukus', 1),
('Dimsum Goreng', 'Dimsum yang digoreng', 2),
('Dimsum Panggang', 'Dimsum yang dipanggang', 3),
('Minuman', 'Berbagai minuman', 4);
