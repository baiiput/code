<?php
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get stock in movements
$query_in = "
    SELECT 
        si.transaction_date,
        si.transaction_code,
        'Stock In' as type,
        s.supplier_name as source,
        i.item_code,
        i.item_name,
        sid.quantity,
        i.unit,
        sid.unit_price,
        sid.subtotal
    FROM stock_in si
    JOIN stock_in_detail sid ON si.stock_in_id = sid.stock_in_id
    JOIN items i ON sid.item_id = i.item_id
    LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
    WHERE DATE(si.transaction_date) BETWEEN '$date_from' AND '$date_to'
";

// Get stock out movements
$query_out = "
    SELECT 
        so.transaction_date,
        so.transaction_code,
        'Stock Out' as type,
        b.branch_name as source,
        i.item_code,
        i.item_name,
        sod.quantity,
        i.unit,
        sod.unit_price,
        sod.subtotal
    FROM stock_out so
    JOIN stock_out_detail sod ON so.stock_out_id = sod.stock_out_id
    JOIN items i ON sod.item_id = i.item_id
    LEFT JOIN branches b ON so.branch_id = b.branch_id
    WHERE DATE(so.transaction_date) BETWEEN '$date_from' AND '$date_to'
";

// Get adjustments
$query_adj = "
    SELECT 
        sa.adjustment_date as transaction_date,
        sa.transaction_code,
        'Adjustment' as type,
        sa.reason as source,
        i.item_code,
        i.item_name,
        sa.difference as quantity,
        i.unit,
        0 as unit_price,
        0 as subtotal
    FROM stock_adjustment sa
    JOIN items i ON sa.item_id = i.item_id
    WHERE DATE(sa.adjustment_date) BETWEEN '$date_from' AND '$date_to'
";

// Combine all
$query = "($query_in) UNION ($query_out) UNION ($query_adj) ORDER BY transaction_date DESC";

$movements = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $movements[] = $row;
}
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-exchange-alt"></i> Laporan Pergerakan Stok</h2>

<div class="filter-section" style="margin-bottom: 20px;">
    <form method="GET" class="filter-form">
        <input type="hidden" name="type" value="stock_movement">
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
            <th width="13%">Tanggal</th>
            <th width="13%">Kode</th>
            <th width="10%">Tipe</th>
            <th>Barang</th>
            <th width="12%" class="text-right">Qty</th>
            <th width="12%" class="text-right">Harga</th>
            <th>Sumber/Tujuan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($movements)): ?>
        <tr><td colspan="8" class="text-center">Tidak ada pergerakan stok</td></tr>
        <?php else: ?>
        <?php foreach ($movements as $index => $mov): ?>
        <tr>
            <td class="text-center"><?php echo $index + 1; ?></td>
            <td><?php echo date('d/m/Y H:i', strtotime($mov['transaction_date'])); ?></td>
            <td><strong><?php echo $mov['transaction_code']; ?></strong></td>
            <td>
                <span class="badge badge-<?php 
                    echo $mov['type'] === 'Stock In' ? 'primary' : 
                        ($mov['type'] === 'Stock Out' ? 'success' : 'warning'); 
                ?>">
                    <?php echo $mov['type']; ?>
                </span>
            </td>
            <td><?php echo $mov['item_code']; ?> - <?php echo $mov['item_name']; ?></td>
            <td class="text-right">
                <strong style="color: <?php echo $mov['quantity'] >= 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
                    <?php echo ($mov['quantity'] >= 0 ? '+' : '') . number_format($mov['quantity'], 0); ?> <?php echo $mov['unit']; ?>
                </strong>
            </td>
            <td class="text-right"><?php echo $mov['unit_price'] > 0 ? formatRupiah($mov['unit_price']) : '-'; ?></td>
            <td><?php echo $mov['source']; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
