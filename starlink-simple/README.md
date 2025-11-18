# Starlink Customer Management System

Sistem manajemen data pelanggan Starlink - Versi Simple (PHP + MySQL)

## Fitur

- Dashboard statistik
- CRUD Pelanggan dengan search & filter
- Manajemen Pembayaran (auto-update status)
- Multi-user dengan 3 role
- Dark mode
- Responsive design

## Requirements

- PHP 7.4+ (recommended 8.0+)
- MySQL 5.7+
- Web server (Apache/Nginx)

## Instalasi

### 1. Upload semua file ke hosting

Upload semua file ke folder `public_html` atau folder website Anda.

### 2. Import Database

1. Buat database baru di phpMyAdmin (misal: `starlink_db`)
2. Import file `database.sql` ke database tersebut

### 3. Edit Konfigurasi Database

Edit file `config.php`, ubah bagian ini sesuai dengan database Anda:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'starlink_db');
define('DB_USER', 'username_database');
define('DB_PASS', 'password_database');
```

### 4. Selesai!

Akses website Anda dan login dengan:

- **Email**: admin@starlink.com
- **Password**: admin123

**PENTING: Segera ubah password setelah login pertama!**

## Struktur File

```
starlink-simple/
├── config.php          # Konfigurasi database & functions
├── database.sql        # SQL schema
├── index.php           # Dashboard
├── login.php           # Halaman login
├── logout.php          # Logout
├── customers.php       # List pelanggan
├── customer_form.php   # Form add/edit pelanggan
├── customer_view.php   # Detail pelanggan
├── payments.php        # List pembayaran
├── payment_form.php    # Form input pembayaran
├── users.php           # List users
├── user_form.php       # Form add/edit user
└── includes/
    ├── header.php      # Header template
    └── footer.php      # Footer template
```

## Roles & Permissions

| Role | Akses |
|------|-------|
| Super Admin | Full access (pelanggan, pembayaran, users) |
| Admin | Kelola pelanggan (CRUD) |
| Viewer | Hanya lihat data |

## Troubleshooting

### Error "Database connection failed"

- Pastikan kredensial database di `config.php` sudah benar
- Pastikan database sudah dibuat dan SQL sudah diimport

### Halaman Blank

- Cek error PHP di error log hosting
- Pastikan PHP version 7.4+

### Session Error

- Pastikan folder writable oleh web server

## Keamanan

- Ubah password default segera setelah instalasi
- Gunakan HTTPS di production
- Backup database secara berkala
- Jangan share file `config.php` dengan kredensial database

## Support

Jika ada pertanyaan atau masalah, silakan hubungi developer.
