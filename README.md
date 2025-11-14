# Sistem Laporan Keuangan - Bisnis Teknologi

Sistem manajemen keuangan lengkap untuk bisnis yang bergerak di bidang penjualan barang dan jasa teknologi (Mikrotik, Starlink, perangkat jaringan, dll).

## ✨ Fitur Utama

- 📊 **Dashboard Interaktif** - Ringkasan real-time penjualan, pembelian, dan profit
- 💰 **Manajemen Penjualan** - Kelola invoice, pembayaran, dan piutang
- 🛒 **Manajemen Pembelian** - Tracking pembelian dari supplier
- 📦 **Manajemen Produk** - Kelola inventory dengan alert stok minimum
- 👥 **Customer & Supplier** - Database pelanggan dan pemasok
- 💸 **Pengeluaran** - Tracking biaya operasional
- 📈 **Laporan Lengkap** - Laporan laba rugi, arus kas, dan analisis
- 🌓 **Dark Mode** - Tema gelap untuk kenyamanan mata
- 📱 **Responsive Design** - Tampilan optimal di desktop, tablet, dan mobile
- 🔐 **Authentication** - Sistem login dengan role management

## 🛠️ Teknologi

- **Backend**: PHP 8.2+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Charts**: Chart.js
- **Architecture**: MVC Pattern dengan RESTful API

## 📋 Persyaratan Sistem

- PHP 8.2 atau lebih tinggi
- MySQL 5.7+ atau MariaDB 10.3+
- Apache/Nginx dengan mod_rewrite
- Extension PHP yang diperlukan:
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - session

## 🚀 Instalasi

### 1. Upload Files

Upload semua file ke hosting Anda menggunakan FTP/SFTP atau cPanel File Manager.

```bash
# Jika menggunakan git
git clone <repository-url>
cd financial-report
```

### 2. Konfigurasi Database

**A. Buat Database**

Buat database baru melalui cPanel atau phpMyAdmin:

```sql
CREATE DATABASE financial_report CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**B. Import Database Schema**

Import file `database.sql` ke database yang baru dibuat:

```bash
# Via command line
mysql -u username -p financial_report < database.sql

# Atau gunakan phpMyAdmin:
# 1. Pilih database 'financial_report'
# 2. Klik tab 'Import'
# 3. Pilih file 'database.sql'
# 4. Klik 'Go'
```

**C. Konfigurasi Koneksi Database**

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');        // Host database Anda
define('DB_NAME', 'financial_report'); // Nama database
define('DB_USER', 'your_username');    // Username MySQL
define('DB_PASS', 'your_password');    // Password MySQL
```

### 3. Set Permissions

Set permission folder untuk upload files:

```bash
chmod 755 uploads/
chmod 755 config/
```

### 4. Konfigurasi Apache

Pastikan `.htaccess` sudah ada dan `mod_rewrite` aktif.

Jika menggunakan Nginx, tambahkan konfigurasi berikut:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### 5. Akses Aplikasi

Buka browser dan akses domain Anda:

```
http://yourdomain.com
```

**Login Default:**
- Username: `admin`
- Password: `admin123`

⚠️ **PENTING**: Segera ganti password default setelah login pertama!

## 🔐 Keamanan

### Langkah-langkah Keamanan yang Disarankan:

1. **Ganti Password Default**
   ```sql
   -- Akses phpMyAdmin dan jalankan:
   UPDATE users SET password = '$2y$10$YourNewHashedPassword' WHERE username = 'admin';
   ```

2. **Update Konfigurasi untuk Production**

   Edit `config/config.php`:
   ```php
   // Disable error display in production
   error_reporting(0);
   ini_set('display_errors', 0);

   // Enable HTTPS
   ini_set('session.cookie_secure', 1);
   ```

3. **Backup Database Secara Berkala**
   ```bash
   # Setup cron job untuk backup otomatis
   0 2 * * * mysqldump -u username -p'password' financial_report > /backup/db_$(date +\%Y\%m\%d).sql
   ```

4. **Proteksi File Sensitif**

   Pastikan folder `config/`, `uploads/`, dan file `.sql` tidak dapat diakses langsung dari browser.

## 📱 Penggunaan

### Dashboard

Dashboard menampilkan ringkasan:
- Total penjualan, pembelian, dan pengeluaran
- Profit bersih dan margin
- Grafik tren penjualan
- Produk terlaris
- Stok yang menipis
- Piutang tertunda

### Transaksi Penjualan

1. Pilih menu **Penjualan** → **Tambah Baru**
2. Pilih customer dan tambahkan produk
3. Sistem otomatis menghitung total, pajak, dan diskon
4. Simpan invoice
5. Kelola pembayaran (lunas, cicilan, atau belum bayar)

### Manajemen Produk

1. Menu **Produk** → **Tambah Produk**
2. Isi informasi: SKU, nama, kategori, harga beli/jual, stok
3. Set minimum stok untuk alert otomatis
4. Sistem akan memberi notifikasi jika stok menipis

### Laporan

Akses berbagai laporan:
- **Laporan Penjualan**: Filter berdasarkan periode, customer, produk
- **Laporan Pembelian**: Analisis pembelian dari supplier
- **Laba Rugi**: Perhitungan profit dan margin
- **Arus Kas**: Tracking cash flow masuk dan keluar

## 🎨 Kustomisasi

### Mengubah Logo & Branding

Edit `config/config.php`:
```php
define('APP_NAME', 'Nama Perusahaan Anda');
```

### Mengubah Tema Warna

Edit `assets/css/style.css`:
```css
:root {
    --primary-color: #your-color;
    --primary-dark: #your-dark-color;
}
```

### Menambah Kategori Produk

Insert ke database:
```sql
INSERT INTO categories (name, description) VALUES
('Kategori Baru', 'Deskripsi kategori');
```

## 📊 Struktur Database

**Tabel Utama:**
- `users` - User dan authentication
- `products` - Master produk
- `categories` - Kategori produk
- `customers` - Data pelanggan
- `suppliers` - Data supplier
- `sales` - Transaksi penjualan
- `sales_details` - Detail item penjualan
- `purchases` - Transaksi pembelian
- `purchases_details` - Detail item pembelian
- `expenses` - Pengeluaran operasional
- `payments` - Pembayaran dari customer
- `supplier_payments` - Pembayaran ke supplier
- `cash_flow` - Arus kas

## 🔧 Troubleshooting

### Error "Connection Failed"

- Periksa kredensial database di `config/database.php`
- Pastikan MySQL service berjalan
- Cek apakah database sudah dibuat

### Halaman Putih / Error 500

- Enable error display sementara:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Periksa PHP error log
- Pastikan semua file ter-upload dengan benar

### .htaccess Not Working

- Pastikan `mod_rewrite` aktif di Apache
- Periksa `AllowOverride All` di konfigurasi Apache
- Test dengan file `.htaccess` yang lebih sederhana

### Upload File Gagal

- Periksa permission folder `uploads/` (755 atau 777)
- Cek `upload_max_filesize` di php.ini
- Pastikan folder dapat ditulis oleh web server

## 📝 Changelog

### Version 1.0.0 (2025-11-14)
- ✅ Rilis awal
- ✅ Dashboard dengan statistik real-time
- ✅ Manajemen penjualan, pembelian, dan expenses
- ✅ Master data produk, customer, supplier
- ✅ Authentication & role management
- ✅ Dark mode support
- ✅ Responsive design
- ✅ RESTful API

## 🤝 Support

Untuk pertanyaan dan support:
- Email: support@yourdomain.com
- Documentation: [Link to docs]

## 📄 License

Copyright © 2025. All rights reserved.

---

**Developed with ❤️ for Indonesian Tech Businesses**
