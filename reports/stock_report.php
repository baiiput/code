<?php
// Get filter parameters
$search = $_GET['search'] ?? '';
$warehouse_filter = $_GET['warehouse_id'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$view_mode = $_GET['view'] ?? 'grouped'; // grouped or detailed

// Get warehouses for filter
$warehouses = [];
$wh_result = $conn->query("SELECT warehouse_id, warehouse_code, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name");
while ($wh = $wh_result->fetch_assoc()) {
    $warehouses[] = $wh;
}

// Build query based on view mode
if ($view_mode === 'grouped') {
    // Grouped view - aggregate per item
    $query = "
        SELECT i.item_id, i.item_code, i.item_name, i.unit, c.category_name, i.min_stock,
               SUM(wi.current_stock) as total_stock,
               AVG(wi.average_cost) as avg_cost,
               SUM(wi.current_stock * wi.average_cost) as stock_value,
               COUNT(DISTINCT wi.warehouse_id) as warehouse_count
        FROM items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        LEFT JOIN warehouse_items wi ON i.item_id = wi.item_id
        LEFT JOIN warehouses w ON wi.warehouse_id = w.warehouse_id
        WHERE w.is_active = 1
    ";

    if ($search) {
        $search_safe = $conn->real_escape_string($search);
        $query .= " AND (i.item_code LIKE '%$search_safe%' OR i.item_name LIKE '%$search_safe%')";
    }

    if ($warehouse_filter) {
        $query .= " AND w.warehouse_id = " . intval($warehouse_filter);
    }

    $query .= " GROUP BY i.item_id";

    if ($sort === 'stock') {
        $query .= " ORDER BY total_stock DESC, i.item_name ASC";
    } else {
        $query .= " ORDER BY i.item_name ASC";
    }
} else {
    // Detailed view - per warehouse
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

    if ($search) {
        $search_safe = $conn->real_escape_string($search);
        $query .= " AND (i.item_code LIKE '%$search_safe%' OR i.item_name LIKE '%$search_safe%')";
    }

    if ($warehouse_filter) {
        $query .= " AND w.warehouse_id = " . intval($warehouse_filter);
    }

    if ($sort === 'stock') {
        $query .= " ORDER BY wi.current_stock DESC, i.item_name ASC";
    } elseif ($sort === 'warehouse') {
        $query .= " ORDER BY w.warehouse_name ASC, i.item_name ASC";
    } else {
        $query .= " ORDER BY i.item_name ASC, w.warehouse_name ASC";
    }
}

$items = [];
$total_value = 0;
$warehouse_totals = [];

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total_value += $row['stock_value'];

    // Calculate per warehouse totals (for detailed view)
    if ($view_mode === 'detailed' && isset($row['warehouse_id'])) {
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
}
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-boxes"></i> Laporan Stok Barang per Warehouse</h2>

<!-- View Mode Toggle -->
<div style="margin-bottom: 16px; display: flex; gap: 8px; justify-content: flex-end;">
    <a href="?type=stock&view=grouped<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $warehouse_filter ? '&warehouse_id=' . $warehouse_filter : ''; ?>&sort=<?php echo $sort; ?>"
       class="btn <?php echo $view_mode === 'grouped' ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 13px; padding: 8px 14px;">
        <i class="fas fa-layer-group"></i> View Grouped
    </a>
    <a href="?type=stock&view=detailed<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $warehouse_filter ? '&warehouse_id=' . $warehouse_filter : ''; ?>&sort=<?php echo $sort; ?>"
       class="btn <?php echo $view_mode === 'detailed' ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 13px; padding: 8px 14px;">
        <i class="fas fa-list"></i> View Detailed
    </a>
</div>

<!-- Summary Cards (only for detailed view) -->
<?php if ($view_mode === 'detailed' && !empty($warehouse_totals)): ?>
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
        <input type="hidden" name="view" value="<?php echo $view_mode; ?>">
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
            <?php if ($view_mode === 'detailed'): ?>
            <option value="warehouse" <?php echo $sort === 'warehouse' ? 'selected' : ''; ?>>Sort: Warehouse</option>
            <?php endif; ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($search || $warehouse_filter || $sort !== 'name'): ?>
        <a href="reports.php?type=stock&view=<?php echo $view_mode; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($view_mode === 'grouped'): ?>
<!-- GROUPED VIEW -->
<table class="data-table expandable-table" id="reportTable">
    <thead>
        <tr>
            <th width="3%"></th>
            <th width="10%">Kode Item</th>
            <th>Nama Barang</th>
            <th width="11%">Kategori</th>
            <th width="9%" class="text-right">Total Stok</th>
            <th width="7%">Unit</th>
            <th width="11%" class="text-right">Harga Avg</th>
            <th width="12%" class="text-right">Nilai Stok</th>
            <th width="10%">Warehouses</th>
            <th width="9%">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?>
        <tr><td colspan="10" class="text-center">Tidak ada data</td></tr>
        <?php else: ?>
        <?php foreach ($items as $index => $item):
            $status = getStockStatus($item['total_stock'], $item['min_stock']);
            $item_id = $item['item_id'];
        ?>
        <tr class="main-row" onclick="toggleDetails(<?php echo $item_id; ?>)" style="cursor: pointer;">
            <td class="text-center">
                <i class="fas fa-plus-circle expand-icon" id="icon-<?php echo $item_id; ?>" style="color: var(--primary-color); font-size: 14px;"></i>
            </td>
            <td><strong><?php echo $item['item_code']; ?></strong></td>
            <td><?php echo $item['item_name']; ?></td>
            <td><?php echo $item['category_name'] ?: '-'; ?></td>
            <td class="text-right"><strong><?php echo number_format($item['total_stock'], 2); ?></strong></td>
            <td><?php echo $item['unit']; ?></td>
            <td class="text-right"><?php echo formatRupiah($item['avg_cost']); ?></td>
            <td class="text-right"><strong><?php echo formatRupiah($item['stock_value']); ?></strong></td>
            <td class="text-center">
                <span class="badge badge-info"><?php echo $item['warehouse_count']; ?> WH</span>
            </td>
            <td><span class="badge badge-<?php echo $status['class']; ?>"><?php echo $status['status']; ?></span></td>
        </tr>
        <tr class="detail-row" id="detail-<?php echo $item_id; ?>" style="display: none;">
            <td colspan="10" style="padding: 0; background: var(--bg-primary);">
                <div class="detail-container" style="padding: 12px 20px;">
                    <table style="width: 100%; font-size: 13px;">
                        <thead>
                            <tr style="background: none; border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 8px; text-align: left; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase;">Warehouse</th>
                                <th style="padding: 8px; text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase;">Stok</th>
                                <th style="padding: 8px; text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase;">Harga Avg</th>
                                <th style="padding: 8px; text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase;">Nilai</th>
                                <th style="padding: 8px; text-align: right; font-weight: 600; color: var(--text-secondary); font-size: 11px; text-transform: uppercase;">Min Stock</th>
                            </tr>
                        </thead>
                        <tbody id="warehouse-details-<?php echo $item_id; ?>">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: var(--text-secondary);">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr style="background: var(--primary-light); font-weight: 700;">
            <td colspan="7" class="text-right">TOTAL NILAI STOK:</td>
            <td class="text-right" style="color: var(--primary-color); font-size: 16px;">
                <?php echo formatRupiah($total_value); ?>
            </td>
            <td colspan="2"></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php else: ?>
<!-- DETAILED VIEW -->
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
<?php endif; ?>

<style>
.expandable-table .main-row:hover {
    background: var(--primary-light);
}

.expandable-table .detail-row td {
    border-top: none !important;
}

.detail-container table tbody tr {
    border-bottom: 1px solid var(--border-color);
}

.detail-container table tbody tr:last-child {
    border-bottom: none;
}

.detail-container table tbody td {
    padding: 10px 8px;
}

.expand-icon {
    transition: transform 0.3s;
}

.expand-icon.expanded {
    transform: rotate(45deg);
}
</style>

<script>
function toggleDetails(itemId) {
    const detailRow = document.getElementById('detail-' + itemId);
    const icon = document.getElementById('icon-' + itemId);
    const detailsContainer = document.getElementById('warehouse-details-' + itemId);

    if (detailRow.style.display === 'none') {
        // Expand
        detailRow.style.display = 'table-row';
        icon.classList.remove('fa-plus-circle');
        icon.classList.add('fa-minus-circle', 'expanded');

        // Load warehouse details via AJAX
        if (detailsContainer.querySelector('.fas.fa-spinner')) {
            fetch(`get_item_warehouse_details.php?item_id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    if (data.length === 0) {
                        html = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-secondary);">Tidak ada data warehouse</td></tr>';
                    } else {
                        data.forEach(wh => {
                            const stockStatus = getStockStatusClass(wh.current_stock, wh.min_stock);
                            html += `
                                <tr style="background: var(--bg-card);">
                                    <td style="padding: 10px 8px;">
                                        <span style="font-weight: 600; color: var(--primary-color); font-size: 12px;">${wh.warehouse_code}</span>
                                        <span style="color: var(--text-secondary); font-size: 11px;"> - ${wh.warehouse_name}</span>
                                    </td>
                                    <td style="padding: 10px 8px; text-align: right; font-weight: 600;">${parseFloat(wh.current_stock).toFixed(2)}</td>
                                    <td style="padding: 10px 8px; text-align: right;">${formatRupiah(wh.average_cost)}</td>
                                    <td style="padding: 10px 8px; text-align: right; font-weight: 600;">${formatRupiah(wh.current_stock * wh.average_cost)}</td>
                                    <td style="padding: 10px 8px; text-align: right;">
                                        <span class="badge badge-${stockStatus}">${parseFloat(wh.min_stock).toFixed(2)}</span>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    detailsContainer.innerHTML = html;
                })
                .catch(error => {
                    detailsContainer.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--danger-color);">Error loading details</td></tr>';
                });
        }
    } else {
        // Collapse
        detailRow.style.display = 'none';
        icon.classList.remove('fa-minus-circle', 'expanded');
        icon.classList.add('fa-plus-circle');
    }
}

function getStockStatusClass(currentStock, minStock) {
    if (currentStock <= 0) return 'danger';
    if (currentStock <= minStock) return 'warning';
    if (currentStock <= minStock * 2) return 'info';
    return 'success';
}

function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(amount));
}
</script>
