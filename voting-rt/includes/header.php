<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

$pengaturan = getPengaturan();
$statusPemilihan = getStatusPemilihan();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="<?= APP_URL ?>" class="logo">
                <span class="logo-icon">&#9745;</span>
                <?= APP_NAME ?>
            </a>
            <ul class="nav-menu">
                <li><a href="<?= APP_URL ?>">Beranda</a></li>
                <li><a href="<?= APP_URL ?>/kandidat.php">Kandidat</a></li>
                <li><a href="<?= APP_URL ?>/hasil.php">Hasil</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?= APP_URL ?>/vote.php">Vote</a></li>
                    <li><a href="<?= APP_URL ?>/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= APP_URL ?>/login.php">Login</a></li>
                    <li><a href="<?= APP_URL ?>/register.php" class="btn btn-primary">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <?= showFlashMessage() ?>
