# Multi User Level System

Sistem Koperasi Syariah kini dilengkapi dengan **Multi User Level** untuk kontrol akses yang lebih baik.

## User Levels

### **Level 1 - Super Admin**
**Full Access:** Akses penuh ke semua fitur sistem

**Hak Akses:**
- ✅ Kelola user admin (tambah, edit, hapus user level 1-3)
- ✅ Kelola pelanggan (CRUD)
- ✅ Kelola barang (CRUD)
- ✅ Buat & kelola transaksi cicilan
- ✅ Monitoring pembayaran
- ✅ Lihat semua laporan & statistik
- ✅ Hapus data (pelanggan, barang, transaksi)

**Default Login:**
- Username: `superadmin`
- Password: `admin123`

---

### **Level 2 - Manager**
**Management Access:** Kelola transaksi & data master

**Hak Akses:**
- ✅ Kelola pelanggan (CRUD)
- ✅ Kelola barang (CRUD)
- ✅ Buat & kelola transaksi cicilan
- ✅ Monitoring pembayaran
- ✅ Lihat semua laporan & statistik
- ✅ Hapus data (pelanggan, barang)
- ❌ Tidak bisa kelola user admin

**Default Login:**
- Username: `manager`
- Password: `admin123`

---

### **Level 3 - Staff**
**Operational Access:** Entry data & monitoring

**Hak Akses:**
- ✅ Tambah & edit pelanggan
- ✅ Tambah & edit barang
- ✅ Buat & edit transaksi cicilan
- ✅ Monitoring pembayaran
- ✅ Lihat laporan & statistik
- ❌ **Tidak bisa hapus data apapun**
- ❌ Tidak bisa kelola user admin

**Default Login:**
- Username: `staff`
- Password: `admin123`

---

### **Level 4 - Customer**
**Customer Self-Service:** Lihat & bayar cicilan sendiri

**Hak Akses:**
- ✅ Lihat cicilan aktif & riwayat
- ✅ Bayar cicilan via Xendit
- ✅ Cek status pembayaran
- ❌ Tidak bisa akses halaman admin

**Login:** Dibuat oleh admin saat menambah pelanggan

---

## Fitur Multi User Level

### 1. **User Management (Super Admin Only)**
- Menu khusus **Users** di admin panel
- Buat user dengan level 1-3 (Super Admin, Manager, Staff)
- Edit username, nama lengkap, password, level
- Disable/enable user
- Hapus user (dengan proteksi)

**Proteksi:**
- Tidak bisa hapus akun sendiri
- Minimal 1 Super Admin harus ada
- Tidak bisa disable akun sendiri

### 2. **Permission Control**
Sistem otomatis mengatur akses berdasarkan level:

**Menu Visibility:**
- Menu "Users" hanya muncul untuk Super Admin
- Menu lain muncul untuk semua admin (level 1-3)

**Button/Action Visibility:**
- Button "Hapus" hide untuk Staff (level 3)
- Button "Edit" visible untuk semua admin

**Backend Protection:**
- Permission check di setiap action (tambah, edit, hapus)
- Staff yang coba hapus data akan di-reject dengan error message

### 3. **UI Enhancements**
**Navbar:**
- Tampil nama lengkap & user level
- Dropdown info: Full Name, Username, Level

**Login Page:**
- Info default login untuk semua level
- Password sama untuk testing: `admin123`

**User List:**
- Badge warna berbeda per level:
  - Purple: Super Admin
  - Blue: Manager
  - Green: Staff

---

## Database Changes

### Users Table
```sql
ALTER TABLE users
ADD COLUMN user_level TINYINT(1) NOT NULL DEFAULT 4 COMMENT '1=Super Admin, 2=Manager, 3=Staff, 4=Customer',
ADD COLUMN full_name VARCHAR(255) NULL,
ADD INDEX idx_user_level (user_level);
```

### Transactions Table
```sql
ALTER TABLE transactions
ADD COLUMN created_by INT NULL COMMENT 'User ID yang membuat transaksi',
ADD INDEX idx_created_by (created_by);
```

---

## Migration dari Versi Lama

Jika Anda sudah punya database lama, jalankan migration script:

```bash
mysql -u username -p koperasi_syariah < database_migration_multilevel.sql
```

**Script ini akan:**
1. Tambah kolom `user_level` dan `full_name` ke table `users`
2. Tambah kolom `created_by` ke table `transactions`
3. Update existing admin user menjadi Super Admin (level 1)
4. Update existing customer users menjadi level 4
5. Insert sample manager & staff users

---

## Cara Penggunaan

### Sebagai Super Admin

1. **Login** dengan `superadmin` / `admin123`
2. **Kelola User Admin:**
   - Klik menu "Users"
   - Tambah user baru (Manager/Staff)
   - Set level yang sesuai
   - Berikan username & password
3. **Kelola Data Master:**
   - Sama seperti biasa: Pelanggan, Barang, Transaksi
4. **Full Control:**
   - Bisa hapus data apapun
   - Bisa edit semua data

### Sebagai Manager

1. **Login** dengan `manager` / `admin123`
2. **Kelola Operasional:**
   - Tambah/edit pelanggan & barang
   - Buat transaksi cicilan
   - Monitor pembayaran
3. **Limited Delete:**
   - Bisa hapus pelanggan & barang
   - Bisa batalkan transaksi
4. **No User Management:**
   - Tidak bisa kelola user admin

### Sebagai Staff

1. **Login** dengan `staff` / `admin123`
2. **Entry Data:**
   - Tambah/edit pelanggan & barang
   - Buat/edit transaksi cicilan
   - Monitor pembayaran
3. **No Delete Access:**
   - Button "Hapus" tidak muncul
   - Jika force delete via API → Error
4. **Read-Only untuk data critical:**
   - Bisa lihat semua data
   - Tidak bisa hapus apapun

---

## Security Features

### 1. **Session Management**
- User level disimpan di session
- Auto-check saat akses halaman
- Redirect jika tidak punya akses

### 2. **Permission Middleware**
```php
// Check di setiap halaman admin
requireAdmin();  // Minimal role admin

// Check level tertentu
requireSuperAdmin();  // Hanya Super Admin
requireLevel(USER_LEVEL_MANAGER);  // Minimal Manager

// Check permission
if (hasPermission(USER_LEVEL_STAFF)) {
    // Allow action
}
```

### 3. **UI Protection**
```php
// Hide button untuk Staff
<?php if (!isStaff()): ?>
    <button>Hapus</button>
<?php endif; ?>

// Show menu untuk Super Admin only
<?php if (isSuperAdmin()): ?>
    <a href="/admin/users.php">Users</a>
<?php endif; ?>
```

### 4. **Backend Validation**
```php
// Di setiap action delete
if (isStaff()) {
    setFlashMessage('error', 'Staff tidak memiliki akses untuk menghapus data');
    exit;
}
```

---

## Best Practices

### Untuk Super Admin:
1. **Ganti password default** setelah instalasi
2. **Buat user dengan level sesuai kebutuhan**
3. **Jangan share akun Super Admin**
4. **Backup regular** untuk proteksi data

### Untuk Manager:
1. **Monitor aktivitas Staff** secara berkala
2. **Review transaksi** yang dibuat
3. **Ganti password** secara berkala

### Untuk Staff:
1. **Hati-hati saat entry data** (tidak bisa delete sendiri)
2. **Double check** sebelum save
3. **Minta Manager/Super Admin** jika perlu delete data

---

## Troubleshooting

### Issue: Menu "Users" tidak muncul
**Solusi:** Pastikan login sebagai Super Admin (level 1)

### Issue: Button "Hapus" tidak muncul untuk Manager
**Solusi:** Ini normal jika halaman tersebut restricted untuk Staff only

### Issue: Error "Staff tidak memiliki akses untuk menghapus data"
**Solusi:**
- Staff memang tidak boleh delete data
- Minta Manager/Super Admin untuk delete
- Atau upgrade user level ke Manager

### Issue: Tidak bisa login setelah migration
**Solusi:**
1. Check database: `SELECT * FROM users WHERE username = 'superadmin'`
2. Reset password:
   ```sql
   UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'superadmin';
   ```
   (Password: admin123)

### Issue: Menu tidak sesuai dengan level
**Solusi:**
1. Logout & login ulang
2. Clear browser cache
3. Check session: `var_dump($_SESSION);`

---

## API Functions

### Check Functions
```php
isLoggedIn()          // User sudah login?
isAdmin()             // Role admin?
isCustomer()          // Role customer?
isSuperAdmin()        // Level 1?
isManager()           // Level 2?
isStaff()             // Level 3?
getUserLevel()        // Get user level (1-4)
hasPermission($level) // Punya permission level tertentu?
```

### Require Functions
```php
requireLogin()           // Harus login
requireAdmin()           // Harus role admin
requireCustomer()        // Harus role customer
requireSuperAdmin()      // Harus level 1
requireLevel($level)     // Harus level tertentu
```

### Helper Functions
```php
getUserLevelName($level) // Nama level (Super Admin, Manager, dll)
getCurrentUser()         // Get user info lengkap
```

---

## Production Checklist

- [ ] Ganti semua password default
- [ ] Buat user dengan level sesuai kebutuhan
- [ ] Test akses setiap level
- [ ] Disable user yang tidak aktif
- [ ] Setup backup database regular
- [ ] Review permission periodically
- [ ] Training user tentang level mereka
- [ ] Monitor user activity logs

---

## Future Enhancements

Fitur yang bisa ditambahkan di masa depan:
- [ ] Activity Logs (siapa create/edit/delete data)
- [ ] Role-based dashboard (berbeda per level)
- [ ] Advanced permissions (per menu/feature)
- [ ] User activity reports
- [ ] Email notification untuk perubahan penting
- [ ] 2FA untuk Super Admin
- [ ] Session timeout per level
- [ ] API rate limiting per level

---

## Support

Jika ada pertanyaan tentang Multi User Level system:
- Check dokumentasi ini
- Test dengan user level berbeda
- Review code di `includes/auth.php`
- Check permission di setiap halaman admin

**Remember:** Sistem ini dirancang untuk keamanan dan kontrol akses yang lebih baik. Gunakan dengan bijak! 🔒
