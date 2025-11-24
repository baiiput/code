<?php
require_once 'config.php';
requireRole('admin'); // Only admin can access this tool

$conn = getDBConnection();
$user = getCurrentUser();

$page_title = 'Perbaikan Stok';

// Handle repair action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'fix_negative') {
        $warehouse_id = intval($_POST['warehouse_id']);
        $item_id = intval($_POST['item_id']);
        $current_stock = floatval($_POST['current_stock']);
        $new_stock = floatval($_POST['new_stock']);

        $conn->begin_transaction();
        try {
            // Update stock
            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("dii", $new_stock, $warehouse_id, $item_id);
            $stmt->execute();
            $stmt->close();

            // Get item and warehouse info for logging
            $stmt = $conn->prepare("
                SELECT i.item_code, i.item_name, w.warehouse_code, w.warehouse_name
                FROM items i, warehouses w
                WHERE i.item_id = ? AND w.warehouse_id = ?
            ");
            $stmt->bind_param("ii", $item_id, $warehouse_id);
            $stmt->execute();
            $info = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Log as stock adjustment
            logActivity('UPDATE', 'stock_repair', "Fixed negative stock: {$info['item_code']} at {$info['warehouse_code']} from {$current_stock} to {$new_stock}");

            $conn->commit();
            $_SESSION['success_message'] = "Stok berhasil diperbaiki!";
            header('Location: admin_stock_repair.php');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
}

// Get all stock anomalies
$negative_stocks = [];
$query = "
    SELECT wi.*,
           i.item_code, i.item_name, i.unit,
           w.warehouse_code, w.warehouse_name
    FROM warehouse_items wi
    JOIN items i ON wi.item_id = i.item_id
    JOIN warehouses w ON wi.warehouse_id = w.warehouse_id
    WHERE wi.current_stock < 0
    ORDER BY w.warehouse_name, i.item_code
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $negative_stocks[] = $row;
}

// Get statistics
$total_warehouses = $conn->query("SELECT COUNT(*) as count FROM warehouses")->fetch_assoc()['count'];
$total_items = $conn->query("SELECT COUNT(*) as count FROM items")->fetch_assoc()['count'];
$total_warehouse_items = $conn->query("SELECT COUNT(*) as count FROM warehouse_items")->fetch_assoc()['count'];
$negative_count = count($negative_stocks);

include 'includes/header.php';

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-wrench"></i> Perbaikan & Audit Stok</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Tool Perbaikan Stok</strong><br>
        Tool ini digunakan untuk mendeteksi dan memperbaiki anomali stok (stok negatif, data tidak konsisten, dll).
        <br><strong>PENTING:</strong> Backup database sebelum melakukan perbaikan!
    </div>

    <!-- Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">Total Warehouses</div>
            <div style="font-size: 32px; font-weight: 700; color: #3b82f6;"><?php echo $total_warehouses; ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">Total Items</div>
            <div style="font-size: 32px; font-weight: 700; color: #10b981;"><?php echo $total_items; ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">Warehouse Items</div>
            <div style="font-size: 32px; font-weight: 700; color: #f59e0b;"><?php echo $total_warehouse_items; ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 14px; color: #64748b; margin-bottom: 8px;">Stok Negatif</div>
            <div style="font-size: 32px; font-weight: 700; color: <?php echo $negative_count > 0 ? '#ef4444' : '#10b981'; ?>;">
                <?php echo $negative_count; ?>
            </div>
        </div>
    </div>

    <!-- Negative Stock Table -->
    <?php if (!empty($negative_stocks)): ?>
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px; color: #ef4444;">
            <i class="fas fa-exclamation-triangle"></i> Stok Negatif Terdeteksi (<?php echo $negative_count; ?>)
        </h2>
        <p style="margin-bottom: 20px; color: #64748b;">
            Item-item berikut memiliki stok negatif. Ini terjadi karena ada transaksi yang di-edit/delete setelah stok didistribusikan.
        </p>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th width="15%">Stok Saat Ini</th>
                        <th width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($negative_stocks as $stock): ?>
                    <tr>
                        <td>
                            <strong><?php echo $stock['warehouse_code']; ?></strong>
                            <br>
                            <small style="color: #64748b;"><?php echo $stock['warehouse_name']; ?></small>
                        </td>
                        <td><strong><?php echo $stock['item_code']; ?></strong></td>
                        <td><?php echo $stock['item_name']; ?></td>
                        <td>
                            <span style="color: #ef4444; font-weight: 700; font-size: 16px;">
                                <?php echo number_format($stock['current_stock'], 2); ?> <?php echo $stock['unit']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-sm btn-warning" onclick='showRepairModal(<?php echo json_encode($stock); ?>)'>
                                <i class="fas fa-wrench"></i> Perbaiki
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <i class="fas fa-check-circle" style="font-size: 64px; color: #10b981; margin-bottom: 20px;"></i>
        <h2 style="color: #10b981; margin-bottom: 10px;">Tidak Ada Anomali Stok</h2>
        <p style="color: #64748b;">Semua stok dalam kondisi normal. Tidak ada stok negatif terdeteksi.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Repair Modal -->
<div id="repairModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-wrench"></i> Perbaiki Stok Negatif</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="fix_negative">
            <input type="hidden" name="warehouse_id" id="warehouseId">
            <input type="hidden" name="item_id" id="itemId">
            <input type="hidden" name="current_stock" id="currentStock">

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Peringatan!</strong> Perbaikan stok akan mengubah nilai stok secara langsung.
                Pastikan Anda memasukkan nilai yang benar!
            </div>

            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <div style="margin-bottom: 12px;">
                    <strong>Warehouse:</strong> <span id="displayWarehouse"></span>
                </div>
                <div style="margin-bottom: 12px;">
                    <strong>Item:</strong> <span id="displayItem"></span>
                </div>
                <div>
                    <strong>Stok Saat Ini:</strong>
                    <span id="displayCurrentStock" style="color: #ef4444; font-weight: 700; font-size: 18px;"></span>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-edit"></i> Stok Baru (Perbaikan) *</label>
                <input type="number" name="new_stock" id="newStock" step="0.01" required>
                <small style="color: var(--text-secondary);">
                    Masukkan nilai stok yang benar. Biasanya 0 jika barang sudah habis terdistribusi.
                </small>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="confirmFix" required>
                    <span>Saya yakin nilai stok yang dimasukkan sudah benar</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Perbaiki Stok
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRepairModal(stock) {
    document.getElementById('warehouseId').value = stock.warehouse_id;
    document.getElementById('itemId').value = stock.item_id;
    document.getElementById('currentStock').value = stock.current_stock;

    document.getElementById('displayWarehouse').textContent = stock.warehouse_code + ' - ' + stock.warehouse_name;
    document.getElementById('displayItem').textContent = stock.item_code + ' - ' + stock.item_name;
    document.getElementById('displayCurrentStock').textContent = parseFloat(stock.current_stock).toFixed(2) + ' ' + stock.unit;

    document.getElementById('newStock').value = '0';
    document.getElementById('confirmFix').checked = false;

    document.getElementById('repairModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('repairModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('repairModal')) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
