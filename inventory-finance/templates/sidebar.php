<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-network-wired me-2"></i><?= getSetting('company_name', APP_NAME) ?></h3>
        <small>Inventory & Finance</small>
    </div>

    <div class="sidebar-menu">
        <!-- Main Menu -->
        <div class="menu-header">Menu Utama</div>
        <ul>
            <li>
                <a href="<?= BASE_URL ?>modules/dashboard/" class="<?= isActiveMenu('/dashboard/') ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- Inventory -->
        <div class="menu-header">Inventory</div>
        <ul>
            <li>
                <a href="<?= BASE_URL ?>modules/categories/" class="<?= isActiveMenu('/categories/') ?>">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/products/" class="<?= isActiveMenu('/products/') ?>">
                    <i class="fas fa-box"></i>
                    <span>Produk</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/stock-in/" class="<?= isActiveMenu('/stock-in/') ?>">
                    <i class="fas fa-arrow-down"></i>
                    <span>Stok Masuk</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/suppliers/" class="<?= isActiveMenu('/suppliers/') ?>">
                    <i class="fas fa-truck"></i>
                    <span>Supplier</span>
                </a>
            </li>
        </ul>

        <!-- Transactions -->
        <div class="menu-header">Transaksi</div>
        <ul>
            <li>
                <a href="<?= BASE_URL ?>modules/stock-out/" class="<?= isActiveMenu('/stock-out/') ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Penjualan</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/customers/" class="<?= isActiveMenu('/customers/') ?>">
                    <i class="fas fa-users"></i>
                    <span>Customer</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/invoices/" class="<?= isActiveMenu('/invoices/') ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Invoice</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/returns/" class="<?= isActiveMenu('/returns/') ?>">
                    <i class="fas fa-undo"></i>
                    <span>Return/RMA</span>
                </a>
            </li>
        </ul>

        <!-- Finance -->
        <div class="menu-header">Keuangan</div>
        <ul>
            <li>
                <a href="<?= BASE_URL ?>modules/debts/" class="<?= isActiveMenu('/debts/') ?>">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Hutang/Piutang</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/expenses/" class="<?= isActiveMenu('/expenses/') ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Pengeluaran</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>modules/reports/" class="<?= isActiveMenu('/reports/') ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
            </li>
        </ul>

        <!-- Settings (Admin Only) -->
        <?php if (hasRole(['admin', 'manager'])): ?>
        <div class="menu-header">Pengaturan</div>
        <ul>
            <li>
                <a href="<?= BASE_URL ?>modules/users/" class="<?= isActiveMenu('/users/') ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Users</span>
                </a>
            </li>
            <?php if (hasRole(['admin'])): ?>
            <li>
                <a href="<?= BASE_URL ?>modules/users/settings.php" class="<?= isActiveMenu('settings.php') ?>">
                    <i class="fas fa-cogs"></i>
                    <span>Pengaturan</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>
    </div>
</aside>
