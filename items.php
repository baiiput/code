<?php
require_once 'config.php';
requireRole(['admin', 'manager', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $item_code = clean($_POST['item_code']);
        $item_name = clean($_POST['item_name']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $unit = clean($_POST['unit']);
        $min_stock = floatval($_POST['min_stock']);
        $description = clean($_POST['description']);
        
        if (empty($item_code) || empty($item_name) || empty($unit)) {
            $error = 'Kode barang, nama barang, dan satuan harus diisi';
        } else {
            if ($action === 'add') {
                // Check duplicate code
                $stmt = $conn->prepare("SELECT item_id FROM items WHERE item_code = ?");
                $stmt->bind_param("s", $item_code);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Kode barang sudah digunakan';
                } else {
                    $stmt = $conn->prepare("INSERT INTO items (item_code, item_name, category_id, unit, min_stock, description) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssisss", $item_code, $item_name, $category_id, $unit, $min_stock, $description);
                    if ($stmt->execute()) {
                        $success = 'Barang berhasil ditambahkan';
                    } else {
                        $error = 'Gagal menambahkan barang';
                    }
                }
                $stmt->close();
            } else {
                $item_id = intval($_POST['item_id']);
                $stmt = $conn->prepare("UPDATE items SET item_name = ?, category_id = ?, unit = ?, min_stock = ?, description = ? WHERE item_id = ?");
                $stmt->bind_param("sisssi", $item_name, $category_id, $unit, $min_stock, $description, $item_id);
                if ($stmt->execute()) {
                    $success = 'Barang berhasil diupdate';
                } else {
                    $error = 'Gagal mengupdate barang';
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $item_id = intval($_POST['item_id']);
        
        // Check if item has transactions
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM stock_in_detail WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            $error = 'Barang tidak dapat dihapus karena sudah memiliki transaksi';
        } else {
            $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute()) {
                $success = 'Barang berhasil dihapus';
            } else {
                $error = 'Gagal menghapus barang';
            }
        }
        $stmt->close();
    }
}

// Get categories for dropdown
$categories = [];
$result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get items list with category and total stock from all warehouses
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "
    SELECT i.*, c.category_name,
           COALESCE(SUM(wi.current_stock), 0) as total_stock,
           COALESCE(AVG(wi.average_cost), 0) as avg_cost,
           COUNT(DISTINCT wi.warehouse_id) as warehouse_count
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    LEFT JOIN warehouse_items wi ON i.item_id = wi.item_id
    WHERE 1=1
";

if (!empty($search)) {
    $query .= " AND (i.item_code LIKE '%$search%' OR i.item_name LIKE '%$search%')";
}

if (!empty($category_filter)) {
    $query .= " AND i.category_id = " . intval($category_filter);
}

$query .= " GROUP BY i.item_id ORDER BY i.item_code ASC";

$items = [];
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$page_title = 'Data Barang';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1>📦 Data Barang</h1>
        <?php if (hasRole('admin')): ?>
        <button class="btn btn-primary" onclick="showAddModal()">+ Tambah Barang</button>
        <?php endif; ?>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Cari kode atau nama barang..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="category">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                    <?php echo $cat['category_name']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="items.php" class="btn btn-secondary">Reset</a>
        </form>
    </div>
    
    <!-- Items Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th class="text-right">Stok</th>
                    <th class="text-center">Satuan</th>
                    <th class="text-right">Min. Stok</th>
                    <th class="text-right">Harga Avg</th>
                    <th class="text-center">Status</th>
                    <?php if (hasRole('admin')): ?>
                    <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="9" class="text-center">Belum ada data barang</td>
                </tr>
                <?php else: ?>
                <?php foreach ($items as $item):
                    $status = getStockStatus($item['total_stock'], $item['min_stock']);
                ?>
                <tr>
                    <td><strong><?php echo $item['item_code']; ?></strong></td>
                    <td><?php echo $item['item_name']; ?></td>
                    <td><?php echo $item['category_name'] ?? '-'; ?></td>
                    <td class="text-right">
                        <strong><?php echo number_format($item['total_stock'], 0); ?></strong>
                        <?php if ($item['warehouse_count'] > 0): ?>
                        <button class="btn-xs btn-info" onclick="showStockDetail(<?php echo $item['item_id']; ?>, '<?php echo addslashes($item['item_name']); ?>')" style="margin-left: 4px;">
                            <i class="fas fa-info-circle"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo $item['unit']; ?></td>
                    <td class="text-right"><?php echo formatNumber($item['min_stock'], 0); ?></td>
                    <td class="text-right"><?php echo formatRupiah($item['avg_cost']); ?></td>
                    <td class="text-center">
                        <span class="badge badge-<?php echo $status['class']; ?>">
                            <?php echo $status['status']; ?>
                        </span>
                    </td>
                    <?php if (hasRole('admin')): ?>
                    <td class="text-center">
                        <button class="btn-sm btn-warning" onclick='editItem(<?php echo json_encode($item); ?>)'>Edit</button>
                        <button class="btn-sm btn-danger" onclick="deleteItem(<?php echo $item['item_id']; ?>, '<?php echo $item['item_code']; ?>')">Hapus</button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="itemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Tambah Barang</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="itemForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="item_id" id="itemId">
            
            <div class="form-group">
                <label>Kode Barang *</label>
                <input type="text" name="item_code" id="itemCode" required>
            </div>
            
            <div class="form-group">
                <label>Nama Barang *</label>
                <input type="text" name="item_name" id="itemName" required>
            </div>
            
            <div class="form-group">
                <label>Kategori</label>
                <select name="category_id" id="categoryId">
                    <option value="">Pilih Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Satuan *</label>
                    <input type="text" name="unit" id="unit" placeholder="pcs, kg, liter, dll" required>
                </div>
                
                <div class="form-group">
                    <label>Minimum Stok</label>
                    <input type="number" name="min_stock" id="minStock" value="0" step="0.01" min="0">
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" id="description" rows="3"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="item_id" id="deleteItemId">
</form>

<style>
.page-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-header h1 {
    font-size: 24px;
    font-weight: 600;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
    border: 1px solid #16a34a;
}

.alert-danger {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border: 1px solid #dc2626;
}

.filter-section {
    background: var(--bg-secondary);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: var(--shadow);
}

.filter-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-form input,
.filter-form select {
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-primary);
    color: var(--text-primary);
    font-size: 14px;
}

.filter-form input {
    flex: 1;
    min-width: 200px;
}

.filter-form select {
    min-width: 150px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-hover);
}

.btn-secondary {
    background: var(--text-secondary);
    color: white;
}

.btn-secondary:hover {
    background: #555;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.btn-warning {
    background: var(--warning-color);
    color: white;
}

.btn-danger {
    background: var(--danger-color);
    color: white;
}

.table-container {
    background: var(--bg-secondary);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: var(--bg-primary);
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
}

.data-table th {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 13px;
    text-transform: uppercase;
}

.data-table tbody tr:hover {
    background: var(--bg-primary);
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    overflow-y: auto;
}

.modal-content {
    background: var(--bg-secondary);
    margin: 50px auto;
    padding: 0;
    border-radius: 12px;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    font-size: 20px;
    font-weight: 600;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: var(--text-secondary);
}

.close:hover {
    color: var(--text-primary);
}

.modal form {
    padding: 20px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 14px;
    background: var(--bg-primary);
    color: var(--text-primary);
}

.form-group textarea {
    resize: vertical;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-form input,
    .filter-form select {
        width: 100%;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    .data-table {
        min-width: 800px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 20px;
        max-width: calc(100% - 40px);
    }
}
</style>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Barang';
    document.getElementById('formAction').value = 'add';
    document.getElementById('itemForm').reset();
    document.getElementById('itemCode').disabled = false;
    document.getElementById('itemModal').style.display = 'block';
}

function editItem(item) {
    document.getElementById('modalTitle').textContent = 'Edit Barang';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('itemId').value = item.item_id;
    document.getElementById('itemCode').value = item.item_code;
    document.getElementById('itemCode').disabled = true;
    document.getElementById('itemName').value = item.item_name;
    document.getElementById('categoryId').value = item.category_id || '';
    document.getElementById('unit').value = item.unit;
    document.getElementById('minStock').value = item.min_stock;
    document.getElementById('description').value = item.description || '';
    document.getElementById('itemModal').style.display = 'block';
}

function deleteItem(itemId, itemCode) {
    if (confirmDelete(`Hapus barang "${itemCode}"?`)) {
        document.getElementById('deleteItemId').value = itemId;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('itemModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('itemModal');
    const stockModal = document.getElementById('stockDetailModal');
    if (event.target == modal) {
        closeModal();
    } else if (event.target == stockModal) {
        closeStockDetailModal();
    }
}

// Stock Detail functions
function showStockDetail(itemId, itemName) {
    document.getElementById('stockDetailItemName').textContent = itemName;
    document.getElementById('stockDetailContent').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    document.getElementById('stockDetailModal').style.display = 'block';

    // Fetch stock details
    fetch('get_stock_detail.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<table class="data-table"><thead><tr><th>Warehouse</th><th class="text-right">Stok</th><th class="text-right">Harga Avg</th><th class="text-right">Nilai</th></tr></thead><tbody>';

                if (data.stocks.length === 0) {
                    html += '<tr><td colspan="4" class="text-center">Tidak ada stok di warehouse manapun</td></tr>';
                } else {
                    data.stocks.forEach(stock => {
                        html += '<tr>';
                        html += '<td><strong>' + stock.warehouse_name + '</strong><br><small style="color: var(--text-secondary);">' + stock.warehouse_code + '</small></td>';
                        html += '<td class="text-right"><strong>' + parseFloat(stock.current_stock).toLocaleString('id-ID') + ' ' + stock.unit + '</strong></td>';
                        html += '<td class="text-right">' + formatRupiahJS(stock.average_cost) + '</td>';
                        html += '<td class="text-right"><strong>' + formatRupiahJS(stock.current_stock * stock.average_cost) + '</strong></td>';
                        html += '</tr>';
                    });

                    // Total row
                    html += '<tr style="background: var(--bg-primary); font-weight: bold;">';
                    html += '<td>TOTAL</td>';
                    html += '<td class="text-right">' + parseFloat(data.total_stock).toLocaleString('id-ID') + ' ' + data.unit + '</td>';
                    html += '<td class="text-right">-</td>';
                    html += '<td class="text-right">' + formatRupiahJS(data.total_value) + '</td>';
                    html += '</tr>';
                }

                html += '</tbody></table>';
                document.getElementById('stockDetailContent').innerHTML = html;
            } else {
                document.getElementById('stockDetailContent').innerHTML = '<div style="text-align: center; padding: 20px; color: var(--danger-color);">Error: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('stockDetailContent').innerHTML = '<div style="text-align: center; padding: 20px; color: var(--danger-color);">Error loading data</div>';
        });
}

function closeStockDetailModal() {
    document.getElementById('stockDetailModal').style.display = 'none';
}

function formatRupiahJS(amount) {
    return 'Rp ' + parseFloat(amount).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}
</script>

<!-- Stock Detail Modal -->
<div id="stockDetailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-warehouse"></i> Detail Stok: <span id="stockDetailItemName"></span></h2>
            <span class="close" onclick="closeStockDetailModal()">&times;</span>
        </div>
        <div id="stockDetailContent" style="padding: 20px;">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<style>
.btn-xs {
    padding: 2px 6px;
    font-size: 11px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    vertical-align: middle;
}

.btn-info {
    background: var(--info-color);
    color: white;
}

.btn-info:hover {
    background: #0891b2;
}
</style>

<?php include 'includes/footer.php'; ?>
