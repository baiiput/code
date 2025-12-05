# 🚀 Enhancement Website Penawaran - Dokumentasi

## 📋 Overview

Enhancement ini menambahkan fitur-fitur baru pada website penawaran untuk meningkatkan produktivitas dan manajemen data:

### ✨ Fitur Baru:
1. **Customer Database** - Menyimpan dan auto-fill data pelanggan
2. **Company Profiles** - Multiple company profiles dengan header information
3. **Penawaran History & Tracking** - Log dan tracking status semua penawaran
4. **Version & Revision System** - Track perubahan dan revisi penawaran
5. **User Management** - Multi-user dengan authentication
6. **Export to PDF** - Generate PDF dari penawaran (menggunakan browser print)

---

## 🔧 Instalasi

### 1. Database Migration

Jalankan salah satu SQL file berikut:

**Opsi A: Update Database yang Sudah Ada**
```bash
mysql -u db_offer -p db_offer < db_migration_enhancement.sql
```

**Opsi B: Import Database Baru Lengkap**
```bash
mysql -u db_offer -p db_offer < db_offer_enhanced.sql
```

### 2. Struktur File

Pastikan struktur folder sudah benar:
```
/code/
├── api/
│   ├── customers.php          (NEW)
│   ├── company_profiles.php   (NEW)
│   ├── penawaran.php          (NEW)
│   ├── auth.php               (NEW)
│   ├── simple-api.php         (EXISTING)
│   ├── templates.php          (EXISTING)
│   └── config.php             (EXISTING)
├── index.html                 (EXISTING - perlu update)
├── db_migration_enhancement.sql   (NEW)
├── db_offer_enhanced.sql          (NEW)
└── README_ENHANCEMENT.md          (NEW)
```

### 3. Konfigurasi Database

Edit konfigurasi database di setiap file API jika berbeda:
```php
$DB_HOST = 'localhost';
$DB_NAME = 'db_offer';
$DB_USER = 'db_offer';    // GANTI SESUAI HOSTING
$DB_PASS = 'db_offer';    // GANTI SESUAI HOSTING
```

### 4. Default Admin Account

Setelah migration, default admin account:
- **Username**: `admin`
- **Email**: `admin@octolink.id`
- **Password**: `admin123`

⚠️ **PENTING**: Segera ganti password default setelah login pertama kali!

---

## 📚 API Documentation

### Base URL
```
http://your-domain.com/api/
```

### Authentication
Untuk endpoint yang memerlukan authentication, gunakan header:
```
Authorization: Bearer {session_token}
```

---

## 🔐 Authentication API (`auth.php`)

### 1. Login
```http
POST /api/auth.php?action=login
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "sessionToken": "abc123...",
    "expiresAt": "2025-12-12 10:00:00",
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@octolink.id",
      "fullName": "Administrator",
      "role": "admin"
    },
    "message": "Login successful"
  }
}
```

### 2. Logout
```http
POST /api/auth.php?action=logout
Authorization: Bearer {session_token}
```

### 3. Verify Session
```http
POST /api/auth.php?action=verify
Content-Type: application/json

{
  "sessionToken": "abc123..."
}
```

### 4. Change Password
```http
POST /api/auth.php?action=change_password
Content-Type: application/json

{
  "sessionToken": "abc123...",
  "currentPassword": "admin123",
  "newPassword": "newpassword123"
}
```

### 5. List Users (Admin Only)
```http
GET /api/auth.php?action=list_users
Authorization: Bearer {admin_session_token}
```

### 6. Create User (Admin Only)
```http
POST /api/auth.php?action=create_user
Authorization: Bearer {admin_session_token}
Content-Type: application/json

{
  "username": "sales1",
  "email": "sales1@octolink.id",
  "password": "password123",
  "fullName": "Sales User",
  "role": "sales",
  "isActive": true
}
```

**User Roles:**
- `admin` - Full access
- `manager` - Manager access
- `sales` - Sales access
- `viewer` - View only

---

## 👥 Customer Management API (`customers.php`)

### 1. List Customers
```http
GET /api/customers.php?action=list&search=hideout&limit=50&offset=0
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "customers": [...],
    "total": 10,
    "limit": 50,
    "offset": 0
  }
}
```

### 2. Get Single Customer
```http
GET /api/customers.php?action=get&id=1
```

### 3. Search (Autocomplete)
```http
GET /api/customers.php?action=search&q=hide
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "nama": "Hideout",
      "alamat": "-",
      "telepon": "-",
      "email": "-",
      "contactPerson": "",
      "companyType": "company"
    }
  ]
}
```

### 4. Create Customer
```http
POST /api/customers.php?action=create
Content-Type: application/json

{
  "nama": "PT. Example Indonesia",
  "alamat": "Jl. Contoh No. 123",
  "telepon": "081234567890",
  "email": "info@example.com",
  "contactPerson": "Budi Santoso",
  "companyType": "company",
  "notes": "VIP Customer"
}
```

### 5. Update Customer
```http
POST /api/customers.php?action=update&id=1
Content-Type: application/json

{
  "nama": "PT. Example Indonesia (Updated)",
  "alamat": "Jl. Contoh No. 456",
  ...
}
```

### 6. Delete Customer
```http
POST /api/customers.php?action=delete&id=1
```

**Note:** Customer tidak bisa dihapus jika sudah digunakan di penawaran.

---

## 🏢 Company Profiles API (`company_profiles.php`)

### 1. List Company Profiles
```http
GET /api/company_profiles.php?action=list&active_only=true
```

### 2. Get Default Profile
```http
GET /api/company_profiles.php?action=get_default
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "namaPerusahaan": "Octolink.ID",
    "alamat": "Kembangarum Area Semarang Barat",
    "telepon": "08567890902",
    "email": "info.octolink@gmail.com",
    "website": "",
    "logoUrl": "",
    "isDefault": true,
    "isActive": true
  }
}
```

### 3. Create Company Profile
```http
POST /api/company_profiles.php?action=create
Content-Type: application/json

{
  "namaPerusahaan": "Octolink Cabang Jakarta",
  "alamat": "Jakarta Selatan",
  "telepon": "021-1234567",
  "email": "jakarta@octolink.id",
  "website": "https://octolink.id",
  "logoUrl": "/logo/jakarta.png",
  "isDefault": false,
  "isActive": true
}
```

### 4. Set as Default
```http
POST /api/company_profiles.php?action=set_default&id=2
```

### 5. Update & Delete
Similar to customer API.

---

## 📄 Penawaran History API (`penawaran.php`)

### 1. List Penawaran dengan Filter
```http
GET /api/penawaran.php?action=list&status=sent&customer_id=1&date_from=2025-12-01&date_to=2025-12-31&limit=50
```

**Query Parameters:**
- `status` - Filter by status (draft, sent, approved, rejected, revised)
- `customer_id` - Filter by customer
- `search` - Search by nomor penawaran atau nama pelanggan
- `date_from` - Filter tanggal mulai
- `date_to` - Filter tanggal akhir
- `limit` - Limit hasil (default: 50)
- `offset` - Offset untuk pagination (default: 0)

### 2. Get Single Penawaran
```http
GET /api/penawaran.php?action=get&id=1
```

### 3. Create Penawaran
```http
POST /api/penawaran.php?action=create
Content-Type: application/json

{
  "customerId": 1,
  "companyProfileId": 1,
  "namaPelanggan": "Hideout",
  "alamatPelanggan": "-",
  "teleponPelanggan": "-",
  "emailPelanggan": "-",
  "namaPerusahaan": "Octolink.ID",
  "alamatPerusahaan": "Kembangarum Area Semarang Barat",
  "teleponPerusahaan": "08567890902",
  "emailPerusahaan": "info.octolink@gmail.com",
  "tanggal": "2025-12-05",
  "berlakuHingga": "2025-12-15",
  "data": {
    "items": [
      {
        "id": 1,
        "nama": "Product A",
        "harga": 100000,
        "jumlah": 2,
        "total": 200000,
        "deskripsi": ["Deskripsi product"]
      }
    ],
    "syarat_ketentuan": "...",
    "gunakanSyaratKetentuan": true
  },
  "diskonPersen": 10,
  "ppnPersen": 11,
  "status": "draft",
  "createdBy": 1
}
```

**Note:** Nomor penawaran akan di-generate otomatis dengan format: `PNW-YYYYMMDD-XXX`

### 4. Update Penawaran
```http
POST /api/penawaran.php?action=update&id=1
Content-Type: application/json

{
  "namaPelanggan": "Updated Name",
  "data": {...},
  "status": "sent"
}
```

### 5. Update Status Only
```http
POST /api/penawaran.php?action=update_status&id=1
Content-Type: application/json

{
  "status": "approved",
  "userId": 1
}
```

**Available Status:**
- `draft` - Draft
- `sent` - Terkirim
- `approved` - Disetujui
- `rejected` - Ditolak
- `revised` - Direvisi

### 6. Get Revisions
```http
GET /api/penawaran.php?action=get_revisions&id=1
```

### 7. Get Statistics
```http
GET /api/penawaran.php?action=stats&date_from=2025-12-01&date_to=2025-12-31
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "byStatus": [
      {
        "status": "draft",
        "count": 5,
        "totalValue": 50000000
      },
      {
        "status": "sent",
        "count": 10,
        "totalValue": 100000000
      }
    ],
    "total": {
      "count": 23,
      "totalValue": 250000000
    },
    "dateRange": {
      "from": "2025-12-01",
      "to": "2025-12-31"
    }
  }
}
```

---

## 🗄️ Database Schema

### Tabel Baru:

#### 1. `customers`
Menyimpan data pelanggan.

#### 2. `company_profiles`
Menyimpan profil perusahaan (support multiple profiles).

#### 3. `users`
User management dengan role-based access.

#### 4. `penawaran_history`
Log semua penawaran yang dibuat.
- Memiliki relasi ke `customers` dan `company_profiles`
- Menyimpan snapshot data customer & company saat penawaran dibuat
- Status tracking dengan timestamp
- Support versioning

#### 5. `penawaran_revisions`
Log perubahan/revisi penawaran untuk audit trail.

#### 6. `user_sessions`
Menyimpan session token untuk authentication.

#### 7. `activity_logs`
Log semua aktivitas penting (optional, automated via triggers).

### Views:
- `v_penawaran_list` - View untuk list penawaran dengan join
- `v_penawaran_revisions_list` - View untuk list revisi

### Stored Procedures:
- `sp_generate_nomor_penawaran` - Generate nomor penawaran otomatis

### Triggers:
- Auto-logging ke `activity_logs`
- Auto-ensure single default company profile

---

## 🎨 Frontend Integration Guide

### 1. Customer Autocomplete Example
```javascript
// Search customer saat user mengetik
async function searchCustomer(query) {
  const response = await fetch(`/api/customers.php?action=search&q=${query}`);
  const result = await response.json();

  if (result.status === 'success') {
    return result.data; // Array of customers
  }
}

// Auto-fill form saat customer dipilih
function selectCustomer(customer) {
  document.getElementById('nama_pelanggan').value = customer.nama;
  document.getElementById('alamat_pelanggan').value = customer.alamat;
  document.getElementById('telepon').value = customer.telepon;
  document.getElementById('email').value = customer.email;
}
```

### 2. Company Profile Selection
```javascript
// Load company profiles untuk dropdown
async function loadCompanyProfiles() {
  const response = await fetch('/api/company_profiles.php?action=list&active_only=true');
  const result = await response.json();

  const select = document.getElementById('company_profile');
  result.data.forEach(profile => {
    const option = document.createElement('option');
    option.value = profile.id;
    option.text = profile.namaPerusahaan;
    if (profile.isDefault) option.selected = true;
    select.add(option);
  });
}

// Auto-fill company info saat profile dipilih
async function selectCompanyProfile(profileId) {
  const response = await fetch(`/api/company_profiles.php?action=get&id=${profileId}`);
  const result = await response.json();

  if (result.status === 'success') {
    const profile = result.data;
    document.getElementById('nama_perusahaan').value = profile.namaPerusahaan;
    document.getElementById('alamat_perusahaan').value = profile.alamat;
    // ... dst
  }
}
```

### 3. Save to History
```javascript
// Simpan penawaran ke history (bukan template)
async function savePenawaran(data) {
  const response = await fetch('/api/penawaran.php?action=create', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  });

  const result = await response.json();

  if (result.status === 'success') {
    alert('Penawaran berhasil disimpan dengan nomor: ' + result.data.nomorPenawaran);
  }
}
```

### 4. Authentication Example
```javascript
// Login
async function login(username, password) {
  const response = await fetch('/api/auth.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });

  const result = await response.json();

  if (result.status === 'success') {
    // Simpan session token
    localStorage.setItem('sessionToken', result.data.sessionToken);
    localStorage.setItem('user', JSON.stringify(result.data.user));
  }
}

// Verify session on page load
async function checkAuth() {
  const token = localStorage.getItem('sessionToken');
  if (!token) {
    // Redirect ke login
    window.location.href = '/login.html';
    return;
  }

  const response = await fetch('/api/auth.php?action=verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sessionToken: token })
  });

  const result = await response.json();

  if (result.status !== 'success') {
    // Session invalid, redirect ke login
    localStorage.clear();
    window.location.href = '/login.html';
  }
}
```

---

## 📊 Export to PDF

### Menggunakan Browser Print
File index.html sudah memiliki CSS print media queries. Untuk export to PDF:

1. Buka penawaran
2. Klik `Ctrl+P` atau menu Print
3. Pilih "Save as PDF"
4. Selesai!

### CSS Print Media Query (sudah ada di index.html)
```css
@media print {
  /* Hide form elements */
  .form-section,
  .items-section button,
  .no-print {
    display: none !important;
  }

  /* Print-specific styling */
  .preview-section {
    display: block !important;
  }
}
```

---

## 🔒 Security Best Practices

1. **Password Hashing**
   - Semua password di-hash menggunakan `password_hash()` PHP
   - Menggunakan bcrypt algorithm (PASSWORD_DEFAULT)

2. **SQL Injection Prevention**
   - Semua query menggunakan prepared statements
   - Parameter di-sanitize dengan PDO

3. **Session Management**
   - Session token di-generate menggunakan `random_bytes(32)`
   - Token expire dalam 7 hari
   - IP address dan user agent di-log

4. **Access Control**
   - Role-based access (admin, manager, sales, viewer)
   - Authorization header required untuk endpoint tertentu

5. **Input Validation**
   - Semua input di-validate sebelum diproses
   - JSON validation

### Recommendations:
- ✅ Ganti password default admin
- ✅ Gunakan HTTPS di production
- ✅ Set proper file permissions (755 untuk folder, 644 untuk file)
- ✅ Backup database secara berkala
- ✅ Monitor activity logs

---

## 🚀 Quick Start Checklist

- [ ] Import database migration
- [ ] Update konfigurasi database di API files
- [ ] Login dengan admin default
- [ ] Ganti password admin
- [ ] Buat user baru jika diperlukan
- [ ] Tambah company profiles
- [ ] Import customer data (atau buat manual)
- [ ] Update frontend untuk integrasi API baru
- [ ] Test semua fitur
- [ ] Backup database

---

## 📞 Support & Troubleshooting

### Common Issues:

**1. Database Connection Failed**
- Pastikan MySQL service berjalan
- Cek username/password database
- Cek nama database sudah dibuat

**2. Permission Denied**
- Cek file permissions
- Pastikan PHP memiliki akses write ke folder yang diperlukan

**3. Session Invalid**
- Clear browser localStorage
- Login ulang

**4. Customer/Company Cannot Delete**
- Entity sudah digunakan di penawaran
- Ini adalah fitur keamanan data (foreign key constraint)

---

## 📝 Changelog

### Version 2.0 - Enhancement (2025-12-05)

#### Added:
- ✅ Customer Database Management
- ✅ Company Profiles Management
- ✅ Penawaran History & Tracking System
- ✅ Version & Revision System
- ✅ User Management & Authentication
- ✅ Role-based Access Control
- ✅ Activity Logging (via triggers)
- ✅ Statistics & Analytics API
- ✅ Comprehensive API Documentation

#### Improved:
- Database structure dengan relational design
- Security dengan password hashing & session management
- Data integrity dengan foreign key constraints

---

## 🎯 Roadmap (Future Enhancements)

Fitur yang bisa ditambahkan di masa depan:
- [ ] Email notification system
- [ ] WhatsApp integration untuk kirim penawaran
- [ ] Dashboard analytics dengan charts
- [ ] Product master database
- [ ] Approval workflow
- [ ] Advanced PDF customization
- [ ] Mobile app
- [ ] API rate limiting
- [ ] Two-factor authentication

---

## 📄 License

Proprietary - Octolink.ID

---

**Developed by: Claude AI Assistant**
**Date: December 5, 2025**
**Version: 2.0**
