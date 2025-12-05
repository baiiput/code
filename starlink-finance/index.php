<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="googlebot" content="noindex, nofollow">
    <title>Starlink Finance - Laporan Keuangan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📡</text></svg>">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <span class="logo-icon">📡</span>
                    <h1>Starlink Finance</h1>
                </div>
                <div class="header-actions">
                    <button id="themeToggle" class="btn btn-icon" title="Toggle Theme">
                        <span class="icon-sun">☀️</span>
                        <span class="icon-moon">🌙</span>
                    </button>
                    <button id="userMgmtBtn" class="btn btn-secondary" style="display: none;">Kelola User</button>
                    <button id="importCsvBtn" class="btn btn-secondary" style="display: none;">Import CSV</button>
                    <button id="exportCsvBtn" class="btn btn-secondary">Export CSV</button>
                    <button id="exportPdfBtn" class="btn btn-secondary">Export PDF</button>
                    <button id="addBtn" class="btn btn-primary" style="display: none;">+ Tambah Transaksi</button>

                    <!-- Auto Refresh Countdown -->
                    <div class="refresh-timer">
                        <button id="refreshNowBtn" class="btn btn-icon" title="Refresh Sekarang">
                            🔄
                        </button>
                        <div class="countdown-display">
                            <span class="countdown-label">Refresh otomatis dalam</span>
                            <span class="countdown-value" id="countdownValue">10:00</span>
                        </div>
                    </div>

                    <!-- User Info (shown when logged in) -->
                    <div id="userInfo" class="user-info" style="display: none;">
                        <span id="userName" class="user-name"></span>
                        <span id="userRole" class="user-role"></span>
                        <button id="logoutBtn" class="btn btn-secondary btn-sm">Logout</button>
                    </div>

                    <!-- Login Button (shown when not logged in) -->
                    <button id="loginBtn" class="btn btn-primary">Login</button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Summary Cards -->
            <section class="summary-section">
                <h2 class="section-title">
                    <span class="title-text">Dashboard Bulan Ini</span>
                    <span class="title-badge" id="currentMonthBadge"></span>
                </h2>
                <div class="summary-cards">
                    <div class="card summary-card income">
                        <div class="card-icon">💰</div>
                        <div class="card-content">
                            <span class="card-label">Pemasukan Bulan Ini</span>
                            <span class="card-value" id="totalPemasukan">Rp 0</span>
                        </div>
                    </div>
                    <div class="card summary-card expense">
                        <div class="card-icon">💸</div>
                        <div class="card-content">
                            <span class="card-label">Pengeluaran Bulan Ini</span>
                            <span class="card-value" id="totalPengeluaran">Rp 0</span>
                        </div>
                    </div>
                    <div class="card summary-card balance">
                        <div class="card-icon">🏦</div>
                        <div class="card-content">
                            <span class="card-label">Saldo Bulan Ini</span>
                            <span class="card-value" id="totalSaldo">Rp 0</span>
                        </div>
                    </div>
                    <div class="card summary-card total">
                        <div class="card-icon">📊</div>
                        <div class="card-content">
                            <span class="card-label">Total Transaksi Bulan Ini</span>
                            <span class="card-value" id="totalTransaksi">0</span>
                            <div class="card-detail">
                                <span class="detail-item">
                                    <span class="detail-label">Masuk:</span>
                                    <span class="detail-value" id="totalTransIn">0</span>
                                </span>
                                <span class="detail-separator">|</span>
                                <span class="detail-item">
                                    <span class="detail-label">Keluar:</span>
                                    <span class="detail-value" id="totalTransOut">0</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Monthly Summary -->
            <section class="monthly-section">
                <div class="monthly-header">
                    <h2>Ringkasan Bulanan</h2>
                    <select id="monthFilter">
                        <option value="">Pilih Bulan</option>
                    </select>
                </div>
                <div class="monthly-cards" id="monthlyCards" style="display: none;">
                    <div class="card monthly-card">
                        <span class="monthly-label">Pemasukan Bulan Ini</span>
                        <span class="monthly-value income" id="monthlyIncome">Rp 0</span>
                    </div>
                    <div class="card monthly-card">
                        <span class="monthly-label">Pengeluaran Bulan Ini</span>
                        <span class="monthly-value expense" id="monthlyExpense">Rp 0</span>
                    </div>
                    <div class="card monthly-card">
                        <span class="monthly-label">Saldo Bulan Ini</span>
                        <span class="monthly-value balance" id="monthlyBalance">Rp 0</span>
                    </div>
                    <div class="card monthly-card">
                        <span class="monthly-label">Total Transaksi Bulan Ini</span>
                        <span class="monthly-value total" id="monthlyTransTotal">0</span>
                        <div class="monthly-detail">
                            <span class="detail-text">Masuk: <strong id="monthlyTransIn">0</strong></span>
                            <span class="detail-separator">|</span>
                            <span class="detail-text">Keluar: <strong id="monthlyTransOut">0</strong></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filters -->
            <section class="filters-section">
                <div class="filters-container">
                    <div class="filter-group">
                        <label for="searchInput">Cari</label>
                        <input type="text" id="searchInput" placeholder="Cari deskripsi...">
                    </div>
                    <div class="filter-group">
                        <label for="startDate">Dari Tanggal</label>
                        <input type="date" id="startDate">
                    </div>
                    <div class="filter-group">
                        <label for="endDate">Sampai Tanggal</label>
                        <input type="date" id="endDate">
                    </div>
                    <div class="filter-group">
                        <label for="typeFilter">Tipe</label>
                        <select id="typeFilter">
                            <option value="">Semua</option>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>
                    <button id="resetFilters" class="btn btn-secondary">Reset</button>
                </div>
            </section>

            <!-- Transactions Table -->
            <section class="table-section">
                <div class="table-container">
                    <table class="data-table" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Deskripsi</th>
                                <th class="text-right">Pemasukan</th>
                                <th class="text-right">Pengeluaran</th>
                                <th class="text-right">Saldo</th>
                                <th id="actionHeader">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsBody">
                            <tr>
                                <td colspan="7" class="loading" id="loadingCell">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="pagination">
                    <button id="prevPage" class="btn btn-secondary" disabled>← Prev</button>
                    <span id="pageInfo">Halaman 1 dari 1</span>
                    <button id="nextPage" class="btn btn-secondary" disabled>Next →</button>
                    <button id="loadAllBtn" class="btn btn-primary">Lihat Semua</button>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <p>Starlink Finance v1.0 - Laporan Keuangan</p>
        </footer>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal" id="transactionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Transaksi</h2>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <form id="transactionForm">
                <input type="hidden" id="transactionId">
                <div class="form-group">
                    <label for="tanggal">Tanggal *</label>
                    <input type="date" id="tanggal" required>
                </div>
                <div class="form-group">
                    <label for="deskripsi">Deskripsi *</label>
                    <textarea id="deskripsi" rows="3" required placeholder="Contoh: Langganan bulanan - Pelanggan A"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="pemasukan">Pemasukan (Rp)</label>
                        <input type="text" id="pemasukan" value="0" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="pengeluaran">Pengeluaran (Rp)</label>
                        <input type="text" id="pengeluaran" value="0" placeholder="0">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Konfirmasi Hapus</h2>
                <button class="modal-close" id="closeDeleteModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus transaksi ini?</p>
                <p class="delete-info" id="deleteInfo"></p>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="cancelDelete">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal" id="loginModal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Login</h2>
                <button class="modal-close" id="closeLoginModal">&times;</button>
            </div>
            <form id="loginForm">
                <div class="form-group">
                    <label for="loginUsername">Username</label>
                    <input type="text" id="loginUsername" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" required autocomplete="current-password">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelLogin">Batal</button>
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div class="modal" id="importModal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Import Data dari CSV</h2>
                <button class="modal-close" id="closeImportModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="import-info">
                    <p><strong>Format CSV yang dibutuhkan:</strong></p>
                    <ul>
                        <li>Header: <code>tanggal,deskripsi,pemasukan,pengeluaran</code></li>
                        <li>Tanggal format: <code>YYYY-MM-DD</code> (contoh: 2025-01-15)</li>
                        <li>Angka tanpa titik atau koma ribuan (contoh: 100000 bukan 100.000)</li>
                        <li>Jika tidak ada pemasukan/pengeluaran, isi dengan 0</li>
                    </ul>
                    <p>Download contoh: <a href="starlink-finance/sample-import.csv" download>sample-import.csv</a></p>
                </div>
                <form id="importForm">
                    <div class="form-group">
                        <label for="csvFile">Pilih File CSV</label>
                        <input type="file" id="csvFile" accept=".csv" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelImport">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- User Management Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Kelola User</h2>
                <button class="modal-close" id="closeUserModal">&times;</button>
            </div>
            <div class="modal-body">
                <button id="addUserBtn" class="btn btn-primary" style="margin-bottom: 1rem;">+ Tambah User</button>
                <table class="data-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="usersBody">
                        <tr><td colspan="6" class="loading">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div class="modal" id="userFormModal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2 id="userFormTitle">Tambah User</h2>
                <button class="modal-close" id="closeUserFormModal">&times;</button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-group">
                    <label for="userUsername">Username *</label>
                    <input type="text" id="userUsername" required>
                </div>
                <div class="form-group">
                    <label for="userNama">Nama *</label>
                    <input type="text" id="userNama" required>
                </div>
                <div class="form-group">
                    <label for="userPassword">Password <span id="pwdNote">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" id="userPassword">
                </div>
                <div class="form-group">
                    <label for="userRoleSelect">Role *</label>
                    <select id="userRoleSelect" required>
                        <option value="viewer">Viewer</option>
                        <option value="editor">Editor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="userActive" checked> Aktif
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelUserForm">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <span id="toastMessage"></span>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
