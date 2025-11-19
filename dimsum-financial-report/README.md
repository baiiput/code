# Laporan Keuangan Dimsum

Sistem laporan keuangan untuk bisnis dimsum dengan dukungan multi-cabang dan multi-user.

## Fitur

- Dashboard dengan grafik interaktif (Chart.js)
- Manajemen transaksi (tambah/edit/hapus)
- Manajemen cabang
- Filter berdasarkan cabang dan tanggal
- Laporan bulanan dan harian
- Export ke Excel (CSV)
- Dark/Light mode dengan UI modern
- Responsive design (mobile-friendly)
- Multi-user dengan role-based access control
- Tidak diindex search engine

## Sistem User & Hak Akses

| Role | Akses |
|------|-------|
| **Viewer** (Guest/Tanpa Login) | View dashboard, laporan, export |
| **Editor** | View + Tambah transaksi |
| **Admin** | Full access (edit, hapus, kelola cabang, kelola user) |

### Default Users
- **Admin**: username `admin`, password `admin123`
- **Editor**: username `editor`, password `editor123`

## Kategori Pemasukan

- Tunai
- QRIS
- Transfer
- Shopee Food
- Grab Food
- Go Food

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Apache dengan mod_rewrite

## Instalasi

1. Upload semua file ke hosting
2. Import `database.sql` ke MySQL
3. Edit `config.php` untuk konfigurasi database:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'dimsum_financial');
   define('DB_USER', 'username_anda');
   define('DB_PASS', 'password_anda');
   ```
4. Akses website melalui browser
5. Login sebagai admin dan ubah password default

## Struktur File

```
dimsum-financial-report/
├── index.php           # Dashboard utama
├── login.php           # Halaman login
├── logout.php          # Logout handler
├── transactions.php    # Form transaksi
├── branches.php        # Manajemen cabang
├── users.php           # Manajemen user
├── reports.php         # Halaman laporan
├── export.php          # Export Excel
├── config.php          # Konfigurasi & helper
├── database.sql        # Schema database
├── robots.txt          # Block search engines
├── .htaccess           # Apache config
├── api/
│   ├── transactions.php
│   ├── branches.php
│   └── users.php
└── assets/
    └── css/
        └── style.css   # Modern UI stylesheet
```

## Penggunaan

### Tanpa Login (Viewer)
- Lihat dashboard dan data transaksi
- Lihat laporan bulanan/harian
- Export data ke Excel

### Login sebagai Editor
- Semua akses Viewer
- Tambah transaksi baru

### Login sebagai Admin
- Semua akses Editor
- Edit dan hapus transaksi
- Kelola cabang
- Kelola user

## Keamanan

- Multi-user authentication dengan password hashing (bcrypt)
- Role-based access control
- Website tidak diindex oleh search engine (robots.txt + meta tags + headers)
- File konfigurasi dilindungi dari akses langsung
- Input sanitization untuk mencegah XSS
- Prepared statements untuk mencegah SQL injection

## Tech Stack

- PHP 8.2
- MySQL/MariaDB
- Bootstrap 5.3
- Chart.js
- DataTables
- SweetAlert2
- Inter Font (Google Fonts)

## License

Private - For internal use only
