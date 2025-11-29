<?php
require_once 'config.php';
requireRole(['admin', 'manager', 'staff_warehouse', 'staff_keuangan']);

$conn = getDBConnection();
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo '<p style="text-align:center;padding:40px;color:var(--danger-color);">ID tidak valid</p>';
    exit;
}

// Get header
$stmt = $conn->prepare("
    SELECT si.*, s.supplier_name, u.full_name as created_by_name
    FROM stock_in si
    LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
    LEFT JOIN users u ON si.created_by = u.user_id
    WHERE si.stock_in_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$trans = $result->fetch_assoc();
$stmt->close();

if (!$trans) {
    echo '<p style="text-align:center;padding:40px;color:var(--danger-color);">Transaksi tidak ditemukan</p>';
    exit;
}

// Get details
$stmt = $conn->prepare("
    SELECT sid.*, i.item_code, i.item_name, i.unit
    FROM stock_in_detail sid
    LEFT JOIN items i ON sid.item_id = i.item_id
    WHERE sid.stock_in_id = ?
    ORDER BY sid.detail_id
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$details = [];
while ($row = $result->fetch_assoc()) {
    $details[] = $row;
}
$stmt->close();
?>

<style>
.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 32px;
    margin-bottom: 24px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-label {
    font-size: 13px;
    color: var(--text-secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.detail-table th,
.detail-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
}

.detail-table th {
    background: var(--bg-primary);
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.detail-table tbody tr:hover {
    background: var(--bg-primary);
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: var(--primary-light);
    border-radius: 12px;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .detail-label {
        font-size: 11px;
    }

    .detail-value {
        font-size: 13px;
    }

    .total-row {
        padding: 15px;
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .detail-table th,
    .detail-table td {
        padding: 8px 6px;
        font-size: 12px;
    }

    .detail-table th {
        font-size: 10px;
    }

    .total-row strong:first-child {
        font-size: 14px;
    }

    .total-row strong:last-child {
        font-size: 18px;
    }
}
</style>

<div class="detail-grid">
    <div class="detail-item">
        <div class="detail-label">Kode Transaksi</div>
        <div class="detail-value"><?php echo $trans['transaction_code']; ?></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Tanggal</div>
        <div class="detail-value"><?php echo date('d F Y, H:i', strtotime($trans['transaction_date'])); ?></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Supplier</div>
        <div class="detail-value"><?php echo $trans['supplier_name']; ?></div>
    </div>
    <div class="detail-item">
        <div class="detail-label">Dibuat Oleh</div>
        <div class="detail-value"><?php echo $trans['created_by_name']; ?></div>
    </div>
    <?php if ($trans['notes']): ?>
    <div class="detail-item" style="grid-column: 1 / -1;">
        <div class="detail-label">Catatan</div>
        <div class="detail-value"><?php echo nl2br($trans['notes']); ?></div>
    </div>
    <?php endif; ?>
</div>

<h3 style="margin: 24px 0 12px; font-size: 16px; font-weight: 700;">
    <i class="fas fa-boxes"></i> Detail Barang
</h3>

<div class="modal-table-wrapper">
    <table class="detail-table" style="min-width: 600px;">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode</th>
                <th>Nama Barang</th>
                <th width="12%" class="text-right">Qty</th>
                <th width="15%" class="text-right">Harga</th>
                <th width="15%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $index => $item): ?>
            <tr>
                <td class="text-center"><?php echo $index + 1; ?></td>
                <td><strong><?php echo $item['item_code']; ?></strong></td>
                <td><?php echo $item['item_name']; ?></td>
                <td class="text-right"><?php echo formatNumber($item['quantity'], 2); ?> <?php echo $item['unit']; ?></td>
                <td class="text-right"><?php echo formatRupiah($item['unit_price']); ?></td>
                <td class="text-right"><strong><?php echo formatRupiah($item['subtotal']); ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="total-row">
    <strong style="font-size: 18px;">TOTAL</strong>
    <strong style="font-size: 24px; color: var(--primary-color);"><?php echo formatRupiah($trans['total_amount']); ?></strong>
</div>
