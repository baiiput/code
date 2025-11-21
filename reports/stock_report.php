<?php
// Get filter parameters
$search = $_GET['search'] ?? '';
$warehouse_filter = $_GET['warehouse_id'] ?? '';
$sort = $_GET['sort'] ?? 'name'; // Default sort by name

// Get warehouses for filter
$warehouses = [];
$wh_result = $conn->query("SELECT warehouse_id, warehouse_code, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name");
while ($wh = $wh_result->fetch_assoc()) {
    $warehouses[] = $wh;
}

// Build query for stock per warehouse
$query = "
    SELECT i.item_id, i.item_code, i.item_name, i.unit, c.category_name,
           w.warehouse_id, w.warehouse_code, w.warehouse_name,
           wi.current_stock, wi.average_cost, wi.min_stock,
           (wi.current_stock * wi.average_cost) as stock_value
    FROM warehouse_items wi
    JOIN items i ON wi.item_id = i.item_id
    JOIN warehouses w ON wi.warehouse_id = w.warehouse_id
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE w.is_active = 1
";

// Add search condition
if ($search) {
    $search_safe = $conn->real_escape_string($search);
    $query .= " AND (i.item_code LIKE '%$search_safe%' OR i.item_name LIKE '%$search_safe%')";
}

// Add warehouse filter
if ($warehouse_filter) {
    $query .= " AND w.warehouse_id = " . intval($warehouse_filter);
}

// Add sort
if ($sort === 'stock') {
    $query .= " ORDER BY wi.current_stock DESC, i.item_name ASC";
} elseif ($sort === 'warehouse') {
    $query .= " ORDER BY w.warehouse_name ASC, i.item_name ASC";
} else {
    $query .= " ORDER BY i.item_name ASC, w.warehouse_name ASC";
}

$items = [];
$total_value = 0;
$warehouse_totals = [];

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total_value += $row['stock_value'];

    // Calculate per warehouse totals
    $wh_id = $row['warehouse_id'];
    if (!isset($warehouse_totals[$wh_id])) {
        $warehouse_totals[$wh_id] = [
            'warehouse_name' => $row['warehouse_name'],
            'warehouse_code' => $row['warehouse_code'],
            'total_value' => 0,
            'total_items' => 0
        ];
    }
    $warehouse_totals[$wh_id]['total_value'] += $row['stock_value'];
    $warehouse_totals[$wh_id]['total_items']++;
}
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-boxes"></i> Laporan Stok Barang per Warehouse</h2>

<!-- Summary Cards -->
<?php if (!empty($warehouse_totals)): ?>
<div class="warehouse-summary-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; margin-bottom: 20px;">
    <?php foreach ($warehouse_totals as $wh_summary): ?>
    <div style="background: var(--bg-primary); border-radius: 10px; padding: 16px; border-left: 4px solid var(--primary-color);">
        <div style="font-size: 11px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">
            <?php echo $wh_summary['warehouse_code']; ?>
        </div>
        <div style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">
            <?php echo $wh_summary['warehouse_name']; ?>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--text-secondary);">
            <span><?php echo number_format($wh_summary['total_items']); ?> items</span>
            <span style="font-weight: 700; color: var(--primary-color); font-size: 14px;">
                <?php echo formatRupiah($wh_summary['total_value']); ?>
            </span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="filter-section" style="margin-bottom: 20px;">
    <form method="GET" class="filter-form">
        <input type="hidden" name="type" value="stock">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Cari kode atau nama barang..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="warehouse_id" style="width: 200px;">
            <option value="">Semua Warehouse</option>
            <?php foreach ($warehouses as $wh): ?>
            <option value="<?php echo $wh['warehouse_id']; ?>" <?php echo $warehouse_filter == $wh['warehouse_id'] ? 'selected' : ''; ?>>
                <?php echo $wh['warehouse_name']; ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="sort" style="width: 200px;">
            <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort: Nama (A-Z)</option>
            <option value="stock" <?php echo $sort === 'stock' ? 'selected' : ''; ?>>Sort: Stok (Banyak-Sedikit)</option>
            <option value="warehouse" <?php echo $sort === 'warehouse' ? 'selected' : ''; ?>>Sort: Warehouse</option>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($search || $warehouse_filter || $sort !== 'name'): ?>
        <a href="reports.php?type=stock" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<table class="data-table" id="reportTable">
    <thead>
        <tr>
            <th width="4%">No</th>
            <th width="10%">Kode Item</th>
            <th>Nama Barang</th>
            <th width="12%">Warehouse</th>
            <th width="10%">Kategori</th>
            <th width="8%" class="text-right">Stok</th>
            <th width="7%">Unit</th>
            <th width="11%" class="text-right">Harga Avg</th>
            <th width="12%" class="text-right">Nilai Stok</th>
            <th width="9%">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?>
        <tr><td colspan="10" class="text-center">Tidak ada data</td></tr>
        <?php else: ?>
        <?php foreach ($items as $index => $item):
            $status = getStockStatus($item['current_stock'], $item['min_stock']);
        ?>
        <tr>
            <td class="text-center"><?php echo $index + 1; ?></td>
            <td><strong><?php echo $item['item_code']; ?></strong></td>
            <td><?php echo $item['item_name']; ?></td>
            <td>
                <span style="font-weight: 600; font-size: 11px; color: var(--primary-color);">
                    <?php echo $item['warehouse_code']; ?>
                </span><br>
                <span style="font-size: 11px; color: var(--text-secondary);">
                    <?php echo $item['warehouse_name']; ?>
                </span>
            </td>
            <td><?php echo $item['category_name'] ?: '-'; ?></td>
            <td class="text-right"><strong><?php echo number_format($item['current_stock'], 2); ?></strong></td>
            <td><?php echo $item['unit']; ?></td>
            <td class="text-right"><?php echo formatRupiah($item['average_cost']); ?></td>
            <td class="text-right"><strong><?php echo formatRupiah($item['stock_value']); ?></strong></td>
            <td><span class="badge badge-<?php echo $status['class']; ?>"><?php echo $status['status']; ?></span></td>
        </tr>
        <?php endforeach; ?>
        <tr style="background: var(--primary-light); font-weight: 700;">
            <td colspan="8" class="text-right">TOTAL NILAI STOK:</td>
            <td class="text-right" style="color: var(--primary-color); font-size: 16px;">
                <?php echo formatRupiah($total_value); ?>
            </td>
            <td></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
