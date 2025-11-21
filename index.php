<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user = getCurrentUser();

// Get dashboard statistics
$stats = [
    'total_items' => 0,
    'total_stock_value' => 0,
    'warehouse_balance' => 0,
    'low_stock_count' => 0,
    'total_suppliers' => 0,
    'total_branches' => 0
];

// Total items and stock value
$result = $conn->query("SELECT COUNT(*) as total, SUM(current_stock * average_cost) as stock_value FROM items");
if ($row = $result->fetch_assoc()) {
    $stats['total_items'] = $row['total'];
    $stats['total_stock_value'] = $row['stock_value'] ?? 0;
}

// Warehouse balance
$result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
if ($row = $result->fetch_assoc()) {
    $stats['warehouse_balance'] = $row['balance_amount'];
}

// Low stock items
$result = $conn->query("SELECT COUNT(*) as total FROM items WHERE current_stock <= min_stock");
if ($row = $result->fetch_assoc()) {
    $stats['low_stock_count'] = $row['total'];
}

// Total suppliers
$result = $conn->query("SELECT COUNT(*) as total FROM suppliers");
if ($row = $result->fetch_assoc()) {
    $stats['total_suppliers'] = $row['total'];
}

// Total branches
$result = $conn->query("SELECT COUNT(*) as total FROM branches");
if ($row = $result->fetch_assoc()) {
    $stats['total_branches'] = $row['total'];
}

// Total warehouses
$result = $conn->query("SELECT COUNT(*) as total FROM warehouses WHERE is_active = 1");
if ($row = $result->fetch_assoc()) {
    $stats['total_warehouses'] = $row['total'];
}

// Recent transactions (last 5) with item details
$recent_transactions = [];

// Check if user is cabang role - only show their transactions
if ($user['role'] === 'cabang' && !empty($user['cabang_id'])) {
    $branch_id = $user['cabang_id'];
    // For cabang: only show stock_out to their branch
    $query = "
        SELECT 'OUT' as type, so.stock_out_id as trans_id, so.transaction_code, so.transaction_date,
               'Warehouse' as partner, so.total_amount
        FROM stock_out so
        WHERE so.branch_id = $branch_id
        ORDER BY so.transaction_date DESC
        LIMIT 5
    ";
} else {
    // For other roles: show all transactions
    $query = "
        SELECT 'IN' as type, si.stock_in_id as trans_id, si.transaction_code, si.transaction_date, s.supplier_name as partner, si.total_amount
        FROM stock_in si
        JOIN suppliers s ON si.supplier_id = s.supplier_id
        UNION ALL
        SELECT 'OUT' as type, so.stock_out_id as trans_id, so.transaction_code, so.transaction_date, b.branch_name as partner, so.total_amount
        FROM stock_out so
        JOIN branches b ON so.branch_id = b.branch_id
        ORDER BY transaction_date DESC
        LIMIT 5
    ";
}

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    // Get items for this transaction
    $items_list = [];
    if ($row['type'] === 'IN') {
        $items_query = "SELECT i.item_name, sid.quantity, i.unit
                       FROM stock_in_detail sid
                       JOIN items i ON sid.item_id = i.item_id
                       WHERE sid.stock_in_id = " . $row['trans_id'] . "
                       LIMIT 3";
    } else {
        $items_query = "SELECT i.item_name, sod.quantity, i.unit
                       FROM stock_out_detail sod
                       JOIN items i ON sod.item_id = i.item_id
                       WHERE sod.stock_out_id = " . $row['trans_id'] . "
                       LIMIT 3";
    }
    $items_result = $conn->query($items_query);
    while ($item = $items_result->fetch_assoc()) {
        $items_list[] = formatNumber($item['quantity'], 0) . ' ' . $item['unit'] . ' ' . $item['item_name'];
    }
    $row['items'] = $items_list;
    $recent_transactions[] = $row;
}

// Low stock items
$low_stock_items = [];
$query = "
    SELECT i.item_code, i.item_name, i.current_stock, i.min_stock, i.unit, c.category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE i.current_stock <= i.min_stock
    ORDER BY i.current_stock ASC
    LIMIT 10
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $low_stock_items[] = $row;
}

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<div class="dashboard-container">
    <!-- Statistics Cards - Hidden for cabang role -->
    <?php if ($user['role'] !== 'cabang'): ?>
    <div class="stats-grid">
        <div class="stat-card primary" onclick="window.location.href='finance.php'" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-info">
                <div class="stat-label">Saldo Warehouse</div>
                <div class="stat-value"><?php echo formatRupiah($stats['warehouse_balance']); ?></div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-box"></i></div>
            <div class="stat-info">
                <div class="stat-label">Total Item</div>
                <div class="stat-value"><?php echo number_format($stats['total_items']); ?></div>
            </div>
        </div>

        <div class="stat-card info" onclick="window.location.href='reports.php'" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-info">
                <div class="stat-label">Nilai Stok</div>
                <div class="stat-value"><?php echo formatRupiah($stats['total_stock_value']); ?></div>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <div class="stat-label">Stok Rendah</div>
                <div class="stat-value"><?php echo number_format($stats['low_stock_count']); ?> <span class="stat-unit">Item</span></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-truck"></i></div>
            <div class="stat-info">
                <div class="stat-label">Supplier</div>
                <div class="stat-value"><?php echo number_format($stats['total_suppliers']); ?></div>
            </div>
        </div>

        <div class="stat-card dual-info">
            <div class="stat-icon"><i class="fas fa-building"></i></div>
            <div class="stat-info">
                <div class="dual-stats">
                    <div class="dual-stat-item">
                        <div class="stat-label">Warehouse</div>
                        <div class="stat-value"><?php echo number_format($stats['total_warehouses']); ?></div>
                    </div>
                    <div class="dual-divider"></div>
                    <div class="dual-stat-item">
                        <div class="stat-label">Cabang</div>
                        <div class="stat-value"><?php echo number_format($stats['total_branches']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h3>Transaksi Terakhir</h3>
                <a href="transactions.php" class="btn-link">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_transactions)): ?>
                    <p class="no-data">Belum ada transaksi</p>
                <?php else: ?>
                    <div class="transaction-list">
                        <?php foreach ($recent_transactions as $trans): ?>
                            <div class="transaction-item">
                                <div class="trans-badge <?php echo $trans['type'] === 'IN' ? 'badge-danger' : 'badge-success'; ?>">
                                    <i class="fas <?php echo $trans['type'] === 'IN' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                                </div>
                                <div class="trans-info">
                                    <div class="trans-code"><?php echo $trans['transaction_code']; ?></div>
                                    <div class="trans-items">
                                        <?php if (!empty($trans['items'])): ?>
                                            <?php echo implode(' • ', $trans['items']); ?>
                                            <?php if (count($trans['items']) == 3): ?><span style="color: var(--text-secondary);">...</span><?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="trans-partner"><?php echo $trans['partner']; ?></div>
                                    <div class="trans-date"><?php echo date('d/m/Y', strtotime($trans['transaction_date'])); ?></div>
                                </div>
                                <div class="trans-amount"><?php echo formatRupiah($trans['total_amount']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Low Stock Alert -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Stok Rendah</h3>
                <a href="reports.php?type=low_stock" class="btn-link">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if (empty($low_stock_items)): ?>
                    <p class="no-data">✅ Semua stok aman</p>
                <?php else: ?>
                    <div class="stock-list">
                        <?php foreach ($low_stock_items as $item): 
                            $status = getStockStatus($item['current_stock'], $item['min_stock']);
                        ?>
                            <div class="stock-item">
                                <div class="stock-info">
                                    <div class="stock-name"><?php echo $item['item_name']; ?></div>
                                    <div class="stock-meta">
                                        <span class="stock-code"><?php echo $item['item_code']; ?></span>
                                        <?php if($item['category_name']): ?>
                                        <span class="stock-category">• <?php echo $item['category_name']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="stock-quantity">
                                    <span class="badge badge-<?php echo $status['class']; ?>">
                                        <?php echo formatNumber($item['current_stock'], 0); ?> <?php echo $item['unit']; ?>
                                    </span>
                                    <div class="stock-min">Min: <?php echo formatNumber($item['min_stock'], 0); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 16px;
    max-width: 1400px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: var(--primary-color);
    opacity: 0;
    transition: opacity 0.3s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-card.primary::before { background: var(--primary-color); opacity: 1; }
.stat-card.success::before { background: var(--success-color); opacity: 1; }
.stat-card.info::before { background: var(--info-color); opacity: 1; }
.stat-card.warning::before { background: var(--warning-color); opacity: 1; }

.stat-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 20px;
    flex-shrink: 0;
}

.stat-card.primary .stat-icon {
    background: var(--primary-light);
    color: var(--primary-color);
}

.stat-card.success .stat-icon {
    background: var(--success-light);
    color: var(--success-color);
}

.stat-card.info .stat-icon {
    background: var(--info-light);
    color: var(--info-color);
}

.stat-card.warning .stat-icon {
    background: var(--warning-light);
    color: var(--warning-color);
}

.stat-info {
    flex: 1;
    min-width: 0; /* Allow text truncation */
}

.stat-label {
    font-size: 11px;
    color: var(--text-secondary);
    margin-bottom: 4px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-value {
    font-size: 17px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.3;
    word-break: break-word;
}

.stat-unit {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-secondary);
}

/* Dual info card styles */
.stat-card.dual-info .stat-info {
    width: 100%;
}

.dual-stats {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    width: 100%;
}

.dual-stat-item {
    flex: 1;
    text-align: center;
}

.dual-divider {
    width: 1px;
    height: 35px;
    background: var(--border-color);
}

.dual-stat-item .stat-label {
    font-size: 10px;
}

.dual-stat-item .stat-value {
    font-size: 20px;
}

.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 16px;
}

.card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.card-header {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-primary);
}

.card-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-header h3 i {
    font-size: 14px;
    color: var(--warning-color);
}

.card-body {
    padding: 16px;
}

.btn-link {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s;
}

.btn-link:hover {
    color: var(--primary-hover);
    gap: 6px;
}

.no-data {
    text-align: center;
    color: var(--text-secondary);
    padding: 32px 16px;
    font-size: 14px;
}

.transaction-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.transaction-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: var(--bg-primary);
    border-radius: 8px;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.transaction-item:hover {
    border-color: var(--border-color);
    transform: translateX(2px);
}

.trans-badge {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.badge-danger {
    background: var(--danger-light);
    color: var(--danger-color);
}

.badge-success {
    background: var(--success-light);
    color: var(--success-color);
}

.trans-info {
    flex: 1;
    min-width: 0;
}

.trans-code {
    font-weight: 600;
    font-size: 13px;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trans-items {
    font-size: 11px;
    color: var(--text-primary);
    margin: 3px 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.trans-partner {
    font-size: 11px;
    color: var(--text-secondary);
    margin: 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trans-date {
    font-size: 10px;
    color: var(--text-secondary);
}

.trans-amount {
    font-weight: 600;
    font-size: 13px;
    color: var(--text-primary);
    text-align: right;
    flex-shrink: 0;
    min-width: 120px;
    word-break: break-word;
}

.stock-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.stock-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: var(--bg-primary);
    border-radius: 8px;
    gap: 10px;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.stock-item:hover {
    border-color: var(--border-color);
}

.stock-info {
    flex: 1;
    min-width: 0;
}

.stock-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stock-meta {
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 2px;
    display: flex;
    gap: 6px;
    align-items: center;
}

.stock-code {
    font-weight: 600;
    text-transform: uppercase;
}

.stock-category {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stock-quantity {
    text-align: right;
    flex-shrink: 0;
}

.badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    white-space: nowrap;
}

.badge-success {
    background: var(--success-light);
    color: var(--success-color);
}

.badge-warning {
    background: var(--warning-light);
    color: var(--warning-color);
}

.badge-danger {
    background: var(--danger-light);
    color: var(--danger-color);
}

.stock-min {
    font-size: 10px;
    color: var(--text-secondary);
    margin-top: 4px;
    font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }

    .stat-value {
        font-size: 15px;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .dashboard-container {
        padding: 12px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .stat-card {
        padding: 12px;
    }

    .stat-value {
        font-size: 13px;
    }

    .stat-icon {
        width: 36px;
        height: 36px;
        font-size: 16px;
    }
    
    .card-body {
        padding: 12px;
    }
}
</style>

<?php
// Helper function for compact rupiah format
function formatRupiahCompact($amount) {
    if ($amount >= 1000000000) {
        return 'Rp&nbsp;' . number_format($amount / 1000000000, 1, ',', '.') . 'M';
    } elseif ($amount >= 1000000) {
        return 'Rp&nbsp;' . number_format($amount / 1000000, 1, ',', '.') . 'jt';
    } else {
        return 'Rp&nbsp;' . number_format($amount, 0, ',', '.');
    }
}
?>

<?php include 'includes/footer.php'; ?>
