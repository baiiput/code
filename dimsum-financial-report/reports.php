<?php
/**
 * Reports - Laporan Keuangan Dimsum
 */
require_once 'config.php';

$db = Database::getConnection();
$branches = getBranches();
$currentUser = getCurrentUser();

// Filter parameters
$branchId = isset($_GET['branch']) ? (int)$_GET['branch'] : 0;
$reportType = isset($_GET['type']) ? $_GET['type'] : 'monthly';
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

// Build where clause
$whereConditions = [];
$params = [];

if ($branchId > 0) {
    $whereConditions[] = "t.branch_id = :branch_id";
    $params[':branch_id'] = $branchId;
}

// Get monthly summary for the year
$monthlySummary = [];
for ($m = 1; $m <= 12; $m++) {
    $startDate = sprintf('%04d-%02d-01', $year, $m);
    $endDate = date('Y-m-t', strtotime($startDate));

    $sql = "SELECT
            COALESCE(SUM(cash + qris + transfer + shopee_food + grab_food + go_food), 0) as total_income,
            COALESCE(SUM(expenses), 0) as total_expenses
            FROM transactions t
            WHERE t.transaction_date BETWEEN :start_date AND :end_date";

    if ($branchId > 0) {
        $sql .= " AND t.branch_id = :branch_id";
    }

    $stmt = $db->prepare($sql);
    $params = [':start_date' => $startDate, ':end_date' => $endDate];
    if ($branchId > 0) {
        $params[':branch_id'] = $branchId;
    }
    $stmt->execute($params);
    $result = $stmt->fetch();

    $monthlySummary[$m] = [
        'income' => $result['total_income'],
        'expenses' => $result['total_expenses'],
        'profit' => $result['total_income'] - $result['total_expenses']
    ];
}

// Get daily summary for selected month
$dailySummary = [];
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));
$daysInMonth = date('t', strtotime($startDate));

$sql = "SELECT
        DAY(transaction_date) as day,
        SUM(cash + qris + transfer + shopee_food + grab_food + go_food) as total_income,
        SUM(expenses) as total_expenses
        FROM transactions t
        WHERE t.transaction_date BETWEEN :start_date AND :end_date";

if ($branchId > 0) {
    $sql .= " AND t.branch_id = :branch_id";
}

$sql .= " GROUP BY DAY(transaction_date) ORDER BY day";

$stmt = $db->prepare($sql);
$params = [':start_date' => $startDate, ':end_date' => $endDate];
if ($branchId > 0) {
    $params[':branch_id'] = $branchId;
}
$stmt->execute($params);
$dailyData = $stmt->fetchAll();

// Create array with all days
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dailySummary[$d] = ['income' => 0, 'expenses' => 0, 'profit' => 0];
}
foreach ($dailyData as $row) {
    $dailySummary[$row['day']] = [
        'income' => $row['total_income'],
        'expenses' => $row['total_expenses'],
        'profit' => $row['total_income'] - $row['total_expenses']
    ];
}

// Get payment method breakdown
$sql = "SELECT
        SUM(cash) as cash,
        SUM(qris) as qris,
        SUM(transfer) as transfer,
        SUM(shopee_food) as shopee_food,
        SUM(grab_food) as grab_food,
        SUM(go_food) as go_food
        FROM transactions t
        WHERE YEAR(transaction_date) = :year";

if ($branchId > 0) {
    $sql .= " AND t.branch_id = :branch_id";
}

$stmt = $db->prepare($sql);
$params = [':year' => $year];
if ($branchId > 0) {
    $params[':branch_id'] = $branchId;
}
$stmt->execute($params);
$paymentBreakdown = $stmt->fetch();

// Indonesian month names
$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Laporan - <?= APP_NAME ?></title>
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
                        <a class="nav-link" href="transactions.php?action=add"><i class="bi bi-plus-circle"></i> Transaksi</a>
                    </li>
                    <?php endif; ?>
                    <?php if (canManageBranches()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="branches.php"><i class="bi bi-shop"></i> Cabang</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php"><i class="bi bi-bar-chart-line"></i> Laporan</a>
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
                    <div class="col-md-2">
                        <label class="form-label">Tahun</label>
                        <select name="year" class="form-select">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bulan</label>
                        <select name="month" class="form-select">
                            <?php foreach ($monthNames as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-3">
                        <div class="btn-group w-100">
                            <a href="export.php?type=excel&year=<?= $year ?>&month=<?= $month ?>&branch=<?= $branchId ?>" class="btn btn-success">
                                <i class="bi bi-file-excel"></i> Excel
                            </a>
                            <button onclick="window.print()" class="btn btn-secondary">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <!-- Monthly Chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Ringkasan Bulanan <?= $year ?></h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Payment Breakdown -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Metode Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Table -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar3"></i> Laporan Bulanan <?= $year ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Bulan</th>
                                        <th class="text-end">Pemasukan</th>
                                        <th class="text-end">Pengeluaran</th>
                                        <th class="text-end">Laba</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $yearIncome = $yearExpenses = $yearProfit = 0;
                                    foreach ($monthlySummary as $m => $data):
                                        $yearIncome += $data['income'];
                                        $yearExpenses += $data['expenses'];
                                        $yearProfit += $data['profit'];
                                    ?>
                                    <tr class="<?= $m == $month ? 'table-primary' : '' ?>">
                                        <td><?= $monthNames[$m] ?></td>
                                        <td class="text-end text-success"><?= formatRupiah($data['income']) ?></td>
                                        <td class="text-end text-danger"><?= formatRupiah($data['expenses']) ?></td>
                                        <td class="text-end <?= $data['profit'] >= 0 ? 'text-primary' : 'text-danger' ?> fw-bold">
                                            <?= formatRupiah($data['profit']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold table-secondary">
                                        <td>Total</td>
                                        <td class="text-end text-success"><?= formatRupiah($yearIncome) ?></td>
                                        <td class="text-end text-danger"><?= formatRupiah($yearExpenses) ?></td>
                                        <td class="text-end <?= $yearProfit >= 0 ? 'text-primary' : 'text-danger' ?>">
                                            <?= formatRupiah($yearProfit) ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Table -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-calendar-day"></i> Laporan Harian - <?= $monthNames[$month] ?> <?= $year ?></h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-body">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th class="text-end">Pemasukan</th>
                                        <th class="text-end">Pengeluaran</th>
                                        <th class="text-end">Laba</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $monthIncome = $monthExpenses = $monthProfit = 0;
                                    foreach ($dailySummary as $d => $data):
                                        $monthIncome += $data['income'];
                                        $monthExpenses += $data['expenses'];
                                        $monthProfit += $data['profit'];
                                        if ($data['income'] == 0 && $data['expenses'] == 0) continue;
                                    ?>
                                    <tr>
                                        <td><?= $d ?> <?= $monthNames[$month] ?></td>
                                        <td class="text-end text-success"><?= formatRupiah($data['income']) ?></td>
                                        <td class="text-end text-danger"><?= formatRupiah($data['expenses']) ?></td>
                                        <td class="text-end <?= $data['profit'] >= 0 ? 'text-primary' : 'text-danger' ?> fw-bold">
                                            <?= formatRupiah($data['profit']) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold table-secondary">
                                        <td>Total</td>
                                        <td class="text-end text-success"><?= formatRupiah($monthIncome) ?></td>
                                        <td class="text-end text-danger"><?= formatRupiah($monthExpenses) ?></td>
                                        <td class="text-end <?= $monthProfit >= 0 ? 'text-primary' : 'text-danger' ?>">
                                            <?= formatRupiah($monthProfit) ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Monthly chart
        const monthlyData = {
            labels: [<?= implode(',', array_map(fn($m) => "'$m'", $monthNames)) ?>],
            datasets: [{
                label: 'Pemasukan',
                data: [<?= implode(',', array_column($monthlySummary, 'income')) ?>],
                backgroundColor: 'rgba(25, 135, 84, 0.5)',
                borderColor: '#198754',
                borderWidth: 1
            }, {
                label: 'Pengeluaran',
                data: [<?= implode(',', array_column($monthlySummary, 'expenses')) ?>],
                backgroundColor: 'rgba(220, 53, 69, 0.5)',
                borderColor: '#dc3545',
                borderWidth: 1
            }]
        };

        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: monthlyData,
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Payment chart
        const paymentData = {
            labels: ['Tunai', 'QRIS', 'Transfer', 'Shopee', 'Grab', 'GoFood'],
            datasets: [{
                data: [
                    <?= $paymentBreakdown['cash'] ?? 0 ?>,
                    <?= $paymentBreakdown['qris'] ?? 0 ?>,
                    <?= $paymentBreakdown['transfer'] ?? 0 ?>,
                    <?= $paymentBreakdown['shopee_food'] ?? 0 ?>,
                    <?= $paymentBreakdown['grab_food'] ?? 0 ?>,
                    <?= $paymentBreakdown['go_food'] ?? 0 ?>
                ],
                backgroundColor: ['#198754', '#0d6efd', '#0dcaf0', '#ffc107', '#dc3545', '#6c757d']
            }]
        };

        new Chart(document.getElementById('paymentChart'), {
            type: 'doughnut',
            data: paymentData,
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
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
