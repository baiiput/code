<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'starlink_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// App Configuration
define('APP_NAME', 'Starlink Customer Management');
define('APP_URL', 'http://localhost');

// Session Configuration
session_start();

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper Functions
function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireRole($roles) {
    $user = currentUser();
    if (!$user) redirect('login.php');

    if (!is_array($roles)) $roles = [$roles];

    // Super admin has access to everything
    if ($user['role'] === 'super_admin') return true;

    if (!in_array($user['role'], $roles)) {
        die('Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
    }
    return true;
}

function canManageCustomers() {
    $user = currentUser();
    return $user && in_array($user['role'], ['super_admin', 'admin']);
}

function canManagePayments() {
    $user = currentUser();
    return $user && $user['role'] === 'super_admin';
}

function canManageUsers() {
    $user = currentUser();
    return $user && $user['role'] === 'super_admin';
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d M Y', strtotime($date));
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
