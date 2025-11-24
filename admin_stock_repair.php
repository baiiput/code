<?php
require_once 'config.php';
requireRole('admin'); // Only admin can access this tool

$conn = getDBConnection();
$user = getCurrentUser();

$page_title = 'Perbaikan Stok';

// Handle repair action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'fix_stock') {
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
            logActivity('UPDATE', 'stock_repair', "Fixed stock: {$info['item_code']} at {$info['warehouse_code']} from {$current_stock} to {$new_stock}");

            $conn->commit();
            $_SESSION['success_message'] = "Stok berhasil diperbaiki!";
            header('Location: admin_stock_repair.php');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'recalculate_stock') {
        $warehouse_id = intval($_POST['warehouse_id']);
        $item_id = intval($_POST['item_id']);

        $conn->begin_transaction();
        try {
            // Recalculate stock from transaction history
            // 1. Get stock_in total
            $query = "SELECT COALESCE(SUM(sid.quantity), 0) as total
                     FROM stock_in_detail sid
                     JOIN stock_in si ON sid.stock_in_id = si.stock_in_id
                     WHERE si.warehouse_id = ? AND sid.item_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $warehouse_id, $item_id);
            $stmt->execute();
            $stock_in_total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // 2. Get stock_out total
            $query = "SELECT COALESCE(SUM(sod.quantity), 0) as total
                     FROM stock_out_detail sod
                     JOIN stock_out so ON sod.stock_out_id = so.stock_out_id
                     WHERE so.warehouse_id = ? AND sod.item_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $warehouse_id, $item_id);
            $stmt->execute();
            $stock_out_total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // 3. Get transfer IN (to this warehouse)
            $query = "SELECT COALESCE(SUM(std.quantity), 0) as total
                     FROM stock_transfer_detail std
                     JOIN stock_transfers st ON std.transfer_id = st.transfer_id
                     WHERE st.to_warehouse_id = ? AND std.item_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $warehouse_id, $item_id);
            $stmt->execute();
            $transfer_in_total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // 4. Get transfer OUT (from this warehouse)
            $query = "SELECT COALESCE(SUM(std.quantity), 0) as total
                     FROM stock_transfer_detail std
                     JOIN stock_transfers st ON std.transfer_id = st.transfer_id
                     WHERE st.from_warehouse_id = ? AND std.item_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $warehouse_id, $item_id);
            $stmt->execute();
            $transfer_out_total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // 5. Get adjustment total (positive and negative)
            $query = "SELECT COALESCE(SUM(sa.difference), 0) as total
                     FROM stock_adjustment sa
                     WHERE sa.warehouse_id = ? AND sa.item_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $warehouse_id, $item_id);
            $stmt->execute();
            $adjustment_total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            // Calculate expected stock
            $expected_stock = $stock_in_total - $stock_out_total + $transfer_in_total - $transfer_out_total + $adjustment_total;

            // Get current stock
            $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("ii", $warehouse_id, $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_stock = $result->num_rows > 0 ? $result->fetch_assoc()['current_stock'] : 0;
            $stmt->close();

            // Update to expected stock
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
                $stmt->bind_param("dii", $expected_stock, $warehouse_id, $item_id);
                $stmt->execute();
                $stmt->close();
            }

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

            logActivity('UPDATE', 'stock_repair', "Recalculated stock: {$info['item_code']} at {$info['warehouse_code']} from {$current_stock} to {$expected_stock}");

            $conn->commit();
            $_SESSION['success_message'] = "Stok berhasil di-recalculate! Old: " . number_format($current_stock, 2) . " → New: " . number_format($expected_stock, 2);
            header('Location: admin_stock_repair.php');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
}

// 1. Get negative stocks
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

// 2. Get stock inconsistencies (calculate from transaction vs current stock)
$inconsistent_stocks = [];
$query = "
    SELECT
        wi.warehouse_id,
        wi.item_id,
        wi.current_stock,
        wi.average_cost,
        i.item_code,
        i.item_name,
        i.unit,
        w.warehouse_code,
        w.warehouse_name,

        COALESCE((SELECT SUM(sid.quantity)
                  FROM stock_in_detail sid
                  JOIN stock_in si ON sid.stock_in_id = si.stock_in_id
                  WHERE si.warehouse_id = wi.warehouse_id AND sid.item_id = wi.item_id), 0) as stock_in,

        COALESCE((SELECT SUM(sod.quantity)
                  FROM stock_out_detail sod
                  JOIN stock_out so ON sod.stock_out_id = so.stock_out_id
                  WHERE so.warehouse_id = wi.warehouse_id AND sod.item_id = wi.item_id), 0) as stock_out,

        COALESCE((SELECT SUM(std.quantity)
                  FROM stock_transfer_detail std
                  JOIN stock_transfers st ON std.transfer_id = st.transfer_id
                  WHERE st.to_warehouse_id = wi.warehouse_id AND std.item_id = wi.item_id), 0) as transfer_in,

        COALESCE((SELECT SUM(std.quantity)
                  FROM stock_transfer_detail std
                  JOIN stock_transfers st ON std.transfer_id = st.transfer_id
                  WHERE st.from_warehouse_id = wi.warehouse_id AND std.item_id = wi.item_id), 0) as transfer_out,

        COALESCE((SELECT SUM(sa.difference)
                  FROM stock_adjustment sa
                  WHERE sa.warehouse_id = wi.warehouse_id AND sa.item_id = wi.item_id), 0) as adjustment
    FROM warehouse_items wi
    JOIN items i ON wi.item_id = i.item_id
    JOIN warehouses w ON wi.warehouse_id = w.warehouse_id
    HAVING ABS(current_stock - (stock_in - stock_out + transfer_in - transfer_out + adjustment)) > 0.01
    ORDER BY w.warehouse_name, i.item_code
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $row['expected_stock'] = $row['stock_in'] - $row['stock_out'] + $row['transfer_in'] - $row['transfer_out'] + $row['adjustment'];
    $row['difference'] = $row['current_stock'] - $row['expected_stock'];
    $inconsistent_stocks[] = $row;
}

// 3. Calculate warehouse balance check
$balance_info = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1")->fetch_assoc();
$current_balance = $balance_info['balance_amount'] ?? 0;

// Calculate expected balance from stock values
$query = "
    SELECT SUM(wi.current_stock * wi.average_cost) as total_stock_value
    FROM warehouse_items wi
";
$result = $conn->query($query);
$total_stock_value = $result->fetch_assoc()['total_stock_value'] ?? 0;

$balance_difference = abs($current_balance - $total_stock_value);
$balance_has_issue = $balance_difference > 1000; // Threshold 1000

// Get statistics
$total_warehouses = $conn->query("SELECT COUNT(*) as count FROM warehouses")->fetch_assoc()['count'];
$total_items = $conn->query("SELECT COUNT(*) as count FROM items")->fetch_assoc()['count'];
$total_warehouse_items = $conn->query("SELECT COUNT(*) as count FROM warehouse_items")->fetch_assoc()['count'];
$negative_count = count($negative_stocks);
$inconsistent_count = count($inconsistent_stocks);
$total_anomalies = $negative_count + $inconsistent_count + ($balance_has_issue ? 1 : 0);

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
        <strong>Tool Perbaikan Stok - Advanced</strong><br>
        Tool ini mendeteksi: (1) Stok Negatif, (2) Stok Tidak Konsisten dengan Transaksi, (3) Selisih Nilai Stok vs Saldo Warehouse
        <br><strong>PENTING:</strong> Backup database sebelum melakukan perbaikan!
    </div>

    <!-- Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 30px;">
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Total Warehouses</div>
            <div style="font-size: 28px; font-weight: 700; color: #3b82f6;"><?php echo $total_warehouses; ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Total Items</div>
            <div style="font-size: 28px; font-weight: 700; color: #10b981;"><?php echo $total_items; ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Warehouse Items</div>
            <div style="font-size: 28px; font-weight: 700; color: #f59e0b;"><?php echo $total_warehouse_items; ?></div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Stok Negatif</div>
            <div style="font-size: 28px; font-weight: 700; color: <?php echo $negative_count > 0 ? '#ef4444' : '#10b981'; ?>;">
                <?php echo $negative_count; ?>
            </div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Stok Tidak Konsisten</div>
            <div style="font-size: 28px; font-weight: 700; color: <?php echo $inconsistent_count > 0 ? '#f59e0b' : '#10b981'; ?>;">
                <?php echo $inconsistent_count; ?>
            </div>
        </div>
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Total Anomali</div>
            <div style="font-size: 28px; font-weight: 700; color: <?php echo $total_anomalies > 0 ? '#ef4444' : '#10b981'; ?>;">
                <?php echo $total_anomalies; ?>
            </div>
        </div>
    </div>

    <!-- Balance Check -->
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px; color: <?php echo $balance_has_issue ? '#f59e0b' : '#10b981'; ?>;">
            <i class="fas fa-wallet"></i> Cek Saldo Warehouse
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Saldo Warehouse (Database)</div>
                <div style="font-size: 24px; font-weight: 700; color: #3b82f6;">
                    <?php echo formatRupiah($current_balance); ?>
                </div>
            </div>
            <div>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Total Nilai Stok (Calculated)</div>
                <div style="font-size: 24px; font-weight: 700; color: #10b981;">
                    <?php echo formatRupiah($total_stock_value); ?>
                </div>
            </div>
            <div>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Selisih</div>
                <div style="font-size: 24px; font-weight: 700; color: <?php echo $balance_has_issue ? '#ef4444' : '#10b981'; ?>;">
                    <?php echo formatRupiah($balance_difference); ?>
                </div>
            </div>
        </div>
        <?php if ($balance_has_issue): ?>
        <div class="alert alert-warning" style="margin-top: 20px; margin-bottom: 0;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Perhatian!</strong> Ada selisih antara saldo warehouse dengan total nilai stok. Ini bisa terjadi karena edit/delete transaksi.
        </div>
        <?php else: ?>
        <div class="alert alert-success" style="margin-top: 20px; margin-bottom: 0;">
            <i class="fas fa-check-circle"></i>
            Saldo warehouse konsisten dengan nilai stok.
        </div>
        <?php endif; ?>
    </div>

    <!-- Negative Stock Table -->
    <?php if (!empty($negative_stocks)): ?>
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px; color: #ef4444;">
            <i class="fas fa-exclamation-triangle"></i> 1. Stok Negatif (<?php echo $negative_count; ?>)
        </h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th width="15%">Stok Saat Ini</th>
                        <th width="22%">Aksi</th>
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
                                <i class="fas fa-wrench"></i> Fix Manual
                            </button>
                            <button class="btn-sm btn-primary" onclick='recalculateStock(<?php echo json_encode($stock); ?>)'>
                                <i class="fas fa-calculator"></i> Recalculate
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Inconsistent Stock Table -->
    <?php if (!empty($inconsistent_stocks)): ?>
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px; color: #f59e0b;">
            <i class="fas fa-exclamation-circle"></i> 2. Stok Tidak Konsisten dengan Transaksi (<?php echo $inconsistent_count; ?>)
        </h2>
        <p style="margin-bottom: 20px; color: #64748b;">
            Stok aktual berbeda dengan stok yang dihitung dari history transaksi (IN - OUT + TRANSFER + ADJUSTMENT).
        </p>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Item</th>
                        <th width="12%">Stok Aktual</th>
                        <th width="12%">Stok Expected</th>
                        <th width="12%">Selisih</th>
                        <th width="22%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inconsistent_stocks as $stock): ?>
                    <tr>
                        <td>
                            <strong><?php echo $stock['warehouse_code']; ?></strong>
                            <br>
                            <small style="color: #64748b;"><?php echo $stock['warehouse_name']; ?></small>
                        </td>
                        <td>
                            <strong><?php echo $stock['item_code']; ?></strong>
                            <br>
                            <small style="color: #64748b;"><?php echo $stock['item_name']; ?></small>
                        </td>
                        <td>
                            <span style="font-weight: 700; font-size: 15px;">
                                <?php echo number_format($stock['current_stock'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 700; font-size: 15px; color: #10b981;">
                                <?php echo number_format($stock['expected_stock'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 700; font-size: 15px; color: <?php echo $stock['difference'] < 0 ? '#ef4444' : '#f59e0b'; ?>;">
                                <?php echo ($stock['difference'] > 0 ? '+' : '') . number_format($stock['difference'], 2); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-sm btn-warning" onclick='showRepairModal(<?php echo json_encode($stock); ?>)'>
                                <i class="fas fa-wrench"></i> Fix Manual
                            </button>
                            <button class="btn-sm btn-primary" onclick='recalculateStock(<?php echo json_encode($stock); ?>)'>
                                <i class="fas fa-calculator"></i> Recalculate
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- No Anomalies -->
    <?php if ($total_anomalies == 0): ?>
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <i class="fas fa-check-circle" style="font-size: 64px; color: #10b981; margin-bottom: 20px;"></i>
        <h2 style="color: #10b981; margin-bottom: 10px;">Tidak Ada Anomali Stok</h2>
        <p style="color: #64748b;">Semua stok dalam kondisi normal. Tidak ada anomali terdeteksi.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Repair Modal -->
<div id="repairModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-wrench"></i> Perbaiki Stok</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="fix_stock">
            <input type="hidden" name="warehouse_id" id="warehouseId">
            <input type="hidden" name="item_id" id="itemId">
            <input type="hidden" name="current_stock" id="currentStock">

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Peringatan!</strong> Perbaikan stok akan mengubah nilai stok secara langsung.
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
                    <span id="displayCurrentStock" style="font-weight: 700; font-size: 18px;"></span>
                </div>
                <div id="expectedStockDiv" style="display: none; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-color);">
                    <strong>Stok Expected:</strong>
                    <span id="displayExpectedStock" style="font-weight: 700; font-size: 18px; color: #10b981;"></span>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-edit"></i> Stok Baru *</label>
                <input type="number" name="new_stock" id="newStock" step="0.01" required>
                <small style="color: var(--text-secondary);">
                    Masukkan nilai stok yang benar.
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

<!-- Recalculate Modal -->
<div id="recalculateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-calculator"></i> Recalculate Stok</h2>
            <span class="close" onclick="closeRecalculateModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="recalculate_stock">
            <input type="hidden" name="warehouse_id" id="recalcWarehouseId">
            <input type="hidden" name="item_id" id="recalcItemId">

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Recalculate Stok</strong><br>
                Sistem akan menghitung ulang stok dari history transaksi (Stock IN, OUT, Transfer, Adjustment).
            </div>

            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <div style="margin-bottom: 12px;">
                    <strong>Warehouse:</strong> <span id="recalcDisplayWarehouse"></span>
                </div>
                <div>
                    <strong>Item:</strong> <span id="recalcDisplayItem"></span>
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="confirmRecalc" required>
                    <span>Saya yakin ingin recalculate stok dari history transaksi</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeRecalculateModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calculator"></i> Recalculate
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

    if (stock.expected_stock !== undefined) {
        document.getElementById('expectedStockDiv').style.display = 'block';
        document.getElementById('displayExpectedStock').textContent = parseFloat(stock.expected_stock).toFixed(2) + ' ' + stock.unit;
        document.getElementById('newStock').value = parseFloat(stock.expected_stock).toFixed(2);
    } else {
        document.getElementById('expectedStockDiv').style.display = 'none';
        document.getElementById('newStock').value = '0';
    }

    document.getElementById('confirmFix').checked = false;
    document.getElementById('repairModal').style.display = 'block';
}

function recalculateStock(stock) {
    document.getElementById('recalcWarehouseId').value = stock.warehouse_id;
    document.getElementById('recalcItemId').value = stock.item_id;

    document.getElementById('recalcDisplayWarehouse').textContent = stock.warehouse_code + ' - ' + stock.warehouse_name;
    document.getElementById('recalcDisplayItem').textContent = stock.item_code + ' - ' + stock.item_name;

    document.getElementById('confirmRecalc').checked = false;
    document.getElementById('recalculateModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('repairModal').style.display = 'none';
}

function closeRecalculateModal() {
    document.getElementById('recalculateModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('repairModal')) {
        closeModal();
    }
    if (event.target == document.getElementById('recalculateModal')) {
        closeRecalculateModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
