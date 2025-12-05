<?php
/**
 * Database Configuration
 * Starlink Financial Report System
 */

// Database credentials - UPDATE THESE VALUES
define('DB_HOST', 'localhost');
define('DB_NAME', 'starlink_finance');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Starlink Finance');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database connection
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    return $pdo;
}

// Helper function to format currency
function formatRupiah($amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Helper function to format date
function formatTanggal($date): string {
    return date('d M Y', strtotime($date));
}

// Security headers
function setSecurityHeaders(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Session management
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true
        ]);
    }
}

// Check if user is logged in
function isLoggedIn(): bool {
    initSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// Get current user info
function getCurrentUser(): ?array {
    initSession();
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama' => $_SESSION['user_nama'],
        'role' => $_SESSION['user_role']
    ];
}

// Check if user is admin
function isAdmin(): bool {
    initSession();
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

// Check if user is editor
function isEditor(): bool {
    initSession();
    return isLoggedIn() && $_SESSION['user_role'] === 'editor';
}

// Check if user can add transactions (admin or editor)
function canAdd(): bool {
    return isAdmin() || isEditor();
}

// Check if user can edit/delete (admin only)
function canEdit(): bool {
    return isAdmin();
}

// Require authentication
function requireAuth(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required', 'code' => 'AUTH_REQUIRED']);
        exit;
    }
}

// Require admin role
function requireAdmin(): void {
    requireAuth();
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required', 'code' => 'ADMIN_REQUIRED']);
        exit;
    }
}

// Require add permission (admin or editor)
function requireAddPermission(): void {
    requireAuth();
    if (!canAdd()) {
        http_response_code(403);
        echo json_encode(['error' => 'Add permission required', 'code' => 'ADD_REQUIRED']);
        exit;
    }
}
