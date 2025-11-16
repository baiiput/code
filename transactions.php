<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user = getCurrentUser();

$type = $_GET['type'] ?? 'all';

// Get transactions based on type
$transactions = [];

// Check if user is cabang role
$is_cabang = ($user['role'] === 'cabang' && !empty($user['cabang_id']));
$branch_id = $is_cabang ? $user['cabang_id'] : null;

// For cabang: don't show stock in transactions
if (!$is_cabang && ($type === 'all' || $type === 'in')) {
    $query = "
        SELECT
            si.transaction_code,
            si.transaction_date,
            'Stock In' as type,
            s.supplier_name as party,
            si.total_amount,
            u.full_name as created_by
        FROM stock_in si
        LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
        LEFT JOIN users u ON si.created_by = u.user_id
        ORDER BY si.transaction_date DESC
        LIMIT 50
    ";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

if ($type === 'all' || $type === 'out') {
    $query = "
        SELECT
            so.transaction_code,
            so.transaction_date,
            'Stock Out' as type,
            b.branch_name as party,
            so.total_amount,
            u.full_name as created_by
        FROM stock_out so
        LEFT JOIN branches b ON so.branch_id = b.branch_id
        LEFT JOIN users u ON so.created_by = u.user_id
    ";

    // Filter for cabang role
    if ($is_cabang) {
        $query .= " WHERE so.branch_id = $branch_id";
    }

    $query .= " ORDER BY so.transaction_date DESC LIMIT 50";

    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

// Sort by date
usort($transactions, function($a, $b) {
    return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

$page_title = 'Transaksi';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-exchange-alt"></i> Semua Transaksi</h1>
    </div>
    
    <div class="filter-section">
        <div style="display: flex; gap: 12px;">
            <?php if (!$is_cabang): ?>
            <a href="?type=all" class="btn <?php echo $type === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="fas fa-list"></i> Semua
            </a>
            <a href="?type=in" class="btn <?php echo $type === 'in' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="fas fa-arrow-down"></i> Stock In
            </a>
            <?php endif; ?>
            <a href="?type=out" class="btn <?php echo $type === 'out' ? 'btn-primary' : 'btn-secondary'; ?>">
                <i class="fas fa-arrow-up"></i> <?php echo $is_cabang ? 'Distribusi Masuk' : 'Stock Out'; ?>
            </a>
        </div>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Kode Transaksi</th>
                    <th width="15%">Tanggal</th>
                    <th width="12%">Tipe</th>
                    <th>Supplier/Cabang</th>
                    <th width="15%" class="text-right">Total</th>
                    <th>Dibuat Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr><td colspan="7" class="text-center">Tidak ada transaksi</td></tr>
                <?php else: ?>
                <?php foreach ($transactions as $index => $trans): ?>
                <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td><strong><?php echo $trans['transaction_code']; ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($trans['transaction_date'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $trans['type'] === 'Stock In' ? 'primary' : 'success'; ?>">
                            <?php echo $trans['type']; ?>
                        </span>
                    </td>
                    <td><?php echo $trans['party']; ?></td>
                    <td class="text-right"><strong><?php echo formatRupiah($trans['total_amount']); ?></strong></td>
                    <td><?php echo $trans['created_by']; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
