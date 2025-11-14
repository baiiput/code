<?php
/**
 * Konfigurasi Database
 * Sesuaikan dengan kredensial database Anda
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Sesuaikan dengan username MySQL Anda
define('DB_PASS', '');      // Sesuaikan dengan password MySQL Anda
define('DB_NAME', 'jimpitan_rt');
define('DB_CHARSET', 'utf8mb4');

/**
 * Konfigurasi Aplikasi
 */
define('BASE_URL', 'http://localhost');  // Sesuaikan dengan domain Anda
define('APP_NAME', 'Sistem Jimpitan RT');
define('APP_VERSION', '1.0.0');

/**
 * Timezone
 */
date_default_timezone_set('Asia/Jakarta');

/**
 * Error Reporting (matikan di production)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
