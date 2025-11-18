# Sistem Voting RT Online

Sistem pemilihan Ketua RT secara online yang aman, transparan, dan mudah digunakan. Dibangun dengan PHP 8.2 dan MySQL.

## Fitur Utama

### Untuk Pemilih
- Registrasi dengan NIK dan data KTP
- Login dengan NIK dan password
- Melihat profil kandidat lengkap (visi, misi, biodata)
- Melakukan pemilihan (1 orang = 1 suara)
- Melihat hasil pemilihan (setelah selesai)

### Untuk Admin
- Dashboard dengan statistik real-time
- Verifikasi pendaftaran pemilih
- Manajemen data kandidat (CRUD)
- Pengaturan jadwal pemilihan
- Melihat hasil & cetak laporan
- Reset data pemilihan

### Keamanan
- Password terenkripsi (bcrypt)
- CSRF protection
- Input sanitization
- Prepared statements (SQL injection prevention)
- Session management

## Persyaratan Sistem

- PHP 8.0 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi / MariaDB 10.3+
- Web server (Apache/Nginx)
- Extension PHP yang diperlukan:
  - PDO
  - PDO MySQL
  - GD (untuk upload gambar)

## Cara Instalasi

### 1. Upload File
Upload semua file ke hosting Anda (public_html atau www).

### 2. Buat Database
1. Masuk ke phpMyAdmin atau MySQL client
2. Buat database baru: `voting_rt`
3. Import file `database.sql`

```sql
CREATE DATABASE voting_rt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Atau import langsung melalui phpMyAdmin.

### 3. Konfigurasi Database
Edit file `includes/config.php`:

```php
define('DB_HOST', 'localhost');     // Host database
define('DB_USER', 'username_db');   // Username database
define('DB_PASS', 'password_db');   // Password database
define('DB_NAME', 'voting_rt');     // Nama database

define('APP_URL', 'https://domain-anda.com'); // URL website Anda
```

### 4. Set Permission Folder
Pastikan folder `uploads` memiliki permission write:

```bash
chmod 755 uploads
chmod 755 uploads/kandidat
```

### 5. Akses Website
- Website: `https://domain-anda.com`
- Admin: `https://domain-anda.com/admin`

## Login Default Admin

- **Username**: admin
- **Password**: admin123

> **PENTING**: Segera ganti password admin setelah instalasi!

## Struktur Folder

```
voting-rt/
├── admin/                  # Panel admin
│   ├── index.php          # Dashboard
│   ├── login.php          # Login admin
│   ├── pemilih.php        # Kelola pemilih
│   ├── kandidat.php       # Kelola kandidat
│   ├── hasil.php          # Lihat hasil
│   ├── pengaturan.php     # Pengaturan
│   └── ...
├── assets/
│   ├── css/               # File CSS
│   └── js/                # File JavaScript
├── includes/
│   ├── config.php         # Konfigurasi
│   ├── database.php       # Koneksi database
│   ├── functions.php      # Helper functions
│   ├── header.php         # Template header
│   └── footer.php         # Template footer
├── uploads/               # Folder upload gambar
│   └── kandidat/          # Foto kandidat
├── index.php              # Halaman utama
├── login.php              # Login pemilih
├── register.php           # Registrasi pemilih
├── vote.php               # Halaman voting
├── kandidat.php           # Daftar kandidat
├── hasil.php              # Hasil pemilihan
├── database.sql           # Schema database
└── README.md              # Dokumentasi
```

## Cara Penggunaan

### Sebagai Admin

1. **Setup Awal**
   - Login ke admin panel
   - Buka menu Pengaturan
   - Atur nama, tanggal mulai, dan selesai pemilihan

2. **Tambah Kandidat**
   - Buka menu Kandidat
   - Klik "Tambah Kandidat"
   - Isi data lengkap (nama, visi, misi, foto)

3. **Verifikasi Pemilih**
   - Buka menu Pemilih
   - Filter status "Pending"
   - Verifikasi atau tolak pendaftar

4. **Monitor Hasil**
   - Buka menu Hasil untuk melihat perolehan suara
   - Cetak laporan jika diperlukan

### Sebagai Pemilih

1. **Registrasi**
   - Klik "Daftar" di website
   - Isi NIK, No KK, nama, alamat, password
   - Tunggu verifikasi dari admin

2. **Login**
   - Setelah diverifikasi, login dengan NIK dan password

3. **Vote**
   - Buka halaman Vote saat pemilihan berlangsung
   - Pilih kandidat
   - Konfirmasi pilihan

4. **Lihat Hasil**
   - Hasil dapat dilihat setelah pemilihan selesai

## Kustomisasi

### Mengubah Warna Theme
Edit file `assets/css/style.css`:

```css
/* Warna utama */
.btn-primary {
    background: #3498db; /* Ganti dengan warna pilihan */
}
```

### Mengubah Logo
Edit file `includes/header.php`:

```php
<a href="<?= APP_URL ?>" class="logo">
    <img src="logo.png" alt="Logo">
    <?= APP_NAME ?>
</a>
```

### Menambah Field Registrasi
1. Tambah kolom di tabel `users` (database.sql)
2. Edit form di `register.php`
3. Update query insert di `register.php`

## Troubleshooting

### Error: "Koneksi database gagal"
- Periksa username dan password database di config.php
- Pastikan database sudah dibuat
- Pastikan MySQL service berjalan

### Error: "Permission denied" saat upload
- Set permission folder uploads: `chmod 755 uploads`

### Halaman blank / error 500
- Cek error log di hosting
- Pastikan PHP version 8.0+
- Aktifkan display_errors di config.php untuk debug

### Gambar tidak muncul
- Pastikan path APP_URL sudah benar
- Cek permission folder uploads

## Keamanan Tambahan

1. **Ganti password admin** segera setelah instalasi
2. **Backup database** secara berkala
3. **Gunakan HTTPS** untuk enkripsi data
4. **Update PHP** ke versi terbaru
5. **Batasi akses** ke folder admin dengan .htaccess jika perlu

## Support

Jika mengalami kendala atau butuh bantuan:
- Buat issue di repository ini
- Hubungi developer

## Lisensi

Sistem ini bebas digunakan untuk keperluan RT/RW. Dilarang menjual kembali tanpa modifikasi signifikan.

---

Dibuat dengan PHP 8.2 dan MySQL
