# Warehouse Management System - Dokumentasi Lengkap

## 📋 Daftar Isi
1. [Setup Warehouse Baru](#setup-warehouse-baru)
2. [Reset Data](#reset-data)
3. [Struktur Database](#struktur-database)
4. [Role & Permission](#role--permission)
5. [Fitur-Fitur](#fitur-fitur)
6. [Troubleshooting](#troubleshooting)

---

## 🚀 Setup Warehouse Baru

### Prerequisites
- PHP 8.0 atau lebih tinggi
- MySQL 8.0 atau lebih tinggi
- Web Server (Apache/Nginx)
- Extension PHP: mysqli, pdo_mysql

### Langkah 1: Persiapan Database

1. **Buat Database Baru**
```sql
CREATE DATABASE warehouse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Buat User Database**
```sql
CREATE USER 'warehouse'@'localhost' IDENTIFIED BY 'password_anda';
GRANT ALL PRIVILEGES ON warehouse.* TO 'warehouse'@'localhost';
FLUSH PRIVILEGES;
```

### Langkah 2: Import Database Schema

```bash
# Import schema lengkap
mysql -u warehouse -p warehouse < database_schema_complete.sql
```

### Langkah 3: Konfigurasi Aplikasi

Edit file `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'warehouse');
define('DB_PASS', 'password_anda');
define('DB_NAME', 'warehouse');
```

### Langkah 4: Login Pertama Kali

**Default Admin Account:**
- Username: `admin`
- Password: `admin123`

⚠️ **PENTING**: Segera ganti password admin setelah login pertama!

### Langkah 5: Setup Data Master

1. **Buat Warehouse**
   - Menu: Data → Warehouses
   - Tambahkan warehouse utama Anda

2. **Buat Kategori Barang**
   - Menu: Data → Kategori
   - Tambahkan kategori sesuai kebutuhan

3. **Buat Supplier**
   - Menu: Data → Supplier
   - Tambahkan data supplier

4. **Buat Cabang**
   - Menu: Data → Cabang
   - Tambahkan cabang-cabang Anda

5. **Tambah Barang**
   - Menu: Data → Data Barang
   - Input barang-barang yang akan dijual

6. **Buat User**
   - Menu: Users
   - Buat user sesuai role yang dibutuhkan

---

## 🔄 Reset Data

### Reset Semua Data Transaksi (Tetap Simpan Master Data)

```sql
-- Jalankan query ini untuk reset transaksi
-- Master data (barang, kategori, supplier, cabang, warehouse) tetap ada

-- 1. Reset Activity Logs
TRUNCATE TABLE activity_logs;

-- 2. Reset Financial Transactions
TRUNCATE TABLE financial_transactions;

-- 3. Reset Stock Transfers
TRUNCATE TABLE stock_transfer_detail;
TRUNCATE TABLE stock_transfers;

-- 4. Reset Stock Adjustments
TRUNCATE TABLE stock_adjustment;

-- 5. Reset Stock Out
TRUNCATE TABLE stock_out_detail;
TRUNCATE TABLE stock_out;

-- 6. Reset Stock In
TRUNCATE TABLE stock_in_detail;
TRUNCATE TABLE stock_in;

-- 7. Reset Warehouse Items (Stok semua warehouse)
TRUNCATE TABLE warehouse_items;

-- 8. Reset Balance
UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1;

-- Done! Semua transaksi di-reset, master data tetap ada.
```

### Reset Semua Data (Termasuk Master Data)

⚠️ **PERINGATAN**: Ini akan menghapus SEMUA data termasuk master data!

```sql
-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Truncate all tables
TRUNCATE TABLE activity_logs;
TRUNCATE TABLE financial_transactions;
TRUNCATE TABLE stock_transfer_detail;
TRUNCATE TABLE stock_transfers;
TRUNCATE TABLE stock_adjustment;
TRUNCATE TABLE stock_out_detail;
TRUNCATE TABLE stock_out;
TRUNCATE TABLE stock_in_detail;
TRUNCATE TABLE stock_in;
TRUNCATE TABLE warehouse_items;
TRUNCATE TABLE items;
TRUNCATE TABLE categories;
TRUNCATE TABLE suppliers;
TRUNCATE TABLE branches;
TRUNCATE TABLE warehouses;
TRUNCATE TABLE users;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Reset balance
UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1;

-- Re-insert default admin
INSERT INTO users (username, password, full_name, role, is_active)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1);
```

### Reset Stok Warehouse Tertentu

```sql
-- Ganti 1 dengan warehouse_id yang ingin di-reset
DELETE FROM warehouse_items WHERE warehouse_id = 1;
```

### Reset Data untuk Testing/Development

```bash
# Backup dulu database Anda!
mysqldump -u warehouse -p warehouse > backup_$(date +%Y%m%d_%H%M%S).sql

# Import ulang schema fresh
mysql -u warehouse -p warehouse < database_schema_complete.sql
```

---

## 📊 Struktur Database

### Tabel Master Data
| Tabel | Fungsi |
|-------|--------|
| `categories` | Kategori barang |
| `items` | Master data barang |
| `suppliers` | Data supplier |
| `branches` | Data cabang |
| `warehouses` | Data warehouse (multi-warehouse) |

### Tabel Inventory
| Tabel | Fungsi |
|-------|--------|
| `warehouse_items` | Stok barang per warehouse (pivot table) |

### Tabel Transaksi
| Tabel | Fungsi |
|-------|--------|
| `stock_in` + `stock_in_detail` | Pembelian dari supplier |
| `stock_out` + `stock_out_detail` | Distribusi ke cabang |
| `stock_adjustment` | Koreksi stok (opname) |
| `stock_transfers` + `stock_transfer_detail` | Transfer antar warehouse |

### Tabel Keuangan
| Tabel | Fungsi |
|-------|--------|
| `warehouse_balance` | Saldo warehouse |
| `financial_transactions` | Log transaksi keuangan |

### Tabel User & Logs
| Tabel | Fungsi |
|-------|--------|
| `users` | Data user & akses |
| `activity_logs` | Log aktivitas user |

---

## 👥 Role & Permission

### 1. Admin
**Akses Penuh:**
- ✅ Semua menu dan fitur
- ✅ User Management
- ✅ Lihat semua Activity Logs (termasuk log admin lain)
- ✅ CRUD semua data

### 2. Manager (NEW!)
**Akses:**
- ✅ Dashboard
- ✅ Data Master (Barang, Kategori, Supplier, Cabang, Warehouse)
- ✅ Transaksi (Stock In, Stock Out, Opname, Transfer)
- ✅ Keuangan
- ✅ Laporan
- ✅ Activity Logs (hanya user non-admin)

**Tidak Bisa Akses:**
- ❌ User Management
- ❌ Log aktivitas Admin

### 3. Staff Warehouse
**Akses:**
- ✅ Dashboard
- ✅ Data Master (Barang, Kategori, Supplier, Cabang)
- ✅ Transaksi (Stock In, Stock Out, Opname, Transfer)
- ✅ Laporan
- ✅ Activity Logs (hanya milik sendiri)

**Assignment:**
- Bisa di-assign ke warehouse tertentu
- Hanya lihat/kelola stok warehouse yang di-assign

### 4. Staff Keuangan
**Akses:**
- ✅ Dashboard
- ✅ Keuangan
- ✅ Laporan
- ✅ Activity Logs (hanya milik sendiri)

### 5. Cabang
**Akses:**
- ✅ Dashboard
- ✅ Distribusi Saya (stock out ke cabang)
- ✅ Activity Logs (hanya milik sendiri)

**Assignment:**
- Di-assign ke cabang tertentu

---

## 🎯 Fitur-Fitur

### Multi-Warehouse System
- ✅ Kelola multiple warehouse
- ✅ Stok terpisah per warehouse
- ✅ Transfer antar warehouse
- ✅ Weighted average cost calculation
- ✅ Per-warehouse reporting

### Stock Management
- ✅ Stock In (Pembelian)
- ✅ Stock Out (Distribusi)
- ✅ Stock Adjustment (Opname)
- ✅ Stock Transfer (Antar Warehouse)
- ✅ Real-time stock tracking
- ✅ Low stock alerts

### Financial Management
- ✅ Warehouse balance tracking
- ✅ Transaction logging
- ✅ Debit/Credit recording
- ✅ Balance history

### Reporting
- ✅ Stock Report (per warehouse & grouped)
- ✅ Distribution Report (warehouse to branch)
- ✅ Low Stock Report
- ✅ Financial Reports
- ✅ Filterable by date, warehouse, branch

### Activity Logging
- ✅ Semua aktivitas tercatat
- ✅ IP Address tracking
- ✅ User Agent tracking
- ✅ Role-based log viewing
- ✅ Filter by user, action, module, date

### UI/UX
- ✅ Modern responsive design
- ✅ Dark/Light theme
- ✅ Mobile-friendly
- ✅ Expandable table rows
- ✅ AJAX-based dynamic loading

---

## 🔧 Troubleshooting

### Error: "Data truncated for column 'role'"

**Penyebab:** Database belum di-update untuk role 'manager'

**Solusi:**
```bash
mysql -u warehouse -p warehouse < migration_add_manager_role.sql
```

### Error: "Table 'activity_logs' doesn't exist"

**Penyebab:** Tabel activity_logs belum dibuat

**Solusi:**
```bash
mysql -u warehouse -p warehouse < migration_activity_logs.sql
```

### Items tidak muncul saat pilih warehouse

**Penyebab:** Warehouse Items belum ada atau stok kosong

**Solusi:**
1. Pastikan sudah stock in ke warehouse tersebut
2. Check tabel warehouse_items apakah ada data untuk warehouse_id tersebut

### Dashboard menampilkan nilai stok salah

**Penyebab:** Query masih menggunakan tabel items lama

**Solusi:** Sudah diperbaiki di update terbaru, pull latest code

### User tidak bisa login setelah ubah role

**Penyebab:** Session masih menyimpan role lama

**Solusi:** Logout dan login ulang

---

## 📝 Changelog

### Version 2.0 (Latest)
- ✅ Manager Role
- ✅ Activity Logs System
- ✅ Improved authorization
- ✅ Bug fixes: user edit, dashboard calculation

### Version 1.5
- ✅ Multi-Warehouse System
- ✅ Warehouse Assignment for Staff
- ✅ Grouped Stock Report
- ✅ Distribution Tracking

### Version 1.0
- ✅ Basic Stock Management
- ✅ Financial Management
- ✅ User Management
- ✅ Reporting

---

## 📞 Support

Untuk pertanyaan atau bantuan, silakan:
1. Check dokumentasi ini terlebih dahulu
2. Lihat error log di `/logs` (jika ada)
3. Check browser console untuk JavaScript errors
4. Backup database sebelum melakukan perubahan besar

---

## ⚠️ Catatan Penting

1. **Backup Regular**: Selalu backup database secara berkala
2. **Password Security**: Ganti password default admin
3. **Production Environment**:
   - Nonaktifkan error display
   - Gunakan HTTPS
   - Secure database credentials
4. **Testing**: Test di environment development dulu sebelum production

---

**Last Updated:** 2024
**System Version:** 2.0
**Database Schema Version:** 2.0
