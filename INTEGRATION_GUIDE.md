# 🔗 Panduan Integrasi Frontend dengan Backend API

## 📋 Overview

Panduan ini menjelaskan cara mengintegrasikan `index.html` (form penawaran yang sudah ada) dengan backend API yang baru dibuat.

---

## ✅ Yang Sudah Dibuat

### 1. **login.html**
- Halaman login dengan authentication
- Session management
- Auto-redirect jika sudah login

### 2. **dashboard.html**
- Dashboard utama setelah login
- Statistik penawaran real-time
- Menu navigasi ke semua fitur
- User info display

### 3. **Backend APIs**
- `api/auth.php` - Authentication
- `api/customers.php` - Customer management
- `api/company_profiles.php` - Company profiles
- `api/penawaran.php` - Penawaran history & tracking

---

## 🔧 Integrasi index.html dengan Backend API

### Perubahan yang Perlu Ditambahkan ke index.html:

#### 1. **Tambahkan Auth Check di Awal**

Tambahkan script ini di bagian atas `<script>` section:

```javascript
// ===== AUTH CHECK =====
const API_BASE = '/api';
let currentUser = null;

// Check authentication
window.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    loadCompanyProfiles();
});

async function checkAuth() {
    const sessionToken = localStorage.getItem('sessionToken');
    const userStr = localStorage.getItem('user');

    if (!sessionToken || !userStr) {
        // Redirect to login if not authenticated
        window.location.href = 'login.html';
        return;
    }

    try {
        currentUser = JSON.parse(userStr);

        // Verify session
        const response = await fetch(`${API_BASE}/auth.php?action=verify`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sessionToken })
        });

        const result = await response.json();

        if (result.status !== 'success') {
            localStorage.clear();
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Auth error:', error);
        localStorage.clear();
        window.location.href = 'login.html';
    }
}
```

#### 2. **Tambahkan Company Profile Dropdown**

Di dalam form section (setelah input nama perusahaan), tambahkan:

```html
<!-- Company Profile Selection -->
<div class="form-group">
    <label>
        <i class="fas fa-building"></i> Pilih Company Profile (Opsional)
    </label>
    <select id="companyProfileSelect" class="form-control" onchange="selectCompanyProfile()">
        <option value="">-- Pilih Company Profile --</option>
    </select>
    <small style="color: #666;">Pilih profile untuk auto-fill data perusahaan</small>
</div>
```

JavaScript untuk load company profiles:

```javascript
// Load company profiles
async function loadCompanyProfiles() {
    try {
        const response = await fetch(`${API_BASE}/company_profiles.php?action=list&active_only=true`);
        const result = await response.json();

        if (result.status === 'success') {
            const select = document.getElementById('companyProfileSelect');
            select.innerHTML = '<option value="">-- Pilih Company Profile --</option>';

            result.data.forEach(profile => {
                const option = document.createElement('option');
                option.value = profile.id;
                option.textContent = profile.namaPerusahaan;
                if (profile.isDefault) option.selected = true;
                select.appendChild(option);
            });

            // Auto-select default
            if (select.value) {
                selectCompanyProfile();
            }
        }
    } catch (error) {
        console.error('Error loading company profiles:', error);
    }
}

// Auto-fill company data from selected profile
async function selectCompanyProfile() {
    const profileId = document.getElementById('companyProfileSelect').value;
    if (!profileId) return;

    try {
        const response = await fetch(`${API_BASE}/company_profiles.php?action=get&id=${profileId}`);
        const result = await response.json();

        if (result.status === 'success') {
            const profile = result.data;
            document.getElementById('nama_perusahaan').value = profile.namaPerusahaan;
            document.getElementById('alamat_perusahaan').value = profile.alamat || '';
            document.getElementById('telepon_perusahaan').value = profile.telepon || '';
            document.getElementById('email_perusahaan').value = profile.email || '';
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}
```

#### 3. **Tambahkan Customer Autocomplete**

Ganti input nama pelanggan dengan autocomplete:

```html
<!-- Customer Name with Autocomplete -->
<div class="form-group">
    <label><i class="fas fa-user"></i> Nama Pelanggan</label>
    <div style="position: relative;">
        <input type="text"
               id="nama_pelanggan"
               class="form-control"
               placeholder="Ketik nama pelanggan..."
               oninput="searchCustomer()"
               autocomplete="off">
        <input type="hidden" id="customer_id">

        <!-- Autocomplete Dropdown -->
        <div id="customerAutocomplete" class="autocomplete-dropdown" style="display:none;">
            <!-- Results will be inserted here -->
        </div>
    </div>
</div>
```

CSS untuk autocomplete:

```css
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.autocomplete-item {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.autocomplete-item:hover {
    background: #f0f8ff;
}

.autocomplete-item strong {
    color: #2980b9;
}
```

JavaScript untuk autocomplete:

```javascript
let searchTimeout = null;

async function searchCustomer() {
    const query = document.getElementById('nama_pelanggan').value.trim();
    const dropdown = document.getElementById('customerAutocomplete');

    if (query.length < 2) {
        dropdown.style.display = 'none';
        return;
    }

    // Debounce search
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`${API_BASE}/customers.php?action=search&q=${encodeURIComponent(query)}`);
            const result = await response.json();

            if (result.status === 'success' && result.data.length > 0) {
                dropdown.innerHTML = result.data.map(customer => `
                    <div class="autocomplete-item" onclick="selectCustomer(${customer.id})">
                        <strong>${customer.nama}</strong><br>
                        <small>${customer.alamat || '-'} | ${customer.telepon || '-'}</small>
                    </div>
                `).join('');
                dropdown.style.display = 'block';
            } else {
                dropdown.innerHTML = '<div class="autocomplete-item">Tidak ada hasil</div>';
                dropdown.style.display = 'block';
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }, 300);
}

async function selectCustomer(customerId) {
    try {
        const response = await fetch(`${API_BASE}/customers.php?action=get&id=${customerId}`);
        const result = await response.json();

        if (result.status === 'success') {
            const customer = result.data;

            // Fill form
            document.getElementById('customer_id').value = customer.id;
            document.getElementById('nama_pelanggan').value = customer.nama;
            document.getElementById('alamat_pelanggan').value = customer.alamat || '';
            document.getElementById('telepon').value = customer.telepon || '';
            document.getElementById('email').value = customer.email || '';

            // Hide dropdown
            document.getElementById('customerAutocomplete').style.display = 'none';
        }
    } catch (error) {
        console.error('Error selecting customer:', error);
    }
}

// Hide dropdown when clicking outside
document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('customerAutocomplete');
    const input = document.getElementById('nama_pelanggan');
    if (e.target !== input && e.target !== dropdown) {
        dropdown.style.display = 'none';
    }
});
```

#### 4. **Tambahkan Tombol "Simpan ke History"**

Di section tombol, tambahkan tombol baru:

```html
<!-- Action Buttons -->
<div style="display: flex; gap: 10px; margin-top: 20px;">
    <button type="button" class="btn btn-primary" onclick="savePenawaranDraft()">
        <i class="fas fa-save"></i> Simpan Draft
    </button>

    <button type="button" class="btn btn-success" onclick="savePenawaranAndSend()">
        <i class="fas fa-paper-plane"></i> Simpan & Kirim
    </button>

    <button type="button" class="btn btn-secondary" onclick="saveTemplate()">
        <i class="fas fa-bookmark"></i> Simpan Template
    </button>
</div>
```

CSS untuk buttons:

```css
.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
```

JavaScript untuk save:

```javascript
// Save penawaran as draft
async function savePenawaranDraft() {
    await savePenawaran('draft');
}

// Save and send penawaran
async function savePenawaranAndSend() {
    if (!confirm('Apakah Anda yakin ingin menyimpan dan mengirim penawaran ini?')) return;
    await savePenawaran('sent');
}

// Main save function
async function savePenawaran(status = 'draft') {
    try {
        // Collect all form data
        const data = {
            customerId: document.getElementById('customer_id').value || null,
            companyProfileId: document.getElementById('companyProfileSelect').value || null,
            namaPelanggan: document.getElementById('nama_pelanggan').value,
            alamatPelanggan: document.getElementById('alamat_pelanggan').value,
            teleponPelanggan: document.getElementById('telepon').value,
            emailPelanggan: document.getElementById('email').value,
            namaPerusahaan: document.getElementById('nama_perusahaan').value,
            alamatPerusahaan: document.getElementById('alamat_perusahaan').value,
            teleponPerusahaan: document.getElementById('telepon_perusahaan').value,
            emailPerusahaan: document.getElementById('email_perusahaan').value,
            tanggal: document.getElementById('tanggal').value,
            berlakuHingga: document.getElementById('berlaku_hingga').value,
            nomorPenawaran: document.getElementById('nomor_penawaran').value,
            data: {
                items: items, // Your existing items array
                syarat_ketentuan: document.getElementById('syarat_ketentuan')?.value || '',
                gunakanSyaratKetentuan: document.getElementById('gunakanSyaratKetentuan')?.checked || false,
                gunakanDeskripsi: document.getElementById('gunakanDeskripsi')?.checked || false
            },
            diskonPersen: parseFloat(document.getElementById('diskon_persen')?.value || 0),
            ppnPersen: parseFloat(document.getElementById('ppn_persen')?.value || 0),
            status: status,
            createdBy: currentUser?.id || null
        };

        // Validate
        if (!data.namaPelanggan || !data.tanggal || !data.data.items || data.data.items.length === 0) {
            alert('Mohon isi data pelanggan, tanggal, dan minimal 1 item!');
            return;
        }

        // Save to API
        const response = await fetch(`${API_BASE}/penawaran.php?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert(`Penawaran berhasil disimpan dengan nomor: ${result.data.nomorPenawaran}`);

            // Ask if want to go to history or create new
            if (confirm('Penawaran berhasil disimpan! Apakah ingin melihat history penawaran?')) {
                window.location.href = 'history.html';
            } else {
                // Reset form for new penawaran
                if (confirm('Buat penawaran baru?')) {
                    location.reload();
                }
            }
        } else {
            alert('Error: ' + (result.message || 'Gagal menyimpan penawaran'));
        }
    } catch (error) {
        console.error('Save error:', error);
        alert('Terjadi kesalahan saat menyimpan penawaran');
    }
}

// Save as template (existing function - keep as is)
async function saveTemplate() {
    // Your existing save template code
}
```

#### 5. **Tambahkan Navigation Bar**

Di bagian paling atas HTML, sebelum form section:

```html
<!-- Navigation Bar -->
<div style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 15px 30px; margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <a href="dashboard.html" style="color: white; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <span style="font-weight: 700; font-size: 1.2em;">Form Penawaran</span>
        </div>
        <div id="userInfo" style="text-align: right; font-size: 0.9em;">
            <div id="userName"></div>
        </div>
    </div>
</div>
```

JavaScript untuk display user info:

```javascript
// Display user info in navbar
function displayUserInfo() {
    if (currentUser) {
        document.getElementById('userName').textContent = currentUser.fullName;
    }
}

// Call after auth check
window.addEventListener('DOMContentLoaded', () => {
    checkAuth().then(() => {
        displayUserInfo();
    });
});
```

---

## 📄 File Tambahan yang Perlu Dibuat

### 1. **history.html** - History & Tracking Penawaran

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>History Penawaran</title>
    <!-- Similar structure to dashboard.html -->
</head>
<body>
    <!-- Table untuk list penawaran dengan filter -->
    <!-- Status badges (draft, sent, approved, rejected) -->
    <!-- Action buttons (view, edit, update status, print) -->
</body>
</html>
```

**Fitur yang diperlukan:**
- Table list penawaran
- Filter by status, customer, date range
- Search by nomor penawaran
- Update status button
- View detail button
- Print/PDF button

### 2. **customers.html** - Customer Management

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Customer Management</title>
</head>
<body>
    <!-- CRUD customer -->
    <!-- Table list customers -->
    <!-- Add/Edit customer modal -->
    <!-- Search & filter -->
</body>
</html>
```

**Fitur yang diperlukan:**
- Table list customers
- Add customer button & form
- Edit customer button & form
- Delete customer with confirmation
- Search functionality

### 3. **company_profiles.html** - Company Profile Management

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Company Profiles</title>
</head>
<body>
    <!-- CRUD company profiles -->
    <!-- Set default profile -->
    <!-- Logo upload -->
</body>
</html>
```

**Fitur yang diperlukan:**
- List company profiles
- Add/Edit profile form
- Set as default button
- Delete profile with confirmation

---

## 🎯 Checklist Integrasi

- [ ] Tambahkan auth check di index.html
- [ ] Tambahkan navigation bar
- [ ] Implementasi company profile dropdown
- [ ] Implementasi customer autocomplete
- [ ] Tambahkan tombol "Simpan ke History"
- [ ] Implementasi save to history function
- [ ] Test save draft
- [ ] Test save & send
- [ ] Test customer autocomplete
- [ ] Test company profile selection
- [ ] Buat halaman history.html
- [ ] Buat halaman customers.html
- [ ] Buat halaman company_profiles.html
- [ ] Test end-to-end workflow

---

## 🚀 Testing Flow

### 1. **Login Flow:**
1. Buka `login.html`
2. Login dengan credentials (admin/admin123)
3. Verify redirect ke dashboard

### 2. **Create Penawaran Flow:**
1. Dari dashboard, klik "Buat Penawaran Baru"
2. Pilih company profile → verify auto-fill
3. Ketik nama customer → verify autocomplete
4. Pilih customer → verify auto-fill
5. Tambah items
6. Klik "Simpan Draft" → verify tersimpan
7. Verify redirect atau reset form

### 3. **View History Flow:**
1. Dari dashboard, klik "History Penawaran"
2. Verify list penawaran muncul
3. Test filter dan search
4. Update status penawaran
5. View detail penawaran

---

## 📝 Notes

- Semua API sudah ready, tinggal integrate di frontend
- Default admin: `admin` / `admin123` (wajib diganti!)
- Session expire dalam 7 hari
- Nomor penawaran auto-generate dengan format: `PNW-YYYYMMDD-XXX`

---

## 🆘 Troubleshooting

**Problem:** Autocomplete tidak muncul
- **Solution:** Check console untuk errors, pastikan API endpoint benar

**Problem:** Save gagal dengan error 401
- **Solution:** Session expired, logout dan login ulang

**Problem:** Data customer tidak auto-fill
- **Solution:** Check customer_id tersimpan di hidden input

**Problem:** Company profile dropdown kosong
- **Solution:** Pastikan ada minimal 1 company profile di database

---

## 📞 Next Steps

1. Integrate semua perubahan di atas ke `index.html`
2. Buat halaman history.html, customers.html, company_profiles.html
3. Test semua flow
4. Deploy ke production
5. Ganti password admin default!

---

**Happy Coding! 🚀**
