# 🏢 Warehouse Management System

Sistem manajemen warehouse multi-gudang dengan fitur lengkap untuk mengelola stok, transaksi, keuangan, dan laporan.

## ✨ Fitur Utama

- 📦 **Multi-Warehouse**: Kelola multiple warehouse dengan stok terpisah
- 🔄 **Stock Management**: Stock In, Stock Out, Adjustment, Transfer
- 💰 **Financial Management**: Tracking saldo & transaksi keuangan
- 📊 **Comprehensive Reports**: Laporan stok, distribusi, dan keuangan
- 👥 **User Management**: 5 role dengan permission berbeda
- 📝 **Activity Logs**: Tracking semua aktivitas user
- 🎨 **Modern UI**: Responsive design dengan dark/light theme

## 🚀 Quick Start

### 1. Setup Database

```bash
# Buat database
mysql -u root -p -e "CREATE DATABASE warehouse"

# Import schema lengkap
mysql -u root -p warehouse < database_schema_complete.sql
```

### 2. Konfigurasi

Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'warehouse');
define('DB_PASS', 'your_password');
define('DB_NAME', 'warehouse');
```

### 3. Login

- URL: `http://localhost/warehouse`
- Username: `admin`
- Password: `admin123`

⚠️ **Ganti password admin setelah login pertama!**

## 📚 Dokumentasi Lengkap

Lihat **[DOCUMENTATION.md](DOCUMENTATION.md)** untuk:
- Setup warehouse baru
- Cara reset data
- Struktur database lengkap
- Role & permission
- Troubleshooting

## 🔄 Reset Data

### Reset Transaksi Only (Master Data Tetap Ada)
```bash
mysql -u warehouse -p warehouse < reset_transactions_only.sql
```

### Reset Total (Semua Data)
```bash
# BACKUP DULU!
mysqldump -u warehouse -p warehouse > backup.sql

# Reset total
mysql -u warehouse -p warehouse < reset_all_data.sql
```

## 👥 Role & Access

| Role | Dashboard | Master Data | Transaksi | Keuangan | Laporan | Users | Activity Logs |
|------|-----------|-------------|-----------|----------|---------|-------|---------------|
| **Admin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Semua |
| **Manager** | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ Non-admin |
| **Staff Warehouse** | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ Sendiri |
| **Staff Keuangan** | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ | ✅ Sendiri |
| **Cabang** | ✅ | ❌ | ✅ Distribusi | ❌ | ❌ | ❌ | ✅ Sendiri |

## 📊 Struktur Database

### Master Data
- `categories` - Kategori barang
- `items` - Master barang
- `suppliers` - Data supplier
- `branches` - Data cabang
- `warehouses` - Data warehouse

### Inventory
- `warehouse_items` - Stok per warehouse

### Transaksi
- `stock_in` + detail - Pembelian
- `stock_out` + detail - Distribusi
- `stock_adjustment` - Opname
- `stock_transfers` + detail - Transfer

### Keuangan
- `warehouse_balance` - Saldo
- `financial_transactions` - Log transaksi

### Users & Logs
- `users` - Data user
- `activity_logs` - Log aktivitas

## 🔧 Tech Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **UI Framework**: Bootstrap Icons, Font Awesome
- **Architecture**: MVC Pattern

## 📝 File Penting

```
/
├── config.php                          # Konfigurasi database
├── database_schema_complete.sql        # Schema database lengkap
├── migration_add_manager_role.sql      # Migration untuk role manager
├── migration_activity_logs.sql         # Migration untuk activity logs
├── reset_transactions_only.sql         # Reset transaksi only
├── reset_all_data.sql                  # Reset total
├── DOCUMENTATION.md                    # Dokumentasi lengkap
├── README.md                           # File ini
│
├── index.php                           # Dashboard
├── login.php                           # Halaman login
├── logout.php                          # Logout handler
│
├── items.php                           # Master barang
├── categories.php                      # Master kategori
├── suppliers.php                       # Master supplier
├── branches.php                        # Master cabang
├── warehouses.php                      # Master warehouse
│
├── stock_in.php                        # Transaksi pembelian
├── stock_out.php                       # Transaksi distribusi
├── stock_adjustment.php                # Opname stok
├── stock_transfer.php                  # Transfer antar warehouse
│
├── finance.php                         # Keuangan
├── reports.php                         # Laporan
├── activity_logs.php                   # Activity logs
├── users.php                           # User management
│
├── includes/
│   ├── header.php                      # Header & navigation
│   └── footer.php                      # Footer
│
└── assets/
    └── css/
        └── common.css                  # Styling
```

## 🔐 Security Features

- ✅ Password hashing (bcrypt)
- ✅ Prepared statements (SQL Injection protection)
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Input sanitization
- ✅ Activity logging

## 📈 Reporting Features

1. **Stock Report**
   - Per warehouse breakdown
   - Grouped view (expandable)
   - Stock value calculation
   - Low stock alerts

2. **Distribution Report**
   - Warehouse to branch tracking
   - Filter by warehouse, branch, date
   - Summary statistics

3. **Financial Report**
   - Transaction history
   - Balance tracking
   - Debit/Credit details

4. **Activity Logs**
   - User activity tracking
   - Filter by user, action, module, date
   - IP address & user agent logging

## ⚡ Performance

- AJAX-based dynamic loading
- Indexed database queries
- Optimized SQL joins
- Cached calculations
- Limit queries to prevent overload

## 🐛 Known Issues & Fixes

Sudah diperbaiki di versi terbaru:
- ✅ Dashboard stock calculation (pakai warehouse_items)
- ✅ User edit bug (undefined username)
- ✅ Role ENUM untuk manager
- ✅ Stock report duplicate items (grouped view)

## 🎯 Roadmap

- [ ] Export reports to Excel/PDF
- [ ] Email notifications
- [ ] Barcode scanning
- [ ] API REST untuk mobile app
- [ ] Advanced analytics dashboard

## 📞 Support

Lihat **DOCUMENTATION.md** untuk troubleshooting dan FAQ.

## 📄 License

Proprietary - All rights reserved

---

**Version:** 2.0
**Last Update:** 2024
**Status:** Production Ready ✅
