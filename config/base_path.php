<?php
/**
 * Base Path Configuration
 * Koperasi Syariah Online
 *
 * PENTING: Sesuaikan BASE_PATH dengan lokasi hosting Anda
 */

// Untuk hosting di root domain (contoh: koperasi.com)
// define('BASE_PATH', '/');

// Untuk hosting di subfolder (contoh: galaxy.octolink.id/koperasi)
define('BASE_PATH', '/koperasi');

// Untuk localhost development
// define('BASE_PATH', '');

/**
 * Helper function untuk generate URL
 */
function baseUrl($path = '') {
    $path = ltrim($path, '/');
    return BASE_PATH . ($path ? '/' . $path : '');
}

/**
 * Helper function untuk redirect
 */
function redirectTo($path) {
    header('Location: ' . baseUrl($path));
    exit;
}
?>
