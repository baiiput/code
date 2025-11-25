<?php
/**
 * Transaction Management - Laporan Keuangan Dimsum
 */
require_once 'config.php';

$db = Database::getConnection();
$branches = getBranches();
$currentUser = getCurrentUser();
$action = isset($_GET['action']) ? $_GET['action'] : 'add';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Permission check - this page is only for add/edit, need at least Editor role
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

// If accessed without valid action, redirect
if (!in_array($action, ['add', 'edit'])) {
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
    if ($action === 'edit' && $id > 0) {
        // Edit mode - single transaction
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
        // Add mode - support multiple transactions
        $transactions = isset($_POST['transactions']) ? $_POST['transactions'] : [0 => $_POST];
        $savedCount = 0;

        foreach ($transactions as $trans) {
            $branchId = (int)($trans['branch_id'] ?? $_POST['branch_id']);
            $date = $trans['transaction_date'] ?? $_POST['transaction_date'];
            $description = sanitize($trans['description'] ?? '');

            // Skip if description is empty
            if (empty($description)) continue;

            $cash = floatval($trans['cash'] ?? 0);
            $qris = floatval($trans['qris'] ?? 0);
            $transfer = floatval($trans['transfer'] ?? 0);
            $shopeeFood = floatval($trans['shopee_food'] ?? 0);
            $grabFood = floatval($trans['grab_food'] ?? 0);
            $goFood = floatval($trans['go_food'] ?? 0);
            $expenses = floatval($trans['expenses'] ?? 0);
            $expenseDesc = sanitize($trans['expense_description'] ?? '');

            $sql = "INSERT INTO transactions
                    (branch_id, transaction_date, description, cash, qris, transfer, shopee_food, grab_food, go_food, expenses, expense_description, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$branchId, $date, $description, $cash, $qris, $transfer, $shopeeFood, $grabFood, $goFood, $expenses, $expenseDesc, $currentUser['id']]);
            $savedCount++;
        }

        $_SESSION['message'] = ['type' => 'success', 'text' => "Berhasil menambahkan $savedCount transaksi!"];
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
                    <?php if (canAdd()): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="transactions.php?action=add"><i class="bi bi-plus-circle"></i> Transaksi</a>
                    </li>
                    <?php endif; ?>
                    <?php if (canManageBranches()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="branches.php"><i class="bi bi-shop"></i> Cabang</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="bi bi-bar-chart-line"></i> Laporan</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" id="themeToggle">
                        <i class="bi bi-moon-fill"></i>
                    </button>

                    <?php if (canManageUsers()): ?>
                    <a href="users.php" class="btn btn-outline-secondary btn-sm" title="Kelola Users">
                        <i class="bi bi-people"></i>
                    </a>
                    <?php endif; ?>

                    <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none p-0" data-bs-toggle="dropdown">
                            <div class="user-menu">
                                <div class="user-avatar"><?= getUserInitial($currentUser) ?></div>
                                <div class="user-info">
                                    <div class="name"><?= htmlspecialchars($currentUser['name']) ?></div>
                                    <div class="role"><?= getRoleDisplayName($currentUser['role']) ?></div>
                                </div>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text">
                                <strong><?= htmlspecialchars($currentUser['name']) ?></strong><br>
                                <small class="text-muted"><?= getRoleDisplayName($currentUser['role']) ?></small>
                            </span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-<?= $action === 'edit' ? 'pencil' : 'plus-lg' ?>"></i>
                            <?= $action === 'edit' ? 'Edit' : 'Tambah' ?> Transaksi
                        </h5>
                        <?php if ($action === 'add'): ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="multipleMode">
                            <label class="form-check-label" for="multipleMode">Input Multiple</label>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="transactionForm">
                            <?php if ($action === 'add'): ?>
                            <!-- Global Fields for Multiple Mode (hidden in single mode) -->
                            <div class="row g-3 mb-4" id="globalFields" style="display:none;">
                                <div class="col-md-6">
                                    <label class="form-label">Cabang <span class="text-danger">*</span></label>
                                    <select name="branch_id" id="globalBranch" class="form-select">
                                        <option value="">Pilih Cabang</option>
                                        <?php foreach ($branches as $branch): ?>
                                        <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="transaction_date" id="globalDate" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <!-- Transaction Forms Container -->
                            <div id="transactionsContainer"></div>

                            <!-- Add Transaction Button (only in multiple mode) -->
                            <div class="mb-3" id="addTransactionBtn" style="display:none;">
                                <button type="button" class="btn btn-outline-primary" onclick="addTransaction()">
                                    <i class="bi bi-plus-circle"></i> Tambah Transaksi Lagi
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Single Transaction Form for Edit Mode -->
                            <?php if ($action === 'edit'): ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cabang <span class="text-danger">*</span></label>
                                    <select name="branch_id" class="form-select" required>
                                        <option value="">Pilih Cabang</option>
                                        <?php foreach ($branches as $branch): ?>
                                        <option value="<?= $branch['id'] ?>" <?= $transaction['branch_id'] == $branch['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($branch['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="transaction_date" class="form-control" required value="<?= $transaction['transaction_date'] ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                    <input type="text" name="description" class="form-control" required
                                           placeholder="Contoh: Penjualan harian, Catering, dll"
                                           value="<?= htmlspecialchars($transaction['description']) ?>">
                                </div>

                                <div class="col-12"><hr><h6 class="text-success"><i class="bi bi-arrow-down-circle"></i> Pemasukan</h6></div>

                                <div class="col-md-4">
                                    <label class="form-label">Tunai</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="cash" class="form-control money-input" data-name="cash"
                                               value="<?= number_format($transaction['cash'], 0, ',', '.') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">QRIS</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="qris" class="form-control money-input" data-name="qris"
                                               value="<?= number_format($transaction['qris'], 0, ',', '.') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Transfer</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="transfer" class="form-control money-input" data-name="transfer"
                                               value="<?= number_format($transaction['transfer'], 0, ',', '.') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Shopee Food</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="shopee_food" class="form-control money-input" data-name="shopee_food"
                                               value="<?= number_format($transaction['shopee_food'], 0, ',', '.') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Grab Food</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="grab_food" class="form-control money-input" data-name="grab_food"
                                               value="<?= number_format($transaction['grab_food'], 0, ',', '.') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Go Food</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="go_food" class="form-control money-input" data-name="go_food"
                                               value="<?= number_format($transaction['go_food'], 0, ',', '.') ?>">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-success">
                                        <strong>Total Pemasukan:</strong> <span id="totalIncome">Rp 0</span>
                                    </div>
                                </div>

                                <div class="col-12"><hr><h6 class="text-danger"><i class="bi bi-arrow-up-circle"></i> Pengeluaran</h6></div>

                                <div class="col-md-6">
                                    <label class="form-label">Jumlah Pengeluaran</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="expenses" class="form-control money-input" data-name="expenses"
                                               value="<?= number_format($transaction['expenses'], 0, ',', '.') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Keterangan Pengeluaran</label>
                                    <input type="text" name="expense_description" class="form-control"
                                           placeholder="Contoh: Beli bahan baku, Bayar listrik"
                                           value="<?= htmlspecialchars($transaction['expense_description']) ?>">
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <div class="alert alert-primary">
                                        <strong>Saldo Transaksi Ini:</strong> <span id="netBalance">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="col-12 mt-3">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg"></i> Simpan
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="bi bi-x-lg"></i> Batal
                                    </a>
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
        // Format number with thousand separator (without decimal)
        function formatNumber(num) {
            const numStr = String(num).replace(/[^\d]/g, '');
            const number = parseInt(numStr) || 0;
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Parse formatted number to integer
        function parseFormattedNumber(str) {
            const cleaned = String(str).replace(/\./g, '');
            return parseInt(cleaned) || 0;
        }

        // Format Rupiah display
        function formatRupiah(num) {
            return 'Rp ' + formatNumber(num);
        }

        // Initialize money input formatting
        function initMoneyInput(input) {
            // Format on input
            input.addEventListener('input', function(e) {
                const cursorPos = this.selectionStart;
                const oldLength = this.value.length;
                const numericValue = this.value.replace(/[^\d]/g, '');
                const formatted = formatNumber(numericValue);
                this.value = formatted;

                const newLength = formatted.length;
                const diff = newLength - oldLength;
                this.setSelectionRange(cursorPos + diff, cursorPos + diff);

                // Calculate totals for this transaction
                const form = this.closest('.transaction-form');
                if (form) {
                    calculateTransactionTotal(form);
                } else {
                    calculateTotals();
                }
            });

            // Format on blur
            input.addEventListener('blur', function() {
                if (this.value === '' || this.value === '0') {
                    this.value = '0';
                }
            });

            // Select all on focus
            input.addEventListener('focus', function() {
                this.select();
            });
        }

        // Calculate totals for a specific transaction form
        function calculateTransactionTotal(form) {
            const moneyInputs = form.querySelectorAll('.money-input');
            let totalIncome = 0;
            let totalExpenses = 0;

            moneyInputs.forEach(input => {
                const value = parseFormattedNumber(input.value);
                const name = input.getAttribute('data-name');

                if (name === 'expenses') {
                    totalExpenses = value;
                } else {
                    totalIncome += value;
                }
            });

            const netBalance = totalIncome - totalExpenses;

            const incomeEl = form.querySelector('.total-income');
            const balanceEl = form.querySelector('.net-balance');

            if (incomeEl) incomeEl.textContent = formatRupiah(totalIncome);
            if (balanceEl) {
                balanceEl.textContent = formatRupiah(netBalance);
                balanceEl.className = 'net-balance ' + (netBalance >= 0 ? 'text-success' : 'text-danger');
            }
        }

        // Calculate totals (for edit mode)
        function calculateTotals() {
            const moneyInputs = document.querySelectorAll('.money-input');
            let totalIncome = 0;
            let totalExpenses = 0;

            moneyInputs.forEach(input => {
                const value = parseFormattedNumber(input.value);
                const name = input.getAttribute('data-name');

                if (name === 'expenses') {
                    totalExpenses = value;
                } else {
                    totalIncome += value;
                }
            });

            const netBalance = totalIncome - totalExpenses;

            const incomeEl = document.getElementById('totalIncome');
            const balanceEl = document.getElementById('netBalance');

            if (incomeEl) incomeEl.textContent = formatRupiah(totalIncome);
            if (balanceEl) {
                balanceEl.textContent = formatRupiah(netBalance);
                balanceEl.className = netBalance >= 0 ? 'text-success' : 'text-danger';
            }
        }

        <?php if ($action === 'add'): ?>
        // Multiple transaction mode variables
        let transactionCount = 0;
        let isMultipleMode = false;

        // Transaction form template
        function getTransactionFormTemplate(index) {
            const prefix = isMultipleMode ? `transactions[${index}]` : '';
            const nameAttr = (field) => isMultipleMode ? `transactions[${index}][${field}]` : field;

            return `
                <div class="transaction-form mb-4 p-3 border rounded" data-index="${index}">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bi bi-receipt"></i> <span class="form-title">${isMultipleMode ? 'Transaksi #' + (index + 1) : 'Transaksi'}</span></h6>
                                ${isMultipleMode && index > 0 ? `<button type="button" class="btn btn-sm btn-outline-danger remove-transaction" onclick="removeTransaction(${index})"><i class="bi bi-trash"></i> Hapus</button>` : ''}
                            </div>
                            <hr>
                        </div>

                        ${!isMultipleMode ? `
                        <div class="col-md-6">
                            <label class="form-label">Cabang <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Pilih Cabang</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        ` : ''}

                        <div class="col-12">
                            <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <input type="text" name="${nameAttr('description')}" class="form-control" required placeholder="Contoh: Penjualan harian, Catering, dll">
                        </div>

                        <div class="col-12"><hr><h6 class="text-success"><i class="bi bi-arrow-down-circle"></i> Pemasukan</h6></div>

                        <div class="col-md-4">
                            <label class="form-label">Tunai</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="${nameAttr('cash')}" class="form-control money-input" data-name="cash" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">QRIS</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="${nameAttr('qris')}" class="form-control money-input" data-name="qris" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Transfer</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="${nameAttr('transfer')}" class="form-control money-input" data-name="transfer" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shopee Food</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="${nameAttr('shopee_food')}" class="form-control money-input" data-name="shopee_food" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Grab Food</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="${nameAttr('grab_food')}" class="form-control money-input" data-name="grab_food" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Go Food</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="${nameAttr('go_food')}" class="form-control money-input" data-name="go_food" value="0">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-success">
                                <strong>Total Pemasukan:</strong> <span class="total-income">Rp 0</span>
                            </div>
                        </div>

                        <div class="col-12"><hr><h6 class="text-danger"><i class="bi bi-arrow-up-circle"></i> Pengeluaran</h6></div>

                        <div class="col-md-6">
                            <label class="form-label">Jumlah Pengeluaran</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="${nameAttr('expenses')}" class="form-control money-input" data-name="expenses" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Keterangan Pengeluaran</label>
                            <input type="text" name="${nameAttr('expense_description')}" class="form-control" placeholder="Contoh: Beli bahan baku, Bayar listrik">
                        </div>

                        <div class="col-12">
                            <hr>
                            <div class="alert alert-primary">
                                <strong>Saldo Transaksi Ini:</strong> <span class="net-balance">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Add new transaction form
        function addTransaction() {
            const container = document.getElementById('transactionsContainer');
            const template = getTransactionFormTemplate(transactionCount);
            container.insertAdjacentHTML('beforeend', template);

            // Initialize money inputs for the new form
            const newForm = container.querySelector(`[data-index="${transactionCount}"]`);
            newForm.querySelectorAll('.money-input').forEach(input => {
                initMoneyInput(input);
            });

            calculateTransactionTotal(newForm);
            transactionCount++;
        }

        // Remove transaction form
        function removeTransaction(index) {
            const form = document.querySelector(`[data-index="${index}"]`);
            if (form) {
                form.remove();
            }
        }

        // Toggle multiple mode
        const multipleModeToggle = document.getElementById('multipleMode');
        if (multipleModeToggle) {
            multipleModeToggle.addEventListener('change', function() {
                isMultipleMode = this.checked;
                const globalFields = document.getElementById('globalFields');
                const addBtn = document.getElementById('addTransactionBtn');
                const container = document.getElementById('transactionsContainer');

                // Clear container
                container.innerHTML = '';
                transactionCount = 0;

                if (isMultipleMode) {
                    // Show global fields and add button
                    globalFields.style.display = '';
                    globalFields.querySelectorAll('select, input').forEach(el => el.required = true);
                    addBtn.style.display = '';

                    // Add first transaction form
                    addTransaction();
                } else {
                    // Hide global fields and add button
                    globalFields.style.display = 'none';
                    globalFields.querySelectorAll('select, input').forEach(el => el.required = false);
                    addBtn.style.display = 'none';

                    // Add single transaction form
                    addTransaction();
                }
            });

            // Initialize with single mode
            addTransaction();
        }
        <?php else: ?>
        // Edit mode - initialize existing inputs
        document.querySelectorAll('.money-input').forEach(input => {
            initMoneyInput(input);
        });
        calculateTotals();
        <?php endif; ?>

        // Convert formatted values back to numbers before submit
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            document.querySelectorAll('.money-input').forEach(input => {
                const numericValue = parseFormattedNumber(input.value);
                input.value = numericValue;
            });
        });

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
