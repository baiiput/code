<?php
/**
 * Authentication Functions
 */

require_once __DIR__ . '/../config/database.php';

function login($username, $password) {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Log activity
        logActivity('login', 'auth', $user['id'], 'User logged in');

        return true;
    }

    return false;
}

function logout() {
    if (isLoggedIn()) {
        logActivity('logout', 'auth', $_SESSION['user_id'], 'User logged out');
    }

    session_destroy();
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    return in_array($_SESSION['role'], $roles);
}

function requireRole($roles) {
    if (!hasRole($roles)) {
        setFlash('error', 'Anda tidak memiliki akses ke halaman ini');
        header('Location: ' . BASE_URL . 'modules/dashboard/');
        exit;
    }
}

function changePassword($userId, $oldPassword, $newPassword) {
    $db = getDB();

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($oldPassword, $user['password'])) {
        return false;
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $userId]);

    logActivity('change_password', 'auth', $userId, 'Password changed');

    return true;
}

function logActivity($action, $module, $referenceId = null, $description = '') {
    $db = getDB();

    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, module, reference_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $module, $referenceId, $description, $ip]);
}
