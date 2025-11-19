<?php
/**
 * View Helpers
 */

// Escape output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Get alert class for status
function getStatusBadge($status, $type = 'payment') {
    $badges = [
        'payment' => [
            'paid' => '<span class="badge bg-success">Lunas</span>',
            'partial' => '<span class="badge bg-warning">Sebagian</span>',
            'unpaid' => '<span class="badge bg-danger">Belum Bayar</span>',
        ],
        'stock' => [
            'available' => '<span class="badge bg-success">Tersedia</span>',
            'sold' => '<span class="badge bg-primary">Terjual</span>',
            'returned' => '<span class="badge bg-warning">Return</span>',
            'damaged' => '<span class="badge bg-danger">Rusak</span>',
            'reserved' => '<span class="badge bg-info">Reserved</span>',
        ],
        'condition' => [
            'new' => '<span class="badge bg-success">Baru</span>',
            'used' => '<span class="badge bg-warning">Bekas</span>',
            'refurbished' => '<span class="badge bg-info">Refurbished</span>',
        ],
        'return' => [
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'approved' => '<span class="badge bg-info">Approved</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
        ],
    ];

    return $badges[$type][$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

// Payment method label
function getPaymentMethodLabel($method) {
    $labels = [
        'cash' => 'Cash',
        'transfer' => 'Transfer',
        'tempo' => 'Tempo',
        'marketplace' => 'Marketplace',
    ];
    return $labels[$method] ?? ucfirst($method);
}

// Role label
function getRoleLabel($role) {
    $labels = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'kasir' => 'Kasir',
        'gudang' => 'Gudang',
    ];
    return $labels[$role] ?? ucfirst($role);
}

// Active menu check
function isActiveMenu($path) {
    $currentPath = $_SERVER['REQUEST_URI'];
    return strpos($currentPath, $path) !== false ? 'active' : '';
}

// Build query string
function buildQuery($params, $exclude = []) {
    $query = $_GET;
    foreach ($exclude as $key) {
        unset($query[$key]);
    }
    $query = array_merge($query, $params);
    return http_build_query($query);
}

// Breadcrumb helper
function breadcrumb($items) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    $count = count($items);
    $i = 0;

    foreach ($items as $label => $url) {
        $i++;
        if ($i == $count) {
            $html .= '<li class="breadcrumb-item active">' . e($label) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . $url . '">' . e($label) . '</a></li>';
        }
    }

    $html .= '</ol></nav>';
    return $html;
}

// Select options helper
function selectOptions($items, $selected = null, $valueKey = 'id', $labelKey = 'name') {
    $html = '';
    foreach ($items as $item) {
        $value = is_array($item) ? $item[$valueKey] : $item;
        $label = is_array($item) ? $item[$labelKey] : $item;
        $sel = $value == $selected ? 'selected' : '';
        $html .= '<option value="' . e($value) . '" ' . $sel . '>' . e($label) . '</option>';
    }
    return $html;
}

// Alert box
function alertBox($type, $message) {
    $icons = [
        'success' => 'check-circle',
        'danger' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle',
    ];
    $icon = $icons[$type] ?? 'info-circle';

    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
        <i class="fas fa-' . $icon . ' me-2"></i>' . $message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// Calculate due days
function getDueDays($dueDate) {
    if (empty($dueDate)) return null;

    $due = new DateTime($dueDate);
    $now = new DateTime();
    $diff = $now->diff($due);

    if ($now > $due) {
        return -$diff->days;
    }
    return $diff->days;
}

// Format due date with color
function formatDueDate($dueDate) {
    if (empty($dueDate)) return '-';

    $days = getDueDays($dueDate);
    $formatted = formatDate($dueDate);

    if ($days < 0) {
        return '<span class="text-danger">' . $formatted . ' (Terlambat ' . abs($days) . ' hari)</span>';
    } elseif ($days <= 7) {
        return '<span class="text-warning">' . $formatted . ' (' . $days . ' hari lagi)</span>';
    }
    return $formatted;
}

// CSRF Token
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch');
    }
}

// Month names in Indonesian
function getMonthName($month) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[$month] ?? '';
}

// Truncate text
function truncate($text, $length = 50) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

// Convert to slug
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}
