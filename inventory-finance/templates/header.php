<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= APP_NAME ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="main-content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="navbar-left">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="hide-mobile"><?= e($pageTitle) ?></span>
                </div>

                <div class="navbar-right">
                    <!-- Theme Toggle -->
                    <button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">
                        <i class="fas fa-moon"></i>
                    </button>

                    <!-- Notifications -->
                    <?php
                    $lowStockCount = count(getLowStockProducts());
                    $expiringWarranties = count(getExpiringWarranties(30));
                    ?>
                    <?php if ($lowStockCount > 0 || $expiringWarranties > 0): ?>
                    <div class="dropdown">
                        <button class="btn btn-icon" data-toggle="dropdown" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="badge bg-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.6rem;">
                                <?= $lowStockCount + $expiringWarranties ?>
                            </span>
                        </button>
                        <div class="dropdown-menu">
                            <?php if ($lowStockCount > 0): ?>
                            <a href="<?= BASE_URL ?>modules/products/?filter=low_stock" class="dropdown-item">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                <?= $lowStockCount ?> produk stok menipis
                            </a>
                            <?php endif; ?>
                            <?php if ($expiringWarranties > 0): ?>
                            <a href="<?= BASE_URL ?>modules/products/warranties.php" class="dropdown-item">
                                <i class="fas fa-clock text-info me-2"></i>
                                <?= $expiringWarranties ?> garansi akan berakhir
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="user-dropdown-btn" data-toggle="dropdown">
                            <div class="user-avatar">
                                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                            </div>
                            <span class="hide-mobile"><?= e($currentUser['name']) ?></span>
                            <i class="fas fa-chevron-down small hide-mobile"></i>
                        </button>
                        <div class="dropdown-menu">
                            <div class="dropdown-item" style="pointer-events: none;">
                                <small class="text-muted"><?= getRoleLabel($currentUser['role']) ?></small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="<?= BASE_URL ?>modules/users/profile.php" class="dropdown-item">
                                <i class="fas fa-user me-2"></i> Profil
                            </a>
                            <?php if (hasRole(['admin'])): ?>
                            <a href="<?= BASE_URL ?>modules/users/settings.php" class="dropdown-item">
                                <i class="fas fa-cog me-2"></i> Pengaturan
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= BASE_URL ?>logout.php" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <?php
                $flash = getFlash();
                if ($flash):
                ?>
                <?= alertBox($flash['type'], $flash['message']) ?>
                <?php endif; ?>
