<?php
require_once 'config.php';
requireRole(['admin', 'manager', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();

// Get warehouses for dropdown (filter by role)
$warehouses = [];
if ($user['role'] === 'staff_warehouse' && !empty($user['warehouse_id'])) {
    $stmt = $conn->prepare("SELECT warehouse_id, warehouse_code, warehouse_name FROM warehouses WHERE warehouse_id = ? AND is_active = 1");
    $stmt->bind_param("i", $user['warehouse_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $warehouses[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query("SELECT warehouse_id, warehouse_code, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name");
    while ($row = $result->fetch_assoc()) {
        $warehouses[] = $row;
    }
}

// Get items - will be loaded via AJAX based on selected warehouse
$items = [];

// Get adjustments
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$query = "
    SELECT sa.*, i.item_code, i.item_name, i.unit, u.full_name as created_by_name,
           w.warehouse_name, w.warehouse_code
    FROM stock_adjustment sa
    LEFT JOIN items i ON sa.item_id = i.item_id
    LEFT JOIN users u ON sa.created_by = u.user_id
    LEFT JOIN warehouses w ON sa.warehouse_id = w.warehouse_id
    WHERE 1=1
";

if ($search) {
    $query .= " AND (sa.transaction_code LIKE '%$search%' OR i.item_name LIKE '%$search%')";
}
if ($date_from) {
    $query .= " AND DATE(sa.adjustment_date) >= '$date_from'";
}
if ($date_to) {
    $query .= " AND DATE(sa.adjustment_date) <= '$date_to'";
}

$query .= " ORDER BY sa.adjustment_date DESC, sa.adjustment_id DESC LIMIT 50";

$adjustments = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $adjustments[] = $row;
}

$page_title = 'Opname Stok';
include 'includes/header.php';

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-wrench"></i> Opname / Koreksi Stok</h1>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Tambah Koreksi
        </button>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Cari kode atau nama barang..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
            <input type="date" name="date_to" value="<?php echo $date_to; ?>">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if ($search || $date_from || $date_to): ?>
            <a href="stock_adjustment.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="10%">Kode</th>
                    <th width="12%">Tanggal</th>
                    <th width="13%">Warehouse</th>
                    <th>Barang</th>
                    <th width="8%" class="text-right">Stok Lama</th>
                    <th width="8%" class="text-right">Stok Baru</th>
                    <th width="8%" class="text-right">Selisih</th>
                    <th>Alasan</th>
                    <th width="<?php echo hasRole('admin') ? '12%' : '8%'; ?>">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adjustments)): ?>
                <tr><td colspan="9" class="text-center">Belum ada koreksi stok</td></tr>
                <?php else: ?>
                <?php foreach ($adjustments as $adj): ?>
                <tr>
                    <td><strong><?php echo $adj['transaction_code']; ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($adj['adjustment_date'])); ?></td>
                    <td><?php echo $adj['warehouse_name'] ?? '-'; ?></td>
                    <td><?php echo $adj['item_code']; ?> - <?php echo $adj['item_name']; ?></td>
                    <td class="text-right"><?php echo formatNumber($adj['old_stock'], 2); ?></td>
                    <td class="text-right"><strong><?php echo formatNumber($adj['new_stock'], 2); ?></strong></td>
                    <td class="text-right">
                        <span style="color: <?php echo $adj['difference'] >= 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>; font-weight: 600;">
                            <?php echo ($adj['difference'] >= 0 ? '+' : '') . formatNumber($adj['difference'], 2); ?>
                        </span>
                    </td>
                    <td><?php echo strlen($adj['reason']) > 30 ? substr($adj['reason'], 0, 30) . '...' : $adj['reason']; ?></td>
                    <td>
                        <button class="btn-sm btn-primary" onclick="viewDetail(<?php echo $adj['adjustment_id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if (hasRole('admin')): ?>
                        <a href="stock_adjustment_edit.php?id=<?php echo $adj['adjustment_id']; ?>" class="btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn-sm btn-danger" onclick="deleteAdjustment(<?php echo $adj['adjustment_id']; ?>, '<?php echo $adj['transaction_code']; ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div id="adjustmentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-wrench"></i> Koreksi Stok</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" action="stock_adjustment_process.php" id="adjustmentForm">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Tanggal *</label>
                    <input type="datetime-local" name="adjustment_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-warehouse"></i> Warehouse *</label>
                    <select name="warehouse_id" id="warehouseSelect" required onchange="loadWarehouseItems()">
                        <option value="">Pilih Warehouse</option>
                        <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo $wh['warehouse_id']; ?>">
                            <?php echo $wh['warehouse_name']; ?> (<?php echo $wh['warehouse_code']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-box"></i> Barang *</label>
                    <select name="item_id" id="itemSelect" required onchange="updateCurrentStock()" disabled>
                        <option value="">Pilih Warehouse terlebih dahulu</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-database"></i> Stok Sistem (Saat Ini)</label>
                    <input type="text" id="oldStock" readonly style="background: var(--bg-primary); font-weight: 600; font-size: 18px;">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-boxes"></i> Stok Fisik (Hasil Hitung) *</label>
                    <input type="number" name="new_stock" id="newStock" step="1" min="0" required onchange="calculateDifference()">
                </div>
            </div>
            
            <div style="padding: 16px; background: var(--bg-primary); border-radius: 10px; margin: 16px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong>Selisih:</strong>
                    <strong id="difference" style="font-size: 20px;">-</strong>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Alasan Koreksi *</label>
                <textarea name="reason" rows="3" required placeholder="Jelaskan alasan koreksi stok (contoh: Barang rusak, hilang, salah input, dll)"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Koreksi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-file-alt"></i> Detail Koreksi</h2>
            <span class="close" onclick="closeDetailModal()">&times;</span>
        </div>
        <div id="detailContent" style="padding: 24px;">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
let itemsData = [];

function showAddModal() {
    document.getElementById('adjustmentModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('adjustmentModal').style.display = 'none';
    document.getElementById('adjustmentForm').reset();
    document.getElementById('oldStock').value = '';
    document.getElementById('difference').textContent = '-';
    document.getElementById('itemSelect').innerHTML = '<option value="">Pilih Warehouse terlebih dahulu</option>';
    document.getElementById('itemSelect').disabled = true;
}

async function loadWarehouseItems() {
    const warehouseId = document.getElementById('warehouseSelect').value;
    const itemSelect = document.getElementById('itemSelect');

    if (!warehouseId) {
        itemSelect.innerHTML = '<option value="">Pilih Warehouse terlebih dahulu</option>';
        itemSelect.disabled = true;
        // Reset fields
        document.getElementById('oldStock').value = '';
        document.getElementById('newStock').value = '';
        document.getElementById('difference').textContent = '-';
        return;
    }

    itemSelect.innerHTML = '<option value="">Loading...</option>';
    itemSelect.disabled = true;

    // Reset fields when warehouse changes
    document.getElementById('oldStock').value = '';
    document.getElementById('newStock').value = '';
    document.getElementById('difference').textContent = '-';

    try {
        const response = await fetch(`get_warehouse_items.php?warehouse_id=${warehouseId}`);
        const items = await response.json();
        itemsData = items;

        itemSelect.innerHTML = '<option value="">Pilih Barang</option>';
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.item_id;
            option.dataset.stock = item.current_stock;
            option.dataset.unit = item.unit;
            option.textContent = `${item.item_code} - ${item.item_name} (Stok: ${parseFloat(item.current_stock).toFixed(2)})`;
            itemSelect.appendChild(option);
        });
        itemSelect.disabled = false;
    } catch (error) {
        console.error('Error loading items:', error);
        itemSelect.innerHTML = '<option value="">Error loading items</option>';
    }
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

function updateCurrentStock() {
    const select = document.getElementById('itemSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const stock = parseFloat(option.dataset.stock);
        const unit = option.dataset.unit;
        document.getElementById('oldStock').value = `${stock.toFixed(2)} ${unit}`;
        document.getElementById('newStock').value = '';
        document.getElementById('difference').textContent = '-';
    }
}

function calculateDifference() {
    const select = document.getElementById('itemSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const oldStock = parseFloat(option.dataset.stock);
        const newStock = parseFloat(document.getElementById('newStock').value) || 0;
        const diff = newStock - oldStock;
        const unit = option.dataset.unit;
        
        const diffElement = document.getElementById('difference');
        diffElement.textContent = `${diff >= 0 ? '+' : ''}${diff.toFixed(2)} ${unit}`;
        diffElement.style.color = diff >= 0 ? 'var(--success-color)' : 'var(--danger-color)';
    }
}

function viewDetail(id) {
    document.getElementById('detailModal').style.display = 'block';
    document.getElementById('detailContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    
    fetch(`stock_adjustment_detail.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('detailContent').innerHTML = '<p style="text-align:center;padding:40px;color:var(--danger-color);">Error loading detail</p>';
        });
}

function deleteAdjustment(id, code) {
    if (confirm(`Apakah Anda yakin ingin menghapus koreksi stok ${code}?\n\nPerhatian: Ini akan:\n- Menghapus transaksi koreksi stok\n- Mengembalikan stok ke kondisi sebelumnya\n\nTindakan ini tidak dapat dibatalkan!`)) {
        window.location.href = `stock_adjustment_delete.php?id=${id}`;
    }
}

window.onclick = function(event) {
    const modal1 = document.getElementById('adjustmentModal');
    const modal2 = document.getElementById('detailModal');
    if (event.target == modal1) closeModal();
    else if (event.target == modal2) closeDetailModal();
}
</script>

<style>
/* Date and DateTime picker improvements for mobile */
input[type="date"],
input[type="datetime-local"] {
    min-height: 44px;
}

/* Detail modal content responsive */
#detailContent {
    padding: 24px;
    max-height: calc(80vh - 100px);
    overflow-y: auto;
}

/* Modal responsive max-widths */
@media (max-width: 900px) {
    .modal-content[style*="max-width: 900px"],
    .modal-content[style*="max-width: 800px"] {
        max-width: calc(100% - 20px) !important;
    }
}

@media (max-width: 768px) {
    input[type="date"],
    input[type="datetime-local"] {
        font-size: 14px;
        padding: 10px;
    }

    #detailContent {
        padding: 15px;
        max-height: calc(85vh - 80px);
    }

    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="datetime-local"]::-webkit-calendar-picker-indicator {
        padding: 4px;
        cursor: pointer;
    }
}

@media (max-width: 480px) {
    input[type="date"],
    input[type="datetime-local"] {
        font-size: 13px;
        padding: 8px;
        min-height: 40px;
    }

    #detailContent {
        padding: 10px;
        max-height: calc(90vh - 60px);
    }

    .modal-content[style*="max-width"] {
        max-width: calc(100% - 10px) !important;
        margin: 5px auto !important;
    }

    input[type="date"]::-webkit-calendar-picker-indicator,
    input[type="datetime-local"]::-webkit-calendar-picker-indicator {
        width: 16px;
        height: 16px;
        padding: 2px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
