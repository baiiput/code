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
                <div class="transaction-form mb-3 border rounded" data-index="${index}">
                    <div class="card">
                        <div class="card-header bg-light cursor-pointer" onclick="toggleTransactionForm(${index})">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-receipt"></i>
                                    <span class="form-title">${isMultipleMode ? 'Transaksi #' + (index + 1) : 'Transaksi'}</span>
                                    <i class="bi bi-chevron-down collapse-icon ms-2" id="collapseIcon${index}"></i>
                                </h6>
                                <div onclick="event.stopPropagation()">
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="clearTransaction(${index})" title="Clear Form">
                                        <i class="bi bi-eraser"></i> Clear
                                    </button>
                                    ${isMultipleMode && index > 0 ? `<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTransaction(${index})"><i class="bi bi-trash"></i> Hapus</button>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="card-body transaction-form-body" id="formBody${index}" style="display: ${index === 0 ? 'block' : 'none'};">
                            <div class="row g-3">

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
                    </div>
                </div>
            `;
        }

        // Toggle transaction form collapse
        function toggleTransactionForm(index) {
            const formBody = document.getElementById(`formBody${index}`);
            const icon = document.getElementById(`collapseIcon${index}`);

            if (!formBody) return;

            if (formBody.style.display === 'none') {
                // Collapse all other forms in multiple mode
                if (isMultipleMode) {
                    document.querySelectorAll('.transaction-form-body').forEach((body, idx) => {
                        body.style.display = 'none';
                        const otherIcon = document.getElementById(`collapseIcon${idx}`);
                        if (otherIcon) {
                            otherIcon.className = 'bi bi-chevron-down collapse-icon ms-2';
                        }
                    });
                }
                // Expand this form
                formBody.style.display = 'block';
                icon.className = 'bi bi-chevron-up collapse-icon ms-2';
            } else {
                // Collapse this form
                formBody.style.display = 'none';
                icon.className = 'bi bi-chevron-down collapse-icon ms-2';
            }
        }

        // Clear transaction form
        function clearTransaction(index) {
            const form = document.querySelector(`[data-index="${index}"]`);
            if (!form) return;

            if (confirm('Clear semua data di form ini?')) {
                // Clear all inputs
                form.querySelectorAll('input[type="text"]').forEach(input => {
                    if (input.classList.contains('money-input')) {
                        input.value = '0';
                    } else {
                        input.value = '';
                    }
                });

                // Reset select if in single mode
                if (!isMultipleMode) {
                    const select = form.querySelector('select[name="branch_id"]');
                    if (select) select.value = '';

                    const dateInput = form.querySelector('input[type="date"]');
                    if (dateInput) dateInput.value = '<?= date('Y-m-d') ?>';
                }

                // Recalculate totals
                calculateTransactionTotal(form);
                saveDraft();
            }
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

            // Add event listeners for autosave
            newForm.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('change', saveDraft);
                input.addEventListener('input', saveDraft);
            });

            calculateTransactionTotal(newForm);
            transactionCount++;
            saveDraft();
        }

        // Remove transaction form
        function removeTransaction(index) {
            const form = document.querySelector(`[data-index="${index}"]`);
            if (form && confirm('Hapus transaksi ini?')) {
                form.remove();
                saveDraft();
            }
        }

        // Save draft to localStorage
        function saveDraft() {
            const draft = {
                mode: isMultipleMode ? 'multiple' : 'single',
                timestamp: Date.now(),
                branch_id: '',
                transaction_date: '',
                transactions: []
            };

            if (isMultipleMode) {
                // Multiple mode: save global fields and all transactions
                draft.branch_id = document.getElementById('globalBranch')?.value || '';
                draft.transaction_date = document.getElementById('globalDate')?.value || '';

                document.querySelectorAll('.transaction-form').forEach(form => {
                    const index = form.getAttribute('data-index');
                    const transaction = {
                        description: form.querySelector(`input[name="transactions[${index}][description]"]`)?.value || '',
                        cash: form.querySelector(`input[name="transactions[${index}][cash]"]`)?.value || '0',
                        qris: form.querySelector(`input[name="transactions[${index}][qris]"]`)?.value || '0',
                        transfer: form.querySelector(`input[name="transactions[${index}][transfer]"]`)?.value || '0',
                        shopee_food: form.querySelector(`input[name="transactions[${index}][shopee_food]"]`)?.value || '0',
                        grab_food: form.querySelector(`input[name="transactions[${index}][grab_food]"]`)?.value || '0',
                        go_food: form.querySelector(`input[name="transactions[${index}][go_food]"]`)?.value || '0',
                        expenses: form.querySelector(`input[name="transactions[${index}][expenses]"]`)?.value || '0',
                        expense_description: form.querySelector(`input[name="transactions[${index}][expense_description]"]`)?.value || ''
                    };
                    draft.transactions.push(transaction);
                });
            } else {
                // Single mode: save the one transaction form
                const form = document.querySelector('.transaction-form');
                if (form) {
                    draft.branch_id = form.querySelector('select[name="branch_id"]')?.value || '';
                    draft.transaction_date = form.querySelector('input[name="transaction_date"]')?.value || '';

                    const transaction = {
                        description: form.querySelector('input[name="description"]')?.value || '',
                        cash: form.querySelector('input[name="cash"]')?.value || '0',
                        qris: form.querySelector('input[name="qris"]')?.value || '0',
                        transfer: form.querySelector('input[name="transfer"]')?.value || '0',
                        shopee_food: form.querySelector('input[name="shopee_food"]')?.value || '0',
                        grab_food: form.querySelector('input[name="grab_food"]')?.value || '0',
                        go_food: form.querySelector('input[name="go_food"]')?.value || '0',
                        expenses: form.querySelector('input[name="expenses"]')?.value || '0',
                        expense_description: form.querySelector('input[name="expense_description"]')?.value || ''
                    };
                    draft.transactions.push(transaction);
                }
            }

            localStorage.setItem('transactionDraft', JSON.stringify(draft));
        }

        // Load draft from localStorage
        function loadDraft() {
            const draftJson = localStorage.getItem('transactionDraft');
            if (!draftJson) return false;

            try {
                const draft = JSON.parse(draftJson);

                // Check if draft has any meaningful data
                const hasData = draft.transactions.some(trans =>
                    trans.description ||
                    (trans.cash && trans.cash !== '0') ||
                    (trans.qris && trans.qris !== '0') ||
                    (trans.transfer && trans.transfer !== '0') ||
                    (trans.shopee_food && trans.shopee_food !== '0') ||
                    (trans.grab_food && trans.grab_food !== '0') ||
                    (trans.go_food && trans.go_food !== '0') ||
                    (trans.expenses && trans.expenses !== '0')
                );

                if (!hasData) return false;

                // If draft mode doesn't match current mode, return false
                const draftMode = draft.mode || 'single';
                const currentMode = isMultipleMode ? 'multiple' : 'single';
                if (draftMode !== currentMode) return false;

                if (isMultipleMode) {
                    // Set global fields
                    const globalBranch = document.getElementById('globalBranch');
                    const globalDate = document.getElementById('globalDate');
                    if (globalBranch) globalBranch.value = draft.branch_id;
                    if (globalDate) globalDate.value = draft.transaction_date;

                    // Load each transaction
                    draft.transactions.forEach((trans, idx) => {
                        if (idx > 0) {
                            addTransaction();
                        }

                        const form = document.querySelector(`[data-index="${idx}"]`);
                        if (!form) return;

                        const setInputValue = (name, value) => {
                            const input = form.querySelector(`input[name="transactions[${idx}][${name}]"]`);
                            if (input) input.value = value;
                        };

                        setInputValue('description', trans.description);
                        setInputValue('cash', trans.cash);
                        setInputValue('qris', trans.qris);
                        setInputValue('transfer', trans.transfer);
                        setInputValue('shopee_food', trans.shopee_food);
                        setInputValue('grab_food', trans.grab_food);
                        setInputValue('go_food', trans.go_food);
                        setInputValue('expenses', trans.expenses);
                        setInputValue('expense_description', trans.expense_description);

                        calculateTransactionTotal(form);
                    });
                } else {
                    // Single mode: load into the one form
                    const form = document.querySelector('.transaction-form');
                    if (form && draft.transactions.length > 0) {
                        const trans = draft.transactions[0];

                        const branchSelect = form.querySelector('select[name="branch_id"]');
                        if (branchSelect) branchSelect.value = draft.branch_id;

                        const dateInput = form.querySelector('input[name="transaction_date"]');
                        if (dateInput) dateInput.value = draft.transaction_date;

                        const setInputValue = (name, value) => {
                            const input = form.querySelector(`input[name="${name}"]`);
                            if (input) input.value = value;
                        };

                        setInputValue('description', trans.description);
                        setInputValue('cash', trans.cash);
                        setInputValue('qris', trans.qris);
                        setInputValue('transfer', trans.transfer);
                        setInputValue('shopee_food', trans.shopee_food);
                        setInputValue('grab_food', trans.grab_food);
                        setInputValue('go_food', trans.go_food);
                        setInputValue('expenses', trans.expenses);
                        setInputValue('expense_description', trans.expense_description);

                        calculateTransactionTotal(form);
                    }
                }

                return true;
            } catch (e) {
                console.error('Error loading draft:', e);
                return false;
            }
        }

        // Clear draft from localStorage
        function clearDraft() {
            localStorage.removeItem('transactionDraft');
        }

        // Show draft notification
        function showDraftNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show';
            notification.style.position = 'fixed';
            notification.style.top = '80px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.maxWidth = '400px';
            notification.innerHTML = `
                <i class="bi bi-info-circle"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 4000);
        }

        // Toggle multiple mode
        const multipleModeToggle = document.getElementById('multipleMode');
        if (multipleModeToggle) {
            multipleModeToggle.addEventListener('change', function() {
                isMultipleMode = this.checked;
                const globalFields = document.getElementById('globalFields');
                const addBtn = document.getElementById('addTransactionBtn');
                const container = document.getElementById('transactionsContainer');

                // Fade out animation
                container.style.opacity = '0';
                container.style.transition = 'opacity 0.3s ease';

                setTimeout(() => {
                    // Clear container
                    container.innerHTML = '';
                    transactionCount = 0;

                    if (isMultipleMode) {
                        // Show global fields and add button with animation
                        globalFields.style.display = '';
                        globalFields.style.opacity = '0';
                        globalFields.style.transition = 'opacity 0.3s ease';
                        globalFields.querySelectorAll('select, input').forEach(el => el.required = true);

                        addBtn.style.display = '';
                        addBtn.style.opacity = '0';
                        addBtn.style.transition = 'opacity 0.3s ease';

                        // Add first transaction form
                        addTransaction();

                        // Try to load draft (only if mode matches)
                        const draftLoaded = loadDraft();

                        // Fade in animations
                        setTimeout(() => {
                            globalFields.style.opacity = '1';
                            addBtn.style.opacity = '1';
                        }, 50);

                    } else {
                        // Hide global fields and add button
                        globalFields.style.display = 'none';
                        globalFields.querySelectorAll('select, input').forEach(el => el.required = false);
                        addBtn.style.display = 'none';

                        // Add single transaction form
                        addTransaction();

                        // Try to load draft (only if mode matches)
                        const draftLoaded = loadDraft();
                    }

                    // Fade in container
                    setTimeout(() => {
                        container.style.opacity = '1';
                    }, 50);
                }, 300);
            });

            // Initialize with single mode and try to restore draft
            addTransaction();

            // Try to restore draft on page load
            setTimeout(() => {
                const draftLoaded = loadDraft();
                if (draftLoaded) {
                    showDraftNotification('Draft berhasil dimuat! Data Anda telah dipulihkan.');
                }
            }, 100);

            // Add autosave listener for global fields
            document.getElementById('globalBranch')?.addEventListener('change', saveDraft);
            document.getElementById('globalDate')?.addEventListener('change', saveDraft);
        }
        <?php else: ?>
        // Edit mode - initialize existing inputs
        document.querySelectorAll('.money-input').forEach(input => {
            initMoneyInput(input);
        });
        calculateTotals();
        <?php endif; ?>

        // Convert formatted values back to numbers before submit and clear draft
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            document.querySelectorAll('.money-input').forEach(input => {
                const numericValue = parseFormattedNumber(input.value);
                input.value = numericValue;
            });
            <?php if ($action === 'add'): ?>
            clearDraft();
            <?php endif; ?>
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
