<?php
/**
 * Branch Management - Laporan Keuangan Dimsum
 */
require_once 'config.php';

$db = Database::getConnection();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    if (isset($_POST['id']) && $_POST['id'] > 0) {
        // Update
        $stmt = $db->prepare("UPDATE branches SET name = ?, address = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $address, $phone, $_POST['id']]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Cabang berhasil diperbarui!'];
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO branches (name, address, phone) VALUES (?, ?, ?)");
        $stmt->execute([$name, $address, $phone]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Cabang berhasil ditambahkan!'];
    }

    header('Location: branches.php');
    exit;
}

// Get all branches
$branches = $db->query("SELECT b.*,
                        (SELECT COUNT(*) FROM transactions WHERE branch_id = b.id) as transaction_count,
                        (SELECT SUM(cash + qris + transfer + shopee_food + grab_food + go_food) FROM transactions WHERE branch_id = b.id) as total_income,
                        (SELECT SUM(expenses) FROM transactions WHERE branch_id = b.id) as total_expenses
                        FROM branches b ORDER BY b.name")->fetchAll();

// Get branch for editing
$branch = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM branches WHERE id = ?");
    $stmt->execute([$id]);
    $branch = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Kelola Cabang - <?= APP_NAME ?></title>
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
                        <a class="nav-link" href="transactions.php"><i class="bi bi-journal-text"></i> Transaksi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="branches.php"><i class="bi bi-shop"></i> Cabang</a>
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
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
            <?= $_SESSION['message']['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); endif; ?>

        <div class="row g-4">
            <!-- Form -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-<?= $branch ? 'pencil' : 'plus-lg' ?>"></i>
                            <?= $branch ? 'Edit' : 'Tambah' ?> Cabang
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($branch): ?>
                            <input type="hidden" name="id" value="<?= $branch['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Nama Cabang <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= $branch ? htmlspecialchars($branch['name']) : '' ?>"
                                       placeholder="Contoh: Cabang Senayan">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2"
                                          placeholder="Alamat lengkap cabang"><?= $branch ? htmlspecialchars($branch['address']) : '' ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telepon</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= $branch ? htmlspecialchars($branch['phone']) : '' ?>"
                                       placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Simpan
                                </button>
                                <?php if ($branch): ?>
                                <a href="branches.php" class="btn btn-secondary">
                                    <i class="bi bi-x-lg"></i> Batal
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Daftar Cabang</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Alamat</th>
                                        <th>Telepon</th>
                                        <th>Transaksi</th>
                                        <th>Total Omset</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($branches as $b): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($b['address']) ?: '-' ?></td>
                                        <td><?= htmlspecialchars($b['phone']) ?: '-' ?></td>
                                        <td><span class="badge bg-primary"><?= $b['transaction_count'] ?></span></td>
                                        <td class="text-success"><?= formatRupiah($b['total_income'] ?? 0) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="branches.php?action=edit&id=<?= $b['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="index.php?branch=<?= $b['id'] ?>" class="btn btn-outline-info" title="Lihat Transaksi">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($b['transaction_count'] == 0): ?>
                                                <button class="btn btn-outline-danger" onclick="deleteBranch(<?= $b['id'] ?>)" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($branches)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada cabang</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        // Delete branch
        function deleteBranch(id) {
            Swal.fire({
                title: 'Hapus Cabang?',
                text: 'Cabang yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/branches.php', {
                        method: 'DELETE',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Terhapus!', 'Cabang berhasil dihapus.', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
