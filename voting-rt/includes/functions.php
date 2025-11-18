<?php
/**
 * Helper Functions untuk Sistem Voting RT
 */

require_once __DIR__ . '/database.php';

/**
 * Sanitasi input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validasi CSRF Token
 */
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Redirect dengan pesan
 */
function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Tampilkan flash message
 */
function showFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Cek apakah admin sudah login
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Get pengaturan pemilihan
 */
function getPengaturan() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM pengaturan ORDER BY id DESC LIMIT 1");
    return $stmt->fetch();
}

/**
 * Cek status pemilihan
 */
function getStatusPemilihan() {
    $pengaturan = getPengaturan();
    if (!$pengaturan) return 'belum_mulai';

    $now = new DateTime();
    $mulai = new DateTime($pengaturan['tanggal_mulai']);
    $selesai = new DateTime($pengaturan['tanggal_selesai']);

    if ($now < $mulai) return 'belum_mulai';
    if ($now > $selesai) return 'selesai';
    return 'berlangsung';
}

/**
 * Format tanggal Indonesia
 */
function formatTanggal($date, $format = 'long') {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $dt = new DateTime($date);

    if ($format == 'long') {
        return $dt->format('d') . ' ' . $bulan[(int)$dt->format('m')] . ' ' . $dt->format('Y');
    } elseif ($format == 'datetime') {
        return $dt->format('d') . ' ' . $bulan[(int)$dt->format('m')] . ' ' . $dt->format('Y H:i');
    } else {
        return $dt->format('d/m/Y');
    }
}

/**
 * Upload file dengan validasi
 */
function uploadFile($file, $folder = '') {
    $uploadDir = UPLOAD_PATH . ($folder ? $folder . '/' : '');

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Validasi ekstensi
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Format file tidak diizinkan'];
    }

    // Validasi ukuran
    if ($fileSize > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar (max 2MB)'];
    }

    // Generate nama unik
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $destination = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmp, $destination)) {
        return ['success' => true, 'filename' => ($folder ? $folder . '/' : '') . $newFileName];
    }

    return ['success' => false, 'message' => 'Gagal mengupload file'];
}

/**
 * Log aktivitas
 */
function logActivity($userType, $userId, $aktivitas, $detail = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO log_aktivitas (user_type, user_id, aktivitas, detail, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userType, $userId, $aktivitas, $detail, $_SERVER['REMOTE_ADDR'] ?? '']);
}

/**
 * Get total suara per kandidat
 */
function getHasilVoting() {
    $db = getDB();
    $stmt = $db->query("
        SELECT k.*, COUNT(v.id) as jumlah_suara
        FROM kandidat k
        LEFT JOIN voting v ON k.id = v.kandidat_id
        WHERE k.status = 'active'
        GROUP BY k.id
        ORDER BY jumlah_suara DESC, k.no_urut ASC
    ");
    return $stmt->fetchAll();
}

/**
 * Get total pemilih
 */
function getTotalPemilih() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status_verifikasi = 'verified'");
    return $stmt->fetchColumn();
}

/**
 * Get total yang sudah memilih
 */
function getTotalSudahMemilih() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE sudah_memilih = 1");
    return $stmt->fetchColumn();
}

/**
 * Validasi NIK
 */
function isValidNIK($nik) {
    return preg_match('/^[0-9]{16}$/', $nik);
}

/**
 * Validasi No KK
 */
function isValidNoKK($nokk) {
    return preg_match('/^[0-9]{16}$/', $nokk);
}

/**
 * Password strength check
 */
function isStrongPassword($password) {
    return strlen($password) >= 6;
}
