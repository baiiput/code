<?php
/**
 * General Application Configuration
 */

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Application Settings
define('APP_NAME', 'Sistem Laporan Keuangan');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost'); // Ganti dengan URL domain Anda

// Session Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Pagination
define('ITEMS_PER_PAGE', 25);

// Currency Format
define('CURRENCY_SYMBOL', 'Rp');
define('CURRENCY_CODE', 'IDR');

// Tax Default
define('DEFAULT_TAX_PERCENTAGE', 11); // PPN 11%

/**
 * Helper Functions
 */

function formatCurrency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 0, ',', '.');
}

function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }

    if (is_string($roles)) {
        $roles = [$roles];
    }

    return in_array($_SESSION['user_role'], $roles);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['user_role']
    ];
}

function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

function redirect($url) {
    header("Location: $url");
    exit;
}
