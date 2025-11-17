    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="<?= APP_URL ?>/index.php" class="brand-link">
            <i class="brand-image fas fa-satellite-dish"></i>
            <span class="brand-text font-weight-light"><b>Starlink</b> Manager</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <i class="fas fa-user-circle fa-2x text-white"></i>
                </div>
                <div class="info">
                    <a href="#" class="d-block"><?= $_SESSION['nama_lengkap'] ?></a>
                    <small class="text-muted"><?= getRoleName($_SESSION['user_role']) ?></small>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- Menu Status (4 menu utama) -->
                    <li class="nav-header">STATUS PELANGGAN</li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/jatuh-tempo.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'jatuh-tempo.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-alt text-info"></i>
                            <p>
                                Jatuh Tempo
                                <span class="badge badge-info right" id="badge-jatuh-tempo">0</span>
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/proses.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'proses.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-sync-alt text-warning"></i>
                            <p>
                                Proses
                                <span class="badge badge-warning right" id="badge-proses">0</span>
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/segera.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'segera.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-exclamation-triangle text-danger"></i>
                            <p>
                                Segera
                                <span class="badge badge-danger right" id="badge-segera">0</span>
                            </p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/observasi.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'observasi.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-eye text-secondary"></i>
                            <p>
                                Observasi
                                <span class="badge badge-secondary right" id="badge-observasi">0</span>
                            </p>
                        </a>
                    </li>

                    <!-- Data Management -->
                    <li class="nav-header">DATA MANAGEMENT</li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/pelanggan.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pelanggan.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Data Pelanggan</p>
                        </a>
                    </li>

                    <?php if (hasPermission('view_pembayaran')): ?>
                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/pembayaran.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pembayaran.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>Pembayaran</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Reports -->
                    <?php if (hasPermission('view_laporan')): ?>
                    <li class="nav-header">LAPORAN</li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/laporan-fee.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'laporan-fee.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Laporan Fee</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/laporan-pembayaran.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'laporan-pembayaran.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Laporan Pembayaran</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Settings (Super Admin only) -->
                    <?php if ($_SESSION['user_role'] == 'super_admin'): ?>
                    <li class="nav-header">PENGATURAN</li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Manajemen User</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/pages/settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Pengaturan Fee</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Logout -->
                    <li class="nav-header">SYSTEM</li>
                    <li class="nav-item">
                        <a href="<?= APP_URL ?>/logout.php" class="nav-link text-danger">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>
