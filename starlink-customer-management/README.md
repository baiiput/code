# Starlink Customer Management System

Sistem manajemen data pelanggan Starlink dengan fitur lengkap, modern UI dengan Tailwind CSS, dan dukungan dark mode.

## Fitur

- **Dashboard** - Statistik pelanggan, pembayaran terbaru, jatuh tempo terdekat
- **Manajemen Pelanggan** - CRUD lengkap dengan search & filter
- **Manajemen Pembayaran** - Input pembayaran, auto-update status pelanggan
- **Multi-User** - 3 role (Super Admin, Admin, Viewer)
- **Dark Mode** - Toggle dark/light mode
- **Responsive** - Mobile-friendly design

## Roles & Permissions

| Role | Akses |
|------|-------|
| Super Admin | Full access (pelanggan, pembayaran, users) |
| Admin | Kelola pelanggan (CRUD) |
| Viewer | Hanya lihat data |

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Composer

## Instalasi di Hosting

### 1. Upload Files

Upload semua file ke hosting Anda. Struktur folder harus seperti ini:

```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/        <- Konten folder ini bisa dipindah ke public_html
├── resources/
├── routes/
├── storage/
├── vendor/
└── ...
```

**Opsi A: Subdomain/Domain pointing ke folder public**

Arahkan domain ke folder `public/`

**Opsi B: Pindahkan konten public ke root**

1. Pindahkan isi folder `public/` ke `public_html/`
2. Edit `public_html/index.php`, ubah path:
```php
require __DIR__.'/../vendor/autoload.php';
// menjadi
require __DIR__.'/vendor/autoload.php';

require_once __DIR__.'/../bootstrap/app.php'
// menjadi
require_once __DIR__.'/bootstrap/app.php'
```

### 2. Install Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Konfigurasi Environment

1. Copy `.env.example` ke `.env`
2. Edit `.env` dengan konfigurasi database Anda:

```env
APP_NAME="Starlink Customer Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=username_database
DB_PASSWORD=password_database
```

### 4. Generate App Key

```bash
php artisan key:generate
```

### 5. Jalankan Migrasi Database

```bash
php artisan migrate
```

### 6. Jalankan Seeder (Opsional)

Untuk membuat user default:

```bash
php artisan db:seed
```

User default yang dibuat:
- **Super Admin**: admin@starlink.com / password123
- **Admin**: admin2@starlink.com / password123
- **Viewer**: viewer@starlink.com / password123

**PENTING: Segera ubah password setelah login pertama!**

### 7. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

### 8. Storage Link (jika diperlukan)

```bash
php artisan storage:link
```

## Akses Aplikasi

Buka browser dan akses domain Anda. Login dengan kredensial yang sudah dibuat.

## Troubleshooting

### Error 500

1. Pastikan `.env` sudah dikonfigurasi dengan benar
2. Jalankan `php artisan config:clear`
3. Cek log di `storage/logs/laravel.log`

### Halaman Blank

1. Pastikan folder `storage` dan `bootstrap/cache` writable
2. Jalankan `php artisan cache:clear`

### Database Connection Error

1. Verifikasi kredensial database di `.env`
2. Pastikan database sudah dibuat
3. Cek apakah user database memiliki permission yang cukup

## Security Notes

- Jangan pernah commit file `.env`
- Ubah password default segera setelah instalasi
- Gunakan HTTPS di production
- Backup database secara berkala

## Support

Jika ada pertanyaan atau masalah, silakan hubungi developer.
