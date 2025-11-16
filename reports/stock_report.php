<?php
// Get filter parameters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name'; // Default sort by name

// Build query with search and sort
$query = "
    SELECT i.*, c.category_name,
           (i.current_stock * i.average_cost) as stock_value
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE 1=1
";

// Add search condition
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $query .= " AND (i.item_code LIKE '%$search_safe%' OR i.item_name LIKE '%$search_safe%')";
}

// Add sort
if ($sort === 'stock') {
    $query .= " ORDER BY i.current_stock DESC, i.item_name ASC";
} else {
    $query .= " ORDER BY i.item_name ASC";
}

$items = [];
$total_value = 0;

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total_value += $row['stock_value'];
}
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-boxes"></i> Laporan Stok Barang</h2>

<div class="filter-section" style="margin-bottom: 20px;">
    <form method="GET" class="filter-form">
        <input type="hidden" name="type" value="stock">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Cari kode atau nama barang..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="sort" style="width: 200px;">
            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort: Nama (A-Z)</option>
            <option value="stock" <?php echo $sort === 'stock' ? 'selected' : ''; ?>>Sort: Stok (Banyak-Sedikit)</option>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($search || $sort !== 'name'): ?>
        <a href="reports.php?type=stock" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

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
