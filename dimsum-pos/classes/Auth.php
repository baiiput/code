<?php
/**
 * Authentication Class
 */
class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($username, $password) {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE username = ? AND is_active = 1",
            [$username]
        );

        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['cabang_id'] = $user['cabang_id'];

            // Get cabang name
            if ($user['cabang_id']) {
                $cabang = $this->db->fetch("SELECT nama FROM cabang WHERE id = ?", [$user['cabang_id']]);
                $_SESSION['cabang_nama'] = $cabang ? $cabang['nama'] : '';
            } else {
                $_SESSION['cabang_nama'] = 'Semua Cabang';
            }

            return true;
        }

        return false;
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function user($key = null) {
        if (!self::check()) return null;

        if ($key) {
            return $_SESSION[$key] ?? null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'nama_lengkap' => $_SESSION['nama_lengkap'],
            'role' => $_SESSION['role'],
            'cabang_id' => $_SESSION['cabang_id'],
            'cabang_nama' => $_SESSION['cabang_nama']
        ];
    }

    public static function role() {
        return $_SESSION['role'] ?? null;
    }

    public static function cabangId() {
        return $_SESSION['cabang_id'] ?? null;
    }

    public static function isSuperAdmin() {
        return self::role() === 'super_admin';
    }

    public static function isOwner() {
        return self::role() === 'owner';
    }

    public static function isAdminCabang() {
        return self::role() === 'admin_cabang';
    }

    public static function isKasir() {
        return self::role() === 'kasir';
    }

    public static function hasAccess($allowedRoles) {
        if (is_string($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        return in_array(self::role(), $allowedRoles);
    }

    public static function requireLogin() {
        if (!self::check()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }

    public static function requireRole($roles) {
        self::requireLogin();
        if (!self::hasAccess($roles)) {
            header('Location: ' . BASE_URL . 'index.php?error=unauthorized');
            exit;
        }
    }
}
