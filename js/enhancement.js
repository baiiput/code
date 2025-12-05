// ===================================================================
// PENAWARAN ENHANCEMENT - INTEGRATION WITH BACKEND API
// Version: 2.0
// ===================================================================

const API_BASE = '/api';
let currentUser = null;
let selectedCustomerId = null;
let selectedCompanyProfileId = null;
let searchTimeout = null;

// ===================================================================
// 1. AUTHENTICATION & INITIALIZATION
// ===================================================================

window.addEventListener('DOMContentLoaded', async () => {
    await checkAuth();
    await loadCompanyProfiles();
    initializeEnhancements();
});

async function checkAuth() {
    const sessionToken = localStorage.getItem('sessionToken');
    const userStr = localStorage.getItem('user');

    // Skip auth check if in demo mode (for testing without backend)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('demo') === 'true') {
        console.log('Running in demo mode - skipping auth');
        return;
    }

    if (!sessionToken || !userStr) {
        if (confirm('Anda belum login. Redirect ke halaman login?')) {
            window.location.href = 'login.html';
        }
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
            if (confirm('Session expired. Redirect ke halaman login?')) {
                window.location.href = 'login.html';
            }
        } else {
            displayUserInfo();
        }
    } catch (error) {
        console.error('Auth error:', error);
        // Continue anyway for offline usage
    }
}

function displayUserInfo() {
    if (currentUser) {
        const userInfoEl = document.getElementById('userInfoNav');
        if (userInfoEl) {
            userInfoEl.innerHTML = `
                <div style="text-align: right; font-size: 0.9em;">
                    <div style="font-weight: 600;">${currentUser.fullName}</div>
                    <div style="opacity: 0.9;">${getRoleLabel(currentUser.role)}</div>
                </div>
            `;
        }
    }
}

function getRoleLabel(role) {
    const roles = {
        'admin': 'Administrator',
        'manager': 'Manager',
        'sales': 'Sales',
        'viewer': 'Viewer'
    };
    return roles[role] || role;
}

// ===================================================================
// 2. COMPANY PROFILE INTEGRATION
// ===================================================================

async function loadCompanyProfiles() {
    try {
        const response = await fetch(`${API_BASE}/company_profiles.php?action=list&active_only=true`);
        const result = await response.json();

        if (result.status === 'success' && result.data.length > 0) {
            const select = document.getElementById('companyProfileSelect');
            if (!select) return;

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
                await selectCompanyProfile();
            }
        }
    } catch (error) {
        console.error('Error loading company profiles:', error);
    }
}

async function selectCompanyProfile() {
    const select = document.getElementById('companyProfileSelect');
    if (!select) return;

    const profileId = select.value;
    if (!profileId) {
        selectedCompanyProfileId = null;
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/company_profiles.php?action=get&id=${profileId}`);
        const result = await response.json();

        if (result.status === 'success') {
            const profile = result.data;
            selectedCompanyProfileId = profile.id;

            // Fill company form
            document.getElementById('nama_perusahaan').value = profile.namaPerusahaan;
            document.getElementById('alamat_perusahaan').value = profile.alamat || '';
            document.getElementById('telepon_perusahaan').value = profile.telepon || '';
            document.getElementById('email_perusahaan').value = profile.email || '';

            showNotification('Company profile loaded: ' + profile.namaPerusahaan, 'success');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showNotification('Error loading company profile', 'error');
    }
}

// ===================================================================
// 3. CUSTOMER AUTOCOMPLETE
// ===================================================================

function setupCustomerAutocomplete() {
    const input = document.getElementById('nama_pelanggan');
    if (!input) return;

    // Add input event for autocomplete
    input.addEventListener('input', searchCustomer);
    input.setAttribute('autocomplete', 'off');

    // Hide dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('customerAutocomplete');
        if (dropdown && e.target !== input && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

async function searchCustomer() {
    const input = document.getElementById('nama_pelanggan');
    const dropdown = document.getElementById('customerAutocomplete');

    if (!input || !dropdown) return;

    const query = input.value.trim();

    if (query.length < 2) {
        dropdown.style.display = 'none';
        selectedCustomerId = null;
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
                        <strong>${escapeHtml(customer.nama)}</strong><br>
                        <small>${escapeHtml(customer.alamat || '-')} | ${escapeHtml(customer.telepon || '-')}</small>
                    </div>
                `).join('');
                dropdown.style.display = 'block';
            } else {
                dropdown.innerHTML = '<div class="autocomplete-item" style="color: #999;">Tidak ada hasil. Ketik enter untuk customer baru.</div>';
                dropdown.style.display = 'block';
                selectedCustomerId = null;
            }
        } catch (error) {
            console.error('Search error:', error);
            dropdown.style.display = 'none';
        }
    }, 300);
}

async function selectCustomer(customerId) {
    try {
        const response = await fetch(`${API_BASE}/customers.php?action=get&id=${customerId}`);
        const result = await response.json();

        if (result.status === 'success') {
            const customer = result.data;
            selectedCustomerId = customer.id;

            // Fill form
            document.getElementById('nama_pelanggan').value = customer.nama;
            document.getElementById('alamat_pelanggan').value = customer.alamat || '';
            document.getElementById('telepon').value = customer.telepon || '';
            document.getElementById('email').value = customer.email || '';

            // Hide dropdown
            const dropdown = document.getElementById('customerAutocomplete');
            if (dropdown) dropdown.style.display = 'none';

            showNotification('Customer loaded: ' + customer.nama, 'success');
        }
    } catch (error) {
        console.error('Error selecting customer:', error);
        showNotification('Error loading customer data', 'error');
    }
}

// Expose to window for onclick
window.selectCustomer = selectCustomer;

// ===================================================================
// 4. SAVE TO HISTORY
// ===================================================================

async function savePenawaranDraft() {
    await savePenawaran('draft');
}

async function savePenawaranAndSend() {
    if (!confirm('Apakah Anda yakin ingin menyimpan dan mengirim penawaran ini?')) return;
    await savePenawaran('sent');
}

async function savePenawaran(status = 'draft') {
    try {
        // Validate items (must be defined from original index.html)
        if (typeof items === 'undefined' || !items || items.length === 0) {
            showNotification('Mohon tambahkan minimal 1 item!', 'error');
            return;
        }

        // Collect form data
        const data = {
            customerId: selectedCustomerId,
            companyProfileId: selectedCompanyProfileId,
            namaPelanggan: document.getElementById('nama_pelanggan').value.trim(),
            alamatPelanggan: document.getElementById('alamat_pelanggan').value.trim(),
            teleponPelanggan: document.getElementById('telepon').value.trim(),
            emailPelanggan: document.getElementById('email').value.trim(),
            namaPerusahaan: document.getElementById('nama_perusahaan').value.trim(),
            alamatPerusahaan: document.getElementById('alamat_perusahaan').value.trim(),
            teleponPerusahaan: document.getElementById('telepon_perusahaan').value.trim(),
            emailPerusahaan: document.getElementById('email_perusahaan').value.trim(),
            tanggal: document.getElementById('tanggal').value,
            berlakuHingga: document.getElementById('berlaku_hingga').value,
            nomorPenawaran: document.getElementById('nomor_penawaran').value.trim(),
            data: {
                items: items,
                syarat_ketentuan: document.getElementById('syarat_ketentuan')?.value || '',
                gunakanSyaratKetentuan: document.getElementById('gunakan_syarat_ketentuan')?.checked || false,
                gunakanDeskripsi: document.getElementById('gunakan_deskripsi_global')?.checked || false
            },
            diskonPersen: parseFloat(document.getElementById('diskon_persen')?.value || 0),
            ppnPersen: parseFloat(document.getElementById('ppn_persen')?.value || 0),
            status: status,
            createdBy: currentUser?.id || null
        };

        // Validate required fields
        if (!data.namaPelanggan || !data.tanggal) {
            showNotification('Mohon isi nama pelanggan dan tanggal!', 'error');
            return;
        }

        // Show loading
        const saveBtn = event?.target;
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        }

        // Save to API
        const response = await fetch(`${API_BASE}/penawaran.php?action=create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.status === 'success') {
            showNotification(`Penawaran berhasil disimpan dengan nomor: ${result.data.nomorPenawaran}`, 'success');

            // Ask user what to do next
            setTimeout(() => {
                if (confirm('Penawaran berhasil disimpan! Apakah ingin melihat history penawaran?')) {
                    window.location.href = 'history.html';
                } else if (confirm('Buat penawaran baru?')) {
                    location.reload();
                }
            }, 1000);
        } else {
            throw new Error(result.message || 'Gagal menyimpan penawaran');
        }
    } catch (error) {
        console.error('Save error:', error);
        showNotification('Error: ' + error.message, 'error');

        // Reset button
        const saveBtn = event?.target;
        if (saveBtn) {
            saveBtn.disabled = false;
            const icon = status === 'draft' ? 'save' : 'paper-plane';
            const text = status === 'draft' ? 'Simpan Draft' : 'Simpan & Kirim';
            saveBtn.innerHTML = `<i class="fas fa-${icon}"></i> ${text}`;
        }
    }
}

// Expose to window
window.savePenawaranDraft = savePenawaranDraft;
window.savePenawaranAndSend = savePenawaranAndSend;

// ===================================================================
// 5. QUICK CREATE CUSTOMER (Bonus Feature)
// ===================================================================

async function quickCreateCustomer() {
    const nama = document.getElementById('nama_pelanggan').value.trim();
    const alamat = document.getElementById('alamat_pelanggan').value.trim();
    const telepon = document.getElementById('telepon').value.trim();
    const email = document.getElementById('email').value.trim();

    if (!nama) {
        showNotification('Nama pelanggan harus diisi untuk membuat customer baru!', 'error');
        return;
    }

    if (!confirm(`Simpan "${nama}" sebagai customer baru?`)) return;

    try {
        const response = await fetch(`${API_BASE}/customers.php?action=create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nama: nama,
                alamat: alamat,
                telepon: telepon,
                email: email,
                companyType: 'company'
            })
        });

        const result = await response.json();

        if (result.status === 'success') {
            selectedCustomerId = result.data.id;
            showNotification('Customer baru berhasil dibuat!', 'success');
        } else {
            throw new Error(result.message || 'Gagal membuat customer');
        }
    } catch (error) {
        console.error('Create customer error:', error);
        showNotification('Error: ' + error.message, 'error');
    }
}

window.quickCreateCustomer = quickCreateCustomer;

// ===================================================================
// 6. UTILITY FUNCTIONS
// ===================================================================

function showNotification(message, type = 'info') {
    // Check if notification system exists in original file
    if (typeof window.showNotification === 'function') {
        window.showNotification(message);
    } else {
        // Fallback to alert
        console.log(`[${type.toUpperCase()}] ${message}`);
        if (type === 'error') {
            alert(message);
        }
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function initializeEnhancements() {
    setupCustomerAutocomplete();
    console.log('✅ Penawaran Enhancement v2.0 loaded successfully!');
    console.log('Features: Customer Autocomplete, Company Profiles, Save to History');
}

// ===================================================================
// 7. NAVIGATION
// ===================================================================

function goToDashboard() {
    if (confirm('Kembali ke dashboard? Perubahan yang belum disimpan akan hilang.')) {
        window.location.href = 'dashboard.html';
    }
}

function logout() {
    if (!confirm('Apakah Anda yakin ingin logout?')) return;

    const sessionToken = localStorage.getItem('sessionToken');
    if (sessionToken) {
        fetch(`${API_BASE}/auth.php?action=logout`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${sessionToken}` }
        }).catch(() => {});
    }

    localStorage.clear();
    window.location.href = 'login.html';
}

window.goToDashboard = goToDashboard;
window.logout = logout;

console.log('🚀 Enhancement.js loaded!');
