<aside class="sidebar" id="sidebar">
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item active">
            <span class="icon">📊</span>
            <span class="text">Dashboard</span>
        </a>

        <div class="menu-section">
            <div class="menu-section-title">Transaksi</div>
            <a href="sales.php" class="menu-item">
                <span class="icon">💰</span>
                <span class="text">Penjualan</span>
            </a>
            <a href="purchases.php" class="menu-item">
                <span class="icon">🛒</span>
                <span class="text">Pembelian</span>
            </a>
            <a href="expenses.php" class="menu-item">
                <span class="icon">💸</span>
                <span class="text">Pengeluaran</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-section-title">Master Data</div>
            <a href="products.php" class="menu-item">
                <span class="icon">📦</span>
                <span class="text">Produk</span>
            </a>
            <a href="customers.php" class="menu-item">
                <span class="icon">👥</span>
                <span class="text">Customer</span>
            </a>
            <a href="suppliers.php" class="menu-item">
                <span class="icon">🏢</span>
                <span class="text">Supplier</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-section-title">Laporan</div>
            <a href="report-sales.php" class="menu-item">
                <span class="icon">📈</span>
                <span class="text">Laporan Penjualan</span>
            </a>
            <a href="report-purchases.php" class="menu-item">
                <span class="icon">📉</span>
                <span class="text">Laporan Pembelian</span>
            </a>
            <a href="report-profit.php" class="menu-item">
                <span class="icon">💹</span>
                <span class="text">Laporan Laba Rugi</span>
            </a>
            <a href="report-cashflow.php" class="menu-item">
                <span class="icon">💵</span>
                <span class="text">Arus Kas</span>
            </a>
        </div>

        <?php if (hasRole(['admin'])): ?>
        <div class="menu-section">
            <div class="menu-section-title">Sistem</div>
            <a href="users.php" class="menu-item">
                <span class="icon">👨‍💼</span>
                <span class="text">Pengguna</span>
            </a>
            <a href="settings.php" class="menu-item">
                <span class="icon">⚙️</span>
                <span class="text">Pengaturan</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</aside>
