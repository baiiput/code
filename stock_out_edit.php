<?php
require_once 'config.php';
requireRole('admin');

$conn = getDBConnection();
$user = getCurrentUser();

// Get transaction ID
$stock_out_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$stock_out_id) {
    header("Location: stock_out.php");
    exit();
}

// Get stock out data with warehouse
$query = "SELECT so.*, b.branch_name, w.warehouse_name, w.warehouse_code
          FROM stock_out so
          LEFT JOIN branches b ON so.branch_id = b.branch_id
          LEFT JOIN warehouses w ON so.warehouse_id = w.warehouse_id
          WHERE so.stock_out_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $stock_out_id);
$stmt->execute();
$result = $stmt->get_result();
$stock_out = $result->fetch_assoc();

if (!$stock_out) {
    header("Location: stock_out.php");
    exit();
}

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

// Get stock out details with warehouse_items stock
$query = "SELECT sod.*, i.item_code, i.item_name, i.unit,
                 COALESCE(wi.current_stock, 0) as current_stock,
                 COALESCE(wi.average_cost, 0) as average_cost
          FROM stock_out_detail sod
          LEFT JOIN items i ON sod.item_id = i.item_id
          LEFT JOIN warehouse_items wi ON sod.item_id = wi.item_id AND wi.warehouse_id = ?
          WHERE sod.stock_out_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $stock_out['warehouse_id'], $stock_out_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all branches for dropdown
$branches = $conn->query("SELECT * FROM branches ORDER BY branch_name");

// Get all items from the selected warehouse for dropdown
$items_query = "SELECT i.item_id, i.item_code, i.item_name, i.unit,
                       COALESCE(wi.current_stock, 0) as current_stock,
                       COALESCE(wi.average_cost, 0) as average_cost
                FROM items i
                LEFT JOIN warehouse_items wi ON i.item_id = wi.item_id AND wi.warehouse_id = ?
                WHERE wi.current_stock > 0
                ORDER BY i.item_name";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $stock_out['warehouse_id']);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

$page_title = 'Edit Stock Out';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-arrow-up"></i> Edit Distribusi Stok</h1>
        <a href="stock_out.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card">
        <form id="stockOutForm" method="POST" action="stock_out_update.php">
            <input type="hidden" name="stock_out_id" value="<?php echo $stock_out_id; ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-barcode"></i> Kode Transaksi</label>
                    <input type="text" class="form-control" value="<?php echo $stock_out['transaction_code']; ?>" readonly 
                           style="background: var(--bg-primary); font-weight: 600;">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Tanggal <span class="required">*</span></label>
                    <input type="datetime-local" name="transaction_date" class="form-control" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($stock_out['transaction_date'])); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-warehouse"></i> Warehouse Asal <span class="required">*</span></label>
                    <select name="warehouse_id" class="form-control" required>
                        <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo $wh['warehouse_id']; ?>"
                                <?php echo ($wh['warehouse_id'] == $stock_out['warehouse_id']) ? 'selected' : ''; ?>>
                            <?php echo $wh['warehouse_name']; ?> (<?php echo $wh['warehouse_code']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-building"></i> Cabang Tujuan <span class="required">*</span></label>
                    <select name="branch_id" class="form-control" required>
                        <option value="">Pilih Cabang</option>
                        <?php while ($branch = $branches->fetch_assoc()): ?>
                        <option value="<?php echo $branch['branch_id']; ?>"
                                <?php echo ($branch['branch_id'] == $stock_out['branch_id']) ? 'selected' : ''; ?>>
                            <?php echo $branch['branch_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-clipboard"></i> Catatan</label>
                    <input type="text" name="notes" class="form-control"
                           value="<?php echo htmlspecialchars($stock_out['notes']); ?>"
                           placeholder="Catatan distribusi (optional)">
                </div>
            </div>

            <hr style="margin: 24px 0; border: none; border-top: 2px solid var(--border-color);">

            <h3 style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: var(--primary-color);">
                <i class="fas fa-boxes"></i> Detail Barang
            </h3>

            <div id="itemsContainer">
                <?php foreach ($details as $index => $detail): ?>
                <div class="item-row" data-row="<?php echo $index; ?>">
                    <div class="form-row" style="grid-template-columns: 2fr 1fr 1fr 1fr 60px; align-items: start; gap: 12px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-box"></i> Barang</label>
                            <select name="items[<?php echo $index; ?>][item_id]" class="form-control item-select" required onchange="updateItemInfo(this, <?php echo $index; ?>)">
                                <option value="">Pilih Barang</option>
                                <?php 
                                mysqli_data_seek($items, 0);
                                while ($item = $items->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $item['item_id']; ?>" 
                                        data-price="<?php echo $item['average_cost']; ?>"
                                        data-stock="<?php echo $item['current_stock']; ?>"
                                        data-unit="<?php echo $item['unit']; ?>"
                                        <?php echo ($item['item_id'] == $detail['item_id']) ? 'selected' : ''; ?>>
                                    <?php echo $item['item_code'] . ' - ' . $item['item_name']; ?> (Stok: <?php echo formatNumber($item['current_stock'], 0); ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="stock-info" style="color: var(--text-secondary); font-size: 11px; display: block; margin-top: 4px;">
                                Tersedia: <?php echo formatNumber($detail['current_stock'], 0); ?> <?php echo $detail['unit']; ?>
                            </small>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-sort-numeric-up"></i> Qty</label>
                            <input type="number" name="items[<?php echo $index; ?>][quantity]" 
                                   class="form-control item-qty" 
                                   value="<?php echo $detail['quantity']; ?>" 
                                   min="1" step="1" 
                                   max="<?php echo $detail['current_stock']; ?>"
                                   required 
                                   onchange="calculateRowTotal(<?php echo $index; ?>)">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-money-bill"></i> Harga (Avg)</label>
                            <input type="text" class="form-control item-price-display" 
                                   value="<?php echo formatRupiah($detail['unit_price']); ?>" 
                                   readonly style="background: var(--bg-primary);">
                            <input type="hidden" name="items[<?php echo $index; ?>][price]" 
                                   class="item-price" 
                                   value="<?php echo $detail['unit_price']; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-calculator"></i> Subtotal</label>
                            <input type="text" class="form-control item-subtotal" 
                                   value="<?php echo formatRupiah($detail['subtotal']); ?>" 
                                   readonly style="background: var(--bg-primary); font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>&nbsp;</label>
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

            <div style="margin-top: 24px; padding: 20px; background: var(--success-light); border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                <strong style="font-size: 18px;">TOTAL:</strong>
                <strong id="grandTotal" style="font-size: 24px; color: var(--success-color);">
                    <?php echo formatRupiah($stock_out['total_amount']); ?>
                </strong>
            </div>

            <div class="form-actions" style="margin-top: 24px;">
                <button type="button" class="btn btn-secondary" onclick="confirmCancel()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Distribusi
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.card {
    background: white;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.item-row {
    margin-bottom: 12px;
    padding: 16px;
    background: var(--bg-primary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.required {
    color: var(--danger-color);
}

.form-actions {
    border-top: 1px solid var(--border-color);
    padding-top: 20px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .item-row .form-row {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
let itemRowCount = <?php echo count($details); ?>;
const itemsData = <?php echo json_encode($items->fetch_all(MYSQLI_ASSOC)); ?>;

function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.setAttribute('data-row', itemRowCount);
    
    newRow.innerHTML = `
        <div class="form-row" style="grid-template-columns: 2fr 1fr 1fr 1fr 60px; align-items: start; gap: 12px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-box"></i> Barang</label>
                <select name="items[${itemRowCount}][item_id]" class="form-control item-select" required onchange="updateItemInfo(this, ${itemRowCount})">
                    <option value="">Pilih Barang</option>
                    <?php 
                    mysqli_data_seek($items, 0);
                    while ($item = $items->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $item['item_id']; ?>" 
                            data-price="<?php echo $item['average_cost']; ?>"
                            data-stock="<?php echo $item['current_stock']; ?>"
                            data-unit="<?php echo $item['unit']; ?>">
                        <?php echo $item['item_code'] . ' - ' . $item['item_name']; ?> (Stok: <?php echo formatNumber($item['current_stock'], 0); ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
                <small class="stock-info" style="color: var(--text-secondary); font-size: 11px; display: block; margin-top: 4px;"></small>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-sort-numeric-up"></i> Qty</label>
                <input type="number" name="items[${itemRowCount}][quantity]" 
                       class="form-control item-qty" 
                       min="1" step="1" required 
                       onchange="calculateRowTotal(${itemRowCount})">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-money-bill"></i> Harga (Avg)</label>
                <input type="text" class="form-control item-price-display" readonly style="background: var(--bg-primary);">
                <input type="hidden" name="items[${itemRowCount}][price]" class="item-price">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><i class="fas fa-calculator"></i> Subtotal</label>
                <input type="text" class="form-control item-subtotal" readonly style="background: var(--bg-primary); font-weight: 600;">
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
    const container = document.getElementById('itemsContainer');
    if (container.children.length > 1) {
        button.closest('.item-row').remove();
        calculateGrandTotal();
    } else {
        alert('Minimal harus ada 1 barang!');
    }
}

function updateItemInfo(select, index) {
    const rows = document.querySelectorAll('.item-row');
    const row = rows[index];
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const stock = parseFloat(option.dataset.stock);
        const price = parseFloat(option.dataset.price);
        const unit = option.dataset.unit;
        
        row.querySelector('.stock-info').textContent = `Tersedia: ${stock} ${unit}`;
        row.querySelector('.item-price').value = price;
        row.querySelector('.item-price-display').value = formatRupiah(price);
        row.querySelector('.item-qty').max = stock;
        row.querySelector('.item-qty').value = '';
        row.querySelector('.item-subtotal').value = '';
        
        calculateGrandTotal();
    } else {
        row.querySelector('.stock-info').textContent = '';
        row.querySelector('.item-price').value = '';
        row.querySelector('.item-price-display').value = '';
        row.querySelector('.item-subtotal').value = '';
    }
}

function calculateRowTotal(index) {
    const rows = document.querySelectorAll('.item-row');
    const row = rows[index];
    
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const maxStock = parseFloat(row.querySelector('.item-qty').max) || 0;
    
    if (qty > maxStock) {
        alert(`Jumlah melebihi stok tersedia (${maxStock})!`);
        row.querySelector('.item-qty').value = maxStock;
        calculateRowTotal(index);
        return;
    }
    
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

function confirmCancel() {
    if (confirm('Batalkan perubahan? Data yang sudah diinput akan hilang.')) {
        window.location.href = 'stock_out.php';
    }
}

// Form validation
document.getElementById('stockOutForm').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('.item-row');
    
    if (rows.length === 0) {
        e.preventDefault();
        alert('Tambahkan minimal 1 item!');
        return false;
    }
    
    // Check stock availability
    let hasError = false;
    rows.forEach(row => {
        const maxStock = parseFloat(row.querySelector('.item-qty').max) || 0;
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        
        if (qty > maxStock) {
            hasError = true;
            row.querySelector('.item-qty').style.border = '2px solid var(--danger-color)';
        }
    });
    
    if (hasError) {
        e.preventDefault();
        alert('Qty melebihi stok yang tersedia!');
        return false;
    }
    
    // Check for duplicate items
    const items = [];
    let hasDuplicate = false;
    
    rows.forEach(row => {
        const itemId = row.querySelector('.item-select').value;
        if (itemId && items.includes(itemId)) {
            hasDuplicate = true;
        }
        items.push(itemId);
    });
    
    if (hasDuplicate) {
        e.preventDefault();
        alert('Ada barang yang duplikat! Setiap barang hanya boleh diinput sekali.');
        return false;
    }
    
    if (!confirm('Update Distribusi Stok ini?')) {
        e.preventDefault();
        return false;
    }
});

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    calculateGrandTotal();
});
</script>

<?php include 'includes/footer.php'; ?>
