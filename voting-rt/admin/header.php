<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin - Sistem Voting RT' ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>&#9745; Voting RT</h2>
                <span>Admin Panel</span>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <span class="icon">&#127968;</span> Dashboard
                </a>
                <a href="pemilih.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'pemilih.php' ? 'active' : '' ?>">
                    <span class="icon">&#128101;</span> Pemilih
                </a>
                <a href="kandidat.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'kandidat.php' ? 'active' : '' ?>">
                    <span class="icon">&#128100;</span> Kandidat
                </a>
                <a href="hasil.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'hasil.php' ? 'active' : '' ?>">
                    <span class="icon">&#128200;</span> Hasil
                </a>
                <a href="pengaturan.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'pengaturan.php' ? 'active' : '' ?>">
                    <span class="icon">&#9881;</span> Pengaturan
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="<?= APP_URL ?>" target="_blank">&#128065; Lihat Website</a>
                <a href="logout.php">&#128682; Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
                </div>
                <div class="header-right">
                    <span class="admin-name"><?= $_SESSION['admin_nama'] ?? 'Admin' ?></span>
                </div>
            </header>

            <?= showFlashMessage() ?>
