<?php
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

$query = "
    SELECT b.branch_name,
           COUNT(DISTINCT so.stock_out_id) as total_transactions,
           SUM(so.total_amount) as total_value,
           SUM(sod.quantity) as total_qty,
           MAX(so.transaction_date) as last_transaction
    FROM stock_out so
    LEFT JOIN branches b ON so.branch_id = b.branch_id
    LEFT JOIN stock_out_detail sod ON so.stock_out_id = sod.stock_out_id
    WHERE DATE(so.transaction_date) BETWEEN '$date_from' AND '$date_to'
    GROUP BY so.branch_id, b.branch_name
    ORDER BY last_transaction DESC, total_value DESC
";

$distributions = [];
$grand_total = 0;

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $distributions[] = $row;
    $grand_total += $row['total_value'];
}
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-truck"></i> Laporan Distribusi per Cabang</h2>

<div class="filter-section" style="margin-bottom: 20px;">
    <form method="GET" class="filter-form">
        <input type="hidden" name="type" value="distribution">
        <input type="date" name="date_from" value="<?php echo $date_from; ?>" required>
        <span>s/d</span>
        <input type="date" name="date_to" value="<?php echo $date_to; ?>" required>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    </form>
</div>

<table class="data-table" id="reportTable">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th>Nama Cabang</th>
            <th width="15%" class="text-right">Jumlah Transaksi</th>
            <th width="15%" class="text-right">Total Qty</th>
            <th width="18%" class="text-right">Total Nilai</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($distributions)): ?>
        <tr><td colspan="5" class="text-center">Tidak ada data distribusi</td></tr>
        <?php else: ?>
        <?php foreach ($distributions as $index => $dist): ?>
        <tr>
            <td class="text-center"><?php echo $index + 1; ?></td>
            <td><strong><?php echo $dist['branch_name']; ?></strong></td>
            <td class="text-right"><?php echo number_format($dist['total_transactions']); ?> transaksi</td>
            <td class="text-right"><?php echo number_format($dist['total_qty'], 0); ?></td>
            <td class="text-right"><strong><?php echo formatRupiah($dist['total_value']); ?></strong></td>
        </tr>
        <?php endforeach; ?>
        <tr style="background: var(--success-light); font-weight: 700;">
            <td colspan="4" class="text-right">TOTAL:</td>
            <td class="text-right" style="color: var(--success-color); font-size: 16px;">
                <?php echo formatRupiah($grand_total); ?>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
