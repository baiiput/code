<?php
// Laporan Stok - All items with current stock
$query = "
    SELECT i.*, c.category_name,
           (i.current_stock * i.average_cost) as stock_value
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    ORDER BY i.item_code
";

$items = [];
$total_value = 0;

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total_value += $row['stock_value'];
}
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-boxes"></i> Laporan Stok Barang</h2>

<table class="data-table" id="reportTable">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="12%">Kode</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th width="10%" class="text-right">Stok</th>
            <th width="8%">Satuan</th>
            <th width="12%" class="text-right">Harga Avg</th>
            <th width="13%" class="text-right">Nilai Stok</th>
            <th width="10%">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?>
        <tr><td colspan="9" class="text-center">Tidak ada data</td></tr>
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
            <td><?php echo $item['unit']; ?></td>
            <td class="text-right"><?php echo formatRupiah($item['average_cost']); ?></td>
            <td class="text-right"><strong><?php echo formatRupiah($item['stock_value']); ?></strong></td>
            <td><span class="badge badge-<?php echo $status['class']; ?>"><?php echo $status['status']; ?></span></td>
        </tr>
        <?php endforeach; ?>
        <tr style="background: var(--primary-light); font-weight: 700;">
            <td colspan="7" class="text-right">TOTAL NILAI STOK:</td>
            <td class="text-right" style="color: var(--primary-color); font-size: 16px;">
                <?php echo formatRupiah($total_value); ?>
            </td>
            <td></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
