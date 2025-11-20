<?php
/**
 * Authentication System with Multi User Level
 * Koperasi Syariah Online
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// User Level Constants
define('USER_LEVEL_SUPERADMIN', 1);
define('USER_LEVEL_MANAGER', 2);
define('USER_LEVEL_STAFF', 3);
define('USER_LEVEL_CUSTOMER', 4);

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
 * Check user level
 */
function getUserLevel() {
    return $_SESSION['user_level'] ?? USER_LEVEL_CUSTOMER;
}

/**
 * Check if user is super admin (level 1)
 */
function isSuperAdmin() {
    return isLoggedIn() && getUserLevel() === USER_LEVEL_SUPERADMIN;
}

/**
 * Check if user is manager (level 2)
 */
function isManager() {
    return isLoggedIn() && getUserLevel() === USER_LEVEL_MANAGER;
}

/**
 * Check if user is staff (level 3)
 */
function isStaff() {
    return isLoggedIn() && getUserLevel() === USER_LEVEL_STAFF;
}

/**
 * Check if user has permission level (user level <= required level)
 */
function hasPermission($requiredLevel) {
    return isLoggedIn() && getUserLevel() <= $requiredLevel;
}

/**
 * Get user level name
 */
function getUserLevelName($level = null) {
    if ($level === null) {
        $level = getUserLevel();
    }

    $levels = [
        USER_LEVEL_SUPERADMIN => 'Super Admin',
        USER_LEVEL_MANAGER => 'Manager',
        USER_LEVEL_STAFF => 'Staff',
        USER_LEVEL_CUSTOMER => 'Customer'
    ];

    return $levels[$level] ?? 'Unknown';
}

/**
 * Require specific user level
 */
function requireLevel($requiredLevel, $redirect = '/admin/index.php') {
    requireLogin();
    if (!hasPermission($requiredLevel)) {
        setFlashMessage('error', 'Anda tidak memiliki akses ke halaman ini');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Require super admin only
 */
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        setFlashMessage('error', 'Hanya Super Admin yang dapat mengakses halaman ini');
        header('Location: /admin/index.php');
        exit;
    }
}

/**
 * Login user
 */
function loginUser($userId, $username, $role, $customerId = null, $userLevel = USER_LEVEL_CUSTOMER, $fullName = null) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['customer_id'] = $customerId;
    $_SESSION['user_level'] = $userLevel;
    $_SESSION['full_name'] = $fullName;
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
        'customer_id' => $_SESSION['customer_id'] ?? null,
        'user_level' => $_SESSION['user_level'] ?? USER_LEVEL_CUSTOMER,
        'level_name' => getUserLevelName(),
        'full_name' => $_SESSION['full_name'] ?? $_SESSION['username']
    ];
}
?>
