<?php
$pageTitle = 'Pengeluaran Operasional';
require_once __DIR__ . '/../../templates/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    delete('expenses', (int)$_GET['delete']);
    setFlash('success', 'Pengeluaran berhasil dihapus');
    header('Location: ' . BASE_URL . 'modules/expenses/');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'category_id' => $_POST['category_id'] ?: null,
        'date' => $_POST['date'],
        'description' => trim($_POST['description']),
        'amount' => (float)str_replace(['.', ','], ['', '.'], $_POST['amount']),
        'payment_method' => $_POST['payment_method'],
        'reference' => trim($_POST['reference']),
        'notes' => trim($_POST['notes']),
        'created_by' => $_SESSION['user_id'],
    ];

    if (!empty($_POST['id'])) {
        update('expenses', $data, (int)$_POST['id']);
        setFlash('success', 'Pengeluaran berhasil diupdate');
    } else {
        $expenseId = insert('expenses', $data);

        // Record cash transaction if payment is cash
        if ($data['payment_method'] === 'cash' && $data['amount'] > 0) {
            recordCashTransaction(
                'out',
                'Operasional',
                'Pengeluaran: ' . $data['description'],
                $data['amount'],
                'expenses',
                $expenseId
            );
        }

        setFlash('success', 'Pengeluaran berhasil ditambahkan');
    }

    header('Location: ' . BASE_URL . 'modules/expenses/');
    exit;
}

// Get expenses
$db = getDB();
$expenses = $db->query("SELECT e.*, ec.name as category_name, u.name as created_by_name
    FROM expenses e
    LEFT JOIN expense_categories ec ON e.category_id = ec.id
    LEFT JOIN users u ON e.created_by = u.id
    ORDER BY e.date DESC, e.id DESC")->fetchAll();

$categories = getAll('expense_categories', ['is_active' => 1], 'name ASC');

// Calculate total
$totalExpenses = array_sum(array_column($expenses, 'amount'));
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Pengeluaran Operasional</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Pengeluaran' => '']) ?>
    </div>
    <button class="btn btn-primary" onclick="openModal('expenseModal')">
        <i class="fas fa-plus me-2"></i>Tambah Pengeluaran
    </button>
</div>

<div class="row mb-3">
    <div class="col-12 col-4">
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($totalExpenses) ?></div>
            <div class="stat-label">Total Pengeluaran</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th class="text-end">Jumlah</th>
                        <th>Pembayaran</th>
                        <th>Created By</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada data pengeluaran</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= formatDate($expense['date']) ?></td>
                        <td><?= e($expense['category_name']) ?: '-' ?></td>
                        <td>
                            <?= e($expense['description']) ?>
                            <?php if ($expense['reference']): ?>
                            <br><small class="text-muted">Ref: <?= e($expense['reference']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-danger"><?= formatCurrency($expense['amount']) ?></td>
                        <td><?= $expense['payment_method'] == 'cash' ? 'Cash' : 'Transfer' ?></td>
                        <td><?= e($expense['created_by_name']) ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline"
                                        onclick="editExpense(<?= htmlspecialchars(json_encode($expense)) ?>)"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?= $expense['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   data-confirm="Yakin ingin menghapus pengeluaran ini?"
                                   title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-backdrop"></div>
<div class="modal" id="expenseModal">
    <div class="modal-header">
        <h5 id="modalTitle">Tambah Pengeluaran</h5>
        <button type="button" class="btn-close" onclick="closeModal('expenseModal')">&times;</button>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="expenseId">
        <div class="modal-body">
            <div class="row">
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="expenseDate" class="form-control"
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" id="expenseCategory" class="form-control form-select">
                            <option value="">-- Pilih Kategori --</option>
                            <?= selectOptions($categories) ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                <input type="text" name="description" id="expenseDesc" class="form-control" required>
            </div>
            <div class="row">
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <input type="text" name="amount" id="expenseAmount" class="form-control" required>
                    </div>
                </div>
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Pembayaran</label>
                        <select name="payment_method" id="expensePayment" class="form-control form-select">
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Referensi</label>
                <input type="text" name="reference" id="expenseRef" class="form-control"
                       placeholder="No. bukti, nota, dll">
            </div>
            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" id="expenseNotes" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('expenseModal')">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
function editExpense(exp) {
    document.getElementById('modalTitle').textContent = 'Edit Pengeluaran';
    document.getElementById('expenseId').value = exp.id;
    document.getElementById('expenseDate').value = exp.date;
    document.getElementById('expenseCategory').value = exp.category_id || '';
    document.getElementById('expenseDesc').value = exp.description;
    document.getElementById('expenseAmount').value = parseInt(exp.amount).toLocaleString('id-ID');
    document.getElementById('expensePayment').value = exp.payment_method;
    document.getElementById('expenseRef').value = exp.reference || '';
    document.getElementById('expenseNotes').value = exp.notes || '';
    openModal('expenseModal');
}

document.querySelector('[onclick*="openModal"]').addEventListener('click', function() {
    document.getElementById('modalTitle').textContent = 'Tambah Pengeluaran';
    document.getElementById('expenseId').value = '';
    document.getElementById('expenseDate').value = '<?= date('Y-m-d') ?>';
    document.getElementById('expenseCategory').value = '';
    document.getElementById('expenseDesc').value = '';
    document.getElementById('expenseAmount').value = '';
    document.getElementById('expensePayment').value = 'cash';
    document.getElementById('expenseRef').value = '';
    document.getElementById('expenseNotes').value = '';
});
</script>
SCRIPT;
?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
