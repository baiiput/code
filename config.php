<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'warehouse');
define('DB_PASS', 'warehouse');
define('DB_NAME', 'warehouse');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    }
    return $_SESSION['role'] === $roles;
}

// Get current user info
function getCurrentUser() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'cabang_id' => $_SESSION['cabang_id'] ?? null
    ];
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirect if not authorized
function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: index.php');
        exit;
    }
}

// Format currency
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Format number
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

// Generate transaction code
function generateTransactionCode($prefix) {
    return $prefix . date('Ymd') . rand(1000, 9999);
}

// Get stock status color and text
function getStockStatus($current_stock, $min_stock) {
    if ($current_stock <= 0) {
        return ['status' => 'Kosong', 'class' => 'danger'];
    } elseif ($current_stock <= $min_stock) {
        return ['status' => 'Krisis', 'class' => 'warning'];
    } elseif ($current_stock <= ($min_stock * 2)) {
        return ['status' => 'Perlu Restock', 'class' => 'info'];
    } else {
        return ['status' => 'Aman', 'class' => 'success'];
    }
}

// Sanitize input
function clean($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>
