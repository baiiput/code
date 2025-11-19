<?php
/**
 * Database Configuration
 * Dimsum Financial Report System
 */

// Database credentials - UBAH SESUAI SERVER ANDA
define('DB_HOST', 'localhost');
define('DB_NAME', 'dimsum_financial');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Laporan Keuangan Dimsum');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (set to 0 for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Database Connection Class
 */
class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}

/**
 * Helper Functions
 */

// Format currency to Indonesian Rupiah
function formatRupiah($number): string {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Format date to Indonesian format
function formatDate($date): string {
    return date('d/m/Y', strtotime($date));
}

// Format date to long Indonesian format
function formatDateLong($date): string {
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('d', strtotime($date));
    $m = $months[(int)date('m', strtotime($date))];
    $y = date('Y', strtotime($date));
    return "$d $m $y";
}

// Sanitize input
function sanitize($input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// JSON response helper
function jsonResponse($data, $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get all branches
function getBranches(): array {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY name");
    return $stmt->fetchAll();
}

// Get expense categories
function getExpenseCategories(): array {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM expense_categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Authentication Functions
 */

// Check if user is logged in
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, username, name, role FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// Check if current user has specific role
function hasRole(string|array $roles): bool {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }

    if (is_string($roles)) {
        $roles = [$roles];
    }

    return in_array($user['role'], $roles);
}

// Check if user can view (everyone can view)
function canView(): bool {
    return true; // Public access for viewing
}

// Check if user can add transactions (editor, admin)
function canAdd(): bool {
    return hasRole(['editor', 'admin']);
}

// Check if user can edit transactions (admin only)
function canEdit(): bool {
    return hasRole(['admin']);
}

// Check if user can delete (admin only)
function canDelete(): bool {
    return hasRole(['admin']);
}

// Check if user can manage branches (admin only)
function canManageBranches(): bool {
    return hasRole(['admin']);
}

// Check if user can manage users (admin only)
function canManageUsers(): bool {
    return hasRole(['admin']);
}

// Require login - redirect if not logged in
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Require specific role
function requireRole(string|array $roles): void {
    requireLogin();
    if (!hasRole($roles)) {
        $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini.';
        header('Location: index.php');
        exit;
    }
}

// Login user
function loginUser(string $username, string $password): bool {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];

        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        return true;
    }

    return false;
}

// Logout user
function logoutUser(): void {
    unset($_SESSION['user_id']);
    session_destroy();
}

// Get user initial for avatar
function getUserInitial(?array $user = null): string {
    if (!$user) {
        $user = getCurrentUser();
    }
    if (!$user) {
        return 'G';
    }
    return strtoupper(substr($user['name'], 0, 1));
}

// Get role badge class
function getRoleBadgeClass(string $role): string {
    return match($role) {
        'admin' => 'bg-danger',
        'editor' => 'bg-primary',
        default => 'bg-secondary'
    };
}

// Get role display name
function getRoleDisplayName(string $role): string {
    return match($role) {
        'admin' => 'Administrator',
        'editor' => 'Editor',
        default => 'Viewer'
    };
}
