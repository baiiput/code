<?php
$pageTitle = 'Hutang/Piutang';
require_once __DIR__ . '/../../templates/header.php';

$tab = $_GET['tab'] ?? 'receivable';
$db = getDB();

// Get receivables (piutang - dari penjualan)
$receivables = $db->query("SELECT s.*, c.name as customer_name, c.phone as customer_phone
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.payment_status IN ('unpaid', 'partial')
    ORDER BY s.due_date ASC, s.date DESC")->fetchAll();

// Get payables (hutang - dari stok masuk)
$payables = $db->query("SELECT si.*, sp.name as supplier_name, sp.phone as supplier_phone
    FROM stock_in si
    LEFT JOIN suppliers sp ON si.supplier_id = sp.id
    WHERE si.payment_status IN ('unpaid', 'partial')
    ORDER BY si.due_date ASC, si.date DESC")->fetchAll();

$totalReceivables = array_sum(array_map(fn($r) => $r['grand_total'] - $r['paid_amount'], $receivables));
$totalPayables = array_sum(array_map(fn($p) => $p['grand_total'] - $p['paid_amount'], $payables));

// Handle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $type = $_POST['type'];
    $refId = (int)$_POST['reference_id'];
    $amount = (float)str_replace(['.', ','], ['', '.'], $_POST['amount']);

    // Record payment
    $paymentData = [
        'type' => $type,
        'reference_type' => $type == 'receivable' ? 'sale' : 'stock_in',
        'reference_id' => $refId,
        'date' => $_POST['date'],
        'amount' => $amount,
        'payment_method' => $_POST['payment_method'],
        'notes' => trim($_POST['notes']),
        'created_by' => $_SESSION['user_id'],
    ];
    insert('payments', $paymentData);

    // Update paid amount
    $table = $type == 'receivable' ? 'sales' : 'stock_in';
    $record = getById($table, $refId);
    $newPaid = $record['paid_amount'] + $amount;
    $status = $newPaid >= $record['grand_total'] ? 'paid' : 'partial';

    $db->prepare("UPDATE $table SET paid_amount = ?, payment_status = ? WHERE id = ?")
       ->execute([$newPaid, $status, $refId]);

    setFlash('success', 'Pembayaran berhasil dicatat');
    header('Location: ' . BASE_URL . 'modules/debts/?tab=' . $type);
    exit;
}
?>

<div class="page-header">
    <h1>Hutang/Piutang</h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Hutang/Piutang' => '']) ?>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-12 col-6">
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($totalReceivables) ?></div>
            <div class="stat-label">Total Piutang (Akan Diterima)</div>
        </div>
    </div>
    <div class="col-12 col-6">
        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($totalPayables) ?></div>
            <div class="stat-label">Total Hutang (Harus Dibayar)</div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="d-flex gap-2 mb-3">
    <a href="?tab=receivable" class="btn <?= $tab == 'receivable' ? 'btn-primary' : 'btn-outline' ?>">
        <i class="fas fa-hand-holding-usd me-2"></i>Piutang (<?= count($receivables) ?>)
    </a>
    <a href="?tab=payable" class="btn <?= $tab == 'payable' ? 'btn-primary' : 'btn-outline' ?>">
        <i class="fas fa-file-invoice-dollar me-2"></i>Hutang (<?= count($payables) ?>)
    </a>
</div>

<?php if ($tab == 'receivable'): ?>
<!-- Piutang -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-hand-holding-usd me-2"></i>Daftar Piutang</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Dibayar</th>
                        <th class="text-end">Sisa</th>
                        <th>Jatuh Tempo</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receivables)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Tidak ada piutang</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($receivables as $r): ?>
                    <tr>
                        <td><strong><?= e($r['invoice_number']) ?></strong></td>
                        <td><?= formatDate($r['date']) ?></td>
                        <td>
                            <?= e($r['customer_name']) ?: 'Walk-in' ?>
                            <?php if ($r['customer_phone']): ?>
                            <br><small class="text-muted"><?= e($r['customer_phone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= formatCurrency($r['grand_total']) ?></td>
                        <td class="text-end text-success"><?= formatCurrency($r['paid_amount']) ?></td>
                        <td class="text-end text-danger fw-bold"><?= formatCurrency($r['grand_total'] - $r['paid_amount']) ?></td>
                        <td><?= $r['due_date'] ? formatDueDate($r['due_date']) : '-' ?></td>
                        <td>
                            <button class="btn btn-sm btn-success"
                                    onclick="showPayment('receivable', <?= $r['id'] ?>, '<?= e($r['invoice_number']) ?>', <?= $r['grand_total'] - $r['paid_amount'] ?>)"
                                    title="Terima Pembayaran">
                                <i class="fas fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Hutang -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-file-invoice-dollar me-2"></i>Daftar Hutang</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Dibayar</th>
                        <th class="text-end">Sisa</th>
                        <th>Jatuh Tempo</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payables)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Tidak ada hutang</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($payables as $p): ?>
                    <tr>
                        <td><strong><?= e($p['invoice_number']) ?></strong></td>
                        <td><?= formatDate($p['date']) ?></td>
                        <td>
                            <?= e($p['supplier_name']) ?: '-' ?>
                            <?php if ($p['supplier_phone']): ?>
                            <br><small class="text-muted"><?= e($p['supplier_phone']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= formatCurrency($p['grand_total']) ?></td>
                        <td class="text-end text-success"><?= formatCurrency($p['paid_amount']) ?></td>
                        <td class="text-end text-danger fw-bold"><?= formatCurrency($p['grand_total'] - $p['paid_amount']) ?></td>
                        <td><?= $p['due_date'] ? formatDueDate($p['due_date']) : '-' ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                    onclick="showPayment('payable', <?= $p['id'] ?>, '<?= e($p['invoice_number']) ?>', <?= $p['grand_total'] - $p['paid_amount'] ?>)"
                                    title="Bayar">
                                <i class="fas fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Payment Modal -->
<div class="modal-backdrop"></div>
<div class="modal" id="paymentModal">
    <div class="modal-header">
        <h5 id="paymentTitle">Catat Pembayaran</h5>
        <button type="button" class="btn-close" onclick="closeModal('paymentModal')">&times;</button>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="type" id="paymentType">
        <input type="hidden" name="reference_id" id="paymentRefId">
        <div class="modal-body">
            <p>Invoice: <strong id="paymentInvoice"></strong></p>
            <p>Sisa: <strong id="paymentRemaining" class="text-danger"></strong></p>

            <div class="form-group">
                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                <input type="text" name="amount" id="paymentAmount" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Metode</label>
                <select name="payment_method" class="form-control form-select">
                    <option value="cash">Cash</option>
                    <option value="transfer">Transfer</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
function showPayment(type, refId, invoice, remaining) {
    document.getElementById('paymentTitle').textContent = type == 'receivable' ? 'Terima Pembayaran' : 'Bayar Hutang';
    document.getElementById('paymentType').value = type;
    document.getElementById('paymentRefId').value = refId;
    document.getElementById('paymentInvoice').textContent = invoice;
    document.getElementById('paymentRemaining').textContent = 'Rp ' + remaining.toLocaleString('id-ID');
    document.getElementById('paymentAmount').value = remaining.toLocaleString('id-ID');
    openModal('paymentModal');
}
</script>
SCRIPT;
?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
