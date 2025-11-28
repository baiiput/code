<?php
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$warehouse_filter = $_GET['warehouse_id'] ?? '';
$branch_filter = $_GET['branch_id'] ?? '';

// Get warehouses and branches for filter
$warehouses = [];
$wh_result = $conn->query("SELECT warehouse_id, warehouse_code, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name");
while ($wh = $wh_result->fetch_assoc()) {
    $warehouses[] = $wh;
}

$branches = [];
$br_result = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name");
while ($br = $br_result->fetch_assoc()) {
    $branches[] = $br;
}

// Query for distribution tracking
$query = "
    SELECT so.stock_out_id, so.transaction_code, so.transaction_date,
           w.warehouse_id, w.warehouse_code, w.warehouse_name,
           b.branch_id, b.branch_name,
           so.total_amount,
           so.notes,
           COUNT(DISTINCT sod.item_id) as item_count,
           SUM(sod.quantity) as total_qty
    FROM stock_out so
    LEFT JOIN warehouses w ON so.warehouse_id = w.warehouse_id
    LEFT JOIN branches b ON so.branch_id = b.branch_id
    LEFT JOIN stock_out_detail sod ON so.stock_out_id = sod.stock_out_id
    WHERE DATE(so.transaction_date) BETWEEN '$date_from' AND '$date_to'
";

if ($warehouse_filter) {
    $query .= " AND so.warehouse_id = " . intval($warehouse_filter);
}

if ($branch_filter) {
    $query .= " AND so.branch_id = " . intval($branch_filter);
}

$query .= " GROUP BY so.stock_out_id
            ORDER BY so.transaction_date DESC, so.stock_out_id DESC";

$distributions = [];
$grand_total = 0;
$warehouse_totals = [];
$branch_totals = [];

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $distributions[] = $row;
    $grand_total += $row['total_amount'];

    // Warehouse totals
    $wh_id = $row['warehouse_id'];
    if (!isset($warehouse_totals[$wh_id])) {
        $warehouse_totals[$wh_id] = [
            'warehouse_name' => $row['warehouse_name'],
            'warehouse_code' => $row['warehouse_code'],
            'total_value' => 0,
            'total_transactions' => 0
        ];
    }
    $warehouse_totals[$wh_id]['total_value'] += $row['total_amount'];
    $warehouse_totals[$wh_id]['total_transactions']++;

    // Branch totals
    $br_id = $row['branch_id'];
    if (!isset($branch_totals[$br_id])) {
        $branch_totals[$br_id] = [
            'branch_name' => $row['branch_name'],
            'total_value' => 0,
            'total_transactions' => 0
        ];
    }
    $branch_totals[$br_id]['total_value'] += $row['total_amount'];
    $branch_totals[$br_id]['total_transactions']++;
}
?>

<h2 style="margin-bottom: 20px;"><i class="fas fa-truck"></i> Laporan Tracking Distribusi Warehouse ke Cabang</h2>

<!-- Summary Section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <!-- Warehouse Summary -->
    <div style="background: var(--bg-primary); border-radius: 12px; padding: 16px; border-left: 4px solid var(--primary-color);">
        <h3 style="font-size: 14px; margin: 0 0 12px 0; color: var(--text-secondary); text-transform: uppercase; font-weight: 600;">
            <i class="fas fa-warehouse"></i> Distribusi per Warehouse
        </h3>
        <?php if (!empty($warehouse_totals)): ?>
            <?php foreach ($warehouse_totals as $wh_summary): ?>
            <div style="padding: 10px; background: var(--bg-card); border-radius: 8px; margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-weight: 700; font-size: 13px; color: var(--primary-color);">
                        <?php echo $wh_summary['warehouse_code']; ?>
                    </span>
                    <span style="font-size: 11px; color: var(--text-secondary);">
                        <?php echo number_format($wh_summary['total_transactions']); ?> transaksi
                    </span>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 4px;">
                    <?php echo $wh_summary['warehouse_name']; ?>
                </div>
                <div style="font-weight: 700; font-size: 14px; color: var(--success-color);">
                    <?php echo formatRupiah($wh_summary['total_value']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-secondary); padding: 20px; font-size: 13px;">Tidak ada data</p>
        <?php endif; ?>
    </div>

    <!-- Branch Summary -->
    <div style="background: var(--bg-primary); border-radius: 12px; padding: 16px; border-left: 4px solid var(--success-color);">
        <h3 style="font-size: 14px; margin: 0 0 12px 0; color: var(--text-secondary); text-transform: uppercase; font-weight: 600;">
            <i class="fas fa-building"></i> Distribusi per Cabang
        </h3>
        <?php if (!empty($branch_totals)): ?>
            <?php foreach ($branch_totals as $br_summary): ?>
            <div style="padding: 10px; background: var(--bg-card); border-radius: 8px; margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-weight: 700; font-size: 13px;">
                        <?php echo $br_summary['branch_name']; ?>
                    </span>
                    <span style="font-size: 11px; color: var(--text-secondary);">
                        <?php echo number_format($br_summary['total_transactions']); ?> transaksi
                    </span>
                </div>
                <div style="font-weight: 700; font-size: 14px; color: var(--success-color);">
                    <?php echo formatRupiah($br_summary['total_value']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-secondary); padding: 20px; font-size: 13px;">Tidak ada data</p>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section" style="margin-bottom: 20px;">
    <form method="GET" class="filter-form">
        <input type="hidden" name="type" value="distribution">
        <input type="date" name="date_from" value="<?php echo $date_from; ?>" required>
        <span>s/d</span>
        <input type="date" name="date_to" value="<?php echo $date_to; ?>" required>
        <select name="warehouse_id" style="width: 180px;">
            <option value="">Semua Warehouse</option>
            <?php foreach ($warehouses as $wh): ?>
            <option value="<?php echo $wh['warehouse_id']; ?>" <?php echo $warehouse_filter == $wh['warehouse_id'] ? 'selected' : ''; ?>>
                <?php echo $wh['warehouse_name']; ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="branch_id" style="width: 180px;">
            <option value="">Semua Cabang</option>
            <?php foreach ($branches as $br): ?>
            <option value="<?php echo $br['branch_id']; ?>" <?php echo $branch_filter == $br['branch_id'] ? 'selected' : ''; ?>>
                <?php echo $br['branch_name']; ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($warehouse_filter || $branch_filter): ?>
        <a href="reports.php?type=distribution&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset Filter
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Detail Tracking Table -->
<table class="data-table" id="reportTable">
    <thead>
        <tr>
            <th width="4%">No</th>
            <th width="11%">Kode Transaksi</th>
            <th width="10%">Tanggal</th>
            <th width="14%">Dari Warehouse</th>
            <th width="14%">Ke Cabang</th>
            <th width="8%" class="text-right">Jml Item</th>
            <th width="9%" class="text-right">Total Qty</th>
            <th width="13%" class="text-right">Total Nilai</th>
            <th width="17%">Catatan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($distributions)): ?>
        <tr><td colspan="9" class="text-center">Tidak ada data distribusi</td></tr>
        <?php else: ?>
        <?php foreach ($distributions as $index => $dist): ?>
        <tr style="cursor: pointer;" onclick="viewDistributionDetail(<?php echo $dist['stock_out_id']; ?>)" title="Klik untuk lihat detail">
            <td class="text-center"><?php echo $index + 1; ?></td>
            <td>
                <strong style="color: var(--primary-color);"><?php echo $dist['transaction_code']; ?></strong>
            </td>
            <td><?php echo date('d/m/Y H:i', strtotime($dist['transaction_date'])); ?></td>
            <td>
                <span style="font-weight: 700; font-size: 11px; color: var(--primary-color); display: block;">
                    <?php echo $dist['warehouse_code']; ?>
                </span>
                <span style="font-size: 11px; color: var(--text-secondary);">
                    <?php echo $dist['warehouse_name']; ?>
                </span>
            </td>
            <td>
                <strong><?php echo $dist['branch_name']; ?></strong>
            </td>
            <td class="text-right">
                <span class="badge badge-info"><?php echo number_format($dist['item_count']); ?> item</span>
            </td>
            <td class="text-right"><?php echo number_format($dist['total_qty'], 0); ?></td>
            <td class="text-right">
                <strong style="color: var(--success-color);">
                    <?php echo formatRupiah($dist['total_amount']); ?>
                </strong>
            </td>
            <td style="font-size: 11px; color: var(--text-secondary);">
                <?php echo $dist['notes'] ? (strlen($dist['notes']) > 30 ? substr($dist['notes'], 0, 30) . '...' : $dist['notes']) : '-'; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr style="background: var(--success-light); font-weight: 700;">
            <td colspan="7" class="text-right">GRAND TOTAL:</td>
            <td class="text-right" style="color: var(--success-color); font-size: 16px;">
                <?php echo formatRupiah($grand_total); ?>
            </td>
            <td></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Detail Modal -->
<div id="distributionDetailModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2><i class="fas fa-file-invoice"></i> Detail Distribusi</h2>
            <span class="close" onclick="closeDistributionDetailModal()">&times;</span>
        </div>
        <div id="distributionDetailContent" style="padding: 24px;">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
function viewDistributionDetail(stockOutId) {
    document.getElementById('distributionDetailModal').style.display = 'block';
    document.getElementById('distributionDetailContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    fetch(`stock_out_detail.php?id=${stockOutId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('distributionDetailContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('distributionDetailContent').innerHTML = '<p style="text-align:center;padding:40px;color:var(--danger-color);">Error loading detail</p>';
        });
}

function closeDistributionDetailModal() {
    document.getElementById('distributionDetailModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('distributionDetailModal');
    if (event.target == modal) {
        closeDistributionDetailModal();
    }
}
</script>

<style>
tr[onclick] {
    transition: all 0.2s;
}

tr[onclick]:hover {
    background: var(--primary-light) !important;
    transform: translateX(2px);
}
</style>
