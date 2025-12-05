# Starlink Finance - Laporan Keuangan

Sistem laporan keuangan sederhana untuk bisnis Starlink dengan PHP 8.2 dan MySQL.

## Fitur

- **Dashboard Summary**: Total pemasukan, pengeluaran, saldo, dan jumlah transaksi
- **Ringkasan Bulanan**: Tutup buku per bulan dengan ringkasan pemasukan/pengeluaran
- **CRUD Transaksi**: Tambah (admin/editor), edit/hapus (admin only)
- **Multi-Level User**: Role admin, editor, dan viewer dengan akses berbeda
- **User Management**: Admin dapat mengelola user dan role
- **Filter & Pencarian**: Filter berdasarkan tanggal, tipe, dan kata kunci
- **Pagination**: 20 transaksi per halaman + tombol lihat semua
- **Export CSV & PDF**: Export data ke CSV dan PDF dengan format rapi
- **Format Rupiah**: Input nominal otomatis format Rupiah (Rp 100.000)
- **Multi-line Deskripsi**: Support deskripsi dengan multiple line
- **Dark/Light Mode**: Toggle tema gelap/terang (Backdrop Blur Modal)
- **Responsive Design**: Tampilan optimal di desktop dan mobile
- **SEO Blocked**: Tidak diindex oleh search engine

## User Roles

| Role | Lihat Data | Tambah | Edit | Hapus | Export | Kelola User |
|------|------------|--------|------|-------|--------|-------------|
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editor | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| Viewer | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Guest (tidak login) | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |

### Default Users
- **Admin**: username `admin`, password `admin123`
- **Editor**: username `editor`, password `admin123`

**PENTING**: Ganti password default setelah instalasi!

## Persyaratan

- PHP 8.2+
- MySQL 5.7+ atau MariaDB 10.3+
- Apache dengan mod_rewrite (opsional)

## Instalasi

### 1. Upload Files
Upload semua file ke direktori hosting Anda.

### 2. Buat Database
Jalankan file `database.sql` di phpMyAdmin atau MySQL client:

```bash
mysql -u username -p database_name < database.sql
```

### 3. Konfigurasi Database
Edit file `includes/config.php` dan sesuaikan kredensial database:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'starlink_finance');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Set Permissions
```bash
chmod 644 includes/config.php
chmod 755 api/
chmod 755 assets/
```

### 5. Akses Website
Buka website melalui browser Anda.

## Struktur File

```
starlink-finance/
├── index.php              # Halaman utama
├── database.sql           # Schema database
├── robots.txt             # Block search engines
├── .htaccess              # Apache configuration
├── README.md              # Dokumentasi
├── api/
│   ├── auth.php           # Authentication API
│   └── transactions.php   # Transactions API
├── includes/
│   └── config.php         # Konfigurasi database
└── assets/
    ├── css/
    │   └── style.css      # Stylesheet
    └── js/
        └── app.js         # JavaScript aplikasi
```

## Penggunaan

### Login
1. Klik tombol "Login" di header
2. Masukkan username dan password
3. Klik "Login"
4. Setelah login, nama dan role akan ditampilkan di header

### Tambah Transaksi (Admin Only)
1. Login sebagai admin
2. Klik tombol "+ Tambah Transaksi"
3. Isi tanggal dan deskripsi
4. Masukkan nilai pemasukan atau pengeluaran
5. Klik "Simpan"

### Filter Data
- **Cari**: Ketik kata kunci untuk mencari di deskripsi
- **Tanggal**: Pilih rentang tanggal
- **Tipe**: Filter pemasukan atau pengeluaran saja

### Export Data
Klik tombol "Export CSV" untuk mengunduh data dalam format CSV.

### Ganti Tema
Klik tombol sun/moon di header untuk toggle dark/light mode.

## Keyboard Shortcuts

- `Ctrl/Cmd + N`: Buka form tambah transaksi baru
- `Escape`: Tutup modal

## Keamanan

- **Role-based Access Control**: Admin dan viewer dengan akses berbeda
- **Password Hashing**: Bcrypt untuk penyimpanan password
- **Session Security**: Secure cookie dengan HttpOnly dan SameSite
- Data tidak diindex search engine (robots.txt + meta tags)
- SQL Injection protection dengan PDO prepared statements
- XSS protection dengan escaping output
- Direktori includes dilindungi dari akses langsung
- Security headers untuk mencegah clickjacking dan XSS

## API Endpoints

### Authentication API

#### GET /api/auth.php
- `?action=status` - Cek status login
- `?action=logout` - Logout user
- `?action=users` - List semua user (admin only)

#### POST /api/auth.php
- `?action=login` - Login user
- `?action=create-user` - Buat user baru (admin only)
- `?action=update-user` - Update user (admin only)
- `?action=change-password` - Ganti password
- `?action=delete-user` - Hapus user (admin only)

### Transactions API

#### GET /api/transactions.php
- `?summary=1` - Dapatkan ringkasan total
- `?id={id}` - Dapatkan transaksi by ID
- `?page={n}&limit={n}` - Pagination
- `?search={keyword}` - Cari deskripsi
- `?start_date={date}&end_date={date}` - Filter tanggal
- `?type={pemasukan|pengeluaran}` - Filter tipe

#### POST /api/transactions.php (Admin Only)
Tambah transaksi baru.

#### PUT /api/transactions.php (Admin Only)
Update transaksi.

#### DELETE /api/transactions.php?id={id} (Admin Only)
Hapus transaksi.

## Troubleshooting

### Error "Database connection failed"
- Pastikan kredensial database di config.php sudah benar
- Pastikan database sudah dibuat dan user punya akses

### Halaman blank/error 500
- Cek PHP error log
- Pastikan PHP versi 8.2+
- Pastikan PDO MySQL extension aktif

### Filter tidak berfungsi
- Pastikan JavaScript tidak diblokir browser
- Buka browser console untuk melihat error

## License

Private use only.
