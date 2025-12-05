/**
 * Starlink Finance - Main Application JavaScript
 */

// Global state
const state = {
    transactions: [],
    currentPage: 1,
    totalPages: 1,
    limit: 20,
    showAll: false,
    filters: {
        search: '',
        startDate: '',
        endDate: '',
        type: '',
        month: ''
    },
    editingId: null,
    deleteId: null,
    editingUserId: null,
    // Auth state
    isLoggedIn: false,
    user: null,
    canAdd: false,
    canEdit: false,
    // Submit state
    isSubmitting: false
};

// DOM Elements
const elements = {
    // Summary
    totalPemasukan: document.getElementById('totalPemasukan'),
    totalPengeluaran: document.getElementById('totalPengeluaran'),
    totalSaldo: document.getElementById('totalSaldo'),
    totalTransaksi: document.getElementById('totalTransaksi'),
    totalTransIn: document.getElementById('totalTransIn'),
    totalTransOut: document.getElementById('totalTransOut'),

    // Monthly Summary
    monthFilter: document.getElementById('monthFilter'),
    monthlyCards: document.getElementById('monthlyCards'),
    monthlyIncome: document.getElementById('monthlyIncome'),
    monthlyExpense: document.getElementById('monthlyExpense'),
    monthlyBalance: document.getElementById('monthlyBalance'),
    monthlyTransTotal: document.getElementById('monthlyTransTotal'),
    monthlyTransIn: document.getElementById('monthlyTransIn'),
    monthlyTransOut: document.getElementById('monthlyTransOut'),

    // Table
    transactionsBody: document.getElementById('transactionsBody'),

    // Filters
    searchInput: document.getElementById('searchInput'),
    startDate: document.getElementById('startDate'),
    endDate: document.getElementById('endDate'),
    typeFilter: document.getElementById('typeFilter'),
    resetFilters: document.getElementById('resetFilters'),

    // Pagination
    prevPage: document.getElementById('prevPage'),
    nextPage: document.getElementById('nextPage'),
    pageInfo: document.getElementById('pageInfo'),
    loadAllBtn: document.getElementById('loadAllBtn'),

    // Buttons
    themeToggle: document.getElementById('themeToggle'),
    importCsvBtn: document.getElementById('importCsvBtn'),
    exportCsvBtn: document.getElementById('exportCsvBtn'),
    exportPdfBtn: document.getElementById('exportPdfBtn'),
    addBtn: document.getElementById('addBtn'),
    userMgmtBtn: document.getElementById('userMgmtBtn'),
    refreshNowBtn: document.getElementById('refreshNowBtn'),
    countdownValue: document.getElementById('countdownValue'),

    // Auth elements
    loginBtn: document.getElementById('loginBtn'),
    logoutBtn: document.getElementById('logoutBtn'),
    userInfo: document.getElementById('userInfo'),
    userName: document.getElementById('userName'),
    userRole: document.getElementById('userRole'),

    // Login Modal
    loginModal: document.getElementById('loginModal'),
    loginForm: document.getElementById('loginForm'),
    loginUsername: document.getElementById('loginUsername'),
    loginPassword: document.getElementById('loginPassword'),
    closeLoginModal: document.getElementById('closeLoginModal'),
    cancelLogin: document.getElementById('cancelLogin'),

    // Transaction Modal
    transactionModal: document.getElementById('transactionModal'),
    modalTitle: document.getElementById('modalTitle'),
    transactionForm: document.getElementById('transactionForm'),
    transactionId: document.getElementById('transactionId'),
    tanggal: document.getElementById('tanggal'),
    deskripsi: document.getElementById('deskripsi'),
    pemasukan: document.getElementById('pemasukan'),
    pengeluaran: document.getElementById('pengeluaran'),
    closeModal: document.getElementById('closeModal'),
    cancelBtn: document.getElementById('cancelBtn'),

    // Delete Modal
    deleteModal: document.getElementById('deleteModal'),
    deleteInfo: document.getElementById('deleteInfo'),
    closeDeleteModal: document.getElementById('closeDeleteModal'),
    cancelDelete: document.getElementById('cancelDelete'),
    confirmDelete: document.getElementById('confirmDelete'),

    // User Management Modal
    userModal: document.getElementById('userModal'),
    closeUserModal: document.getElementById('closeUserModal'),
    usersBody: document.getElementById('usersBody'),
    addUserBtn: document.getElementById('addUserBtn'),

    // User Form Modal
    userFormModal: document.getElementById('userFormModal'),
    userFormTitle: document.getElementById('userFormTitle'),
    userForm: document.getElementById('userForm'),
    userId: document.getElementById('userId'),
    userUsername: document.getElementById('userUsername'),
    userNama: document.getElementById('userNama'),
    userPassword: document.getElementById('userPassword'),
    userRoleSelect: document.getElementById('userRoleSelect'),
    userActive: document.getElementById('userActive'),
    closeUserFormModal: document.getElementById('closeUserFormModal'),
    cancelUserForm: document.getElementById('cancelUserForm'),
    pwdNote: document.getElementById('pwdNote'),

    // Import CSV Modal
    importModal: document.getElementById('importModal'),
    importForm: document.getElementById('importForm'),
    csvFile: document.getElementById('csvFile'),
    closeImportModal: document.getElementById('closeImportModal'),
    cancelImport: document.getElementById('cancelImport'),

    // Toast
    toast: document.getElementById('toast'),
    toastMessage: document.getElementById('toastMessage')
};

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initMonthSelector();
    initDefaultDateFilter();
    initEventListeners();
    checkAuthStatus();
    loadSummary();
    loadTransactions();
    startAutoRefresh();
});

// Theme Management
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

// Initialize month selector
function initMonthSelector() {
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth();

    let options = '<option value="">Pilih Bulan</option>';
    for (let y = currentYear; y >= currentYear - 2; y--) {
        for (let m = 11; m >= 0; m--) {
            if (y === currentYear && m > currentMonth) continue;
            const value = `${y}-${String(m + 1).padStart(2, '0')}`;
            options += `<option value="${value}">${months[m]} ${y}</option>`;
        }
    }
    elements.monthFilter.innerHTML = options;
}

// Initialize default date filter to current month
function initDefaultDateFilter() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');

    // First day of current month
    const firstDay = `${year}-${month}-01`;

    // Last day of current month
    const lastDay = new Date(year, now.getMonth() + 1, 0).getDate();
    const lastDayStr = `${year}-${month}-${String(lastDay).padStart(2, '0')}`;

    // Set the input values
    elements.startDate.value = firstDay;
    elements.endDate.value = lastDayStr;

    // Update state
    state.filters.startDate = firstDay;
    state.filters.endDate = lastDayStr;
}

// Event Listeners
function initEventListeners() {
    // Theme toggle
    elements.themeToggle.addEventListener('click', toggleTheme);

    // Add button
    elements.addBtn.addEventListener('click', () => {
        if (!state.canAdd) {
            showToast('Anda harus login untuk menambah transaksi', 'error');
            return;
        }
        openModal();
    });

    // Export buttons
    elements.exportCsvBtn.addEventListener('click', exportToCSV);
    elements.exportPdfBtn.addEventListener('click', exportToPDF);

    // Refresh button
    elements.refreshNowBtn.addEventListener('click', refreshData);

    // Import button
    elements.importCsvBtn.addEventListener('click', openImportModal);

    // Import modal events
    elements.closeImportModal.addEventListener('click', closeImportModal);
    elements.cancelImport.addEventListener('click', closeImportModal);
    elements.importForm.addEventListener('submit', handleImport);
    elements.importModal.addEventListener('click', (e) => {
        if (e.target === elements.importModal) closeImportModal();
    });

    // User management button
    elements.userMgmtBtn.addEventListener('click', openUserModal);

    // Auth buttons
    elements.loginBtn.addEventListener('click', openLoginModal);
    elements.logoutBtn.addEventListener('click', logout);

    // Login modal events
    elements.closeLoginModal.addEventListener('click', closeLoginModal);
    elements.cancelLogin.addEventListener('click', closeLoginModal);
    elements.loginForm.addEventListener('submit', handleLogin);
    elements.loginModal.addEventListener('click', (e) => {
        if (e.target === elements.loginModal) closeLoginModal();
    });

    // Monthly filter
    elements.monthFilter.addEventListener('change', (e) => {
        state.filters.month = e.target.value;
        if (e.target.value) {
            loadMonthlySummary(e.target.value);
            elements.monthlyCards.style.display = 'grid';
        } else {
            elements.monthlyCards.style.display = 'none';
        }
    });

    // Filter inputs with debounce
    let searchTimeout;
    elements.searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.filters.search = e.target.value;
            state.currentPage = 1;
            state.showAll = false;
            loadTransactions();
        }, 300);
    });

    elements.startDate.addEventListener('change', (e) => {
        state.filters.startDate = e.target.value;
        state.currentPage = 1;
        state.showAll = false;
        loadTransactions();
    });

    elements.endDate.addEventListener('change', (e) => {
        state.filters.endDate = e.target.value;
        state.currentPage = 1;
        state.showAll = false;
        loadTransactions();
    });

    elements.typeFilter.addEventListener('change', (e) => {
        state.filters.type = e.target.value;
        state.currentPage = 1;
        state.showAll = false;
        loadTransactions();
    });

    // Reset filters
    elements.resetFilters.addEventListener('click', () => {
        elements.searchInput.value = '';
        elements.startDate.value = '';
        elements.endDate.value = '';
        elements.typeFilter.value = '';
        state.filters = { search: '', startDate: '', endDate: '', type: '', month: state.filters.month };
        state.currentPage = 1;
        state.showAll = false;
        loadTransactions();
    });

    // Pagination
    elements.prevPage.addEventListener('click', () => {
        if (state.currentPage > 1) {
            state.currentPage--;
            loadTransactions();
        }
    });

    elements.nextPage.addEventListener('click', () => {
        if (state.currentPage < state.totalPages) {
            state.currentPage++;
            loadTransactions();
        }
    });

    // Load all button
    elements.loadAllBtn.addEventListener('click', () => {
        state.showAll = !state.showAll;
        elements.loadAllBtn.textContent = state.showAll ? 'Tampilkan 20' : 'Lihat Semua';
        loadTransactions();
    });

    // Modal events
    elements.closeModal.addEventListener('click', closeModal);
    elements.cancelBtn.addEventListener('click', closeModal);
    elements.transactionForm.addEventListener('submit', handleFormSubmit);

    // Delete modal events
    elements.closeDeleteModal.addEventListener('click', closeDeleteModal);
    elements.cancelDelete.addEventListener('click', closeDeleteModal);
    elements.confirmDelete.addEventListener('click', confirmDelete);

    // Close modals on backdrop click
    elements.transactionModal.addEventListener('click', (e) => {
        if (e.target === elements.transactionModal) closeModal();
    });

    elements.deleteModal.addEventListener('click', (e) => {
        if (e.target === elements.deleteModal) closeDeleteModal();
    });

    // User management modal events
    elements.closeUserModal.addEventListener('click', closeUserModal);
    elements.userModal.addEventListener('click', (e) => {
        if (e.target === elements.userModal) closeUserModal();
    });
    elements.addUserBtn.addEventListener('click', () => openUserFormModal());

    // User form modal events
    elements.closeUserFormModal.addEventListener('click', closeUserFormModal);
    elements.cancelUserForm.addEventListener('click', closeUserFormModal);
    elements.userFormModal.addEventListener('click', (e) => {
        if (e.target === elements.userFormModal) closeUserFormModal();
    });
    elements.userForm.addEventListener('submit', handleUserFormSubmit);

    // Rupiah formatting on input
    elements.pemasukan.addEventListener('input', formatRupiahInput);
    elements.pengeluaran.addEventListener('input', formatRupiahInput);

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
            closeDeleteModal();
            closeLoginModal();
            closeUserModal();
            closeUserFormModal();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'n' && state.canAdd) {
            e.preventDefault();
            openModal();
        }
    });
}

// Format rupiah on input
function formatRupiahInput(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value) {
        e.target.value = parseInt(value).toLocaleString('id-ID');
    }
}

// Parse rupiah input to number
function parseRupiahInput(value) {
    return parseInt(value.replace(/\D/g, '')) || 0;
}

// Auth Functions
async function checkAuthStatus() {
    try {
        const response = await fetch('api/auth.php?action=status');
        const data = await response.json();

        state.isLoggedIn = data.logged_in;
        state.user = data.user;
        state.canAdd = data.can_add;
        state.canEdit = data.can_edit;

        updateAuthUI();
    } catch (error) {
        console.error('Failed to check auth status:', error);
    }
}

function updateAuthUI() {
    if (state.isLoggedIn && state.user) {
        elements.loginBtn.style.display = 'none';
        elements.userInfo.style.display = 'flex';
        elements.userName.textContent = state.user.nama;
        elements.userRole.textContent = state.user.role;
        elements.userRole.className = `user-role ${state.user.role}`;

        elements.addBtn.style.display = state.canAdd ? 'inline-flex' : 'none';
        elements.userMgmtBtn.style.display = state.canEdit ? 'inline-flex' : 'none';
        elements.importCsvBtn.style.display = state.canEdit ? 'inline-flex' : 'none';
    } else {
        elements.loginBtn.style.display = 'inline-flex';
        elements.userInfo.style.display = 'none';
        elements.addBtn.style.display = 'none';
        elements.userMgmtBtn.style.display = 'none';
        elements.importCsvBtn.style.display = 'none';
    }

    // Show/hide action column based on role
    const actionHeader = document.getElementById('actionHeader');
    if (actionHeader) {
        actionHeader.style.display = state.canEdit ? '' : 'none';
    }

    if (state.transactions.length > 0) {
        renderTransactions();
    }
}

function openLoginModal() {
    elements.loginForm.reset();
    elements.loginModal.classList.add('active');
    elements.loginUsername.focus();
}

function closeLoginModal() {
    elements.loginModal.classList.remove('active');
}

async function handleLogin(e) {
    e.preventDefault();

    const username = elements.loginUsername.value.trim();
    const password = elements.loginPassword.value;

    if (!username || !password) {
        showToast('Username dan password harus diisi', 'error');
        return;
    }

    try {
        const response = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json();

        if (!response.ok) {
            showToast(data.error || 'Login gagal', 'error');
            return;
        }

        state.isLoggedIn = true;
        state.user = data.user;
        state.canAdd = data.can_add;
        state.canEdit = data.can_edit;

        closeLoginModal();
        updateAuthUI();
        showToast(`Selamat datang, ${data.user.nama}!`, 'success');
    } catch (error) {
        console.error('Login error:', error);
        showToast('Terjadi kesalahan saat login', 'error');
    }
}

async function logout() {
    try {
        await fetch('api/auth.php?action=logout');

        state.isLoggedIn = false;
        state.user = null;
        state.canAdd = false;
        state.canEdit = false;

        updateAuthUI();
        showToast('Logout berhasil', 'success');
    } catch (error) {
        console.error('Logout error:', error);
    }
}

// API Calls
async function apiCall(endpoint, options = {}) {
    try {
        const response = await fetch(`api/transactions.php${endpoint}`, {
            headers: { 'Content-Type': 'application/json' },
            ...options
        });

        const data = await response.json();

        if (!response.ok) {
            if (data.code === 'AUTH_REQUIRED') {
                showToast('Silakan login terlebih dahulu', 'error');
                openLoginModal();
                throw new Error(data.error);
            }
            if (data.code === 'ADMIN_REQUIRED' || data.code === 'ADD_REQUIRED') {
                showToast('Anda tidak memiliki akses', 'error');
                throw new Error(data.error);
            }
            throw new Error(data.error || 'API request failed');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Load Summary
async function loadSummary() {
    try {
        const data = await apiCall('?summary=1');
        elements.totalPemasukan.textContent = formatRupiah(data.total_pemasukan);
        elements.totalPengeluaran.textContent = formatRupiah(data.total_pengeluaran);
        elements.totalSaldo.textContent = formatRupiah(data.saldo);
        elements.totalTransaksi.textContent = data.total_transaksi;
        elements.totalTransIn.textContent = data.transaksi_masuk || 0;
        elements.totalTransOut.textContent = data.transaksi_keluar || 0;

        // Update current month badge
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const now = new Date();
        const currentMonthName = months[now.getMonth()];
        const currentYear = now.getFullYear();
        const badge = document.getElementById('currentMonthBadge');
        if (badge) {
            badge.textContent = `${currentMonthName} ${currentYear}`;
        }

        if (parseFloat(data.saldo) < 0) {
            elements.totalSaldo.classList.add('amount-negative');
        } else {
            elements.totalSaldo.classList.remove('amount-negative');
        }
    } catch (error) {
        console.error('Failed to load summary:', error);
    }
}

// Load Monthly Summary
async function loadMonthlySummary(month) {
    try {
        const [year, m] = month.split('-');
        const monthNum = parseInt(m);
        const startDate = `${year}-${m}-01`;

        // Get last day of month properly
        const lastDay = new Date(parseInt(year), monthNum, 0).getDate();
        const endDate = `${year}-${m}-${String(lastDay).padStart(2, '0')}`;

        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
            limit: 10000
        });

        const data = await apiCall(`?${params.toString()}`);

        let income = 0, expense = 0, transIn = 0, transOut = 0;
        data.data.forEach(t => {
            const pem = parseFloat(t.pemasukan);
            const pen = parseFloat(t.pengeluaran);
            income += pem;
            expense += pen;
            if (pem > 0) transIn++;
            if (pen > 0) transOut++;
        });

        elements.monthlyIncome.textContent = formatRupiah(income);
        elements.monthlyExpense.textContent = formatRupiah(expense);
        elements.monthlyBalance.textContent = formatRupiah(income - expense);
        elements.monthlyTransTotal.textContent = data.data.length;
        elements.monthlyTransIn.textContent = transIn;
        elements.monthlyTransOut.textContent = transOut;
    } catch (error) {
        console.error('Failed to load monthly summary:', error);
    }
}

// Load Transactions
async function loadTransactions() {
    elements.transactionsBody.innerHTML = '<tr><td colspan="7" class="loading">Memuat data...</td></tr>';

    try {
        const params = new URLSearchParams({
            page: state.showAll ? 1 : state.currentPage,
            limit: state.showAll ? 10000 : state.limit
        });

        if (state.filters.search) params.append('search', state.filters.search);
        if (state.filters.startDate) params.append('start_date', state.filters.startDate);
        if (state.filters.endDate) params.append('end_date', state.filters.endDate);
        if (state.filters.type) params.append('type', state.filters.type);

        const data = await apiCall(`?${params.toString()}`);

        state.transactions = data.data;
        state.totalPages = data.pages;

        renderTransactions();
        updatePagination(data.total);
    } catch (error) {
        elements.transactionsBody.innerHTML = '<tr><td colspan="7" class="empty">Gagal memuat data</td></tr>';
    }
}

// Render Transactions
function renderTransactions() {
    const colspan = state.canEdit ? '7' : '6';

    if (state.transactions.length === 0) {
        elements.transactionsBody.innerHTML = `<tr><td colspan="${colspan}" class="empty">Tidak ada transaksi</td></tr>`;
        return;
    }

    const startNo = state.showAll ? 1 : (state.currentPage - 1) * state.limit + 1;

    elements.transactionsBody.innerHTML = state.transactions.map((trans, index) => `
        <tr>
            <td>${startNo + index}</td>
            <td>${formatDate(trans.tanggal)}</td>
            <td class="description-cell">${escapeHtml(trans.deskripsi)}</td>
            <td class="text-right ${trans.pemasukan > 0 ? 'amount-income' : ''}">
                ${trans.pemasukan > 0 ? formatRupiah(trans.pemasukan) : '-'}
            </td>
            <td class="text-right ${trans.pengeluaran > 0 ? 'amount-expense' : ''}">
                ${trans.pengeluaran > 0 ? formatRupiah(trans.pengeluaran) : '-'}
            </td>
            <td class="text-right amount-balance ${trans.saldo < 0 ? 'amount-negative' : ''}">
                ${formatRupiah(trans.saldo)}
            </td>
            ${state.canEdit ? `
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-secondary" onclick="editTransaction(${trans.id})" title="Edit">✏️</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTransaction(${trans.id})" title="Hapus">🗑️</button>
                    </div>
                </td>
            ` : ''}
        </tr>
    `).join('');
}

// Update Pagination
function updatePagination(total) {
    if (state.showAll) {
        elements.pageInfo.textContent = `Menampilkan semua (${total} transaksi)`;
        elements.prevPage.style.display = 'none';
        elements.nextPage.style.display = 'none';
    } else {
        elements.pageInfo.textContent = `Halaman ${state.currentPage} dari ${state.totalPages} (${total} transaksi)`;
        elements.prevPage.style.display = 'inline-flex';
        elements.nextPage.style.display = 'inline-flex';
        elements.prevPage.disabled = state.currentPage <= 1;
        elements.nextPage.disabled = state.currentPage >= state.totalPages;
    }
}

// Modal Functions
function openModal(id = null) {
    if (!state.canAdd) {
        showToast('Anda harus login untuk menambah transaksi', 'error');
        return;
    }

    state.editingId = id;
    elements.transactionForm.reset();

    if (id) {
        if (!state.canEdit) {
            showToast('Anda tidak memiliki akses edit', 'error');
            return;
        }
        elements.modalTitle.textContent = 'Edit Transaksi';
        loadTransactionForEdit(id);
    } else {
        elements.modalTitle.textContent = 'Tambah Transaksi';
        elements.tanggal.value = new Date().toISOString().split('T')[0];
        elements.pemasukan.value = '0';
        elements.pengeluaran.value = '0';
    }

    elements.transactionModal.classList.add('active');
    elements.tanggal.focus();
}

function closeModal() {
    elements.transactionModal.classList.remove('active');
    state.editingId = null;
}

async function loadTransactionForEdit(id) {
    try {
        const data = await apiCall(`?id=${id}`);
        elements.transactionId.value = data.id;
        elements.tanggal.value = data.tanggal;
        elements.deskripsi.value = data.deskripsi;
        elements.pemasukan.value = parseInt(data.pemasukan).toLocaleString('id-ID');
        elements.pengeluaran.value = parseInt(data.pengeluaran).toLocaleString('id-ID');
    } catch (error) {
        closeModal();
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();

    // Prevent double submission
    if (state.isSubmitting) return;

    const formData = {
        tanggal: elements.tanggal.value,
        deskripsi: elements.deskripsi.value.trim(),
        pemasukan: parseRupiahInput(elements.pemasukan.value),
        pengeluaran: parseRupiahInput(elements.pengeluaran.value)
    };

    if (!formData.tanggal || !formData.deskripsi) {
        showToast('Tanggal dan deskripsi harus diisi', 'error');
        return;
    }

    // Set submitting state
    state.isSubmitting = true;
    const submitBtn = elements.transactionForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.textContent = state.editingId ? 'Menyimpan...' : 'Menambahkan...';

    try {
        if (state.editingId) {
            formData.id = state.editingId;
            await apiCall('', { method: 'PUT', body: JSON.stringify(formData) });
            showToast('Transaksi berhasil diupdate', 'success');
        } else {
            await apiCall('', { method: 'POST', body: JSON.stringify(formData) });
            showToast('Transaksi berhasil ditambahkan', 'success');
        }

        closeModal();
        loadSummary();
        loadTransactions();
        if (state.filters.month) loadMonthlySummary(state.filters.month);
    } catch (error) {
        // Error handled in apiCall
    } finally {
        // Reset submitting state
        state.isSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        submitBtn.textContent = originalText;
    }
}

// Delete Functions
function deleteTransaction(id) {
    if (!state.canEdit) {
        showToast('Anda tidak memiliki akses hapus', 'error');
        return;
    }

    // Find transaction description from state
    const transaction = state.transactions.find(t => t.id === id);
    const description = transaction ? transaction.deskripsi : 'transaksi ini';

    state.deleteId = id;
    elements.deleteInfo.textContent = description;
    elements.deleteModal.classList.add('active');
}

function closeDeleteModal() {
    elements.deleteModal.classList.remove('active');
    state.deleteId = null;
}

async function confirmDelete() {
    if (!state.deleteId || !state.canEdit) return;

    try {
        await apiCall(`?id=${state.deleteId}`, { method: 'DELETE' });
        showToast('Transaksi berhasil dihapus', 'success');
        closeDeleteModal();
        loadSummary();
        loadTransactions();
        if (state.filters.month) loadMonthlySummary(state.filters.month);
    } catch (error) {
        // Error handled in apiCall
    }
}

// Global functions for onclick
window.editTransaction = function(id) {
    if (!state.canEdit) {
        showToast('Anda tidak memiliki akses edit', 'error');
        return;
    }
    openModal(id);
};

window.deleteTransaction = deleteTransaction;

// User Management Functions
function openUserModal() {
    elements.userModal.classList.add('active');
    loadUsers();
}

function closeUserModal() {
    elements.userModal.classList.remove('active');
}

async function loadUsers() {
    elements.usersBody.innerHTML = '<tr><td colspan="6" class="loading">Memuat...</td></tr>';

    try {
        const response = await fetch('api/auth.php?action=users');
        const users = await response.json();

        if (users.length === 0) {
            elements.usersBody.innerHTML = '<tr><td colspan="6" class="empty">Tidak ada user</td></tr>';
            return;
        }

        elements.usersBody.innerHTML = users.map(user => `
            <tr>
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.nama)}</td>
                <td><span class="user-role ${user.role}">${user.role}</span></td>
                <td><span class="status-badge ${user.is_active ? 'active' : 'inactive'}">${user.is_active ? 'Aktif' : 'Nonaktif'}</span></td>
                <td>${user.last_login ? formatDate(user.last_login) : '-'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-secondary" onclick="editUser(${user.id})" title="Edit">✏️</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id}, '${escapeHtml(user.username)}')" title="Hapus">🗑️</button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        elements.usersBody.innerHTML = '<tr><td colspan="6" class="empty">Gagal memuat user</td></tr>';
    }
}

function openUserFormModal(userId = null) {
    state.editingUserId = userId;
    elements.userForm.reset();
    elements.userActive.checked = true;

    if (userId) {
        elements.userFormTitle.textContent = 'Edit User';
        elements.pwdNote.style.display = 'inline';
        elements.userPassword.removeAttribute('required');
        loadUserForEdit(userId);
    } else {
        elements.userFormTitle.textContent = 'Tambah User';
        elements.pwdNote.style.display = 'none';
        elements.userPassword.setAttribute('required', 'required');
    }

    elements.userFormModal.classList.add('active');
}

function closeUserFormModal() {
    elements.userFormModal.classList.remove('active');
    state.editingUserId = null;
}

async function loadUserForEdit(userId) {
    try {
        const response = await fetch('api/auth.php?action=users');
        const users = await response.json();
        const user = users.find(u => u.id == userId);

        if (user) {
            elements.userId.value = user.id;
            elements.userUsername.value = user.username;
            elements.userNama.value = user.nama;
            elements.userRoleSelect.value = user.role;
            elements.userActive.checked = user.is_active;
        }
    } catch (error) {
        closeUserFormModal();
    }
}

async function handleUserFormSubmit(e) {
    e.preventDefault();

    const formData = {
        username: elements.userUsername.value.trim(),
        nama: elements.userNama.value.trim(),
        role: elements.userRoleSelect.value,
        is_active: elements.userActive.checked
    };

    if (elements.userPassword.value) {
        formData.password = elements.userPassword.value;
    }

    try {
        let response;
        if (state.editingUserId) {
            formData.id = state.editingUserId;
            response = await fetch('api/auth.php?action=update-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        } else {
            response = await fetch('api/auth.php?action=create-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        }

        const data = await response.json();

        if (!response.ok) {
            showToast(data.error || 'Gagal menyimpan user', 'error');
            return;
        }

        showToast(state.editingUserId ? 'User berhasil diupdate' : 'User berhasil dibuat', 'success');
        closeUserFormModal();
        loadUsers();
    } catch (error) {
        showToast('Terjadi kesalahan', 'error');
    }
}

window.editUser = function(userId) {
    openUserFormModal(userId);
};

window.deleteUser = async function(userId, username) {
    if (!confirm(`Hapus user "${username}"?`)) return;

    try {
        const response = await fetch('api/auth.php?action=delete-user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: userId })
        });

        const data = await response.json();

        if (!response.ok) {
            showToast(data.error || 'Gagal menghapus user', 'error');
            return;
        }

        showToast('User berhasil dihapus', 'success');
        loadUsers();
    } catch (error) {
        showToast('Terjadi kesalahan', 'error');
    }
};

// Import CSV Functions
function openImportModal() {
    if (!state.canEdit) {
        showToast('Hanya admin yang dapat import data', 'error');
        return;
    }
    elements.importForm.reset();
    elements.importModal.classList.add('active');
}

function closeImportModal() {
    elements.importModal.classList.remove('active');
}

async function handleImport(e) {
    e.preventDefault();

    if (!state.canEdit) {
        showToast('Hanya admin yang dapat import data', 'error');
        return;
    }

    const fileInput = elements.csvFile;
    if (!fileInput.files || fileInput.files.length === 0) {
        showToast('Pilih file CSV terlebih dahulu', 'error');
        return;
    }

    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('csv_file', file);

    try {
        const response = await fetch('api/transactions.php?action=import', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!response.ok) {
            showToast(data.error || 'Gagal import data', 'error');
            return;
        }

        let message = data.message;
        if (data.errors && data.errors.length > 0) {
            message += `\n\nPeringatan (${data.errors.length} baris dilewati):\n`;
            message += data.errors.slice(0, 5).join('\n');
            if (data.errors.length > 5) {
                message += `\n... dan ${data.errors.length - 5} error lainnya`;
            }
        }

        showToast(message, 'success');
        closeImportModal();
        loadSummary();
        loadTransactions();
        if (state.filters.month) loadMonthlySummary(state.filters.month);
    } catch (error) {
        showToast('Terjadi kesalahan saat import', 'error');
        console.error('Import error:', error);
    }
}

// Export to CSV
async function exportToCSV() {
    try {
        const params = new URLSearchParams({ limit: 10000 });
        if (state.filters.search) params.append('search', state.filters.search);
        if (state.filters.startDate) params.append('start_date', state.filters.startDate);
        if (state.filters.endDate) params.append('end_date', state.filters.endDate);
        if (state.filters.type) params.append('type', state.filters.type);

        const data = await apiCall(`?${params.toString()}`);

        if (data.data.length === 0) {
            showToast('Tidak ada data untuk diexport', 'error');
            return;
        }

        const headers = ['No', 'Tanggal', 'Deskripsi', 'Pemasukan', 'Pengeluaran', 'Saldo'];
        const rows = data.data.map((trans, index) => [
            index + 1,
            trans.tanggal,
            `"${trans.deskripsi.replace(/"/g, '""')}"`,
            trans.pemasukan,
            trans.pengeluaran,
            trans.saldo
        ]);

        const csvContent = [headers.join(','), ...rows.map(row => row.join(','))].join('\n');

        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `starlink-finance-${new Date().toISOString().split('T')[0]}.csv`;
        link.click();

        showToast(`Berhasil export ${data.data.length} transaksi`, 'success');
    } catch (error) {
        showToast('Gagal export data', 'error');
    }
}

// Export to PDF
async function exportToPDF() {
    try {
        const params = new URLSearchParams({ limit: 10000 });
        if (state.filters.search) params.append('search', state.filters.search);
        if (state.filters.startDate) params.append('start_date', state.filters.startDate);
        if (state.filters.endDate) params.append('end_date', state.filters.endDate);
        if (state.filters.type) params.append('type', state.filters.type);

        const data = await apiCall(`?${params.toString()}`);

        if (data.data.length === 0) {
            showToast('Tidak ada data untuk diexport', 'error');
            return;
        }

        // Create printable HTML
        let totalPemasukan = 0, totalPengeluaran = 0;
        const rows = data.data.map((trans, index) => {
            totalPemasukan += parseFloat(trans.pemasukan);
            totalPengeluaran += parseFloat(trans.pengeluaran);
            return `
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${index + 1}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${formatDate(trans.tanggal)}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; white-space: pre-wrap; text-align: left;">${escapeHtml(trans.deskripsi)}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${trans.pemasukan > 0 ? formatRupiah(trans.pemasukan) : '-'}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${trans.pengeluaran > 0 ? formatRupiah(trans.pengeluaran) : '-'}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${formatRupiah(trans.saldo)}</td>
                </tr>
            `;
        }).join('');

        const html = `
            <html>
            <head>
                <title>Laporan Keuangan Starlink</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h1 { text-align: center; }
                    .summary { margin: 20px 0; display: flex; gap: 20px; justify-content: center; }
                    .summary div { padding: 10px 20px; background: #f5f5f5; border-radius: 8px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th { background: #333; color: white; padding: 10px; text-align: center; }
                    @media print { body { padding: 0; } }
                </style>
            </head>
            <body>
                <h1>Laporan Keuangan Starlink Finance</h1>
                <p style="text-align: center;">Tanggal: ${new Date().toLocaleDateString('id-ID')}</p>
                <div class="summary">
                    <div><strong>Total Pemasukan:</strong> ${formatRupiah(totalPemasukan)}</div>
                    <div><strong>Total Pengeluaran:</strong> ${formatRupiah(totalPengeluaran)}</div>
                    <div><strong>Saldo:</strong> ${formatRupiah(totalPemasukan - totalPengeluaran)}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th>Pemasukan</th>
                            <th>Pengeluaran</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.print();

        showToast('PDF siap dicetak', 'success');
    } catch (error) {
        showToast('Gagal export PDF', 'error');
    }
}

// Utility Functions
function formatRupiah(amount) {
    const num = parseFloat(amount) || 0;
    return 'Rp ' + num.toLocaleString('id-ID');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'success') {
    elements.toastMessage.textContent = message;
    elements.toast.className = `toast ${type} show`;
    setTimeout(() => elements.toast.classList.remove('show'), 3000);
}

// Auto Refresh Functions
let refreshTimer;
let countdownInterval;
let secondsRemaining = 600; // 10 minutes = 600 seconds

function startAutoRefresh() {
    secondsRemaining = 600;
    updateCountdownDisplay();

    // Clear any existing intervals
    if (countdownInterval) clearInterval(countdownInterval);
    if (refreshTimer) clearTimeout(refreshTimer);

    // Start countdown
    countdownInterval = setInterval(() => {
        secondsRemaining--;
        updateCountdownDisplay();

        if (secondsRemaining <= 0) {
            refreshData();
        }
    }, 1000);
}

function updateCountdownDisplay() {
    const minutes = Math.floor(secondsRemaining / 60);
    const seconds = secondsRemaining % 60;
    const display = `${minutes}:${String(seconds).padStart(2, '0')}`;
    elements.countdownValue.textContent = display;

    // Add visual warning when less than 1 minute
    if (secondsRemaining <= 60 && secondsRemaining > 0) {
        elements.countdownValue.classList.add('countdown-warning');
    } else {
        elements.countdownValue.classList.remove('countdown-warning');
    }
}

function refreshData() {
    // Add rotation animation to refresh button
    elements.refreshNowBtn.classList.add('rotating');

    // Reload all data
    loadSummary();
    loadTransactions();
    if (state.filters.month) {
        loadMonthlySummary(state.filters.month);
    }

    // Reset countdown
    startAutoRefresh();

    // Remove rotation after animation
    setTimeout(() => {
        elements.refreshNowBtn.classList.remove('rotating');
    }, 1000);

    showToast('Data berhasil di-refresh', 'success');
}
