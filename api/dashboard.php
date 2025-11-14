<?php
/**
 * Dashboard API - Summary & Statistics
 */

require_once '../config/database.php';
require_once '../config/config.php';

requireLogin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();

    $period = $_GET['period'] ?? 'month'; // day, week, month, year
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;

    // Tentukan periode
    if ($startDate && $endDate) {
        $dateCondition = "BETWEEN '$startDate' AND '$endDate'";
    } else {
        switch ($period) {
            case 'day':
                $dateCondition = "= CURDATE()";
                break;
            case 'week':
                $dateCondition = ">= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'year':
                $dateCondition = ">= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
            case 'month':
            default:
                $dateCondition = ">= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                break;
        }
    }

    // Total Penjualan
    $stmt = $db->query("
        SELECT
            COUNT(*) as total_transactions,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COALESCE(SUM(paid_amount), 0) as total_paid,
            COALESCE(SUM(total_amount - paid_amount), 0) as total_unpaid
        FROM sales
        WHERE sale_date $dateCondition
    ");
    $salesData = $stmt->fetch();

    // Total Pembelian
    $stmt = $db->query("
        SELECT
            COUNT(*) as total_transactions,
            COALESCE(SUM(total_amount), 0) as total_purchases,
            COALESCE(SUM(paid_amount), 0) as total_paid,
            COALESCE(SUM(total_amount - paid_amount), 0) as total_unpaid
        FROM purchases
        WHERE purchase_date $dateCondition
    ");
    $purchasesData = $stmt->fetch();

    // Total Pengeluaran
    $stmt = $db->query("
        SELECT
            COUNT(*) as total_transactions,
            COALESCE(SUM(amount), 0) as total_expenses
        FROM expenses
        WHERE expense_date $dateCondition
    ");
    $expensesData = $stmt->fetch();

    // Gross Profit
    $grossProfit = $salesData['total_sales'] - $purchasesData['total_purchases'];

    // Net Profit
    $netProfit = $grossProfit - $expensesData['total_expenses'];

    // Profit Margin
    $profitMargin = $salesData['total_sales'] > 0
        ? ($netProfit / $salesData['total_sales']) * 100
        : 0;

    // Top Selling Products
    $stmt = $db->query("
        SELECT
            p.name,
            c.name as category,
            SUM(sd.quantity) as total_sold,
            SUM(sd.subtotal) as revenue
        FROM sales_details sd
        JOIN products p ON sd.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE s.sale_date $dateCondition
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $topProducts = $stmt->fetchAll();

    // Low Stock Products
    $stmt = $db->query("
        SELECT
            p.name,
            p.sku,
            p.stock,
            p.min_stock,
            c.name as category
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.stock <= p.min_stock AND p.is_active = 1
        ORDER BY (p.min_stock - p.stock) DESC
        LIMIT 10
    ");
    $lowStockProducts = $stmt->fetchAll();

    // Recent Sales
    $stmt = $db->query("
        SELECT
            s.invoice_number,
            s.sale_date,
            c.name as customer_name,
            s.total_amount,
            s.payment_status
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        ORDER BY s.sale_date DESC, s.created_at DESC
        LIMIT 10
    ");
    $recentSales = $stmt->fetchAll();

    // Pending Payments
    $stmt = $db->query("
        SELECT
            s.invoice_number,
            s.sale_date,
            c.name as customer_name,
            s.total_amount,
            s.paid_amount,
            (s.total_amount - s.paid_amount) as remaining
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        WHERE s.payment_status IN ('unpaid', 'partial')
        ORDER BY s.due_date ASC
        LIMIT 10
    ");
    $pendingPayments = $stmt->fetchAll();

    // Sales Chart Data (last 12 months or based on period)
    $stmt = $db->query("
        SELECT
            DATE_FORMAT(sale_date, '%Y-%m') as month,
            SUM(total_amount) as total
        FROM sales
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
        ORDER BY month ASC
    ");
    $salesChart = $stmt->fetchAll();

    // Expenses by Category
    $stmt = $db->query("
        SELECT
            category,
            SUM(amount) as total
        FROM expenses
        WHERE expense_date $dateCondition
        GROUP BY category
        ORDER BY total DESC
    ");
    $expensesByCategory = $stmt->fetchAll();

    $response = [
        'success' => true,
        'period' => $period,
        'summary' => [
            'sales' => [
                'total_transactions' => (int)$salesData['total_transactions'],
                'total_amount' => (float)$salesData['total_sales'],
                'total_paid' => (float)$salesData['total_paid'],
                'total_unpaid' => (float)$salesData['total_unpaid']
            ],
            'purchases' => [
                'total_transactions' => (int)$purchasesData['total_transactions'],
                'total_amount' => (float)$purchasesData['total_purchases'],
                'total_paid' => (float)$purchasesData['total_paid'],
                'total_unpaid' => (float)$purchasesData['total_unpaid']
            ],
            'expenses' => [
                'total_transactions' => (int)$expensesData['total_transactions'],
                'total_amount' => (float)$expensesData['total_expenses']
            ],
            'profit' => [
                'gross_profit' => (float)$grossProfit,
                'net_profit' => (float)$netProfit,
                'profit_margin' => round($profitMargin, 2)
            ]
        ],
        'charts' => [
            'sales_trend' => $salesChart,
            'expenses_by_category' => $expensesByCategory
        ],
        'top_products' => $topProducts,
        'low_stock_products' => $lowStockProducts,
        'recent_sales' => $recentSales,
        'pending_payments' => $pendingPayments
    ];

    jsonResponse($response);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
