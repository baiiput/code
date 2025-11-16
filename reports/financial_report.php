<?php
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$query = "
    SELECT ft.*, u.full_name as created_by_name
    FROM financial_transactions ft
    LEFT JOIN users u ON ft.created_by = u.user_id
    WHERE DATE(ft.transaction_date) BETWEEN '$date_from' AND '$date_to'
    ORDER BY ft.transaction_date DESC, ft.transaction_id DESC
";

$transactions = [];
$total_debit = 0;
$total_credit = 0;

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
    $total_debit += $row['debit'];
    $total_credit += $row['credit'];
}

// Get current balance
$result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
$current_balance = $result->fetch_assoc()['balance_amount'] ?? 0;

$net = $total_debit - $total_credit;
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-wallet"></i> Laporan Keuangan</h2>

<div class="filter-section" style="margin-bottom: 20px;">
    <form method="GET" class="filter-form">
        <input type="hidden" name="type" value="financial">
        <input type="date" name="date_from" value="<?php echo $date_from; ?>" required>
        <span>s/d</span>
        <input type="date" name="date_to" value="<?php echo $date_to; ?>" required>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    </form>
</div>

<!-- Summary Cards - Compact & Responsive -->
<div class="financial-summary">
    <!-- Total Debit Card -->
    <div class="financial-card debit">
        <div class="financial-card-header">
            <div class="financial-card-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div style="flex: 1;">
                <p class="financial-card-title">Total Debit (+)</p>
                <h3 class="financial-card-amount">
                    <span><?php echo formatRupiah($total_debit); ?></span>
                </h3>
            </div>
        </div>
        <div class="financial-card-detail">
            Pemasukan & pendapatan periode ini
        </div>
    </div>
    
    <!-- Total Kredit Card -->
    <div class="financial-card kredit">
        <div class="financial-card-header">
            <div class="financial-card-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div style="flex: 1;">
                <p class="financial-card-title">Total Kredit (-)</p>
                <h3 class="financial-card-amount">
                    <span><?php echo formatRupiah($total_credit); ?></span>
                </h3>
            </div>
        </div>
        <div class="financial-card-detail">
            Pengeluaran & biaya operasional
        </div>
    </div>
    
    <!-- Saldo Saat Ini Card -->
    <div class="financial-card net">
        <div class="financial-card-header">
            <div class="financial-card-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div style="flex: 1;">
                <p class="financial-card-title">Saldo Saat Ini</p>
                <h3 class="financial-card-amount" style="color: var(--primary-color);">
                    <span><?php echo formatRupiah($current_balance); ?></span>
                </h3>
            </div>
        </div>
        <div class="financial-card-detail">
            Saldo aktual di warehouse
        </div>
    </div>
</div>

<table class="data-table" id="reportTable">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="13%">Tanggal</th>
            <th width="10%">Tipe</th>
            <th width="13%">Referensi</th>
            <th>Deskripsi</th>
            <th width="12%" class="text-right">Debit (+)</th>
            <th width="12%" class="text-right">Kredit (-)</th>
            <th width="12%" class="text-right">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($transactions)): ?>
        <tr><td colspan="8" class="text-center">Tidak ada transaksi</td></tr>
        <?php else: ?>
        <?php foreach ($transactions as $index => $trans): ?>
        <tr>
            <td class="text-center"><?php echo $index + 1; ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($trans['transaction_date'])); ?></td>
            <td>
                <span class="badge badge-<?php 
                    echo $trans['transaction_type'] === 'modal' ? 'info' : 
                        ($trans['transaction_type'] === 'penarikan' ? 'warning' :
                        ($trans['transaction_type'] === 'stock_in' ? 'danger' : 'success')); 
                ?>">
                    <?php 
                    $types = [
                        'modal' => 'Modal',
                        'penarikan' => 'Penarikan',
                        'stock_in' => 'Pembelian',
                        'stock_out' => 'Penjualan'
                    ];
                    echo $types[$trans['transaction_type']] ?? $trans['transaction_type'];
                    ?>
                </span>
            </td>
            <td><strong><?php echo $trans['reference_code']; ?></strong></td>
            <td><?php echo $trans['description']; ?></td>
            <td class="text-right">
                <?php if ($trans['debit'] > 0): ?>
                    <strong style="color: var(--success-color);"><?php echo formatRupiah($trans['debit']); ?></strong>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td class="text-right">
                <?php if ($trans['credit'] > 0): ?>
                    <strong style="color: var(--danger-color);"><?php echo formatRupiah($trans['credit']); ?></strong>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td class="text-right"><strong><?php echo formatRupiah($trans['balance_after']); ?></strong></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Bottom Summary - Compact & Responsive -->
<div class="financial-bottom-summary">
    <div class="summary-item">
        <span class="summary-label">Total Debit:</span>
        <span class="summary-value debit-value"><?php echo formatRupiah($total_debit); ?></span>
    </div>
    <div class="summary-item">
        <span class="summary-label">Total Kredit:</span>
        <span class="summary-value kredit-value"><?php echo formatRupiah($total_credit); ?></span>
    </div>
    <div class="summary-item">
        <span class="summary-label">Total Saldo:</span>
        <span class="summary-value net-value" style="color: <?php echo $net >= 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
            <?php echo formatRupiah($net); ?>
        </span>
    </div>
</div>
