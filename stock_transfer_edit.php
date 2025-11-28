<?php
require_once 'config.php';
requireRole('admin'); // Only admin can edit

$conn = getDBConnection();
$user = getCurrentUser();

$transfer_id = intval($_GET['id'] ?? 0);

if ($transfer_id <= 0) {
    $_SESSION['error_message'] = "ID transfer tidak valid";
    header('Location: stock_transfer.php');
    exit;
}

// Get transfer header
$stmt = $conn->prepare("
    SELECT st.*,
           w_from.warehouse_name as from_warehouse_name, w_from.warehouse_code as from_warehouse_code,
           w_to.warehouse_name as to_warehouse_name, w_to.warehouse_code as to_warehouse_code
    FROM stock_transfers st
    LEFT JOIN warehouses w_from ON st.from_warehouse_id = w_from.warehouse_id
    LEFT JOIN warehouses w_to ON st.to_warehouse_id = w_to.warehouse_id
    WHERE st.transfer_id = ?
");
$stmt->bind_param("i", $transfer_id);
$stmt->execute();
$transfer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transfer) {
    $_SESSION['error_message'] = "Transfer tidak ditemukan";
    header('Location: stock_transfer.php');
    exit;
}

// Get transfer details with current stock in destination warehouse
$stmt = $conn->prepare("
    SELECT std.*, i.item_code, i.item_name, i.unit,
           COALESCE(wi.current_stock, 0) as current_stock_in_dest
    FROM stock_transfer_detail std
    JOIN items i ON std.item_id = i.item_id
    LEFT JOIN warehouse_items wi ON wi.item_id = std.item_id AND wi.warehouse_id = ?
    WHERE std.transfer_id = ?
");
$stmt->bind_param("ii", $transfer['to_warehouse_id'], $transfer_id);
$stmt->execute();
$details = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $details[] = $row;
}
$stmt->close();

// Get warehouses
$warehouses = [];
$result = $conn->query("SELECT warehouse_id, warehouse_code, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name");
while ($row = $result->fetch_assoc()) {
    $warehouses[] = $row;
}

// Get items
$items = [];
$result = $conn->query("SELECT item_id, item_code, item_name, unit FROM items ORDER BY item_code");
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$page_title = 'Edit Transfer Stok';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Transfer Stok</h1>
        <a href="stock_transfer.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Perhatian!</strong> Edit transfer akan otomatis recalculate stok di kedua warehouse. Pastikan data yang diinput benar.
    </div>

    <?php
    // Check if any item has insufficient stock in destination warehouse
    $has_insufficient_stock = false;
    $insufficient_details = [];
    foreach ($details as $detail) {
        if ($detail['current_stock_in_dest'] < $detail['quantity']) {
            $has_insufficient_stock = true;
            $insufficient_details[] = $detail;
        }
    }

    if ($has_insufficient_stock):
    ?>
    <div class="alert alert-danger">
        <i class="fas fa-ban"></i>
        <strong>PERINGATAN!</strong> Beberapa item tidak dapat di-edit/revert karena stok di warehouse tujuan tidak mencukupi.
        <br><br>
        <strong>Item yang bermasalah:</strong>
        <ul style="margin-top: 10px; margin-bottom: 0;">
            <?php foreach ($insufficient_details as $detail): ?>
            <li>
                <strong><?php echo $detail['item_code']; ?> - <?php echo $detail['item_name']; ?></strong>
                <br>
                Transfer: <?php echo number_format($detail['quantity'], 2); ?> <?php echo $detail['unit']; ?>
                | Tersedia: <?php echo number_format($detail['current_stock_in_dest'], 2); ?> <?php echo $detail['unit']; ?>
                | <span style="color: #dc2626;">Kekurangan: <?php echo number_format($detail['quantity'] - $detail['current_stock_in_dest'], 2); ?> <?php echo $detail['unit']; ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <br>
        <strong>Solusi:</strong> Batalkan distribusi/transaksi keluar dari warehouse tujuan terlebih dahulu, atau gunakan Stock Adjustment untuk memperbaiki stok.
    </div>
    <?php endif; ?>

    <form method="POST" action="stock_transfer_update.php">
        <input type="hidden" name="transfer_id" value="<?php echo $transfer_id; ?>">

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-barcode"></i> Kode Transaksi</label>
                <input type="text" value="<?php echo $transfer['transaction_code']; ?>" readonly style="background: var(--bg-primary);">
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Tanggal Transfer *</label>
                <input type="datetime-local" name="transfer_date" required value="<?php echo date('Y-m-d\TH:i', strtotime($transfer['transfer_date'])); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-warehouse"></i> Dari Warehouse *</label>
                <select name="from_warehouse_id" id="fromWarehouse" required>
                    <?php foreach ($warehouses as $wh): ?>
                    <option value="<?php echo $wh['warehouse_id']; ?>" <?php echo $wh['warehouse_id'] == $transfer['from_warehouse_id'] ? 'selected' : ''; ?>>
                        <?php echo $wh['warehouse_name']; ?> (<?php echo $wh['warehouse_code']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-warehouse"></i> Ke Warehouse *</label>
                <select name="to_warehouse_id" id="toWarehouse" required>
                    <?php foreach ($warehouses as $wh): ?>
                    <option value="<?php echo $wh['warehouse_id']; ?>" <?php echo $wh['warehouse_id'] == $transfer['to_warehouse_id'] ? 'selected' : ''; ?>>
                        <?php echo $wh['warehouse_name']; ?> (<?php echo $wh['warehouse_code']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label><i class="fas fa-clipboard"></i> Catatan</label>
            <textarea name="notes" rows="2"><?php echo $transfer['notes']; ?></textarea>
        </div>

        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">

        <h3 style="margin-bottom: 16px;"><i class="fas fa-boxes"></i> Detail Items</h3>

        <div id="itemsContainer">
            <?php foreach ($details as $index => $detail): ?>
            <div class="item-row" style="margin-bottom: 12px;">
                <div class="form-row" style="grid-template-columns: 2fr 1fr 60px; gap: 12px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="items[<?php echo $index; ?>][item_id]" class="item-select" required>
                            <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['item_id']; ?>" <?php echo $item['item_id'] == $detail['item_id'] ? 'selected' : ''; ?>>
                                <?php echo $item['item_code']; ?> - <?php echo $item['item_name']; ?> (<?php echo $item['unit']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="number" name="items[<?php echo $index; ?>][quantity]" class="item-qty" value="<?php echo $detail['quantity']; ?>" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="button" class="btn-sm btn-danger" onclick="removeItemRow(this)" style="width: 100%; height: 44px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn btn-secondary" onclick="addItemRow()" style="margin-top: 12px;">
            <i class="fas fa-plus"></i> Tambah Item
        </button>

        <div class="form-actions" style="margin-top: 24px;">
            <a href="stock_transfer.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary" onclick="return validateForm()">
                <i class="fas fa-save"></i> Update Transfer
            </button>
        </div>
    </form>
</div>

<script>
let itemRowCount = <?php echo count($details); ?>;
const itemsData = <?php echo json_encode($items); ?>;

function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.querySelector('.item-row').cloneNode(true);

    newRow.querySelectorAll('input, select').forEach(input => {
        if (input.name) {
            input.name = input.name.replace(/\[\d+\]/, `[${itemRowCount}]`);
        }
        if (input.classList.contains('item-qty')) {
            input.value = '';
        }
    });

    container.appendChild(newRow);
    itemRowCount++;
}

function removeItemRow(button) {
    const container = document.getElementById('itemsContainer');
    if (container.children.length > 1) {
        button.closest('.item-row').remove();
    } else {
        alert('Minimal harus ada 1 item!');
    }
}

function validateForm() {
    const fromWarehouse = document.getElementById('fromWarehouse').value;
    const toWarehouse = document.getElementById('toWarehouse').value;
    const rows = document.querySelectorAll('.item-row');

    if (fromWarehouse === toWarehouse) {
        alert('Warehouse asal dan tujuan tidak boleh sama!');
        return false;
    }

    if (rows.length === 0) {
        alert('Minimal harus ada 1 item!');
        return false;
    }

    return confirm('Yakin update transfer ini? Stok di kedua warehouse akan di-recalculate.');
}
</script>

<?php include 'includes/footer.php'; ?>
