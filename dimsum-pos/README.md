# Dimsum Umami POS System

Sistem Point of Sale (POS) untuk usaha dimsum dengan fitur multi cabang.

## Fitur Utama

- Multi Cabang
- Manajemen Menu dengan Variasi (Isi 3, Isi 5, dll)
- Sistem Kasir yang Responsif
- Diskon & Promo (Persen, Nominal, Beli X Gratis Y, Member, Happy Hour)
- Pengaturan Pajak
- Laporan Penjualan dengan Export PDF & Excel
- Dark/Light Mode
- Mobile Responsive

## Persyaratan Sistem

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Web Server (Apache/Nginx)

## Instalasi

1. Upload semua file ke hosting
2. Import `database.sql` ke MySQL
3. Edit konfigurasi database di `config/config.php`
4. Sesuaikan `BASE_URL` di `config/config.php`
5. Pastikan folder `uploads` dan `exports` memiliki permission write (755)

## Login Default

- **Username:** admin
- **Password:** admin123

**PENTING:** Segera ubah password setelah login pertama kali!

## Struktur User Role

| Role | Akses |
|------|-------|
| Super Admin | Semua fitur, semua cabang |
| Owner | Semua fitur, semua cabang |
| Admin Cabang | Master data, transaksi, laporan (cabang sendiri) |
| Kasir | Kasir, transaksi (cabang sendiri) |

## Metode Pembayaran

- Tunai
- QRIS
- Transfer BCA

## Lisensi

Hak Cipta © 2024 Dimsum Umami. All rights reserved.
