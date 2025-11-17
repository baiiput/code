<?php
/**
 * Application Configuration
 * Starlink Customer Management System
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Application settings
define('APP_NAME', 'Starlink Customer Management');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/starlink'); // SESUAIKAN dengan URL hosting Anda

// Path settings
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// URL paths
define('ASSETS_URL', APP_URL . '/assets');
define('UPLOADS_URL', APP_URL . '/uploads');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security settings
define('SECURE_COOKIE', false); // Set true if using HTTPS
define('SESSION_LIFETIME', 3600); // 1 hour

// Pagination
define('RECORDS_PER_PAGE', 25);

// Date format
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');

// Currency
define('CURRENCY_SYMBOL', 'Rp');

// Include database connection
require_once CONFIG_PATH . '/database.php';

// Include helper functions
require_once INCLUDES_PATH . '/functions.php';

// Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit();
    }
}

// Check user role
function checkRole($allowedRoles = []) {
    checkLogin();

    if (!empty($allowedRoles) && !in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: ' . APP_URL . '/unauthorized.php');
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama' => $_SESSION['nama_lengkap'],
        'role' => $_SESSION['user_role']
    ];
}
