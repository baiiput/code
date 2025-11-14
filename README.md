# Sistem Jimpitan RT

Website laporan keuangan untuk jimpitan RT dengan pembagian per Dawis. Aplikasi ini dilengkapi dengan fitur dark mode, responsive design, dan tampilan modern yang mudah digunakan.

## Fitur Utama

✨ **Fitur-fitur:**
- 📊 Dashboard dengan statistik real-time
- 👥 Manajemen data warga per Dawis
- 💰 Pencatatan transaksi jimpitan (setoran & penarikan)
- 💸 Pencatatan pengeluaran RT
- 📑 Laporan keuangan per Dawis
- 🌓 Dark mode toggle
- 📱 Responsive design (Mobile, Tablet, Desktop)
- 🎨 Tampilan modern dan user-friendly
- 🖨️ Fitur cetak laporan

## Teknologi

- **Backend:** PHP 8.2
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (Custom), JavaScript (Vanilla)
- **Pattern:** MVC-like structure with API endpoints

## Struktur Database

Aplikasi ini menggunakan 5 tabel utama:
1. **dawis** - Data Dawis (Dasa Wisma)
2. **warga** - Data warga RT
3. **transaksi** - Transaksi jimpitan (setoran/penarikan)
4. **pengeluaran** - Pengeluaran RT
5. **users** - User admin/bendahara

## Instalasi

### Prasyarat
- PHP 8.2 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Domain dan hosting (sudah tersedia menurut requirement)

### Langkah Instalasi

#### 1. Upload Files
Upload semua file ke direktori web server Anda (biasanya `public_html` atau `www`)

```bash
# Struktur file yang harus diupload:
/
├── api/
│   ├── dashboard.php
│   ├── transaksi.php
│   ├── warga.php
│   └── pengeluaran.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/
│   └── database.php
├── includes/
│   ├── Database.php
│   └── functions.php
├── index.php
├── laporan.php
├── warga.php
├── transaksi.php
├── pengeluaran.php
└── database.sql
```

#### 2. Setup Database

**Opsi A: Menggunakan phpMyAdmin**
1. Login ke phpMyAdmin
2. Klik "New" untuk membuat database baru
3. Buka tab "Import"
4. Pilih file `database.sql`
5. Klik "Go" untuk import

**Opsi B: Menggunakan Command Line**
```bash
# Login ke MySQL
mysql -u root -p

# Jalankan file SQL
source /path/to/database.sql
```

#### 3. Konfigurasi Database

Edit file `config/database.php` dan sesuaikan dengan kredensial database Anda:

```php
define('DB_HOST', 'localhost');          // Host database
define('DB_USER', 'your_username');      // Username MySQL
define('DB_PASS', 'your_password');      // Password MySQL
define('DB_NAME', 'jimpitan_rt');        // Nama database
define('BASE_URL', 'https://yourdomain.com'); // URL domain Anda
```

#### 4. Set Permissions

Pastikan file dan folder memiliki permission yang benar:

```bash
# Set permission untuk folder
chmod 755 api config includes assets
chmod 755 assets/css assets/js

# Set permission untuk file PHP
chmod 644 *.php
chmod 644 api/*.php
chmod 644 config/*.php
chmod 644 includes/*.php
```

#### 5. Akses Aplikasi

Buka browser dan akses domain Anda:
```
https://yourdomain.com
```

## Data Default

### User Admin
Setelah instalasi, Anda dapat login dengan:
- **Username:** admin
- **Password:** admin123

> ⚠️ **Penting:** Segera ganti password default setelah login pertama kali!

### Data Sample
Database sudah dilengkapi dengan data sample:
- 3 Dawis (Dawis 1, Dawis 2, Dawis 3)
- 6 Warga (2 per Dawis)
- Beberapa transaksi contoh
- Beberapa pengeluaran contoh

## Penggunaan

### 1. Dashboard
- Melihat ringkasan keuangan RT
- Statistik total jimpitan, pengeluaran, dan saldo
- Laporan per Dawis
- Transaksi terakhir

### 2. Laporan Per Dawis
- Filter laporan berdasarkan Dawis, bulan, dan tahun
- Lihat detail setoran dan penarikan per warga
- Cetak laporan

### 3. Data Warga
- Tambah warga baru
- Edit data warga
- Kelola status warga (aktif/tidak aktif)
- Lihat saldo per warga

### 4. Transaksi
- Catat setoran jimpitan warga
- Catat penarikan warga
- Filter transaksi per bulan/tahun
- Edit/hapus transaksi

### 5. Pengeluaran
- Catat pengeluaran RT
- Kategorisasi pengeluaran
- Filter per bulan/tahun
- Edit/hapus pengeluaran

### 6. Dark Mode
- Toggle dark mode dengan tombol di header
- Preferensi tersimpan di browser (localStorage)
- Otomatis apply saat buka aplikasi

## Customisasi

### Mengubah Warna Tema
Edit file `assets/css/style.css` pada bagian `:root`:

```css
:root {
    --primary-color: #4f46e5;  /* Warna utama */
    --secondary-color: #10b981; /* Warna sukses */
    /* ... dan seterusnya */
}
```

### Menambah Kategori Pengeluaran
Edit file `pengeluaran.php` pada bagian select kategori:

```html
<option value="Kategori Baru">Kategori Baru</option>
```

### Mengubah Logo/Nama Aplikasi
Edit file `config/database.php`:

```php
define('APP_NAME', 'Nama Aplikasi Anda');
```

## Backup Database

Sangat disarankan untuk melakukan backup database secara berkala:

```bash
# Backup via command line
mysqldump -u username -p jimpitan_rt > backup_$(date +%Y%m%d).sql

# Atau gunakan phpMyAdmin:
# 1. Pilih database
# 2. Klik tab "Export"
# 3. Pilih format SQL
# 4. Klik "Go"
```

## Troubleshooting

### Error: Database connection failed
- Periksa kredensial database di `config/database.php`
- Pastikan MySQL service berjalan
- Cek apakah database sudah dibuat

### Error: 404 Not Found
- Pastikan semua file sudah diupload dengan struktur yang benar
- Cek permission file dan folder

### Dark mode tidak berfungsi
- Pastikan JavaScript enabled di browser
- Clear cache browser
- Periksa console untuk error JavaScript

### Tampilan tidak responsive
- Clear cache browser
- Pastikan file CSS sudah ter-load dengan benar

## Keamanan

### Rekomendasi Keamanan:
1. **Ganti password default** admin setelah instalasi
2. **Matikan error reporting** di production:
   ```php
   // Di config/database.php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```
3. **Gunakan HTTPS** untuk koneksi aman
4. **Backup database** secara berkala
5. **Update PHP** ke versi terbaru secara berkala

## Support

Jika mengalami kendala:
1. Periksa file log error di web server
2. Periksa console browser untuk JavaScript errors
3. Pastikan semua requirement terpenuhi

## Lisensi

Project ini dibuat untuk keperluan RT/RW dan dapat digunakan secara bebas.

## Changelog

### Version 1.0.0 (2025-01-14)
- ✅ Initial release
- ✅ Dashboard dengan statistik
- ✅ Manajemen warga per Dawis
- ✅ Transaksi jimpitan
- ✅ Pengeluaran RT
- ✅ Laporan per Dawis
- ✅ Dark mode
- ✅ Responsive design
- ✅ Cetak laporan

---

Dibuat dengan ❤️ untuk kemudahan pengelolaan keuangan RT
