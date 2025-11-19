<?php
/**
 * Helper Class - Utility Functions
 */
class Helper {

    /**
     * Format currency to Rupiah
     */
    public static function rupiah($amount, $withPrefix = true) {
        $formatted = number_format($amount, 0, ',', '.');
        return $withPrefix ? 'Rp ' . $formatted : $formatted;
    }

    /**
     * Format date to Indonesian format
     */
    public static function tanggal($date, $withTime = false) {
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $timestamp = strtotime($date);
        $d = date('j', $timestamp);
        $m = $bulan[(int)date('n', $timestamp)];
        $y = date('Y', $timestamp);

        $result = "{$d} {$m} {$y}";

        if ($withTime) {
            $result .= ' ' . date('H:i', $timestamp);
        }

        return $result;
    }

    /**
     * Generate transaction number
     */
    public static function generateNoTransaksi($cabangId) {
        $prefix = 'TRX';
        $date = date('Ymd');
        $db = Database::getInstance();

        // Get last transaction number for today
        $last = $db->fetch(
            "SELECT no_transaksi FROM transaksi
             WHERE cabang_id = ? AND DATE(created_at) = CURDATE()
             ORDER BY id DESC LIMIT 1",
            [$cabangId]
        );

        if ($last) {
            $lastNum = (int)substr($last['no_transaksi'], -4);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }

        return $prefix . $cabangId . $date . str_pad($newNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Flash message
     */
    public static function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /**
     * Redirect
     */
    public static function redirect($url) {
        header('Location: ' . BASE_URL . $url);
        exit;
    }

    /**
     * Upload image
     */
    public static function uploadImage($file, $folder = 'menu') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload gagal'];
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)'];
        }

        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
            return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $ext;
        $uploadPath = UPLOADS_PATH . $folder . '/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
            return ['success' => true, 'filename' => $filename];
        }

        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }

    /**
     * Delete image
     */
    public static function deleteImage($filename, $folder = 'menu') {
        $filepath = UPLOADS_PATH . $folder . '/' . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Get role label
     */
    public static function roleLabel($role) {
        $labels = [
            'super_admin' => 'Super Admin',
            'owner' => 'Owner',
            'admin_cabang' => 'Admin Cabang',
            'kasir' => 'Kasir'
        ];
        return $labels[$role] ?? $role;
    }

    /**
     * Get status badge
     */
    public static function statusBadge($status, $type = 'transaksi') {
        $badges = [
            'transaksi' => [
                'pending' => '<span class="badge bg-warning">Pending</span>',
                'completed' => '<span class="badge bg-success">Selesai</span>',
                'cancelled' => '<span class="badge bg-danger">Batal</span>'
            ]
        ];
        return $badges[$type][$status] ?? $status;
    }

    /**
     * Pagination helper
     */
    public static function paginate($total, $perPage, $currentPage) {
        $totalPages = ceil($total / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;

        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'offset' => $offset
        ];
    }

    /**
     * CSRF Token
     */
    public static function generateCsrf() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generateCsrf() . '">';
    }
}
