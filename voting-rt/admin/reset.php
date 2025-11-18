<?php
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('pengaturan.php');
}

$db = getDB();
$action = $_POST['action'] ?? '';

if ($action == 'reset_votes') {
    // Reset voting data only
    $db->exec("DELETE FROM voting");
    $db->exec("UPDATE users SET sudah_memilih = 0, waktu_memilih = NULL");

    logActivity('admin', $_SESSION['admin_id'], 'Reset Suara', 'Semua data suara direset');
    redirect('pengaturan.php', 'Semua data suara berhasil direset', 'success');

} elseif ($action == 'reset_all') {
    // Reset everything except admin and pengaturan
    $db->exec("DELETE FROM voting");
    $db->exec("DELETE FROM users");
    $db->exec("DELETE FROM kandidat");
    $db->exec("DELETE FROM log_aktivitas WHERE user_type = 'user'");

    logActivity('admin', $_SESSION['admin_id'], 'Reset Semua Data', 'Semua data pemilih, kandidat, dan suara direset');
    redirect('pengaturan.php', 'Semua data berhasil direset', 'success');

} else {
    redirect('pengaturan.php', 'Aksi tidak valid', 'danger');
}
