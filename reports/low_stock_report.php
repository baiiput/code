<?php
$query = "
    SELECT i.*, c.category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE i.current_stock <= i.min_stock
    ORDER BY i.current_stock ASC
";

$items = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-exclamation-triangle"></i> Laporan Stok Rendah / Kosong</h2>

<table class="data-table" id="reportTable">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="12%">Kode</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th width="10%" class="text-right">Stok Saat Ini</th>
            <th width="10%" class="text-right">Min. Stok</th>
            <th width="8%">Satuan</th>
            <th width="10%">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?>
        <tr><td colspan="8" class="text-center" style="padding: 40px; color: var(--success-color);">
            <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px;"></i><br>
            <strong style="font-size: 18px;">Semua Stok Aman!</strong>
        </td></tr>
        <?php else: ?>
        <?php foreach ($items as $index => $item): 
            $status = getStockStatus($item['current_stock'], $item['min_stock']);
        ?>
        <tr>
            <td class="text-center"><?php echo $index + 1; ?></td>
            <td><strong><?php echo $item['item_code']; ?></strong></td>
            <td><?php echo $item['item_name']; ?></td>
            <td><?php echo $item['category_name'] ?: '-'; ?></td>
            <td class="text-right"><strong><?php echo number_format($item['current_stock'], 0); ?></strong></td>
            <td class="text-right"><?php echo number_format($item['min_stock'], 0); ?></td>
            <td><?php echo $item['unit']; ?></td>
            <td><span class="badge badge-<?php echo $status['class']; ?>"><?php echo $status['status']; ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
