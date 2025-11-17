# 🛰️ Starlink Customer Management System

Sistem manajemen pelanggan Starlink berbasis PHP 8.2 dan MySQL dengan fitur lengkap untuk tracking jatuh tempo, pembayaran, dan perhitungan fee otomatis.

## ✨ Fitur Utama

### 📊 Dashboard & Monitoring
- **4 Menu Status Utama:**
  - **JATUH TEMPO** - Client yang jatuh tempo besok
  - **PROSES** - Client yang jatuh tempo kemarin
  - **SEGERA** - Client sudah lunas tapi lewat tanggal
  - **OBSERVASI** - Client dengan status pending
- Real-time badge counter di sidebar
- Summary cards untuk total pelanggan aktif, pembayaran, dan fee

### 👥 Manajemen Pelanggan
- CRUD (Create, Read, Update, Delete) pelanggan
- Filter berdasarkan status client (Aktif, Non Aktif, Lepas)
- Pencarian multi-field (nama, KIT, nomor, email)
- Data lengkap sesuai Google Sheets:
  - Informasi personal & kontak
  - KIT Number & Serial Number (support multiple)
  - Paket & status langganan
  - Tanggal jatuh tempo
  - History transaksi

### 💰 Sistem Pembayaran
- Input pembayaran manual
- **Perhitungan FEE otomatis** (bisa disesuaikan)
- **Auto-update tanggal jatuh tempo** (+1 bulan, tanggal tetap sama)
- Update status pelanggan otomatis
- History pembayaran lengkap
- Filter berdasarkan bulan & tahun

### 📈 Laporan & Export
- **Laporan Perhitungan Fee** dengan summary
- Filter berdasarkan periode (bulan/tahun)
- **Export ke Excel** (.xls)
- Total fee, nominal, dan keseluruhan
- Detail per transaksi

### 📱 WhatsApp Reminder
- Tombol kirim reminder langsung dari dashboard
- Template pesan otomatis:
  - H-3 (3 hari sebelum jatuh tempo)
  - H-2 (2 hari sebelum jatuh tempo)
  - H-1 (1 hari sebelum jatuh tempo)
  - WARNING (hari H / jatuh tempo hari ini)

### 🔐 Multi-User & Role Management
- **4 Level User:**
  - **Super Admin** - Full access
  - **Admin** - Kelola pelanggan & pembayaran
  - **Finance** - View laporan & pembayaran
  - **Staff** - View only
- Activity log untuk audit trail
- Session management

### 🎨 User Interface
- **AdminLTE 3** - Modern & professional
- **Dark Mode** dengan toggle (saved in cookie)
- Fully **responsive** & mobile-friendly
- DataTables untuk sorting, searching, pagination
- SweetAlert2 untuk konfirmasi
- Select2 untuk dropdown yang lebih baik

---

## 📋 Requirements

- **PHP** >= 8.2
- **MySQL** >= 5.7 atau MariaDB >= 10.2
- **Web Server** (Apache/Nginx)
- **PHP Extensions:**
  - PDO
  - pdo_mysql
  - mbstring
  - json

---

## 🚀 Cara Instalasi

### Step 1: Upload File ke Hosting

1. **Download** semua file dari repository ini
2. **Upload** ke hosting Anda (misal: public_html atau subdomain)
3. Pastikan struktur folder seperti ini:
   ```
   /starlink/
   ├── api/
   ├── config/
   ├── database/
   ├── includes/
   ├── pages/
   ├── index.php
   ├── login.php
   └── logout.php
   ```

### Step 2: Buat Database MySQL

1. **Login ke cPanel** hosting Anda
2. Buka **phpMyAdmin**
3. Klik **New** untuk membuat database baru
4. Nama database: `starlink_db` (atau sesuai keinginan)
5. **Catat** nama database, username, dan password

### Step 3: Import Database Schema

1. Di **phpMyAdmin**, pilih database yang baru dibuat
2. Klik tab **Import**
3. Klik **Choose File**
4. Pilih file: `database/schema.sql`
5. Klik **Go** untuk import
6. ✅ Database siap digunakan!

> **Default User:**
> - Username: `admin`
> - Password: `admin123`

### Step 4: Konfigurasi Database

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');      // Hostname (biasanya localhost)
define('DB_NAME', 'starlink_db');    // Nama database Anda
define('DB_USER', 'root');           // Username database
define('DB_PASS', '');               // Password database
```

### Step 5: Konfigurasi Aplikasi

Edit file `config/config.php`:

```php
define('APP_URL', 'https://yourdomain.com/starlink'); // Sesuaikan dengan URL Anda
```

Jika di root domain:
```php
define('APP_URL', 'https://yourdomain.com');
```

### Step 6: Set Permission (jika diperlukan)

Jika menggunakan Linux hosting, pastikan permission yang benar:

```bash
chmod 755 /path/to/starlink
chmod 644 /path/to/starlink/config/*.php
```

### Step 7: Akses Aplikasi

1. Buka browser
2. Akses: `https://yourdomain.com/starlink/login.php`
3. Login dengan:
   - Username: `admin`
   - Password: `admin123`
4. **✅ Selamat! Aplikasi siap digunakan**

---

## 🔧 Konfigurasi Tambahan

### Mengatur Fee Default

1. Login sebagai Super Admin
2. Menu **Pengaturan > Pengaturan Fee**
3. Ubah nilai fee sesuai kebutuhan
4. Simpan

### Menambah User Baru

1. Login sebagai Super Admin
2. Menu **Pengaturan > Manajemen User**
3. Klik **Tambah User**
4. Isi data dan pilih role
5. Simpan

### Mengubah Timezone

Edit file `config/config.php`:

```php
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan timezone Anda
```

---

## 📱 Penggunaan Fitur

### Input Pembayaran

1. Menu **Pembayaran > Input Pembayaran**
2. Pilih pelanggan
3. Masukkan nominal
4. Fee akan otomatis terisi (bisa diedit)
5. **Centang** "Update Tanggal Jatuh Tempo" untuk auto +1 bulan
6. Simpan

### Melihat Laporan Fee

1. Menu **Laporan > Laporan Fee**
2. Pilih bulan & tahun
3. Klik **Tampilkan**
4. Untuk export: Klik **Export Excel**

### Mengirim Reminder WhatsApp

1. Buka salah satu menu status (Jatuh Tempo/Proses/dll)
2. Klik tombol **WhatsApp** (ikon hijau)
3. Akan membuka WhatsApp Web dengan pesan otomatis
4. Tinggal klik Send

---

## 🎨 Mengaktifkan Dark Mode

Klik icon **bulan/matahari** di navbar kanan atas. Preferensi akan tersimpan di cookie browser.

---

## 🔒 Keamanan

- Password di-hash menggunakan `password_hash()` PHP
- Protection terhadap SQL Injection dengan Prepared Statements
- XSS Protection dengan `htmlspecialchars()`
- Session Management dengan timeout
- Role-based Access Control (RBAC)

---

## 🐛 Troubleshooting

### Error: Connection Failed

**Solusi:**
- Periksa kredensial database di `config/database.php`
- Pastikan MySQL service running
- Cek username & password database

### Error: 404 Not Found

**Solusi:**
- Periksa `APP_URL` di `config/config.php`
- Pastikan .htaccess ada (jika menggunakan Apache)
- Cek file permissions

### Dark Mode tidak tersimpan

**Solusi:**
- Clear browser cache
- Pastikan cookies enabled di browser
- Cek apakah domain support cookies

### Laporan tidak bisa export

**Solusi:**
- Pastikan PHP allow downloads
- Cek permission folder
- Disable popup blocker di browser

---

## 📝 Migrasi Data dari Google Sheets

### Cara Import Data Pelanggan

1. Export Google Sheets ke CSV
2. Login ke aplikasi sebagai Admin
3. Menu **Data Pelanggan > Tambah Pelanggan**
4. Input data satu per satu, atau
5. Gunakan phpMyAdmin untuk import bulk:
   - Export CSV dengan format kolom yang sama
   - Import ke tabel `pelanggan`

### Mapping Kolom

| Google Sheets | Database |
|---------------|----------|
| Nama | nama |
| Login GMAIL | login_gmail |
| Login Starlink | login_starlink |
| ACC No. | acc_no |
| Email Client | email_client |
| Nomor CS | nomor_cs |
| Alamat | alamat |
| KIT Number | kit_number |
| Serial Number | serial_number |
| Tanggal Jatuh Tempo | tanggal_jatuh_tempo |
| STATUS | status |
| Paket | paket |

---

## 🆘 Support

Jika ada pertanyaan atau kendala:

1. Cek **Troubleshooting** di atas
2. Review konfigurasi database dan APP_URL
3. Cek error log: `error_log` di hosting

---

## 📄 License

Proprietary - Untuk penggunaan internal

---

## 🎉 Terima Kasih!

Aplikasi ini dibuat untuk mempermudah manajemen pelanggan Starlink Anda. Semoga bermanfaat!

**Selamat menggunakan! 🚀**
