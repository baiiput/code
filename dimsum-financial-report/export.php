<?php
/**
 * Export to Excel - Laporan Keuangan Dimsum
 */
require_once 'config.php';

$db = Database::getConnection();

// Get parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'excel';
$branchId = isset($_GET['branch']) ? (int)$_GET['branch'] : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

// If no date range, use month/year
if (!$startDate || !$endDate) {
    $startDate = sprintf('%04d-%02d-01', $year, $month);
    $endDate = date('Y-m-t', strtotime($startDate));
}

// Build query
$whereConditions = ["t.transaction_date BETWEEN :start_date AND :end_date"];
$params = [':start_date' => $startDate, ':end_date' => $endDate];

if ($branchId > 0) {
    $whereConditions[] = "t.branch_id = :branch_id";
    $params[':branch_id'] = $branchId;
}

$whereClause = implode(' AND ', $whereConditions);

// Get transactions
$sql = "SELECT t.*, b.name as branch_name
        FROM transactions t
        JOIN branches b ON t.branch_id = b.id
        WHERE $whereClause
        ORDER BY t.transaction_date ASC, t.id ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get branch name for filename
$branchName = 'Semua_Cabang';
if ($branchId > 0) {
    $stmt = $db->prepare("SELECT name FROM branches WHERE id = ?");
    $stmt->execute([$branchId]);
    $branchName = str_replace(' ', '_', $stmt->fetchColumn());
}

// Indonesian month names
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Generate filename
$filename = "Laporan_Keuangan_Dimsum_{$branchName}_{$monthNames[$month]}_{$year}";

if ($type === 'excel') {
    // Export to Excel (CSV with BOM for UTF-8)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    // Add BOM for Excel UTF-8
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // Title
    fputcsv($output, ['Laporan Keuangan Dimsum']);
    fputcsv($output, ['Periode: ' . formatDateLong($startDate) . ' - ' . formatDateLong($endDate)]);
    fputcsv($output, ['Cabang: ' . ($branchId > 0 ? $branchName : 'Semua Cabang')]);
    fputcsv($output, []);

    // Header
    fputcsv($output, [
        'No',
        'Tanggal',
        'Cabang',
        'Deskripsi',
        'Tunai',
        'QRIS',
        'Transfer',
        'Shopee Food',
        'Grab Food',
        'Go Food',
        'Total Pemasukan',
        'Pengeluaran',
        'Saldo'
    ]);

    // Data
    $no = 1;
    $runningBalance = 0;
    $totalCash = $totalQris = $totalTransfer = $totalShopee = $totalGrab = $totalGoFood = $totalExpenses = $totalIncome = 0;

    foreach ($transactions as $t) {
        $income = $t['cash'] + $t['qris'] + $t['transfer'] + $t['shopee_food'] + $t['grab_food'] + $t['go_food'];
        $runningBalance += $income - $t['expenses'];

        $totalCash += $t['cash'];
        $totalQris += $t['qris'];
        $totalTransfer += $t['transfer'];
        $totalShopee += $t['shopee_food'];
        $totalGrab += $t['grab_food'];
        $totalGoFood += $t['go_food'];
        $totalIncome += $income;
        $totalExpenses += $t['expenses'];

        fputcsv($output, [
            $no++,
            formatDate($t['transaction_date']),
            $t['branch_name'],
            $t['description'],
            $t['cash'],
            $t['qris'],
            $t['transfer'],
            $t['shopee_food'],
            $t['grab_food'],
            $t['go_food'],
            $income,
            $t['expenses'],
            $runningBalance
        ]);
    }

    // Footer totals
    fputcsv($output, []);
    fputcsv($output, [
        '',
        '',
        '',
        'TOTAL',
        $totalCash,
        $totalQris,
        $totalTransfer,
        $totalShopee,
        $totalGrab,
        $totalGoFood,
        $totalIncome,
        $totalExpenses,
        $totalIncome - $totalExpenses
    ]);

    fclose($output);
    exit;
}

// If not excel, redirect back
header('Location: index.php');
exit;
