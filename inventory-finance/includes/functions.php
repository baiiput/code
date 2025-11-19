<?php
/**
 * Main Application Functions
 */

require_once __DIR__ . '/../config/database.php';

// =====================================================
// SECURITY: ALLOWED TABLES AND FIELDS
// =====================================================

define('ALLOWED_TABLES', [
    'users', 'suppliers', 'customers', 'categories', 'products',
    'product_serials', 'stock_in', 'stock_in_items', 'sales', 'sale_items',
    'returns', 'return_items', 'expenses', 'expense_categories',
    'payments', 'settings', 'activity_logs'
]);

define('ALLOWED_ORDER_FIELDS', [
    'id', 'name', 'code', 'date', 'created_at', 'updated_at',
    'invoice_number', 'return_number', 'serial_number'
]);

function validateTable($table) {
    if (!in_array($table, ALLOWED_TABLES)) {
        throw new InvalidArgumentException('Invalid table name: ' . $table);
    }
    return $table;
}

function validateOrderBy($orderBy) {
    // Extract field name from "field ASC" or "field DESC"
    $parts = explode(' ', trim($orderBy));
    $field = $parts[0];
    $direction = strtoupper($parts[1] ?? 'ASC');

    if (!in_array($field, ALLOWED_ORDER_FIELDS)) {
        return 'id DESC'; // Default safe value
    }
    if (!in_array($direction, ['ASC', 'DESC'])) {
        $direction = 'DESC';
    }
    return "$field $direction";
}

// =====================================================
// FLASH MESSAGES
// =====================================================

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// =====================================================
// FORMAT HELPERS
// =====================================================

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatNumber($number) {
    return number_format($number, 0, ',', '.');
}

// =====================================================
// GENERATE CODES
// =====================================================

function generateCode($prefix, $table, $field = 'code') {
    $db = getDB();
    $year = date('y');
    $month = date('m');

    // Validate table and field
    validateTable($table);
    $allowedFields = ['code', 'invoice_number', 'return_number'];
    if (!in_array($field, $allowedFields)) {
        $field = 'code';
    }

    $pattern = $prefix . $year . $month . '%';
    $stmt = $db->prepare("SELECT `$field` FROM `$table` WHERE `$field` LIKE ? ORDER BY `$field` DESC LIMIT 1");
    $stmt->execute([$pattern]);
    $last = $stmt->fetch();

    if ($last) {
        $lastNum = (int)substr($last[$field], -4);
        $newNum = $lastNum + 1;
    } else {
        $newNum = 1;
    }

    return $prefix . $year . $month . str_pad($newNum, 4, '0', STR_PAD_LEFT);
}

function generateInvoiceNumber($type = 'sale') {
    $db = getDB();

    $prefixes = [
        'sale' => getSetting('invoice_prefix', 'INV'),
        'stock_in' => getSetting('stock_in_prefix', 'SI'),
        'return' => getSetting('return_prefix', 'RET')
    ];

    $tables = [
        'sale' => 'sales',
        'stock_in' => 'stock_in',
        'return' => 'returns'
    ];

    $fields = [
        'sale' => 'invoice_number',
        'stock_in' => 'invoice_number',
        'return' => 'return_number'
    ];

    return generateCode($prefixes[$type], $tables[$type], $fields[$type]);
}

// =====================================================
// SETTINGS
// =====================================================

function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['value'] : $default;
}

function setSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    $stmt->execute([$key, $value, $value]);
}

// =====================================================
// CRUD HELPERS
// =====================================================

function getAll($table, $conditions = [], $orderBy = 'id DESC', $limit = null) {
    $db = getDB();

    // Validate inputs
    validateTable($table);
    $orderBy = validateOrderBy($orderBy);
    $limit = $limit ? (int)$limit : null;

    $sql = "SELECT * FROM `$table`";
    $params = [];

    if (!empty($conditions)) {
        $where = [];
        foreach ($conditions as $key => $value) {
            // Only allow alphanumeric field names
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $where[] = "`$key` = ?";
            $params[] = $value;
        }
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $sql .= " ORDER BY $orderBy";

    if ($limit) {
        $sql .= " LIMIT $limit";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getById($table, $id) {
    $db = getDB();
    validateTable($table);
    $stmt = $db->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

function insert($table, $data) {
    $db = getDB();
    validateTable($table);

    $fields = [];
    foreach (array_keys($data) as $field) {
        // Sanitize field names
        $fields[] = '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $field) . '`';
    }
    $placeholders = array_fill(0, count($fields), '?');

    $sql = "INSERT INTO `$table` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_values($data));

    return $db->lastInsertId();
}

function update($table, $data, $id) {
    $db = getDB();
    validateTable($table);

    $set = [];
    foreach (array_keys($data) as $field) {
        // Sanitize field names
        $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
        $set[] = "`$field` = ?";
    }

    $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE id = ?";
    $params = array_values($data);
    $params[] = (int)$id;

    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function delete($table, $id) {
    $db = getDB();
    validateTable($table);
    $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

// =====================================================
// STOCK FUNCTIONS
// =====================================================

function getProductStock($productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as stock FROM product_serials WHERE product_id = ? AND status = 'available'");
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    return $result['stock'];
}

function getAvailableSerials($productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM product_serials WHERE product_id = ? AND status = 'available' ORDER BY created_at ASC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function updateSerialStatus($serialId, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE product_serials SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $serialId]);
}

function getLowStockProducts() {
    $db = getDB();
    $sql = "SELECT p.*,
            (SELECT COUNT(*) FROM product_serials ps WHERE ps.product_id = p.id AND ps.status = 'available') as current_stock
            FROM products p
            WHERE p.is_active = 1
            AND p.min_stock > 0
            AND (SELECT COUNT(*) FROM product_serials ps WHERE ps.product_id = p.id AND ps.status = 'available') <= p.min_stock
            ORDER BY current_stock ASC";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

// =====================================================
// FINANCIAL FUNCTIONS
// =====================================================

function getTotalSales($startDate = null, $endDate = null) {
    $db = getDB();
    $sql = "SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE 1=1";
    $params = [];

    if ($startDate) {
        $sql .= " AND date >= ?";
        $params[] = $startDate;
    }
    if ($endDate) {
        $sql .= " AND date <= ?";
        $params[] = $endDate;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ? $result['total'] : 0;
}

function getTotalProfit($startDate = null, $endDate = null) {
    $db = getDB();
    $sql = "SELECT COALESCE(SUM(profit), 0) as total FROM sales WHERE 1=1";
    $params = [];

    if ($startDate) {
        $sql .= " AND date >= ?";
        $params[] = $startDate;
    }
    if ($endDate) {
        $sql .= " AND date <= ?";
        $params[] = $endDate;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ? $result['total'] : 0;
}

function getTotalExpenses($startDate = null, $endDate = null) {
    $db = getDB();
    $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE 1=1";
    $params = [];

    if ($startDate) {
        $sql .= " AND date >= ?";
        $params[] = $startDate;
    }
    if ($endDate) {
        $sql .= " AND date <= ?";
        $params[] = $endDate;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ? $result['total'] : 0;
}

function getTotalReceivables() {
    $db = getDB();
    $stmt = $db->query("SELECT COALESCE(SUM(grand_total - paid_amount), 0) as total FROM sales WHERE payment_status IN ('unpaid', 'partial')");
    $result = $stmt->fetch();
    return $result ? $result['total'] : 0;
}

function getTotalPayables() {
    $db = getDB();
    $stmt = $db->query("SELECT COALESCE(SUM(grand_total - paid_amount), 0) as total FROM stock_in WHERE payment_status IN ('unpaid', 'partial')");
    $result = $stmt->fetch();
    return $result ? $result['total'] : 0;
}

// =====================================================
// DASHBOARD STATS
// =====================================================

function getDashboardStats() {
    $db = getDB();

    $today = date('Y-m-d');
    $thisMonth = date('Y-m-01');
    $endMonth = date('Y-m-t');

    return [
        'total_products' => $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn(),
        'total_stock' => $db->query("SELECT COUNT(*) FROM product_serials WHERE status = 'available'")->fetchColumn(),
        'low_stock_count' => count(getLowStockProducts()),
        'today_sales' => getTotalSales($today, $today),
        'month_sales' => getTotalSales($thisMonth, $endMonth),
        'month_profit' => getTotalProfit($thisMonth, $endMonth),
        'month_expenses' => getTotalExpenses($thisMonth, $endMonth),
        'total_receivables' => getTotalReceivables(),
        'total_payables' => getTotalPayables(),
        'total_customers' => $db->query("SELECT COUNT(*) FROM customers WHERE is_active = 1")->fetchColumn(),
        'total_suppliers' => $db->query("SELECT COUNT(*) FROM suppliers WHERE is_active = 1")->fetchColumn(),
    ];
}

function getMonthlySalesChart($year = null) {
    $db = getDB();
    $year = $year ?: date('Y');

    $sql = "SELECT MONTH(date) as month,
            SUM(grand_total) as sales,
            SUM(profit) as profit
            FROM sales
            WHERE YEAR(date) = ?
            GROUP BY MONTH(date)
            ORDER BY month";

    $stmt = $db->prepare($sql);
    $stmt->execute([$year]);

    $data = array_fill(1, 12, ['sales' => 0, 'profit' => 0]);
    foreach ($stmt->fetchAll() as $row) {
        $data[$row['month']] = [
            'sales' => (float)$row['sales'],
            'profit' => (float)$row['profit']
        ];
    }

    return $data;
}

function getTopSellingProducts($limit = 10, $startDate = null, $endDate = null) {
    $db = getDB();

    $sql = "SELECT p.*, SUM(si.quantity) as total_sold, SUM(si.subtotal) as total_revenue
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE 1=1";
    $params = [];

    if ($startDate) {
        $sql .= " AND s.date >= ?";
        $params[] = $startDate;
    }
    if ($endDate) {
        $sql .= " AND s.date <= ?";
        $params[] = $endDate;
    }

    $sql .= " GROUP BY p.id ORDER BY total_sold DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// =====================================================
// WARRANTY FUNCTIONS
// =====================================================

function getExpiringWarranties($days = 30) {
    $db = getDB();
    $endDate = date('Y-m-d', strtotime("+$days days"));

    $sql = "SELECT ps.*, p.name as product_name, p.code as product_code,
            s.invoice_number, c.name as customer_name
            FROM product_serials ps
            JOIN products p ON ps.product_id = p.id
            LEFT JOIN sale_items si ON ps.id = si.serial_id
            LEFT JOIN sales s ON si.sale_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE ps.warranty_end BETWEEN CURDATE() AND ?
            AND ps.status = 'sold'
            ORDER BY ps.warranty_end ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$endDate]);
    return $stmt->fetchAll();
}

// =====================================================
// PAGINATION
// =====================================================

function paginate($table, $conditions = [], $orderBy = 'id DESC', $perPage = 20) {
    $db = getDB();

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $perPage;

    // Count total
    $countSql = "SELECT COUNT(*) FROM $table";
    $params = [];

    if (!empty($conditions)) {
        $where = [];
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $where[] = "$key " . $value[0] . " ?";
                $params[] = $value[1];
            } else {
                $where[] = "$key = ?";
                $params[] = $value;
            }
        }
        $countSql .= " WHERE " . implode(' AND ', $where);
    }

    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Get data
    $sql = "SELECT * FROM $table";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', array_map(function($k, $v) {
            if (is_array($v)) {
                return "$k " . $v[0] . " ?";
            }
            return "$k = ?";
        }, array_keys($conditions), array_values($conditions)));
    }
    $sql .= " ORDER BY $orderBy LIMIT $perPage OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    return [
        'data' => $data,
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => ceil($total / $perPage)
    ];
}

function renderPagination($pagination, $baseUrl = '?') {
    if ($pagination['total_pages'] <= 1) return '';

    $html = '<nav><ul class="pagination justify-content-center">';

    // Previous
    if ($pagination['current_page'] > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . ($pagination['current_page'] - 1) . '">&laquo;</a></li>';
    }

    // Pages
    for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++) {
        $active = $i == $pagination['current_page'] ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . 'page=' . $i . '">' . $i . '</a></li>';
    }

    // Next
    if ($pagination['current_page'] < $pagination['total_pages']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . ($pagination['current_page'] + 1) . '">&raquo;</a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}

// =====================================================
// EXPORT FUNCTIONS
// =====================================================

function exportToExcel($data, $filename, $headers = []) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');

    echo '<table border="1">';

    // Headers
    if (!empty($headers)) {
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<th>' . $header . '</th>';
        }
        echo '</tr>';
    }

    // Data
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . $cell . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
    exit;
}
