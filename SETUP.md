# Panduan Setup Koperasi Syariah Online

Panduan lengkap instalasi dan konfigurasi sistem.

## Prerequisites

- **Hosting:** PHP 8.2+, MySQL 5.7+
- **Domain:** Sudah terdaftar dan pointing ke hosting
- **Xendit Account:** Untuk payment gateway
- **cPanel/DirectAdmin** atau akses SSH

## Step 1: Upload Files

### Via cPanel File Manager
1. Login ke cPanel
2. Buka File Manager
3. Navigate ke `public_html` atau folder domain
4. Upload semua file project
5. Extract jika dalam format zip

### Via FTP
1. Gunakan FileZilla atau FTP client lain
2. Connect ke hosting
3. Upload semua file ke folder public_html
4. Pastikan struktur folder tetap sama

## Step 2: Setup Database

### Cara 1: phpMyAdmin
1. Login cPanel → phpMyAdmin
2. Klik "New" untuk buat database baru
3. Nama database: `koperasi_syariah`
4. Klik database yang baru dibuat
5. Klik tab "Import"
6. Choose file: `database.sql`
7. Klik "Go"

### Cara 2: MySQL Command
```bash
mysql -u username -p
CREATE DATABASE koperasi_syariah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE koperasi_syariah;
SOURCE /path/to/database.sql;
```

### Cara 3: cPanel MySQL Databases
1. cPanel → MySQL Databases
2. Create Database: `koperasi_syariah`
3. Create User dan password
4. Add User to Database dengan ALL PRIVILEGES
5. phpMyAdmin → Import `database.sql`

## Step 3: Konfigurasi Database

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');        // Biasanya localhost
define('DB_USER', 'your_db_user');     // Username database
define('DB_PASS', 'your_db_password'); // Password database
define('DB_NAME', 'koperasi_syariah'); // Nama database
```

**Catatan:** Beberapa hosting mengharuskan format `username_dbname` untuk nama database.

## Step 4: Setup Xendit

### 4.1 Daftar Xendit
1. Kunjungi https://dashboard.xendit.co/register
2. Daftar dengan email bisnis
3. Verifikasi email
4. Login ke dashboard

### 4.2 Get API Key
1. Dashboard Xendit → Settings → Developers → API Keys
2. **Untuk Testing:**
   - Copy **Test Secret Key** (xnd_development_...)
3. **Untuk Production:**
   - Lengkapi verifikasi bisnis
   - Copy **Live Secret Key** (xnd_production_...)

### 4.3 Konfigurasi API Key

Edit file `config/xendit.php`:

```php
// Untuk testing
define('XENDIT_API_KEY', 'xnd_development_XXXXXXXXXXXXXXXXX');

// Untuk production (setelah verifikasi)
// define('XENDIT_API_KEY', 'xnd_production_XXXXXXXXXXXXXXXXX');

// Ganti dengan domain Anda
define('XENDIT_SUCCESS_REDIRECT_URL', 'https://yourdomain.com/customer/payment-success.php');
define('XENDIT_FAILURE_REDIRECT_URL', 'https://yourdomain.com/customer/payment-failed.php');
```

### 4.4 Test Mode vs Live Mode

**Test Mode** (Development):
- Menggunakan `xnd_development_...`
- Tidak ada transaksi real
- Untuk testing: gunakan nomor kartu/VA test dari docs Xendit
- Link: https://docs.xendit.co/payment-methods

**Live Mode** (Production):
- Menggunakan `xnd_production_...`
- Transaksi real money
- Butuh verifikasi bisnis lengkap
- Fee per transaksi sesuai agreement

## Step 5: File Permissions

Set permission yang benar:

```bash
# Via SSH
chmod 755 admin customer api config includes assets
chmod 644 *.php
chmod 644 config/*.php
chmod 644 .htaccess

# Atau via cPanel File Manager:
# Klik kanan file/folder → Change Permissions
# Folder: 755
# Files: 644
```

## Step 6: Testing

### 6.1 Test Login Admin
1. Buka: `https://yourdomain.com`
2. Login dengan:
   - Username: `admin`
   - Password: `admin123`
3. Jika berhasil, Anda akan masuk ke Dashboard Admin

### 6.2 Test Database Connection
Jika error "Database connection failed":
- Cek credentials di `config/database.php`
- Pastikan database sudah di-import
- Cek MySQL service running
- Cek user database memiliki privileges

### 6.3 Test Xendit Integration
1. Login sebagai admin
2. Tambah pelanggan test
3. Tambah barang test
4. Buat transaksi cicilan
5. Login sebagai customer (username dari admin)
6. Coba buat payment link
7. Jika error "Failed to create invoice":
   - Cek API Key Xendit benar
   - Cek koneksi internet server
   - Pastikan curl extension PHP aktif

## Step 7: Security (Production Only)

### 7.1 Change Default Passwords
```sql
-- Via phpMyAdmin → SQL tab
UPDATE users SET password = '$2y$10$NEW_HASH_HERE' WHERE username = 'admin';
```

Generate password hash:
```php
<?php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
?>
```

### 7.2 Enable HTTPS
1. cPanel → SSL/TLS Status
2. Run AutoSSL untuk domain
3. Atau install Let's Encrypt certificate
4. Edit `.htaccess`, uncomment baris force HTTPS:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 7.3 Disable PHP Error Display
Edit `.htaccess`:
```apache
php_flag display_errors off
php_flag log_errors on
php_value error_log /home/user/public_html/php_errors.log
```

### 7.4 Protect Config Files
Already configured in `.htaccess`:
```apache
<Files "database.php">
    Order allow,deny
    Deny from all
</Files>
```

## Step 8: Production Checklist

- [ ] Database imported successfully
- [ ] Config database correct
- [ ] Xendit API Key configured (Live Mode)
- [ ] HTTPS enabled
- [ ] Admin password changed
- [ ] PHP error display disabled
- [ ] File permissions set correctly
- [ ] Test payment flow works
- [ ] Backup system in place

## Common Issues & Solutions

### Issue 1: "Internal Server Error"
**Solusi:**
- Cek `.htaccess` syntax
- Disable `.htaccess` temporary (rename to `.htaccess.bak`)
- Cek PHP error log
- Pastikan PHP 8.2+ installed

### Issue 2: "Database Connection Failed"
**Solusi:**
- Cek DB credentials di `config/database.php`
- Cek database exists: `SHOW DATABASES;`
- Cek user privileges: `SHOW GRANTS FOR 'user'@'localhost';`
- Test connection via phpMyAdmin

### Issue 3: "Failed to create Xendit invoice"
**Solusi:**
- Cek API Key valid
- Cek email customer tidak kosong
- Enable curl extension PHP
- Cek server bisa access api.xendit.com
- Test dengan curl:
```bash
curl -X POST https://api.xendit.com/v2/invoices \
  -u xnd_development_XXXX: \
  -d external_id=test123 \
  -d amount=10000 \
  -d payer_email=test@email.com \
  -d description=test
```

### Issue 4: "Payment status not updating"
**Solusi:**
- Customer harus klik button "Cek Status" manual
- Cek Xendit invoice ID benar
- Cek payment sudah paid di Xendit dashboard
- Cek server bisa call Xendit API

### Issue 5: Session not working
**Solusi:**
- Cek `session.save_path` di php.ini
- Pastikan folder session writeable
- Tambah di `.htaccess`:
```apache
php_value session.save_path "/tmp"
```

## Backup Strategy

### Database Backup (Daily)
```bash
# Via cron
mysqldump -u user -p koperasi_syariah > backup_$(date +%Y%m%d).sql
```

### File Backup (Weekly)
- Backup entire `public_html` folder
- Atau gunakan cPanel Backup feature

## Support & Help

**Dokumentasi:**
- Xendit Docs: https://docs.xendit.co
- PHP Manual: https://www.php.net/manual/en/

**Logs untuk debugging:**
- PHP Error Log: `/home/user/public_html/php_errors.log`
- Apache Error Log: `/var/log/apache2/error.log`
- MySQL Error Log: `/var/log/mysql/error.log`

## Next Steps

1. **Customize Design** - Edit Tailwind classes di file PHP
2. **Add Features** - Extend functionality sesuai kebutuhan
3. **Setup Backup** - Implement automated backup
4. **Monitor System** - Setup monitoring & alerts
5. **Train Users** - Latih admin & customer cara penggunaan

---

Selamat! Sistem Koperasi Syariah Online Anda sudah siap digunakan! 🎉
