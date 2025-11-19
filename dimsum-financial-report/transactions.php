<?php
/**
 * Transaction Management - Laporan Keuangan Dimsum
 */
require_once 'config.php';

$db = Database::getConnection();
$branches = getBranches();
$currentUser = getCurrentUser();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Permission check
if ($action === 'add' && !canAdd()) {
    $_SESSION['error'] = 'Anda harus login sebagai Editor atau Admin untuk menambah transaksi.';
    header('Location: login.php');
    exit;
}

if ($action === 'edit' && !canEdit()) {
    $_SESSION['error'] = 'Hanya Admin yang dapat mengedit transaksi.';
    header('Location: index.php');
    exit;
}

$transaction = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
    $transaction = $stmt->fetch();
    if (!$transaction) {
        header('Location: index.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branchId = (int)$_POST['branch_id'];
    $date = $_POST['transaction_date'];
    $description = sanitize($_POST['description']);
    $cash = floatval($_POST['cash'] ?? 0);
    $qris = floatval($_POST['qris'] ?? 0);
    $transfer = floatval($_POST['transfer'] ?? 0);
    $shopeeFood = floatval($_POST['shopee_food'] ?? 0);
    $grabFood = floatval($_POST['grab_food'] ?? 0);
    $goFood = floatval($_POST['go_food'] ?? 0);
    $expenses = floatval($_POST['expenses'] ?? 0);
    $expenseDesc = sanitize($_POST['expense_description'] ?? '');

    if ($action === 'edit' && $id > 0) {
        $sql = "UPDATE transactions SET
                branch_id = ?, transaction_date = ?, description = ?,
                cash = ?, qris = ?, transfer = ?,
                shopee_food = ?, grab_food = ?, go_food = ?,
                expenses = ?, expense_description = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$branchId, $date, $description, $cash, $qris, $transfer, $shopeeFood, $grabFood, $goFood, $expenses, $expenseDesc, $id]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Transaksi berhasil diperbarui!'];
    } else {
        $sql = "INSERT INTO transactions
                (branch_id, transaction_date, description, cash, qris, transfer, shopee_food, grab_food, go_food, expenses, expense_description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$branchId, $date, $description, $cash, $qris, $transfer, $shopeeFood, $grabFood, $goFood, $expenses, $expenseDesc]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Transaksi berhasil ditambahkan!'];
    }

    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title><?= $action === 'edit' ? 'Edit' : 'Tambah' ?> Transaksi - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cup-hot-fill"></i> <?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="transactions.php"><i class="bi bi-journal-text"></i> Transaksi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="branches.php"><i class="bi bi-shop"></i> Cabang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary btn-sm" id="themeToggle">
                        <i class="bi bi-moon-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-<?= $action === 'edit' ? 'pencil' : 'plus-lg' ?>"></i>
                            <?= $action === 'edit' ? 'Edit' : 'Tambah' ?> Transaksi
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="transactionForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cabang <span class="text-danger">*</span></label>
                                    <select name="branch_id" class="form-select" required>
                                        <option value="">Pilih Cabang</option>
                                        <?php foreach ($branches as $branch): ?>
                                        <option value="<?= $branch['id'] ?>" <?= ($transaction && $transaction['branch_id'] == $branch['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($branch['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="transaction_date" class="form-control" required
                                           value="<?= $transaction ? $transaction['transaction_date'] : date('Y-m-d') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                    <input type="text" name="description" class="form-control" required
                                           placeholder="Contoh: Penjualan harian, Catering, dll"
                                           value="<?= $transaction ? htmlspecialchars($transaction['description']) : '' ?>">
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <h6 class="text-success"><i class="bi bi-arrow-down-circle"></i> Pemasukan</h6>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Tunai</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="cash" class="form-control income-input" min="0" step="1000"
                                               value="<?= $transaction ? $transaction['cash'] : '0' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">QRIS</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="qris" class="form-control income-input" min="0" step="1000"
                                               value="<?= $transaction ? $transaction['qris'] : '0' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Transfer</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="transfer" class="form-control income-input" min="0" step="1000"
                                               value="<?= $transaction ? $transaction['transfer'] : '0' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Shopee Food</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="shopee_food" class="form-control income-input" min="0" step="1000"
                                               value="<?= $transaction ? $transaction['shopee_food'] : '0' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Grab Food</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="grab_food" class="form-control income-input" min="0" step="1000"
                                               value="<?= $transaction ? $transaction['grab_food'] : '0' ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Go Food</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="go_food" class="form-control income-input" min="0" step="1000"
                                               value="<?= $transaction ? $transaction['go_food'] : '0' ?>">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-success">
                                        <strong>Total Pemasukan:</strong> <span id="totalIncome">Rp 0</span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <h6 class="text-danger"><i class="bi bi-arrow-up-circle"></i> Pengeluaran</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Jumlah Pengeluaran</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="expenses" class="form-control" id="expenseInput" min="0" step="1000"
                                               value="<?= $transaction ? $transaction['expenses'] : '0' ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Keterangan Pengeluaran</label>
                                    <input type="text" name="expense_description" class="form-control"
                                           placeholder="Contoh: Beli bahan baku, Bayar listrik"
                                           value="<?= $transaction ? htmlspecialchars($transaction['expense_description']) : '' ?>">
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <div class="alert alert-primary">
                                        <strong>Saldo Transaksi Ini:</strong> <span id="netBalance">Rp 0</span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-lg"></i> Simpan
                                        </button>
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="bi bi-x-lg"></i> Batal
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate totals
        function calculateTotals() {
            const incomeInputs = document.querySelectorAll('.income-input');
            let totalIncome = 0;
            incomeInputs.forEach(input => {
                totalIncome += parseFloat(input.value) || 0;
            });

            const expenses = parseFloat(document.getElementById('expenseInput').value) || 0;
            const netBalance = totalIncome - expenses;

            document.getElementById('totalIncome').textContent = formatRupiah(totalIncome);
            document.getElementById('netBalance').textContent = formatRupiah(netBalance);

            // Update color based on balance
            const balanceEl = document.getElementById('netBalance');
            balanceEl.className = netBalance >= 0 ? 'text-success' : 'text-danger';
        }

        function formatRupiah(num) {
            return 'Rp ' + num.toLocaleString('id-ID');
        }

        // Add event listeners
        document.querySelectorAll('.income-input, #expenseInput').forEach(input => {
            input.addEventListener('input', calculateTotals);
        });

        // Initial calculation
        calculateTotals();

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'light' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
        }
    </script>
</body>
</html>
