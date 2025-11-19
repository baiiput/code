<?php
$theme = $_COOKIE['theme'] ?? 'light';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <?php if (isset($extraCss)): ?>
    <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>index.php" class="sidebar-brand">
                <i class="bi bi-shop me-2"></i>
                <span class="fw-bold"><?= APP_NAME ?></span>
            </a>
        </div>

        <nav class="sidebar-menu">
            <ul class="nav flex-column">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Kasir/POS -->
                <?php if (Auth::hasAccess(['super_admin', 'admin_cabang', 'kasir'])): ?>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/kasir.php" class="nav-link <?= $currentPage === 'kasir' ? 'active' : '' ?>">
                        <i class="bi bi-calculator"></i>
                        <span>Kasir</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Master Data -->
                <?php if (Auth::hasAccess(['super_admin', 'owner', 'admin_cabang'])): ?>
                <li class="menu-header">Master Data</li>

                <?php if (Auth::hasAccess(['super_admin', 'owner'])): ?>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/cabang.php" class="nav-link <?= $currentPage === 'cabang' ? 'active' : '' ?>">
                        <i class="bi bi-building"></i>
                        <span>Cabang</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/kategori.php" class="nav-link <?= $currentPage === 'kategori' ? 'active' : '' ?>">
                        <i class="bi bi-tags"></i>
                        <span>Kategori</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/menu.php" class="nav-link <?= $currentPage === 'menu' ? 'active' : '' ?>">
                        <i class="bi bi-journal-text"></i>
                        <span>Menu</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Transaksi -->
                <li class="menu-header">Transaksi</li>

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/transaksi.php" class="nav-link <?= $currentPage === 'transaksi' ? 'active' : '' ?>">
                        <i class="bi bi-receipt"></i>
                        <span>Riwayat Transaksi</span>
                    </a>
                </li>

                <!-- Promo & Diskon -->
                <?php if (Auth::hasAccess(['super_admin', 'owner', 'admin_cabang'])): ?>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/diskon.php" class="nav-link <?= $currentPage === 'diskon' ? 'active' : '' ?>">
                        <i class="bi bi-percent"></i>
                        <span>Diskon & Promo</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Laporan -->
                <?php if (Auth::hasAccess(['super_admin', 'owner', 'admin_cabang'])): ?>
                <li class="menu-header">Laporan</li>

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/laporan.php" class="nav-link <?= $currentPage === 'laporan' ? 'active' : '' ?>">
                        <i class="bi bi-bar-chart"></i>
                        <span>Laporan Penjualan</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Pengaturan -->
                <?php if (Auth::hasAccess(['super_admin', 'owner'])): ?>
                <li class="menu-header">Pengaturan</li>

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/users.php" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                        <i class="bi bi-people"></i>
                        <span>Pengguna</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= BASE_URL ?>pages/pajak.php" class="nav-link <?= $currentPage === 'pajak' ? 'active' : '' ?>">
                        <i class="bi bi-cash-stack"></i>
                        <span>Pengaturan Pajak</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <button class="btn btn-icon btn-light d-lg-none me-2" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0 d-none d-md-block"><?= $pageTitle ?? 'Dashboard' ?></h5>
            </div>
            <div class="header-right">
                <!-- Cabang Info -->
                <span class="badge bg-primary">
                    <i class="bi bi-building me-1"></i>
                    <?= Auth::user('cabang_nama') ?>
                </span>

                <!-- Theme Toggle -->
                <button class="btn btn-icon btn-light" id="themeToggle" title="Toggle Theme">
                    <i class="bi bi-moon-stars"></i>
                </button>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-md-inline"><?= Auth::user('nama_lengkap') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted"><?= Helper::roleLabel(Auth::role()) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>pages/profil.php">
                                <i class="bi bi-person me-2"></i>Profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">
            <?php
            // Flash message
            $flash = Helper::getFlash();
            if ($flash):
            ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
