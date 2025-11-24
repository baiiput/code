<?php
/**
 * Import CSV - Laporan Keuangan Dimsum
 */
require_once 'config.php';

// Only Editor and Admin can import
requireRole(['editor', 'admin']);

$db = Database::getConnection();
$currentUser = getCurrentUser();
$branches = getBranches();

// Create branch lookup array (name => id)
$branchLookup = [];
foreach ($branches as $branch) {
    $branchLookup[strtolower(trim($branch['name']))] = $branch['id'];
}

$errors = [];
$success = false;
$imported = 0;
$skipped = 0;
$previewData = [];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Error saat mengupload file. Silakan coba lagi.';
    } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        $errors[] = 'File terlalu besar. Maksimal 5MB.';
    } elseif (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['csv'])) {
        $errors[] = 'File harus berformat CSV.';
    } else {
        // Read and process CSV
        $handle = fopen($file['tmp_name'], 'r');

        if ($handle === false) {
            $errors[] = 'Tidak dapat membaca file CSV.';
        } else {
            // Read header
            $header = fgetcsv($handle, 1000, ',');

            if (!$header) {
                $errors[] = 'File CSV kosong atau tidak valid.';
            } else {
                // Validate header
                $requiredColumns = ['tanggal', 'cabang', 'deskripsi', 'tunai', 'qris', 'transfer', 'shopee_food', 'grab_food', 'go_food', 'pengeluaran'];
                $header = array_map('strtolower', array_map('trim', $header));

                $missingColumns = array_diff($requiredColumns, $header);
                if (!empty($missingColumns)) {
                    $errors[] = 'Kolom yang kurang: ' . implode(', ', $missingColumns);
                } else {
                    // Process rows
                    $rowNum = 1;
                    $db->beginTransaction();

                    try {
                        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                            $rowNum++;

                            if (count($row) !== count($header)) {
                                $skipped++;
                                continue;
                            }

                            $data = array_combine($header, $row);

                            // Validate and clean data
                            $tanggal = trim($data['tanggal']);
                            $cabang = strtolower(trim($data['cabang']));
                            $deskripsi = trim($data['deskripsi']);

                            // Validate date
                            $dateObj = DateTime::createFromFormat('Y-m-d', $tanggal);
                            if (!$dateObj || $dateObj->format('Y-m-d') !== $tanggal) {
                                $errors[] = "Baris $rowNum: Format tanggal salah (harus: YYYY-MM-DD)";
                                $skipped++;
                                continue;
                            }

                            // Find branch ID
                            if (!isset($branchLookup[$cabang])) {
                                $errors[] = "Baris $rowNum: Cabang '$cabang' tidak ditemukan";
                                $skipped++;
                                continue;
                            }
                            $branchId = $branchLookup[$cabang];

                            // Parse amounts (remove non-numeric characters except dots and commas)
                            $tunai = floatval(preg_replace('/[^0-9.]/', '', $data['tunai']));
                            $qris = floatval(preg_replace('/[^0-9.]/', '', $data['qris']));
                            $transfer = floatval(preg_replace('/[^0-9.]/', '', $data['transfer']));
                            $shopeeFood = floatval(preg_replace('/[^0-9.]/', '', $data['shopee_food']));
                            $grabFood = floatval(preg_replace('/[^0-9.]/', '', $data['grab_food']));
                            $goFood = floatval(preg_replace('/[^0-9.]/', '', $data['go_food']));
                            $pengeluaran = floatval(preg_replace('/[^0-9.]/', '', $data['pengeluaran']));

                            // Insert transaction
                            $stmt = $db->prepare("INSERT INTO transactions
                                (branch_id, transaction_date, description, cash, qris, transfer, shopee_food, grab_food, go_food, expenses, created_by)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                            $stmt->execute([
                                $branchId,
                                $tanggal,
                                $deskripsi,
                                $tunai,
                                $qris,
                                $transfer,
                                $shopeeFood,
                                $grabFood,
                                $goFood,
                                $pengeluaran,
                                $currentUser['id']
                            ]);

                            $imported++;
                        }

                        $db->commit();
                        $success = true;
                        $_SESSION['message'] = [
                            'type' => 'success',
                            'text' => "Berhasil mengimport $imported transaksi" . ($skipped > 0 ? ", $skipped baris dilewati" : "")
                        ];
                        header('Location: index.php');
                        exit;

                    } catch (Exception $e) {
                        $db->rollBack();
                        $errors[] = 'Error saat menyimpan data: ' . $e->getMessage();
                    }
                }
            }

            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Import CSV - <?= APP_NAME ?></title>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-file-earmark-arrow-up"></i> Import Data dari CSV</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <strong><i class="bi bi-info-circle"></i> Petunjuk Import CSV:</strong>
                            <ul class="mb-0 mt-2">
                                <li>File harus berformat CSV dengan separator koma (,)</li>
                                <li>Baris pertama harus berisi header kolom</li>
                                <li>Format tanggal: YYYY-MM-DD (contoh: 2024-01-15)</li>
                                <li>Nama cabang harus sesuai dengan cabang yang sudah ada</li>
                                <li>Angka tidak perlu separator ribuan, cukup angka saja</li>
                                <li>Maksimal ukuran file: 5MB</li>
                            </ul>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label">Pilih File CSV <span class="text-danger">*</span></label>
                                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                                <small class="text-muted">Format file: .csv (maksimal 5MB)</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> Upload & Import
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-lg"></i> Batal
                                </a>
                                <a href="template_import.csv" class="btn btn-success" download>
                                    <i class="bi bi-download"></i> Download Template
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Example Card -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-file-text"></i> Contoh Format CSV</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Berikut adalah contoh format file CSV yang benar:</p>
                        <div style="background: var(--bg-input); padding: 1rem; border-radius: 0.5rem; overflow-x: auto; font-family: monospace; font-size: 0.75rem;">
<pre style="margin: 0; color: var(--text-primary);">tanggal,cabang,deskripsi,tunai,qris,transfer,shopee_food,grab_food,go_food,pengeluaran
2024-01-15,Cabang Pusat,Penjualan Hari Senin,500000,300000,200000,150000,100000,50000,200000
2024-01-16,Cabang Pusat,Penjualan Hari Selasa,600000,350000,250000,180000,120000,60000,250000
2024-01-17,Cabang Senayan,Penjualan Hari Rabu,450000,280000,180000,140000,90000,45000,180000</pre>
                        </div>

                        <div class="mt-4">
                            <h6>Penjelasan Kolom:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Kolom</th>
                                            <th>Format</th>
                                            <th>Contoh</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>tanggal</code></td>
                                            <td>YYYY-MM-DD</td>
                                            <td>2024-01-15</td>
                                            <td>Tanggal transaksi</td>
                                        </tr>
                                        <tr>
                                            <td><code>cabang</code></td>
                                            <td>Text</td>
                                            <td>Cabang Pusat</td>
                                            <td>Nama cabang (harus sudah ada)</td>
                                        </tr>
                                        <tr>
                                            <td><code>deskripsi</code></td>
                                            <td>Text</td>
                                            <td>Penjualan Hari Senin</td>
                                            <td>Keterangan transaksi</td>
                                        </tr>
                                        <tr>
                                            <td><code>tunai</code></td>
                                            <td>Angka</td>
                                            <td>500000</td>
                                            <td>Pemasukan tunai</td>
                                        </tr>
                                        <tr>
                                            <td><code>qris</code></td>
                                            <td>Angka</td>
                                            <td>300000</td>
                                            <td>Pemasukan QRIS</td>
                                        </tr>
                                        <tr>
                                            <td><code>transfer</code></td>
                                            <td>Angka</td>
                                            <td>200000</td>
                                            <td>Pemasukan transfer</td>
                                        </tr>
                                        <tr>
                                            <td><code>shopee_food</code></td>
                                            <td>Angka</td>
                                            <td>150000</td>
                                            <td>Pemasukan Shopee Food</td>
                                        </tr>
                                        <tr>
                                            <td><code>grab_food</code></td>
                                            <td>Angka</td>
                                            <td>100000</td>
                                            <td>Pemasukan Grab Food</td>
                                        </tr>
                                        <tr>
                                            <td><code>go_food</code></td>
                                            <td>Angka</td>
                                            <td>50000</td>
                                            <td>Pemasukan Go Food</td>
                                        </tr>
                                        <tr>
                                            <td><code>pengeluaran</code></td>
                                            <td>Angka</td>
                                            <td>200000</td>
                                            <td>Total pengeluaran</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <strong><i class="bi bi-exclamation-triangle"></i> Catatan Penting:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Nama cabang harus sama persis</strong> dengan nama cabang yang ada di sistem</li>
                                <li>Cabang yang tersedia: <?php foreach ($branches as $i => $b): ?><?= $i > 0 ? ', ' : '' ?><strong><?= htmlspecialchars($b['name']) ?></strong><?php endforeach; ?></li>
                                <li>Jika nama cabang tidak ditemukan, baris tersebut akan dilewati</li>
                            </ul>
                        </div>
                    </div>
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
    <script>
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
