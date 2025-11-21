<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();

// Get warehouses for dropdown (filter by role)
$warehouses = [];
if ($user['role'] === 'staff_warehouse' && !empty($user['warehouse_id'])) {
    // Staff can only transfer FROM their warehouse
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

// Get all warehouses for destination (no filter)
$all_warehouses = [];
$result = $conn->query("SELECT warehouse_id, warehouse_code, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name");
while ($row = $result->fetch_assoc()) {
    $all_warehouses[] = $row;
}

// Get transfers
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$query = "
    SELECT st.*,
           w_from.warehouse_name as from_warehouse_name, w_from.warehouse_code as from_warehouse_code,
           w_to.warehouse_name as to_warehouse_name, w_to.warehouse_code as to_warehouse_code,
           u.full_name as created_by_name
    FROM stock_transfers st
    LEFT JOIN warehouses w_from ON st.from_warehouse_id = w_from.warehouse_id
    LEFT JOIN warehouses w_to ON st.to_warehouse_id = w_to.warehouse_id
    LEFT JOIN users u ON st.created_by = u.user_id
    WHERE 1=1
";

if ($search) {
    $query .= " AND st.transaction_code LIKE '%$search%'";
}
if ($date_from) {
    $query .= " AND DATE(st.transfer_date) >= '$date_from'";
}
if ($date_to) {
    $query .= " AND DATE(st.transfer_date) <= '$date_to'";
}

$query .= " ORDER BY st.transfer_date DESC, st.transfer_id DESC LIMIT 50";

$transfers = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $transfers[] = $row;
}

$page_title = 'Transfer Stok';
include 'includes/header.php';

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-exchange-alt"></i> Transfer Stok Antar Warehouse</h1>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Tambah Transfer
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
                <input type="text" name="search" placeholder="Cari kode transaksi..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
            <input type="date" name="date_to" value="<?php echo $date_to; ?>">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if ($search || $date_from || $date_to): ?>
            <a href="stock_transfer.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="12%">Kode</th>
                    <th width="13%">Tanggal</th>
                    <th>Dari Warehouse</th>
                    <th>Ke Warehouse</th>
                    <th width="15%">Total Items</th>
                    <th>Catatan</th>
                    <th width="<?php echo hasRole('admin') ? '12%' : '8%'; ?>">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                <tr><td colspan="7" class="text-center">Belum ada transfer stok</td></tr>
                <?php else: ?>
                <?php foreach ($transfers as $transfer): ?>
                <tr>
                    <td><strong><?php echo $transfer['transaction_code']; ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($transfer['transfer_date'])); ?></td>
                    <td><?php echo $transfer['from_warehouse_name']; ?> (<?php echo $transfer['from_warehouse_code']; ?>)</td>
                    <td><?php echo $transfer['to_warehouse_name']; ?> (<?php echo $transfer['to_warehouse_code']; ?>)</td>
                    <td>
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM stock_transfer_detail WHERE transfer_id = ?");
                        $stmt->bind_param("i", $transfer['transfer_id']);
                        $stmt->execute();
                        $count = $stmt->get_result()->fetch_assoc()['total'];
                        $stmt->close();
                        echo $count . ' item' . ($count > 1 ? 's' : '');
                        ?>
                    </td>
                    <td><?php echo $transfer['notes'] ?: '-'; ?></td>
                    <td>
                        <button class="btn-sm btn-primary" onclick="viewDetail(<?php echo $transfer['transfer_id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if (hasRole('admin')): ?>
                        <a href="stock_transfer_edit.php?id=<?php echo $transfer['transfer_id']; ?>" class="btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn-sm btn-danger" onclick="deleteTransfer(<?php echo $transfer['transfer_id']; ?>, '<?php echo $transfer['transaction_code']; ?>')">
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
<div id="transferModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2><i class="fas fa-exchange-alt"></i> Transfer Stok</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" action="stock_transfer_process.php" id="transferForm">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Tanggal *</label>
                    <input type="datetime-local" name="transfer_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-warehouse"></i> Dari Warehouse *</label>
                    <select name="from_warehouse_id" id="fromWarehouse" required onchange="loadSourceItems()">
                        <option value="">Pilih Warehouse Asal</option>
                        <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo $wh['warehouse_id']; ?>">
                            <?php echo $wh['warehouse_name']; ?> (<?php echo $wh['warehouse_code']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-warehouse"></i> Ke Warehouse *</label>
                    <select name="to_warehouse_id" id="toWarehouse" required>
                        <option value="">Pilih Warehouse Tujuan</option>
                        <?php foreach ($all_warehouses as $wh): ?>
                        <option value="<?php echo $wh['warehouse_id']; ?>">
                            <?php echo $wh['warehouse_name']; ?> (<?php echo $wh['warehouse_code']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-clipboard"></i> Catatan</label>
                <textarea name="notes" rows="2" placeholder="Catatan transfer (optional)"></textarea>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 2px solid var(--border-color);">

            <h3 style="margin-bottom: 16px; color: var(--primary-color);">
                <i class="fas fa-boxes"></i> Items yang Ditransfer
            </h3>

            <div id="itemsContainer">
                <!-- Items will be added here -->
            </div>

            <button type="button" class="btn btn-secondary" onclick="addItemRow()" id="addItemBtn" disabled>
                <i class="fas fa-plus"></i> Tambah Item
            </button>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Proses Transfer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-file-alt"></i> Detail Transfer</h2>
            <span class="close" onclick="closeDetailModal()">&times;</span>
        </div>
        <div id="detailContent" style="padding: 24px;">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<style>
.item-row {
    margin-bottom: 12px;
    padding: 16px;
    background: var(--bg-primary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.item-row .form-row {
    display: grid;
    grid-template-columns: 2fr 1fr 60px;
    gap: 12px;
    align-items: start;
}

@media (max-width: 768px) {
    .item-row .form-row {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
let itemRowCount = 0;
let availableItems = [];

function showAddModal() {
    document.getElementById('transferModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('transferModal').style.display = 'none';
    document.getElementById('transferForm').reset();
    document.getElementById('itemsContainer').innerHTML = '';
    document.getElementById('addItemBtn').disabled = true;
    itemRowCount = 0;
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

async function loadSourceItems() {
    const fromWarehouseId = document.getElementById('fromWarehouse').value;
    const addBtn = document.getElementById('addItemBtn');

    if (!fromWarehouseId) {
        addBtn.disabled = true;
        document.getElementById('itemsContainer').innerHTML = '';
        return;
    }

    try {
        const response = await fetch(`get_warehouse_items.php?warehouse_id=${fromWarehouseId}`);
        availableItems = await response.json();

        if (availableItems.length === 0) {
            alert('Tidak ada item di warehouse ini');
            addBtn.disabled = true;
        } else {
            addBtn.disabled = false;
            // Clear existing items when warehouse changes
            document.getElementById('itemsContainer').innerHTML = '';
            itemRowCount = 0;
        }
    } catch (error) {
        console.error('Error loading items:', error);
        alert('Error loading items');
        addBtn.disabled = true;
    }
}

function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.setAttribute('data-row', itemRowCount);

    let optionsHTML = '<option value="">Pilih Item</option>';
    availableItems.forEach(item => {
        optionsHTML += `<option value="${item.item_id}" data-stock="${item.current_stock}" data-unit="${item.unit}">
            ${item.item_code} - ${item.item_name} (Stok: ${parseFloat(item.current_stock).toFixed(2)} ${item.unit})
        </option>`;
    });

    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-box"></i> Item</label>
                <select name="items[${itemRowCount}][item_id]" class="form-control item-select" required onchange="updateStock(${itemRowCount})">
                    ${optionsHTML}
                </select>
                <small class="stock-info" style="color: var(--text-secondary); font-size: 11px; display: block; margin-top: 4px;"></small>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-sort-numeric-up"></i> Quantity</label>
                <input type="number" name="items[${itemRowCount}][quantity]" class="form-control item-qty" min="0.01" step="0.01" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>&nbsp;</label>
                <button type="button" class="btn-sm btn-danger" onclick="removeItemRow(this)" style="width: 100%; height: 44px;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;

    container.appendChild(newRow);
    itemRowCount++;
}

function removeItemRow(button) {
    button.closest('.item-row').remove();
}

function updateStock(index) {
    const rows = document.querySelectorAll('.item-row');
    const row = rows[index];
    const select = row.querySelector('.item-select');
    const option = select.options[select.selectedIndex];

    if (option.value) {
        const stock = parseFloat(option.dataset.stock);
        const unit = option.dataset.unit;
        row.querySelector('.stock-info').textContent = `Tersedia: ${stock.toFixed(2)} ${unit}`;
        row.querySelector('.item-qty').max = stock;
    } else {
        row.querySelector('.stock-info').textContent = '';
        row.querySelector('.item-qty').max = '';
    }
}

function viewDetail(id) {
    document.getElementById('detailModal').style.display = 'block';
    document.getElementById('detailContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';

    fetch(`stock_transfer_detail.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('detailContent').innerHTML = '<p style="text-align:center;padding:40px;color:var(--danger-color);">Error loading detail</p>';
        });
}

function deleteTransfer(id, code) {
    if (confirm(`Apakah Anda yakin ingin menghapus transfer ${code}?\n\nPerhatian: Ini akan:\n- Menghapus transaksi transfer\n- Mengembalikan stok ke warehouse asal\n- Mengurangi stok dari warehouse tujuan\n\nTindakan ini tidak dapat dibatalkan!`)) {
        window.location.href = `stock_transfer_delete.php?id=${id}`;
    }
}

// Form validation
document.getElementById('transferForm').addEventListener('submit', function(e) {
    const fromWarehouse = document.getElementById('fromWarehouse').value;
    const toWarehouse = document.getElementById('toWarehouse').value;
    const rows = document.querySelectorAll('.item-row');

    if (fromWarehouse === toWarehouse) {
        e.preventDefault();
        alert('Warehouse asal dan tujuan tidak boleh sama!');
        return false;
    }

    if (rows.length === 0) {
        e.preventDefault();
        alert('Tambahkan minimal 1 item untuk ditransfer!');
        return false;
    }

    // Validate quantities
    let hasError = false;
    rows.forEach(row => {
        const select = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.item-qty');
        const option = select.options[select.selectedIndex];

        if (option.value) {
            const maxStock = parseFloat(option.dataset.stock);
            const qty = parseFloat(qtyInput.value) || 0;

            if (qty > maxStock) {
                hasError = true;
                qtyInput.style.border = '2px solid var(--danger-color)';
            }
        }
    });

    if (hasError) {
        e.preventDefault();
        alert('Quantity melebihi stok yang tersedia!');
        return false;
    }

    if (!confirm('Proses transfer stok ini?')) {
        e.preventDefault();
        return false;
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal1 = document.getElementById('transferModal');
    const modal2 = document.getElementById('detailModal');
    if (event.target == modal1) closeModal();
    else if (event.target == modal2) closeDetailModal();
}
</script>

<?php include 'includes/footer.php'; ?>
