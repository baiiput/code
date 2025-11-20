# Koperasi Syariah Online - Sistem Cicilan Murabahah

Sistem manajemen cicilan berbasis syariah dengan integrasi Xendit untuk pembayaran online.

## Fitur Utama

### Admin
- Dashboard dengan statistik lengkap
- Kelola data pelanggan (CRUD)
- Kelola data barang (CRUD)
- Buat transaksi cicilan dengan margin manual
- Monitoring pembayaran real-time

### Pelanggan
- Dashboard cicilan aktif
- Lihat detail cicilan dan sisa hutang
- Pembayaran fleksibel (sesuai angsuran atau lebih)
- Integrasi Xendit untuk berbagai metode pembayaran
- Cek status pembayaran manual (button)

## Teknologi

- PHP 8.2
- MySQL
- Tailwind CSS
- Alpine.js
- Xendit Payment Gateway

## Instalasi

### 1. Requirements
- PHP 8.2 atau lebih tinggi
- MySQL 5.7+
- Web server (Apache/Nginx)
- Extension PHP: mysqli, curl, json

### 2. Setup Database

```bash
# Import database
mysql -u root -p < database.sql
```

Atau import manual melalui phpMyAdmin.

### 3. Konfigurasi

#### Database (config/database.php)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'koperasi_syariah');
```

#### Xendit (config/xendit.php)
```php
define('XENDIT_API_KEY', 'xnd_development_XXXXXX'); // Ganti dengan API Key Xendit
define('XENDIT_SUCCESS_REDIRECT_URL', 'http://yourdomain.com/customer/payment-success.php');
define('XENDIT_FAILURE_REDIRECT_URL', 'http://yourdomain.com/customer/payment-failed.php');
```

### 4. Setup Xendit

1. Daftar di [Xendit](https://dashboard.xendit.co/)
2. Ambil API Key dari Dashboard → Settings → Developers → API Keys
3. Untuk testing, gunakan **Test Mode API Key** (xnd_development_...)
4. Untuk production, gunakan **Live Mode API Key** (xnd_production_...)
5. Masukkan API Key ke `config/xendit.php`

### 5. File Permissions

```bash
chmod 755 config
chmod 644 config/*.php
```

### 6. Akses Aplikasi

Default login:
- **Admin**
  - Username: `admin`
  - Password: `admin123`

- **Customer** (dibuat otomatis saat admin menambah pelanggan)
  - Username: sesuai yang dibuat
  - Password default: `12345`

## Struktur Folder

```
/
├── admin/              # Halaman admin
│   ├── index.php       # Dashboard
│   ├── customers.php   # Kelola pelanggan
│   ├── products.php    # Kelola barang
│   ├── transactions.php # Kelola transaksi
│   └── payments.php    # Monitoring pembayaran
├── customer/           # Halaman pelanggan
│   ├── index.php       # Dashboard pelanggan
│   └── payment.php     # Halaman pembayaran
├── api/               # API endpoints
│   ├── create_payment.php  # Buat payment link
│   └── check_payment.php   # Cek status pembayaran
├── config/            # Konfigurasi
│   ├── database.php   # Config database
│   └── xendit.php     # Config Xendit
├── includes/          # File include
│   ├── auth.php       # Sistem autentikasi
│   ├── functions.php  # Helper functions
│   ├── header.php     # Layout header
│   └── footer.php     # Layout footer
├── assets/            # Asset files
│   ├── css/
│   └── js/
├── login.php          # Halaman login
├── logout.php         # Logout
└── database.sql       # SQL dump
```

## Cara Penggunaan

### Untuk Admin

1. **Login** sebagai admin
2. **Tambah Pelanggan**
   - Masuk ke menu "Pelanggan"
   - Klik "Tambah Pelanggan"
   - Isi form lengkap
   - Sistem akan otomatis membuat akun login untuk pelanggan
3. **Tambah Barang**
   - Masuk ke menu "Barang"
   - Klik "Tambah Barang"
   - Isi detail barang dan harga modal (opsional)
4. **Buat Transaksi Cicilan**
   - Masuk ke menu "Transaksi"
   - Klik "Buat Transaksi Baru"
   - Pilih pelanggan dan barang
   - **Input margin secara manual** (misal: modal 10jt, margin 2jt)
   - Pilih tenor (6, 12, 18, 24, 36 bulan)
   - Sistem akan otomatis menghitung angsuran per bulan
5. **Monitor Pembayaran**
   - Masuk ke menu "Pembayaran"
   - Lihat status semua pembayaran
   - Filter berdasarkan status

### Untuk Pelanggan

1. **Login** menggunakan username/password yang diberikan admin
2. **Lihat Cicilan Aktif** di dashboard
3. **Bayar Cicilan**
   - Klik "Bayar Cicilan" pada cicilan yang aktif
   - Pilih nominal (minimal 1x angsuran, maksimal sisa hutang)
   - Atau gunakan tombol quick:
     - 1x Angsuran
     - 2x Angsuran
     - 3x Angsuran
     - Lunas (bayar semua sisa hutang)
   - Klik "Buat Link Pembayaran"
4. **Proses Pembayaran**
   - Sistem akan generate Xendit payment link
   - Klik "Bayar Sekarang" untuk membuka link
   - Pilih metode pembayaran (VA, E-Wallet, dll)
   - Selesaikan pembayaran
5. **Update Status**
   - Setelah bayar, kembali ke halaman pembayaran
   - Klik tombol "Cek Status" untuk update status di sistem
   - Status akan berubah menjadi "Success" jika pembayaran berhasil

## Akad Murabahah

Sistem ini menggunakan akad **Murabahah** (jual beli dengan margin keuntungan):

- Harga modal + margin tetap = Total harga
- Total harga dibagi tenor = Angsuran per bulan
- **Tidak ada bunga/denda keterlambatan** (sesuai syariah)
- Pembayaran lebih akan langsung mengurangi total hutang

Contoh:
- Harga Modal: Rp 10.000.000
- Margin: Rp 2.000.000
- Total Harga: Rp 12.000.000
- Tenor: 12 bulan
- Angsuran/bulan: Rp 1.000.000

## Catatan Penting

### Xendit Webhook (Opsional)
Saat ini sistem menggunakan **manual check** melalui button "Cek Status". Jika ingin menggunakan webhook otomatis:

1. Setup webhook URL di Xendit Dashboard: `https://yourdomain.com/api/webhook.php`
2. Buat file `api/webhook.php` untuk handle callback otomatis
3. Verifikasi webhook token

### Payment Link Validity
- Payment link Xendit berlaku selama **24 jam**
- Setelah expired, customer harus buat payment link baru

### Security
- Ganti password default admin setelah instalasi pertama
- Ganti password default customer setelah login pertama
- Gunakan HTTPS untuk production
- Jangan expose API key di repository public

## Troubleshooting

### Payment Link Tidak Terbuat
- Cek API Key Xendit valid
- Cek email customer (harus valid atau gunakan default)
- Cek error log PHP

### Status Pembayaran Tidak Update
- Pastikan Xendit invoice ID benar
- Cek koneksi internet server
- Manual check dengan button "Cek Status"

### Error Database Connection
- Cek credentials database di `config/database.php`
- Pastikan MySQL service running
- Cek user database memiliki permission

## Support

Untuk bantuan dan informasi lebih lanjut:
- Email: support@koperasi.com
- Documentation: [Link ke docs]

## License

Copyright © 2025 Koperasi Syariah Online
