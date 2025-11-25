# Database Migration

## Error: Column 'created_by' not found

Jika Anda mendapatkan error:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'created_by' in 'field list'
```

Ini berarti database Anda perlu di-update dengan kolom baru untuk tracking user yang membuat transaksi.

## Cara Menjalankan Migration

### Opsi 1: Via phpMyAdmin
1. Login ke phpMyAdmin
2. Pilih database `dimsum_financial`
3. Klik tab "SQL"
4. Copy semua isi file `migration_add_created_by.sql`
5. Paste ke SQL editor
6. Klik "Go" atau "Execute"

### Opsi 2: Via MySQL Command Line
```bash
mysql -u username -p dimsum_financial < migration_add_created_by.sql
```

Ganti `username` dengan username MySQL Anda.

### Opsi 3: Via cPanel / Hosting Control Panel
1. Login ke cPanel
2. Buka MySQL Databases atau phpMyAdmin
3. Pilih database dimsum_financial
4. Import file `migration_add_created_by.sql`

## Verifikasi

Setelah menjalankan migration, coba refresh halaman dan tambah transaksi lagi.
Error seharusnya sudah hilang.

## Untuk Instalasi Baru

Jika Anda melakukan fresh install, gunakan file `database.sql` yang sudah include kolom `created_by`.
