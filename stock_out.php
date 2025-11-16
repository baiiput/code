<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();

// Get branches and items for dropdowns
$branches = [];
$result = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

$items = [];
$result = $conn->query("SELECT item_id, item_code, item_name, unit, current_stock, average_cost FROM items WHERE current_stock > 0 ORDER BY item_code");
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Get stock out transactions with item details
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$query = "
    SELECT so.*, b.branch_name, u.full_name as created_by_name
    FROM stock_out so
    LEFT JOIN branches b ON so.branch_id = b.branch_id
    LEFT JOIN users u ON so.created_by = u.user_id
    WHERE 1=1
";

if ($search) {
    $query .= " AND (so.transaction_code LIKE '%$search%' OR b.branch_name LIKE '%$search%')";
}
if ($date_from) {
    $query .= " AND DATE(so.transaction_date) >= '$date_from'";
}
if ($date_to) {
    $query .= " AND DATE(so.transaction_date) <= '$date_to'";
}

$query .= " ORDER BY so.transaction_date DESC, so.stock_out_id DESC LIMIT 50";

$transactions = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    // Get items for this transaction
    $items_query = "SELECT i.item_name, sod.quantity, i.unit
                   FROM stock_out_detail sod
                   JOIN items i ON sod.item_id = i.item_id
                   WHERE sod.stock_out_id = " . $row['stock_out_id'];
    $items_result = $conn->query($items_query);
    $items_list = [];
    while ($item = $items_result->fetch_assoc()) {
        $items_list[] = formatNumber($item['quantity'], 0) . ' ' . $item['unit'] . ' ' . $item['item_name'];
    }
    $row['items'] = implode(', ', $items_list);
    $transactions[] = $row;
}

$page_title = 'Stok Keluar';
include 'includes/header.php';

// Get messages
$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-arrow-up"></i> Distribusi Stok Keluar</h1>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Tambah Distribusi
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
                <input type="text" name="search" placeholder="Cari kode transaksi atau cabang..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>" placeholder="Dari Tanggal" style="width: auto;">
            <input type="date" name="date_to" value="<?php echo $date_to; ?>" placeholder="Sampai Tanggal" style="width: auto;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if ($search || $date_from || $date_to): ?>
            <a href="stock_out.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="12%">Kode Transaksi</th>
                    <th width="13%">Tanggal</th>
                    <th width="15%">Cabang</th>
                    <th>Barang</th>
                    <th width="12%" class="text-right">Total</th>
                    <th width="10%">Dibuat Oleh</th>
                    <th width="<?php echo hasRole('admin') ? '18%' : '10%'; ?>">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr><td colspan="7" class="text-center">Belum ada distribusi stok keluar</td></tr>
                <?php else: ?>
                <?php foreach ($transactions as $trans): ?>
                <tr>
                    <td><strong><?php echo $trans['transaction_code']; ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($trans['transaction_date'])); ?></td>
                    <td><?php echo $trans['branch_name']; ?></td>
                    <td><small><?php echo $trans['items']; ?></small></td>
                    <td class="text-right"><strong><?php echo formatRupiah($trans['total_amount']); ?></strong></td>
                    <td><?php echo $trans['created_by_name']; ?></td>
                    <td>
                        <button class="btn-sm btn-primary" onclick="viewDetail(<?php echo $trans['stock_out_id']; ?>)">
                            <i class="fas fa-eye"></i> Detail
                        </button>
                        <?php if (hasRole('admin')): ?>
                        <a href="stock_out_edit.php?id=<?php echo $trans['stock_out_id']; ?>" class="btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button class="btn-sm btn-danger" onclick="deleteTransaction(<?php echo $trans['stock_out_id']; ?>, '<?php echo $trans['transaction_code']; ?>')">
                            <i class="fas fa-trash"></i> Hapus
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

<!-- Add Transaction Modal -->
<div id="transactionModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2><i class="fas fa-arrow-up"></i> Tambah Distribusi Stok</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" action="stock_out_process.php" id="transactionForm">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Tanggal Distribusi *</label>
                    <input type="datetime-local" name="transaction_date" id="transactionDate" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Cabang Tujuan *</label>
                    <select name="branch_id" id="branchId" required>
                        <option value="">Pilih Cabang</option>
                        <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['branch_id']; ?>"><?php echo $branch['branch_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-clipboard"></i> Catatan</label>
                <textarea name="notes" rows="2" placeholder="Catatan distribusi (optional)"></textarea>
            </div>
            
            <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border-color);">
            
            <h3 style="margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-boxes"></i> Detail Barang
            </h3>
            
            <div id="itemsContainer">
                <div class="item-row">
                    <div class="form-row" style="grid-template-columns: 2fr 1fr 1fr 1fr 60px; align-items: start; gap: 12px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-box"></i> Barang</label>
                            <select name="items[0][item_id]" class="item-select" required onchange="updateItemInfo(this, 0)">
                                <option value="">Pilih Barang</option>
                                <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['item_id']; ?>" 
                                    data-stock="<?php echo $item['current_stock']; ?>"
                                    data-unit="<?php echo $item['unit']; ?>"
                                    data-price="<?php echo $item['average_cost']; ?>">
                                    <?php echo $item['item_code']; ?> - <?php echo $item['item_name']; ?> (Stok: <?php echo formatNumber($item['current_stock'], 0); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-sort-numeric-up"></i> Jumlah</label>
                            <input type="number" name="items[0][quantity]" class="item-qty" step="1" min="1" required onchange="calculateRowTotal(0)">
                            <small class="stock-info" style="color: var(--text-secondary); font-size: 11px; display: block; margin-top: 4px;"></small>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-money-bill"></i> Harga (Avg)</label>
                            <input type="text" class="item-price-display" readonly style="background: var(--bg-primary);">
                            <input type="hidden" name="items[0][unit_price]" class="item-price">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-calculator"></i> Subtotal</label>
                            <input type="text" class="item-subtotal" readonly style="background: var(--bg-primary); font-weight: 600;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>&nbsp;</label>
                            <button type="button" class="btn-sm btn-danger" onclick="removeItemRow(this)" style="width: 100%; height: 44px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn btn-secondary" onclick="addItemRow()" style="margin-top: 12px;">
                <i class="fas fa-plus"></i> Tambah Barang
            </button>
            
            <div style="margin-top: 24px; padding: 20px; background: var(--success-light); border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                <strong style="font-size: 18px;">TOTAL:</strong>
                <strong id="grandTotal" style="font-size: 24px; color: var(--success-color);">Rp 0</strong>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Kirim Distribusi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2><i class="fas fa-file-invoice"></i> Detail Distribusi</h2>
            <span class="close" onclick="closeDetailModal()">&times;</span>
        </div>
        <div id="detailContent" style="padding: 24px;">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
let itemRowCount = 1;
const itemsData = <?php echo json_encode($items); ?>;

function showAddModal() {
    document.getElementById('transactionModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('transactionModal').style.display = 'none';
    document.getElementById('transactionForm').reset();
    document.getElementById('itemsContainer').innerHTML = document.querySelector('.item-row').outerHTML;
    itemRowCount = 1;
    calculateGrandTotal();
}

function closeDetailModal() {
    document.getElementById('detailModal').style.display = 'none';
}

function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.querySelector('.item-row').cloneNode(true);
    
    const inputs = newRow.querySelectorAll('select, input');
    inputs.forEach(input => {
        if (input.name) {
            input.name = input.name.replace('[0]', `[${itemRowCount}]`);
        }
        if (input.type !== 'button') {
            input.value = '';
        }
    });
    
    newRow.querySelector('.item-select').setAttribute('onchange', `updateItemInfo(this, ${itemRowCount})`);
    newRow.querySelector('.item-qty').setAttribute('onchange', `calculateRowTotal(${itemRowCount})`);
    
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
        
        calculateRowTotal(index);
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

function viewDetail(id) {
    document.getElementById('detailModal').style.display = 'block';
    document.getElementById('detailContent').innerHTML = '<p style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    
    fetch(`stock_out_detail.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('detailContent').innerHTML = '<p style="text-align:center;padding:40px;color:var(--danger-color);">Error loading detail</p>';
        });
}

function deleteTransaction(id, code) {
    if (confirm(`Apakah Anda yakin ingin menghapus transaksi ${code}?\n\nPerhatian: Ini akan:\n- Menghapus transaksi distribusi stok keluar\n- Mengembalikan stok barang\n- Menghitung ulang keuangan secara otomatis\n\nTindakan ini tidak dapat dibatalkan!`)) {
        window.location.href = `stock_out_delete.php?id=${id}`;
    }
}

window.onclick = function(event) {
    const modal1 = document.getElementById('transactionModal');
    const modal2 = document.getElementById('detailModal');
    if (event.target == modal1) closeModal();
    else if (event.target == modal2) closeDetailModal();
}
</script>

<?php include 'includes/footer.php'; ?>
