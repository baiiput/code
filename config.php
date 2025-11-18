<?php
// ==========================================
// CONFIGURATION & DATABASE
// ==========================================

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'galaxy_db');
define('DB_PASS', 'galaxy_db');
define('DB_NAME', 'galaxy_db');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// AUTH FUNCTIONS
// ==========================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isKaryawan() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'karyawan';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

function requireKaryawan() {
    requireLogin();
    if (!isKaryawan()) {
        header("Location: dashboard.php");
        exit();
    }
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// ==========================================
// BONUS CALCULATION - 4 ACTIVITY LEVELS + 5 EMPLOYEE LEVELS
// ==========================================

// Bonus rates based on employee level (Level 1-5)
function getBonusRates() {
    return [
        '1' => ['A' => 10000, 'B' => 15000, 'C' => 25000, 'D' => 50000],
        '2' => ['A' => 12000, 'B' => 20000, 'C' => 35000, 'D' => 65000],
        '3' => ['A' => 15000, 'B' => 25000, 'C' => 45000, 'D' => 80000],  // default
        '4' => ['A' => 18000, 'B' => 30000, 'C' => 55000, 'D' => 95000],
        '5' => ['A' => 20000, 'B' => 35000, 'C' => 65000, 'D' => 110000],
    ];
}

// Calculate activity level (A, B, C, D) based on duration
function getActivityLevel($durasi_jam) {
    if ($durasi_jam < 1) {
        return 'A';  // Level A: < 1 jam
    } elseif ($durasi_jam >= 1 && $durasi_jam < 2) {
        return 'B';  // Level B: 1 - 1.9 jam
    } elseif ($durasi_jam >= 2 && $durasi_jam < 4) {
        return 'C';  // Level C: 2 - 3.9 jam
    } else {
        return 'D';  // Level D: ≥ 4 jam
    }
}

// Calculate bonus based on duration and employee level
function calculateLevelBonus($durasi_jam, $level_karyawan = '3') {
    $activity_level = getActivityLevel($durasi_jam);
    $bonus_rates = getBonusRates();
    
    // Get bonus for this employee level
    $bonus = $bonus_rates[$level_karyawan][$activity_level];
    
    return [
        'level' => $activity_level,
        'bonus' => $bonus
    ];
}

function calculateDuration($jam_mulai, $jam_selesai) {
    $start = new DateTime($jam_mulai);
    $end = new DateTime($jam_selesai);
    
    // Jika jam selesai lebih kecil dari jam mulai, tambahkan 1 hari (kerja melewati tengah malam)
    if ($end < $start) {
        $end->modify('+1 day');
    }
    
    $interval = $start->diff($end);
    $hours = $interval->h + ($interval->i / 60);
    
    return round($hours, 2);
}

// ==========================================
// FILE UPLOAD FUNCTIONS
// ==========================================

function uploadMultipleFiles($files, $allowed_extensions = ['jpg', 'jpeg', 'png']) {
    $upload_dir = __DIR__ . '/uploads/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded_files = [];
    $errors = [];
    
    // Validasi jumlah file (max 5)
    $file_count = count($files['name']);
    if ($file_count > 5) {
        return ['success' => false, 'message' => 'Maksimal 5 foto'];
    }
    
    for ($i = 0; $i < $file_count; $i++) {
        // Skip jika tidak ada file
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        // Check error
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "File " . ($i + 1) . ": Error upload";
            continue;
        }
        
        // Check file size (max 5MB per file)
        if ($files['size'][$i] > 5 * 1024 * 1024) {
            $errors[] = "File " . ($i + 1) . ": Ukuran terlalu besar (max 5MB)";
            continue;
        }
        
        // Get file extension
        $file_extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        
        // Check allowed extensions
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "File " . ($i + 1) . ": Format tidak diizinkan";
            continue;
        }
        
        // Generate unique filename
        $new_filename = uniqid() . '_' . time() . '_' . $i . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
            $uploaded_files[] = $new_filename;
        } else {
            $errors[] = "File " . ($i + 1) . ": Gagal upload";
        }
    }
    
    if (count($errors) > 0) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }
    
    return ['success' => true, 'files' => $uploaded_files];
}

// ==========================================
// USER FUNCTIONS
// ==========================================

function getUserInfo($user_id) {
    global $conn;
    
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

// ==========================================
// AKTIVITAS FUNCTIONS
// ==========================================

function getAktivitasUser($user_id, $month = null, $year = null) {
    global $conn;
    
    $query = "SELECT * FROM aktivitas WHERE user_id = ?";
    $params = [$user_id];
    $types = "i";
    
    if ($month !== null && $year !== null) {
        $query .= " AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
        $params[] = $month;
        $params[] = $year;
        $types .= "ii";
    }
    
    $query .= " ORDER BY tanggal DESC, waktu_input DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $aktivitas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $aktivitas[] = $row;
    }
    
    return $aktivitas;
}

function getDailyBonus($user_id, $tanggal) {
    global $conn;
    
    // Get employee level
    $user_query = "SELECT level_karyawan FROM users WHERE id = ?";
    $user_stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user_data = mysqli_fetch_assoc($user_result);
    $level_karyawan = $user_data['level_karyawan'] ?? '3'; // default level 3
    
    // Get total duration for the day
    $query = "SELECT SUM(durasi_jam) as total_durasi 
              FROM aktivitas 
              WHERE user_id = ? AND tanggal = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $tanggal);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $total_durasi = $row['total_durasi'] ?? 0;
    $levelBonus = calculateLevelBonus($total_durasi, $level_karyawan);
    
    return [
        'total_durasi' => $total_durasi,
        'level' => $levelBonus['level'],
        'bonus' => $levelBonus['bonus']
    ];
}

function getMonthlyBonusAccurate($user_id, $month = null, $year = null) {
    global $conn;
    
    if ($month === null) $month = date('m');
    if ($year === null) $year = date('Y');
    
    $query = "SELECT DISTINCT tanggal 
              FROM aktivitas 
              WHERE user_id = ? 
              AND MONTH(tanggal) = ? 
              AND YEAR(tanggal) = ?
              ORDER BY tanggal";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $month, $year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $total_bonus_bulan = 0;
    $detail_per_hari = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $tanggal = $row['tanggal'];
        $daily = getDailyBonus($user_id, $tanggal);
        $total_bonus_bulan += $daily['bonus'];
        
        $detail_per_hari[] = [
            'tanggal' => $tanggal,
            'durasi' => $daily['total_durasi'],
            'level' => $daily['level'],
            'bonus' => $daily['bonus']
        ];
    }
    
    return [
        'total_bonus' => $total_bonus_bulan,
        'detail_per_hari' => $detail_per_hari
    ];
}

function getMonthlySalary($user_id, $month = null, $year = null) {
    if ($month === null) $month = date('m');
    if ($year === null) $year = date('Y');
    
    $gaji_pokok = 300000;
    $bonus_data = getMonthlyBonusAccurate($user_id, $month, $year);
    $total_bonus = $bonus_data['total_bonus'];
    $total_gaji = $gaji_pokok + $total_bonus;
    
    if ($total_gaji > 2500000) {
        $total_gaji = 2500000;
    }
    
    return [
        'gaji_pokok' => $gaji_pokok,
        'total_bonus' => $total_bonus,
        'total_gaji' => $total_gaji,
        'detail_per_hari' => $bonus_data['detail_per_hari']
    ];
}

// ==========================================
// ADMIN FUNCTIONS
// ==========================================

function getAdminStats() {
    global $conn;
    
    $stats = [];
    
    // Total karyawan aktif
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'karyawan' AND status = 'aktif'";
    $result = mysqli_query($conn, $query);
    $stats['total_karyawan'] = mysqli_fetch_assoc($result)['total'];
    
    // Total aktivitas bulan ini
    $query = "SELECT COUNT(*) as total FROM aktivitas WHERE MONTH(tanggal) = MONTH(CURRENT_DATE) AND YEAR(tanggal) = YEAR(CURRENT_DATE)";
    $result = mysqli_query($conn, $query);
    $stats['total_aktivitas'] = mysqli_fetch_assoc($result)['total'];
    
    // Total bonus bulan ini
    $total_bonus_semua = 0;
    $query = "SELECT id FROM users WHERE role = 'karyawan' AND status = 'aktif'";
    $result = mysqli_query($conn, $query);
    
    while ($karyawan = mysqli_fetch_assoc($result)) {
        $bonus_data = getMonthlyBonusAccurate($karyawan['id'], date('m'), date('Y'));
        $total_bonus_semua += $bonus_data['total_bonus'];
    }
    
    $stats['total_bonus'] = $total_bonus_semua;
    $stats['total_gaji'] = ($stats['total_karyawan'] * 300000) + $stats['total_bonus'];
    
    return $stats;
}

// ==========================================
// BADGE COLOR HELPER (Updated for 4 levels)
// ==========================================

function getLevelBadgeClass($level) {
    switch($level) {
        case 'A': return 'danger';   // Red
        case 'B': return 'warning';  // Yellow
        case 'C': return 'info';     // Blue
        case 'D': return 'success';  // Green
        default: return 'secondary';
    }
}
?>
