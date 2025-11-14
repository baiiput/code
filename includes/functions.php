<?php
/**
 * Helper Functions
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
function formatTanggal($date, $format = 'd-m-Y') {
    if (empty($date)) return '-';

    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($date);
    $tanggal = date('j', $timestamp);
    $bulan = $bulanIndo[date('n', $timestamp)];
    $tahun = date('Y', $timestamp);

    return "{$tanggal} {$bulan} {$tahun}";
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to another page
 */
function redirect($url) {
    header("Location: {$url}");
    exit();
}

/**
 * Get current month name in Indonesian
 */
function getBulanIndo($month = null) {
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $month = $month ?? date('n');
    return $bulanIndo[$month];
}

/**
 * JSON Response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Get request method
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get POST data
 */
function getPostData() {
    return json_decode(file_get_contents('php://input'), true) ?? $_POST;
}
