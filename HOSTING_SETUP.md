# 🚀 Panduan Setup Hosting - Koperasi Syariah Online

## 📋 Checklist Upload ke Hosting

### 1. **Upload Files ke Hosting**

Upload semua file project ke hosting Anda via FTP atau cPanel File Manager.

**Struktur folder di hosting:**
```
public_html/koperasi/          (atau nama folder lain)
├── admin/
├── api/
├── assets/
├── config/
├── customer/
├── includes/
├── .htaccess
├── index.php
├── login.php
└── ... (file lainnya)
```

### 2. **Import Database**

1. Login ke **phpMyAdmin** di hosting
2. Create database baru (misal: `koperasi_syariah`)
3. Klik tab **Import**
4. Pilih file **`database_complete.sql`**
5. Klik **Go** dan tunggu selesai

### 3. **Konfigurasi File PHP**

#### A. Update `config/database.php`

```php
<?php
$host = 'localhost';                    // Biasanya localhost
$username = 'username_db_hosting';      // ⬅️ GANTI dengan username MySQL hosting
$password = 'password_db_hosting';      // ⬅️ GANTI dengan password MySQL hosting
$database = 'koperasi_syariah';         // ⬅️ GANTI dengan nama database Anda
```

#### B. Update `config/base_path.php`

**PENTING!** Sesuaikan `BASE_PATH` dengan lokasi hosting Anda:

```php
<?php
// Jika di ROOT domain (contoh: koperasi.com)
define('BASE_PATH', '');

// Jika di SUBFOLDER (contoh: galaxy.octolink.id/koperasi)
define('BASE_PATH', '/koperasi');  // ⬅️ GANTI sesuai nama folder Anda

// Untuk localhost development
// define('BASE_PATH', '');
```

**Contoh kasus:**
- URL: `galaxy.octolink.id/koperasi` → `define('BASE_PATH', '/koperasi');`
- URL: `koperasi.octolink.id` → `define('BASE_PATH', '');`
- URL: `mysite.com/apps/koperasi` → `define('BASE_PATH', '/apps/koperasi');`

#### C. Update `.htaccess` (Line 6)

Sesuaikan `RewriteBase` dengan BASE_PATH:

```apache
# Jika di ROOT domain
RewriteBase /

# Jika di SUBFOLDER /koperasi
RewriteBase /koperasi/

# Jika di SUBFOLDER /apps/koperasi
RewriteBase /apps/koperasi/
```

### 4. **Test Akses**

Buka browser dan akses:
```
https://galaxy.octolink.id/koperasi
```

Seharusnya otomatis redirect ke halaman login.

**Login dengan:**
- Username: `superadmin`
- Password: `admin123`

---

## ⚠️ Troubleshooting "Not Found" Error

### Problem 1: "404 Not Found" saat akses domain

**Penyebab:** `BASE_PATH` tidak sesuai dengan lokasi folder

**Solusi:**
1. Cek lokasi folder di hosting (misal: `/public_html/koperasi`)
2. Update `config/base_path.php`:
   ```php
   define('BASE_PATH', '/koperasi');
   ```
3. Update `.htaccess` line 6:
   ```apache
   RewriteBase /koperasi/
   ```

### Problem 2: "500 Internal Server Error"

**Penyebab:** Error di konfigurasi database atau PHP

**Solusi:**
1. Cek error log di cPanel → Error Logs
2. Pastikan credentials database sudah benar di `config/database.php`
3. Pastikan PHP version minimal 7.4 (recommended: PHP 8.0+)

### Problem 3: "Access Denied" saat login

**Penyebab:** Database belum diimport atau credentials salah

**Solusi:**
1. Pastikan `database_complete.sql` sudah diimport
2. Cek koneksi database dengan test:
   ```php
   <?php
   include 'config/database.php';
   if ($conn) {
       echo "Database connected!";
   } else {
       echo "Connection failed: " . mysqli_connect_error();
   }
   ?>
   ```

### Problem 4: CSS/JS tidak load (tampilan berantakan)

**Penyebab:** Path CSS/JS tidak sesuai

**Solusi:**
Tailwind CSS diload dari CDN, pastikan:
- Hosting support HTTPS
- Tidak ada firewall blocking CDN

### Problem 5: "Database connection failed"

**Penyebab:** Credentials database salah atau database tidak exist

**Solusi:**
1. Login ke phpMyAdmin, cek apakah database `koperasi_syariah` exist
2. Cek username & password MySQL di cPanel
3. Update `config/database.php` dengan credentials yang benar

---

## 🔒 Keamanan Production

Setelah berhasil login, **SEGERA LAKUKAN:**

### 1. Ganti Password Default
- Login sebagai `superadmin`
- Menu **Users** → Edit user
- Ganti password dari `admin123` ke password yang kuat
- Lakukan untuk semua user (manager, staff)

### 2. Update Xendit API Key (Production)
Edit `config/xendit.php`:
```php
define('XENDIT_SECRET_KEY', 'xnd_production_xxxxx'); // Ganti dengan production key
```

### 3. Aktifkan HTTPS
Uncomment di `.htaccess` line 13-14:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 4. Hide Error Display (Production)
Edit `.htaccess` line 77:
```apache
php_flag display_errors off  # Pastikan off untuk production
php_flag log_errors on       # Log error ke file saja
```

---

## 📊 Verifikasi Instalasi

Setelah setup, verifikasi:

✅ Bisa akses halaman login
✅ Bisa login dengan `superadmin / admin123`
✅ Dashboard admin tampil dengan benar
✅ Menu Investor & Kas muncul (untuk Super Admin/Manager)
✅ Data sample investor terlihat (5 investor total Rp 175 juta)
✅ Bisa create transaksi dengan alokasi investor
✅ CSS Tailwind load dengan benar (dark mode toggle ada)

---

## 🆘 Masih Bermasalah?

### Quick Debug Steps:

1. **Cek PHP Version:**
   Buat file `info.php`:
   ```php
   <?php phpinfo(); ?>
   ```
   Upload ke root, akses via browser, pastikan PHP >= 7.4

2. **Cek Database Connection:**
   Buat file `test_db.php`:
   ```php
   <?php
   include 'config/database.php';
   if ($conn) {
       echo "✅ Database connected successfully!";
       $result = $conn->query("SELECT COUNT(*) as total FROM users");
       $row = $result->fetch_assoc();
       echo "<br>Total users: " . $row['total'];
   } else {
       echo "❌ Connection failed: " . mysqli_connect_error();
   }
   ?>
   ```

3. **Cek File Permissions:**
   - Folders: 755
   - Files: 644
   - Gunakan FileZilla atau cPanel File Manager

4. **Cek .htaccess:**
   Rename `.htaccess` menjadi `.htaccess.bak` sementara.
   Jika website jadi bisa diakses, berarti ada masalah di konfigurasi Apache.

---

## 📞 Support

Jika masih ada error, catat:
1. URL yang diakses
2. Error message lengkap
3. Screenshot jika ada
4. PHP version dari phpinfo()
5. Hasil dari test_db.php

---

**Good luck! 🚀**
