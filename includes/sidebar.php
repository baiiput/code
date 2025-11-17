<!-- Sidebar -->
<div class="sidebar bg-light border-end d-none d-lg-block" id="sidebarMenu">
    <div class="sidebar-sticky">
        <!-- User Panel -->
        <div class="text-center py-4 border-bottom">
            <i class="fas fa-user-circle fa-3x text-primary mb-2"></i>
            <h6 class="mb-0"><?= $_SESSION['nama_lengkap'] ?></h6>
            <small class="text-muted"><?= getRoleName($_SESSION['user_role']) ?></small>
        </div>

        <!-- Navigation -->
        <nav class="mt-2">
            <ul class="nav flex-column">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= APP_URL ?>/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>

                <!-- Status Pelanggan -->
                <li class="sidebar-heading">STATUS PELANGGAN</li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/jatuh-tempo.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'jatuh-tempo.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt text-info"></i>
                        Jatuh Tempo
                        <span class="badge bg-info" id="badge-jatuh-tempo">0</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/proses.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'proses.php' ? 'active' : '' ?>">
                        <i class="fas fa-sync-alt text-warning"></i>
                        Proses
                        <span class="badge bg-warning text-dark" id="badge-proses">0</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/segera.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'segera.php' ? 'active' : '' ?>">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        Segera
                        <span class="badge bg-danger" id="badge-segera">0</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/observasi.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'observasi.php' ? 'active' : '' ?>">
                        <i class="fas fa-eye text-secondary"></i>
                        Observasi
                        <span class="badge bg-secondary" id="badge-observasi">0</span>
                    </a>
                </li>

                <!-- Data Management -->
                <li class="sidebar-heading">DATA MANAGEMENT</li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/pelanggan.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pelanggan.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        Data Pelanggan
                    </a>
                </li>

                <?php if (hasPermission('view_pembayaran')): ?>
                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/pembayaran.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pembayaran.php' ? 'active' : '' ?>">
                        <i class="fas fa-money-bill-wave"></i>
                        Pembayaran
                    </a>
                </li>
                <?php endif; ?>

                <!-- Laporan -->
                <?php if (hasPermission('view_laporan')): ?>
                <li class="sidebar-heading">LAPORAN</li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/laporan-fee.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'laporan-fee.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        Laporan Fee
                    </a>
                </li>
                <?php endif; ?>

                <!-- Settings (Super Admin only) -->
                <?php if ($_SESSION['user_role'] == 'super_admin'): ?>
                <li class="sidebar-heading">PENGATURAN</li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-shield"></i>
                        Manajemen User
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i>
                        Pengaturan Fee
                    </a>
                </li>
                <?php endif; ?>

                <!-- System -->
                <li class="sidebar-heading">SYSTEM</li>
                <li class="nav-item">
                    <a href="<?= APP_URL ?>/logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarMenu">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">
            <i class="fas fa-satellite-dish text-primary"></i> <strong>Starlink</strong> Manager
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <!-- User Panel -->
        <div class="text-center py-4 border-bottom">
            <i class="fas fa-user-circle fa-3x text-primary mb-2"></i>
            <h6 class="mb-0"><?= $_SESSION['nama_lengkap'] ?></h6>
            <small class="text-muted"><?= getRoleName($_SESSION['user_role']) ?></small>
        </div>

        <!-- Navigation -->
        <nav class="mt-2">
            <ul class="nav flex-column">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= APP_URL ?>/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>

                <!-- Status Pelanggan -->
                <li class="sidebar-heading">STATUS PELANGGAN</li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/jatuh-tempo.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'jatuh-tempo.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt text-info"></i>
                        Jatuh Tempo
                        <span class="badge bg-info">0</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/proses.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'proses.php' ? 'active' : '' ?>">
                        <i class="fas fa-sync-alt text-warning"></i>
                        Proses
                        <span class="badge bg-warning text-dark">0</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/segera.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'segera.php' ? 'active' : '' ?>">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        Segera
                        <span class="badge bg-danger">0</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/observasi.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'observasi.php' ? 'active' : '' ?>">
                        <i class="fas fa-eye text-secondary"></i>
                        Observasi
                        <span class="badge bg-secondary">0</span>
                    </a>
                </li>

                <!-- Data Management -->
                <li class="sidebar-heading">DATA MANAGEMENT</li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/pelanggan.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pelanggan.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        Data Pelanggan
                    </a>
                </li>

                <?php if (hasPermission('view_pembayaran')): ?>
                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/pembayaran.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pembayaran.php' ? 'active' : '' ?>">
                        <i class="fas fa-money-bill-wave"></i>
                        Pembayaran
                    </a>
                </li>
                <?php endif; ?>

                <!-- Laporan -->
                <?php if (hasPermission('view_laporan')): ?>
                <li class="sidebar-heading">LAPORAN</li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/laporan-fee.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'laporan-fee.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        Laporan Fee
                    </a>
                </li>
                <?php endif; ?>

                <!-- Settings (Super Admin only) -->
                <?php if ($_SESSION['user_role'] == 'super_admin'): ?>
                <li class="sidebar-heading">PENGATURAN</li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-shield"></i>
                        Manajemen User
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= APP_URL ?>/pages/settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i>
                        Pengaturan Fee
                    </a>
                </li>
                <?php endif; ?>

                <!-- System -->
                <li class="sidebar-heading">SYSTEM</li>
                <li class="nav-item">
                    <a href="<?= APP_URL ?>/logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>
