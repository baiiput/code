# Multi-Warehouse Migration Instructions

## ⚠️ PENTING: Backup Database Terlebih Dahulu!

Sebelum menjalankan migration, **WAJIB backup database Anda** terlebih dahulu!

```bash
mysqldump -u username -p warehouse_db > backup_before_multiwarehouse_$(date +%Y%m%d_%H%M%S).sql
```

## Langkah-Langkah Migration

### 1. **Backup Database** (WAJIB!)
   - Backup database Anda sebelum melakukan migration
   - Pastikan backup tersimpan di lokasi yang aman

### 2. **Execute Migration SQL**

#### Cara 1: Via phpMyAdmin
1. Login ke phpMyAdmin
2. Pilih database Anda
3. Klik tab "SQL"
4. Copy-paste isi file `migration_multi_warehouse.sql`
5. Klik "Go" / "Execute"

#### Cara 2: Via MySQL Command Line
```bash
mysql -u username -p warehouse_db < migration_multi_warehouse.sql
```

### 3. **Verifikasi Migration**

Jalankan query berikut untuk memastikan migration berhasil:

```sql
-- Cek tabel warehouses
SELECT * FROM warehouses;

-- Cek warehouse_items (harus sama jumlahnya dengan items)
SELECT COUNT(*) as total_items FROM items;
SELECT COUNT(*) as total_warehouse_items FROM warehouse_items;

-- Cek stock_in sudah ada warehouse_id
SELECT warehouse_id, COUNT(*) as total FROM stock_in GROUP BY warehouse_id;

-- Cek stock_out sudah ada warehouse_id
SELECT warehouse_id, COUNT(*) as total FROM stock_out GROUP BY warehouse_id;
```

### 4. **Deploy Kode Baru**

Setelah migration SQL berhasil, pull kode baru dari git:

```bash
git pull origin claude/improve-html-code-013baTYPayXXvM3iBR4zDL5D
```

### 5. **Test Sistem**

1. **Login sebagai Admin**
   - Cek menu "Data" → "Warehouses"
   - Pastikan ada "Warehouse Utama" sebagai default
   - Coba tambah warehouse baru

2. **Test Stock In**
   - Buat transaksi stock in baru
   - Pastikan ada dropdown untuk pilih warehouse

3. **Test Stock Out**
   - Buat transaksi stock out baru
   - Pastikan ada dropdown untuk pilih warehouse

4. **Test Transfer Antar Warehouse**
   - Akses menu "Stock Transfer"
   - Coba transfer barang dari warehouse A ke warehouse B

5. **Test Dashboard**
   - Pastikan ada filter warehouse di dashboard
   - Cek apakah statistik berubah sesuai warehouse yang dipilih

## Yang Berubah

### Database Changes:
1. ✅ Tabel baru: `warehouses`
2. ✅ Tabel baru: `warehouse_items` (stok per warehouse)
3. ✅ Tabel baru: `stock_transfers` (transfer antar warehouse)
4. ✅ Tabel baru: `stock_transfer_detail`
5. ✅ Kolom baru `users.warehouse_id` (untuk staff warehouse)
6. ✅ Kolom baru `stock_in.warehouse_id`
7. ✅ Kolom baru `stock_out.warehouse_id`
8. ✅ Kolom baru `stock_adjustment.warehouse_id`

### Feature Changes:
1. ✅ Master Warehouse (CRUD)
2. ✅ Stock per Warehouse
3. ✅ Stock In: Pilih warehouse tujuan
4. ✅ Stock Out: Pilih warehouse asal
5. ✅ Stock Adjustment: Per warehouse
6. ✅ Stock Transfer: Transfer antar warehouse
7. ✅ Dashboard: Filter per warehouse
8. ✅ Reports: Filter per warehouse
9. ✅ Role Access: Staff warehouse hanya akses warehouse mereka

### Catatan Penting:
- Saldo keuangan tetap **shared** untuk semua warehouse (tidak berubah)
- Data transaksi existing akan di-assign ke "Warehouse Utama" (WH-001)
- Kolom `items.current_stock` dan `items.average_cost` masih ada tapi tidak digunakan lagi (untuk backup)

## Troubleshooting

### Migration Error: "Table already exists"
- Ini normal jika Anda menjalankan migration lebih dari sekali
- Migration menggunakan `IF NOT EXISTS` sehingga aman di-run berkali-kali

### Data tidak muncul di warehouse_items
```sql
-- Cek apakah ada data di items
SELECT COUNT(*) FROM items;

-- Manual insert ke warehouse_items
SET @default_wh = (SELECT warehouse_id FROM warehouses WHERE warehouse_code = 'WH-001');
INSERT INTO warehouse_items (warehouse_id, item_id, current_stock, average_cost, min_stock)
SELECT @default_wh, item_id, current_stock, average_cost, min_stock
FROM items;
```

### Rollback Migration
Jika ada masalah dan ingin rollback:

```sql
-- HATI-HATI! Ini akan menghapus semua data warehouse
DROP TABLE IF EXISTS stock_transfer_detail;
DROP TABLE IF EXISTS stock_transfers;
DROP TABLE IF EXISTS warehouse_items;
DROP TABLE IF EXISTS warehouses;
ALTER TABLE users DROP COLUMN warehouse_id;
ALTER TABLE stock_in DROP COLUMN warehouse_id;
ALTER TABLE stock_out DROP COLUMN warehouse_id;
ALTER TABLE stock_adjustment DROP COLUMN warehouse_id;
```

## Support

Jika ada masalah, hubungi developer atau cek log error di:
- PHP Error Log: `/var/log/apache2/error.log` atau `/var/log/nginx/error.log`
- MySQL Error Log: `/var/log/mysql/error.log`
