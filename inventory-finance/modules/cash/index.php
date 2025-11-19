<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Handle delete (admin only)
if (isset($_GET['delete']) && hasRole(['admin'])) {
    $id = (int)$_GET['delete'];

    // Get transaction to recalculate balance
    $trx = getById('cash_transactions', $id);
    if ($trx) {
        // Delete the transaction
        delete('cash_transactions', $id);

        // Recalculate all balances after this transaction
        recalculateCashBalances();

        setFlash('success', 'Transaksi kas berhasil dihapus');
    }

    header('Location: ' . BASE_URL . 'modules/cash/');
    exit;
}

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $type = $_POST['type'];
    $amount = (float)str_replace(['.', ','], ['', '.'], $_POST['amount']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $date = $_POST['date'] ?? date('Y-m-d');
    $editId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($amount > 0 && !empty($description)) {
        if ($editId > 0 && hasRole(['admin'])) {
            // Update existing transaction
            $db = getDB();
            $db->prepare("UPDATE cash_transactions SET date = ?, type = ?, category = ?, description = ?, amount = ? WHERE id = ?")
               ->execute([$date, $type, $category, $description, $amount, $editId]);

            // Recalculate all balances
            recalculateCashBalances();

            setFlash('success', 'Transaksi kas berhasil diupdate');
        } else {
            // Insert new transaction
            recordCashTransaction($type, $category, $description, $amount, null, null, $date);
            setFlash('success', 'Transaksi kas berhasil dicatat');
        }
    } else {
        setFlash('danger', 'Jumlah dan deskripsi harus diisi');
    }

    header('Location: ' . BASE_URL . 'modules/cash/');
    exit;
}

$pageTitle = 'Buku Kas';
require_once __DIR__ . '/../../templates/header.php';

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Get data
$currentBalance = getCurrentCashBalance();
$transactions = getCashTransactions($startDate, $endDate, 200);
$summary = getCashSummary($startDate, $endDate);
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Buku Kas</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Buku Kas' => '']) ?>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus me-2"></i>Tambah Transaksi
    </button>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-6 col-3">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($currentBalance) ?></div>
            <div class="stat-label">Saldo Saat Ini</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($summary['total_in']) ?></div>
            <div class="stat-label">Total Pemasukan</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($summary['total_out']) ?></div>
            <div class="stat-label">Total Pengeluaran</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($summary['total_in'] - $summary['total_out']) ?></div>
            <div class="stat-label">Selisih Periode</div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-3 align-items-center">
            <div class="form-group mb-0">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
            </div>
            <div class="form-group mb-0" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Transaction History -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-list me-2"></i>Riwayat Transaksi</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th width="100">Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th class="text-end">Pemasukan</th>
                        <th class="text-end">Pengeluaran</th>
                        <th class="text-end">Saldo</th>
                        <?php if (hasRole(['admin'])): ?>
                        <th width="100">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="<?= hasRole(['admin']) ? '7' : '6' ?>" class="text-center text-muted">
                            Belum ada transaksi. Mulai dengan menambahkan modal awal.
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($transactions as $trx): ?>
                    <tr>
                        <td><?= formatDate($trx['date']) ?></td>
                        <td>
                            <?= e($trx['description']) ?>
                            <?php if ($trx['reference_type']): ?>
                            <br><small class="text-muted"><?= e($trx['reference_type']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= e($trx['category']) ?></span></td>
                        <td class="text-end text-success">
                            <?= $trx['type'] === 'in' ? formatCurrency($trx['amount']) : '-' ?>
                        </td>
                        <td class="text-end text-danger">
                            <?= $trx['type'] === 'out' ? formatCurrency($trx['amount']) : '-' ?>
                        </td>
                        <td class="text-end fw-bold"><?= formatCurrency($trx['balance']) ?></td>
                        <?php if (hasRole(['admin'])): ?>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline"
                                        onclick="editTransaction(<?= htmlspecialchars(json_encode($trx)) ?>)"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?= $trx['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   data-confirm="Yakin ingin menghapus transaksi ini? Saldo akan dihitung ulang."
                                   title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Transaction -->
<div class="modal-backdrop"></div>
<div class="modal" id="cashModal">
    <div class="modal-header">
        <h5 id="modalTitle">Tambah Transaksi Kas</h5>
        <button type="button" class="btn-close" onclick="closeModal('cashModal')">&times;</button>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="trxId">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" name="date" id="trxDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Tipe Transaksi <span class="text-danger">*</span></label>
                <select name="type" id="trxType" class="form-control form-select" required>
                    <option value="in">Pemasukan (Kas Masuk)</option>
                    <option value="out">Pengeluaran (Kas Keluar)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                <select name="category" id="trxCategory" class="form-control form-select" required>
                    <option value="Modal">Modal / Saldo Awal</option>
                    <option value="Penjualan">Penjualan</option>
                    <option value="Pembelian">Pembelian Barang</option>
                    <option value="Operasional">Operasional</option>
                    <option value="Gaji">Gaji</option>
                    <option value="Terima Piutang">Terima Piutang</option>
                    <option value="Bayar Hutang">Bayar Hutang</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                <input type="text" name="description" id="trxDesc" class="form-control" required
                       placeholder="Contoh: Modal awal usaha">
            </div>

            <div class="form-group">
                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                <input type="text" name="amount" id="trxAmount" class="form-control" required
                       placeholder="0">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('cashModal')">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Transaksi Kas';
    document.getElementById('trxId').value = '';
    document.getElementById('trxDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('trxType').value = 'in';
    document.getElementById('trxCategory').value = 'Modal';
    document.getElementById('trxDesc').value = '';
    document.getElementById('trxAmount').value = '';
    openModal('cashModal');
}

function editTransaction(trx) {
    document.getElementById('modalTitle').textContent = 'Edit Transaksi Kas';
    document.getElementById('trxId').value = trx.id;
    document.getElementById('trxDate').value = trx.date;
    document.getElementById('trxType').value = trx.type;
    document.getElementById('trxCategory').value = trx.category;
    document.getElementById('trxDesc').value = trx.description;
    document.getElementById('trxAmount').value = parseInt(trx.amount).toLocaleString('id-ID');
    openModal('cashModal');
}
</script>
SCRIPT;
?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
