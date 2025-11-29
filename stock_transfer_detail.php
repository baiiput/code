<?php
require_once 'config.php';
requireRole(['admin', 'manager', 'staff_warehouse']);

$conn = getDBConnection();

$transfer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$transfer_id) {
    echo '<p>Invalid transfer ID</p>';
    exit;
}

// Get transfer header
$query = "SELECT st.*,
          w_from.warehouse_name as from_warehouse_name, w_from.warehouse_code as from_warehouse_code,
          w_to.warehouse_name as to_warehouse_name, w_to.warehouse_code as to_warehouse_code,
          u.full_name as created_by_name
          FROM stock_transfers st
          LEFT JOIN warehouses w_from ON st.from_warehouse_id = w_from.warehouse_id
          LEFT JOIN warehouses w_to ON st.to_warehouse_id = w_to.warehouse_id
          LEFT JOIN users u ON st.created_by = u.user_id
          WHERE st.transfer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $transfer_id);
$stmt->execute();
$transfer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transfer) {
    echo '<p>Transfer not found</p>';
    exit;
}

// Get transfer details
$query = "SELECT std.*, i.item_code, i.item_name, i.unit
          FROM stock_transfer_detail std
          LEFT JOIN items i ON std.item_id = i.item_id
          WHERE std.transfer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $transfer_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<style>
.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.info-table td {
    padding: 8px;
}

.info-table td:first-child {
    font-weight: 600;
    width: 180px;
}

.info-table tr:nth-child(even) {
    background: var(--bg-primary);
}

@media (max-width: 480px) {
    .info-table td:first-child {
        width: 120px;
        font-size: 12px;
    }

    .info-table td {
        padding: 6px;
        font-size: 13px;
    }
}
</style>

<div style="margin-bottom: 20px;">
    <h3 style="margin-bottom: 16px; color: var(--primary-color); font-size: 16px;">Informasi Transfer</h3>
    <div class="modal-table-wrapper">
        <table class="info-table" style="min-width: 400px;">
            <tr>
                <td>Kode Transaksi:</td>
                <td><?php echo $transfer['transaction_code']; ?></td>
            </tr>
            <tr>
                <td>Tanggal Transfer:</td>
                <td><?php echo date('d/m/Y H:i', strtotime($transfer['transfer_date'])); ?></td>
            </tr>
            <tr>
                <td>Dari Warehouse:</td>
                <td>
                    <strong><?php echo $transfer['from_warehouse_name']; ?></strong>
                    (<?php echo $transfer['from_warehouse_code']; ?>)
                </td>
            </tr>
            <tr>
                <td>Ke Warehouse:</td>
                <td>
                    <strong><?php echo $transfer['to_warehouse_name']; ?></strong>
                    (<?php echo $transfer['to_warehouse_code']; ?>)
                </td>
            </tr>
            <tr>
                <td>Catatan:</td>
                <td><?php echo $transfer['notes'] ?: '-'; ?></td>
            </tr>
            <tr>
                <td>Dibuat Oleh:</td>
                <td><?php echo $transfer['created_by_name']; ?></td>
            </tr>
        </table>
    </div>
</div>

<h3 style="margin-bottom: 16px; color: var(--primary-color); font-size: 16px;">Detail Items</h3>
<div class="modal-table-wrapper">
    <table style="width: 100%; min-width: 500px; border-collapse: collapse; border: 1px solid var(--border-color);">
        <thead>
            <tr style="background: var(--primary-color); color: white;">
                <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Kode Item</th>
                <th style="padding: 12px; text-align: left; border: 1px solid var(--border-color);">Nama Item</th>
                <th style="padding: 12px; text-align: center; border: 1px solid var(--border-color);">Unit</th>
                <th style="padding: 12px; text-align: right; border: 1px solid var(--border-color);">Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $detail): ?>
            <tr>
                <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo $detail['item_code']; ?></td>
                <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo $detail['item_name']; ?></td>
                <td style="padding: 10px; text-align: center; border: 1px solid var(--border-color);"><?php echo $detail['unit']; ?></td>
                <td style="padding: 10px; text-align: right; border: 1px solid var(--border-color); font-weight: 600;">
                    <?php echo formatNumber($detail['quantity'], 2); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
