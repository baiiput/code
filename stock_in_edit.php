<?php
require_once 'config.php';
requireRole('admin'); // Only admin can edit

$conn = getDBConnection();
$user = getCurrentUser();

$stock_in_id = intval($_GET['id'] ?? 0);

if ($stock_in_id <= 0) {
    $_SESSION['error_message'] = "ID transaksi tidak valid";
    header('Location: stock_in.php');
    exit;
}

// Get transaction header
$stmt = $conn->prepare("
    SELECT si.*, s.supplier_name
    FROM stock_in si
    LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
    WHERE si.stock_in_id = ?
");
$stmt->bind_param("i", $stock_in_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) {
    $_SESSION['error_message'] = "Transaksi tidak ditemukan";
    header('Location: stock_in.php');
    exit;
}

// Get transaction details
$stmt = $conn->prepare("
    SELECT sid.*, i.item_code, i.item_name, i.unit
    FROM stock_in_detail sid
    JOIN items i ON sid.item_id = i.item_id
    WHERE sid.stock_in_id = ?
");
$stmt->bind_param("i", $stock_in_id);
$stmt->execute();
$details = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $details[] = $row;
}
$stmt->close();

// Get suppliers
$suppliers = [];
$result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row;
}

// Get items
$items = [];
$result = $conn->query("SELECT item_id, item_code, item_name, unit, current_stock, average_cost FROM items ORDER BY item_code");
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$page_title = 'Edit Stok Masuk';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Transaksi Stok Masuk</h1>
        <a href="stock_in.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Perhatian!</strong> Edit transaksi akan otomatis recalculate stok dan keuangan. Pastikan data yang diinput benar.
    </div>
    
    <form method="POST" action="stock_in_update.php">
        <input type="hidden" name="stock_in_id" value="<?php echo $stock_in_id; ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-barcode"></i> Kode Transaksi</label>
                <input type="text" value="<?php echo $transaction['transaction_code']; ?>" readonly style="background: var(--bg-primary);">
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Tanggal Transaksi *</label>
                <input type="datetime-local" name="transaction_date" required value="<?php echo date('Y-m-d\TH:i', strtotime($transaction['transaction_date'])); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-truck"></i> Supplier *</label>
            <select name="supplier_id" required>
                <?php foreach ($suppliers as $sup): ?>
                <option value="<?php echo $sup['supplier_id']; ?>" <?php echo $sup['supplier_id'] == $transaction['supplier_id'] ? 'selected' : ''; ?>>
                    <?php echo $sup['supplier_name']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-clipboard"></i> Catatan</label>
            <textarea name="notes" rows="2"><?php echo $transaction['notes']; ?></textarea>
        </div>
        
        <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">
        
        <h3 style="margin-bottom: 16px;"><i class="fas fa-boxes"></i> Detail Barang</h3>
        
        <div id="itemsContainer">
            <?php foreach ($details as $index => $detail): ?>
            <div class="item-row" style="margin-bottom: 12px;">
                <div class="form-row" style="grid-template-columns: 2fr 1fr 1fr 1fr 60px; gap: 12px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <select name="items[<?php echo $index; ?>][item_id]" class="item-select" required>
                            <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['item_id']; ?>" <?php echo $item['item_id'] == $detail['item_id'] ? 'selected' : ''; ?>>
                                <?php echo $item['item_code']; ?> - <?php echo $item['item_name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="number" name="items[<?php echo $index; ?>][quantity]" class="item-qty" value="<?php echo $detail['quantity']; ?>" step="1" min="1" required onchange="calculateRowTotal(<?php echo $index; ?>)">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="number" name="items[<?php echo $index; ?>][unit_price]" class="item-price" value="<?php echo $detail['unit_price']; ?>" step="1" min="0" required onchange="calculateRowTotal(<?php echo $index; ?>)">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <input type="text" class="item-subtotal" value="<?php echo formatRupiah($detail['subtotal']); ?>" readonly style="background: var(--bg-primary); font-weight: 600;">
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
            <i class="fas fa-plus"></i> Tambah Barang
        </button>
        
        <div style="margin-top: 24px; padding: 20px; background: var(--primary-light); border-radius: 12px; display: flex; justify-content: space-between;">
            <strong style="font-size: 18px;">TOTAL:</strong>
            <strong id="grandTotal" style="font-size: 24px; color: var(--primary-color);"><?php echo formatRupiah($transaction['total_amount']); ?></strong>
        </div>
        
        <div class="form-actions" style="margin-top: 24px;">
            <a href="stock_in.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Yakin update transaksi ini? Stok dan keuangan akan di-recalculate.')">
                <i class="fas fa-save"></i> Update Transaksi
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
        if (input.classList.contains('item-qty') || input.classList.contains('item-price')) {
            input.value = '';
        }
        if (input.classList.contains('item-subtotal')) {
            input.value = 'Rp 0';
        }
    });
    
    const qtyInput = newRow.querySelector('.item-qty');
    const priceInput = newRow.querySelector('.item-price');
    qtyInput.setAttribute('onchange', `calculateRowTotal(${itemRowCount})`);
    priceInput.setAttribute('onchange', `calculateRowTotal(${itemRowCount})`);
    
    container.appendChild(newRow);
    itemRowCount++;
}

function removeItemRow(button) {
    const container = document.getElementById('itemsContainer');
    if (container.children.length > 1) {
        button.closest('.item-row').remove();
        calculateGrandTotal();
    } else {
        alert('Minimal harus ada 1 barang!');
    }
}

function calculateRowTotal(index) {
    const rows = document.querySelectorAll('.item-row');
    const row = rows[index];
    
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const subtotal = qty * price;
    
    row.querySelector('.item-subtotal').value = formatRupiah(subtotal);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        total += qty * price;
    });
    
    document.getElementById('grandTotal').textContent = formatRupiah(total);
}

function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(amount));
}
</script>

<?php include 'includes/footer.php'; ?>
