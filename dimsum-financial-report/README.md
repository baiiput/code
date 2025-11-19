# Laporan Keuangan Dimsum

Sistem laporan keuangan untuk bisnis dimsum dengan dukungan multi-cabang.

## Fitur

- Dashboard dengan grafik interaktif
- Manajemen transaksi (tambah/edit/hapus)
- Manajemen cabang
- Filter berdasarkan cabang dan tanggal
- Laporan bulanan dan harian
- Export ke Excel
- Dark/Light mode
- Responsive design (mobile-friendly)
- Tidak diindex search engine

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

## Struktur File

```
dimsum-financial-report/
├── index.php           # Dashboard utama
├── transactions.php    # Manajemen transaksi
├── branches.php        # Manajemen cabang
├── reports.php         # Halaman laporan
├── export.php          # Export Excel
├── config.php          # Konfigurasi database
├── database.sql        # Schema database
├── robots.txt          # Block search engines
├── .htaccess           # Apache config
├── api/
│   ├── transactions.php
│   └── branches.php
└── assets/
    └── css/
        └── style.css
```

## Penggunaan

1. **Tambah Cabang**: Buka menu Cabang > Isi form > Simpan
2. **Tambah Transaksi**: Dashboard > Tambah Transaksi > Isi data > Simpan
3. **Lihat Laporan**: Menu Laporan > Filter periode > Lihat grafik & tabel
4. **Export Data**: Klik tombol "Export Excel" di Dashboard atau Laporan

## Keamanan

- Website tidak diindex oleh search engine (robots.txt + meta tags)
- File konfigurasi dilindungi dari akses langsung
- Input sanitization untuk mencegah XSS

## License

Private - For internal use only
