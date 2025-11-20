<?php
/**
 * Authentication System
 * Koperasi Syariah Online
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is customer
 */
function isCustomer() {
    return isLoggedIn() && $_SESSION['role'] === 'customer';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /customer/index.php');
        exit;
    }
}

/**
 * Require customer role
 */
function requireCustomer() {
    requireLogin();
    if (!isCustomer()) {
        header('Location: /admin/index.php');
        exit;
    }
}

/**
 * Login user
 */
function loginUser($userId, $username, $role, $customerId = null) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['customer_id'] = $customerId;
    $_SESSION['login_time'] = time();
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'customer_id' => $_SESSION['customer_id'] ?? null
    ];
}
?>
