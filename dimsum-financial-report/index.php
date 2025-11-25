<?php
/**
 * Dashboard - Laporan Keuangan Dimsum
 */
require_once 'config.php';

$db = Database::getConnection();
$branches = getBranches();
$currentUser = getCurrentUser();

// Filter parameters
$branchId = isset($_GET['branch']) ? (int)$_GET['branch'] : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build query
$whereConditions = ["t.transaction_date BETWEEN :start_date AND :end_date"];
$params = [':start_date' => $startDate, ':end_date' => $endDate];

if ($branchId > 0) {
    $whereConditions[] = "t.branch_id = :branch_id";
    $params[':branch_id'] = $branchId;
}

$whereClause = implode(' AND ', $whereConditions);

// Get transactions
$sql = "SELECT t.*, b.name as branch_name
        FROM transactions t
        JOIN branches b ON t.branch_id = b.id
        WHERE $whereClause
        ORDER BY t.transaction_date DESC, t.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate totals
$totalCash = $totalQris = $totalTransfer = $totalShopee = $totalGrab = $totalGoFood = $totalExpenses = 0;
foreach ($transactions as $t) {
    $totalCash += $t['cash'];
    $totalQris += $t['qris'];
    $totalTransfer += $t['transfer'];
    $totalShopee += $t['shopee_food'];
    $totalGrab += $t['grab_food'];
    $totalGoFood += $t['go_food'];
    $totalExpenses += $t['expenses'];
}
$totalIncome = $totalCash + $totalQris + $totalTransfer + $totalShopee + $totalGrab + $totalGoFood;
$netProfit = $totalIncome - $totalExpenses;

// Get summary by branch
$branchSummary = [];
if ($branchId == 0) {
    $sqlSummary = "SELECT b.name,
                   SUM(t.cash + t.qris + t.transfer + t.shopee_food + t.grab_food + t.go_food) as total_income,
                   SUM(t.expenses) as total_expenses
                   FROM transactions t
                   JOIN branches b ON t.branch_id = b.id
                   WHERE t.transaction_date BETWEEN :start_date AND :end_date
                   GROUP BY b.id, b.name";
    $stmtSummary = $db->prepare($sqlSummary);
    $stmtSummary->execute([':start_date' => $startDate, ':end_date' => $endDate]);
    $branchSummary = $stmtSummary->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="googlebot" content="noindex, nofollow">
    <title><?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <?php if (canAdd()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php?action=add"><i class="bi bi-plus-circle"></i> Transaksi</a>
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

    <div class="container-fluid py-4">
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
            <?= $_SESSION['message']['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); endif; ?>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Cabang</label>
                        <select name="branch" class="form-select">
                            <option value="0">Semua Cabang</option>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>" <?= $branchId == $branch['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($branch['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card income">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Total Pemasukan</h6>
                                <h3><?= formatRupiah($totalIncome) ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-arrow-down-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card expense">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Total Pengeluaran</h6>
                                <h3><?= formatRupiah($totalExpenses) ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card <?= $netProfit >= 0 ? 'profit' : 'loss' ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Saldo / Laba Bersih</h6>
                                <h3><?= formatRupiah($netProfit) ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-wallet2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Income Breakdown -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-pie-chart"></i> Rincian Pemasukan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <canvas id="incomeChart"></canvas>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tbody>
                                        <tr>
                                            <td><span class="badge bg-success">Tunai</span></td>
                                            <td class="text-end"><?= formatRupiah($totalCash) ?></td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-primary">QRIS</span></td>
                                            <td class="text-end"><?= formatRupiah($totalQris) ?></td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-info">Transfer</span></td>
                                            <td class="text-end"><?= formatRupiah($totalTransfer) ?></td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-warning">Shopee Food</span></td>
                                            <td class="text-end"><?= formatRupiah($totalShopee) ?></td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-danger">Grab Food</span></td>
                                            <td class="text-end"><?= formatRupiah($totalGrab) ?></td>
                                        </tr>
                                        <tr>
                                            <td><span class="badge bg-secondary">Go Food</span></td>
                                            <td class="text-end"><?= formatRupiah($totalGoFood) ?></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-semibold">
                                            <td>Total</td>
                                            <td class="text-end"><?= formatRupiah($totalIncome) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5><i class="bi bi-building"></i> Per Cabang</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($branchSummary)): ?>
                        <canvas id="branchChart"></canvas>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-bar-chart"></i>
                            <p>Pilih "Semua Cabang" untuk melihat perbandingan</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5><i class="bi bi-table"></i> Data Transaksi</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (canAdd()): ?>
                    <a href="transactions.php?action=add" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Tambah
                    </a>
                    <a href="import.php" class="btn btn-info btn-sm">
                        <i class="bi bi-file-earmark-arrow-up"></i> Import CSV
                    </a>
                    <?php endif; ?>
                    <button class="btn btn-success btn-sm" onclick="exportExcel()">
                        <i class="bi bi-file-earmark-excel"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="transactionTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th width="30"></th>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Cabang</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Pemasukan</th>
                                <th class="text-end">Pengeluaran</th>
                                <th class="text-end">Saldo</th>
                                <?php if (canEdit() || canDelete()): ?>
                                <th>Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $runningBalance = 0;
                            $reversedTransactions = array_reverse($transactions);
                            $balances = [];
                            foreach ($reversedTransactions as $t) {
                                $income = $t['cash'] + $t['qris'] + $t['transfer'] + $t['shopee_food'] + $t['grab_food'] + $t['go_food'];
                                $runningBalance += $income - $t['expenses'];
                                $balances[$t['id']] = $runningBalance;
                            }

                            foreach ($transactions as $t):
                                $income = $t['cash'] + $t['qris'] + $t['transfer'] + $t['shopee_food'] + $t['grab_food'] + $t['go_food'];
                                $hasDetails = $t['cash'] > 0 || $t['qris'] > 0 || $t['transfer'] > 0 || $t['shopee_food'] > 0 || $t['grab_food'] > 0 || $t['go_food'] > 0;

                                // Prepare detail data for JavaScript
                                $detailData = [
                                    'cash' => $t['cash'],
                                    'qris' => $t['qris'],
                                    'transfer' => $t['transfer'],
                                    'shopee_food' => $t['shopee_food'],
                                    'grab_food' => $t['grab_food'],
                                    'go_food' => $t['go_food']
                                ];
                            ?>
                            <!-- Main Row -->
                            <tr class="main-row" data-detail='<?= $hasDetails ? json_encode($detailData) : '' ?>' data-has-details="<?= $hasDetails ? '1' : '0' ?>">
                                <td class="dt-control">
                                    <?php if ($hasDetails): ?>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?= $no++ ?></td>
                                <td><?= formatDate($t['transaction_date']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($t['branch_name']) ?></span></td>
                                <td><?= htmlspecialchars($t['description']) ?></td>
                                <td class="text-end text-success fw-semibold"><?= formatRupiah($income) ?></td>
                                <td class="text-end">
                                    <?php if ($t['expenses'] > 0): ?>
                                        <span class="text-danger fw-semibold"><?= formatRupiah($t['expenses']) ?></span>
                                        <?php if (!empty($t['expense_description'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($t['expense_description']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold <?= $balances[$t['id']] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= formatRupiah($balances[$t['id']]) ?>
                                </td>
                                <?php if (canEdit() || canDelete()): ?>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if (canEdit()): ?>
                                        <a href="transactions.php?action=edit&id=<?= $t['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (canDelete()): ?>
                                        <button class="btn btn-outline-danger" onclick="deleteTransaction(<?= $t['id'] ?>)" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
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
    </div>

    <footer class="footer">
        <div class="container text-center">
            <span class="text-muted">&copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?></span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Chart colors matching modern theme
        const chartColors = ['#10b981', '#6366f1', '#06b6d4', '#f59e0b', '#ef4444', '#64748b'];

        // Income pie chart
        const incomeData = {
            labels: ['Tunai', 'QRIS', 'Transfer', 'Shopee Food', 'Grab Food', 'Go Food'],
            datasets: [{
                data: [<?= $totalCash ?>, <?= $totalQris ?>, <?= $totalTransfer ?>, <?= $totalShopee ?>, <?= $totalGrab ?>, <?= $totalGoFood ?>],
                backgroundColor: chartColors
            }]
        };

        if (document.getElementById('incomeChart')) {
            new Chart(document.getElementById('incomeChart'), {
                type: 'doughnut',
                data: incomeData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 15, usePointStyle: true }
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        <?php if (!empty($branchSummary)): ?>
        // Branch chart
        const branchData = {
            labels: [<?= implode(',', array_map(fn($b) => "'" . $b['name'] . "'", $branchSummary)) ?>],
            datasets: [{
                label: 'Pemasukan',
                data: [<?= implode(',', array_map(fn($b) => $b['total_income'], $branchSummary)) ?>],
                backgroundColor: '#10b981'
            }, {
                label: 'Pengeluaran',
                data: [<?= implode(',', array_map(fn($b) => $b['total_expenses'], $branchSummary)) ?>],
                backgroundColor: '#ef4444'
            }]
        };

        if (document.getElementById('branchChart')) {
            new Chart(document.getElementById('branchChart'), {
                type: 'bar',
                data: branchData,
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 15, usePointStyle: true }
                        }
                    }
                }
            });
        }
        <?php endif; ?>

        // Format currency for display
        function formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }

        // Create detail row HTML
        function createDetailRow(data) {
            let html = '<div class="detail-content bg-light p-3 rounded">';
            html += '<h6 class="mb-3"><i class="bi bi-info-circle"></i> Detail Pemasukan</h6>';
            html += '<div class="row g-3">';

            if (data.cash > 0) {
                html += '<div class="col-md-4"><div class="d-flex justify-content-between">';
                html += '<span><i class="bi bi-cash-stack text-success"></i> Tunai:</span>';
                html += '<strong>' + formatRupiah(data.cash) + '</strong></div></div>';
            }
            if (data.qris > 0) {
                html += '<div class="col-md-4"><div class="d-flex justify-content-between">';
                html += '<span><i class="bi bi-qr-code text-primary"></i> QRIS:</span>';
                html += '<strong>' + formatRupiah(data.qris) + '</strong></div></div>';
            }
            if (data.transfer > 0) {
                html += '<div class="col-md-4"><div class="d-flex justify-content-between">';
                html += '<span><i class="bi bi-bank text-info"></i> Transfer:</span>';
                html += '<strong>' + formatRupiah(data.transfer) + '</strong></div></div>';
            }
            if (data.shopee_food > 0) {
                html += '<div class="col-md-4"><div class="d-flex justify-content-between">';
                html += '<span><i class="bi bi-bag text-warning"></i> Shopee Food:</span>';
                html += '<strong>' + formatRupiah(data.shopee_food) + '</strong></div></div>';
            }
            if (data.grab_food > 0) {
                html += '<div class="col-md-4"><div class="d-flex justify-content-between">';
                html += '<span><i class="bi bi-bag text-danger"></i> Grab Food:</span>';
                html += '<strong>' + formatRupiah(data.grab_food) + '</strong></div></div>';
            }
            if (data.go_food > 0) {
                html += '<div class="col-md-4"><div class="d-flex justify-content-between">';
                html += '<span><i class="bi bi-bag text-secondary"></i> Go Food:</span>';
                html += '<strong>' + formatRupiah(data.go_food) + '</strong></div></div>';
            }

            html += '</div></div>';
            return html;
        }

        // DataTable
        $(document).ready(function() {
            const table = $('#transactionTable').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
                order: [[2, 'desc']], // Sort by Tanggal column
                pageLength: 25,
                columnDefs: [
                    { orderable: false, targets: 0 }, // Disable sort on chevron column
                    { width: '30px', targets: 0 }
                ]
            });

            // Handle detail row toggle
            $('#transactionTable tbody').on('click', 'td.dt-control', function() {
                const tr = $(this).closest('tr');
                const row = table.row(tr);
                const hasDetails = tr.data('has-details') === '1';

                if (!hasDetails) return;

                if (row.child.isShown()) {
                    // Close this row
                    row.child.hide();
                    tr.find('.dt-control i').removeClass('bi-chevron-down').addClass('bi-chevron-right');
                } else {
                    // Open this row
                    const detailData = tr.data('detail');
                    if (detailData) {
                        row.child(createDetailRow(detailData)).show();
                        tr.find('.dt-control i').removeClass('bi-chevron-right').addClass('bi-chevron-down');
                    }
                }
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

        // Delete transaction
        function deleteTransaction(id) {
            Swal.fire({
                title: 'Hapus Transaksi?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/transactions.php', {
                        method: 'DELETE',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Terhapus!', 'Transaksi berhasil dihapus.', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    });
                }
            });
        }

        // Export Excel
        function exportExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'export.php?type=excel&' + params.toString();
        }
    </script>
</body>
</html>
