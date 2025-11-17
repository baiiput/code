<?php
/**
 * Helper Functions
 * Starlink Customer Management System
 */

/**
 * Format rupiah
 */
function formatRupiah($angka) {
    return CURRENCY_SYMBOL . ' ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function formatTanggal($tanggal, $format = DATE_FORMAT) {
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return '-';
    }

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $bulanAngka = date('n', $timestamp);
    $tahun = date('Y', $timestamp);

    return $hari . ' ' . $bulan[$bulanAngka] . ' ' . $tahun;
}

/**
 * Format tanggal untuk input
 */
function formatTanggalInput($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return '';
    }
    return date('Y-m-d', strtotime($tanggal));
}

/**
 * Generate alert HTML
 */
function showAlert($message, $type = 'success') {
    $icons = [
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-times-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle'
    ];

    $icon = $icons[$type] ?? $icons['info'];

    return '
    <div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
        <i class="' . $icon . ' mr-2"></i>
        ' . $message . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
}

/**
 * Sanitize input
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Log activity
 */
function logActivity($db, $action, $table_name = null, $record_id = null, $description = null) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    try {
        $query = "INSERT INTO activity_log
                  (user_id, action, table_name, record_id, description, ip_address, user_agent)
                  VALUES (:user_id, :action, :table_name, :record_id, :description, :ip_address, :user_agent)";

        $stmt = $db->prepare($query);

        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':table_name', $table_name);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':description', $description);
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bindParam(':ip_address', $ip);
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $stmt->bindParam(':user_agent', $ua);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Log Activity Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get status badge class
 */
function getStatusBadge($status) {
    $badges = [
        'Lunas' => 'success',
        'Belum' => 'warning',
        'Pending' => 'info',
        'Rencana Bayarkan' => 'primary',
        'Active' => 'success',
        'Inactive' => 'danger'
    ];

    $badgeClass = $badges[$status] ?? 'secondary';
    return '<span class="badge badge-' . $badgeClass . '">' . $status . '</span>';
}

/**
 * Get days difference
 */
function getDaysDifference($date) {
    if (empty($date) || $date == '0000-00-00') {
        return null;
    }

    $today = new DateTime();
    $targetDate = new DateTime($date);
    $interval = $today->diff($targetDate);

    return $interval->days * ($interval->invert ? -1 : 1);
}

/**
 * Get status kategori (Jatuh Tempo, Proses, Segera, Observasi)
 */
function getStatusKategori($tanggal_jatuh_tempo, $status) {
    if ($status == 'Pending') {
        return 'observasi';
    }

    $days = getDaysDifference($tanggal_jatuh_tempo);

    if ($days === null) {
        return null;
    }

    // Jatuh tempo besok
    if ($days == 1) {
        return 'jatuh_tempo';
    }

    // Jatuh tempo kemarin
    if ($days == -1) {
        return 'proses';
    }

    // Sudah lunas tapi lewat tanggal
    if ($days < 0 && in_array($status, ['Lunas', 'Rencana Bayarkan'])) {
        return 'segera';
    }

    return null;
}

/**
 * Generate WhatsApp link
 */
function generateWhatsAppLink($nama, $nomor, $kitNumber, $paket, $tanggal_jatuh_tempo, $type = 'H-3') {
    $messages = [
        'H-3' => "📡 *Halo {$nama}, Pengguna Layanan Internet Starlink* 📡\n\n" .
                 "📌 *Informasi Layanan Anda:*\n" .
                 "🔹 *Nama:* {$nama}\n" .
                 "🔹 *KIT Number:* {$kitNumber}\n" .
                 "🔹 *Paket:* {$paket}\n" .
                 "🔹 *Tanggal Jatuh Tempo:* " . formatTanggal($tanggal_jatuh_tempo) . "\n\n" .
                 "📅 Kami ingin menginformasikan bahwa layanan Starlink Anda akan jatuh tempo dalam 3 hari.\n\n" .
                 "Untuk memastikan kesinambungan layanan internet Anda, mohon konfirmasi perpanjangan layanan.\n\n" .
                 "Jika memerlukan bantuan atau informasi lebih lanjut, silakan hubungi kami.\n\n" .
                 "Terima kasih atas perhatian dan kepercayaan Anda.\n\n" .
                 "🚀 *Octolink.id - Starlink UnOfficial Support* 🚀",

        'H-2' => "📡 *Halo {$nama}, Pengguna Layanan Internet Starlink* 📡\n\n" .
                 "📌 *Informasi Layanan Anda:*\n" .
                 "🔹 *Nama:* {$nama}\n" .
                 "🔹 *KIT Number:* {$kitNumber}\n" .
                 "🔹 *Paket:* {$paket}\n" .
                 "🔹 *Tanggal Jatuh Tempo:* " . formatTanggal($tanggal_jatuh_tempo) . "\n\n" .
                 "⚠️ Pemberitahuan penting: layanan Starlink Anda akan jatuh tempo dalam 2 hari.\n\n" .
                 "Untuk menghindari gangguan layanan, kami merekomendasikan agar perpanjangan segera dilakukan.\n\n" .
                 "⏰ Waktu perpanjangan semakin terbatas, mohon segera lakukan konfirmasi.\n\n" .
                 "🚀 *Octolink.id - Starlink UnOfficial Support* 🚀",

        'H-1' => "📡 *Halo {$nama}, Pengguna Layanan Internet Starlink* 📡\n\n" .
                 "📌 *Informasi Layanan Anda:*\n" .
                 "🔹 *Nama:* {$nama}\n" .
                 "🔹 *KIT Number:* {$kitNumber}\n" .
                 "🔹 *Paket:* {$paket}\n" .
                 "🔹 *Tanggal Jatuh Tempo:* " . formatTanggal($tanggal_jatuh_tempo) . "\n\n" .
                 "🚨 Peringatan urgent: layanan Starlink Anda akan jatuh tempo BESOK.\n\n" .
                 "Mohon konfirmasi perpanjangan layanan HARI INI untuk menghindari terputusnya koneksi internet.\n\n" .
                 "⏰ Ini adalah pengingat terakhir sebelum masa aktif berakhir.\n\n" .
                 "🚀 *Octolink.id - Starlink UnOfficial Support* 🚀",

        'WARNING' => "⚠️ *PENTING: Layanan Internet Starlink Berakhir Hari Ini* ⚠️\n\n" .
                     "👤 *{$nama}*\n" .
                     "📅 *Masa Aktif Berakhir: HARI INI*\n" .
                     "🛰️ *KIT Number:* {$kitNumber}\n" .
                     "📦 *Paket:* {$paket}\n\n" .
                     "⏰ *Mohon konfirmasi perpanjangan secepatnya untuk menghindari pemutusan layanan.*\n\n" .
                     "🚀 *Octolink.id - Starlink UnOfficial Support* 🚀"
    ];

    $message = $messages[$type] ?? $messages['H-3'];
    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    // Format nomor: jika mulai dengan 0, ganti dengan 62
    if (substr($nomor, 0, 1) == '0') {
        $nomor = '62' . substr($nomor, 1);
    }

    return 'https://web.whatsapp.com/send?phone=' . $nomor . '&text=' . urlencode($message);
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'success') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);

        return showAlert($message, $type);
    }
    return '';
}

/**
 * Check permission
 */
function hasPermission($permission, $userRole = null) {
    if ($userRole === null) {
        $userRole = $_SESSION['user_role'] ?? '';
    }

    $permissions = [
        'super_admin' => ['*'], // Full access
        'admin' => [
            'view_dashboard', 'view_pelanggan', 'add_pelanggan', 'edit_pelanggan', 'delete_pelanggan',
            'view_pembayaran', 'add_pembayaran', 'edit_pembayaran', 'view_laporan', 'export_data'
        ],
        'finance' => [
            'view_dashboard', 'view_pelanggan', 'view_pembayaran', 'view_laporan', 'export_data'
        ],
        'staff' => [
            'view_dashboard', 'view_pelanggan', 'view_pembayaran'
        ]
    ];

    // Super admin has all permissions
    if ($userRole == 'super_admin') {
        return true;
    }

    return in_array($permission, $permissions[$userRole] ?? []);
}

/**
 * Get role name
 */
function getRoleName($role) {
    $roles = [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'finance' => 'Finance',
        'staff' => 'Staff'
    ];

    return $roles[$role] ?? $role;
}
