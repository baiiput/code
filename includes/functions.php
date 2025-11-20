<?php
/**
 * Helper Functions
 * Koperasi Syariah Online
 */

/**
 * Format currency to Rupiah
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format date to Indonesian format
 */
function formatTanggal($date) {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('j', $timestamp);
    $m = date('n', $timestamp);
    $y = date('Y', $timestamp);
    return $d . ' ' . $bulan[$m] . ' ' . $y;
}

/**
 * Format datetime to Indonesian format
 */
function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return formatTanggal($datetime) . ' ' . date('H:i', strtotime($datetime));
}

/**
 * Generate nomor kontrak
 */
function generateNomorKontrak() {
    return 'KSO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Sanitize input
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'];
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Get status badge color
 */
function getStatusBadge($status) {
    $badges = [
        'aktif' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'lunas' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'batal' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    ];
    return $badges[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
}

/**
 * Calculate remaining installments
 */
function calculateRemainingInstallments($totalHarga, $totalDibayar, $angsuranPerbulan) {
    $sisaHutang = $totalHarga - $totalDibayar;
    if ($sisaHutang <= 0) return 0;
    return ceil($sisaHutang / $angsuranPerbulan);
}
?>
