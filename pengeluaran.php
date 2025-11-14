<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Get filter parameters
$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');

// Get pengeluaran with filters
$params = [];
$sql = "SELECT * FROM pengeluaran WHERE 1=1";

if ($bulan) {
    $sql .= " AND MONTH(tanggal_pengeluaran) = :bulan";
    $params['bulan'] = $bulan;
}

if ($tahun) {
    $sql .= " AND YEAR(tanggal_pengeluaran) = :tahun";
    $params['tahun'] = $tahun;
}

$sql .= " ORDER BY tanggal_pengeluaran DESC";

$pengeluaran = $db->fetchAll($sql, $params);

// Calculate total
$total = 0;
foreach ($pengeluaran as $p) {
    $total += $p['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengeluaran RT - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span>💰</span>
                    <span><?php echo APP_NAME; ?></span>
                </a>
                <div class="header-actions">
                    <button id="theme-toggle" class="theme-toggle">
                        <span id="theme-icon">🌙</span>
                        <span id="theme-text">Dark</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav">
        <div class="container">
            <ul class="nav-list">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="laporan.php">Laporan Per Dawis</a></li>
                <li><a href="warga.php">Data Warga</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li><a href="pengeluaran.php" class="active">Pengeluaran</a></li>
                <li><a href="alert.php">Notifikasi</a></li>
                <li><a href="settings.php">Pengaturan</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">Filter Pengeluaran</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="pengeluaran.php" class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Bulan</label>
                        <select name="bulan" class="form-control">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $bulan == $m ? 'selected' : ''; ?>>
                                    <?php echo getBulanIndo($m); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tahun</label>
                        <select name="tahun" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end; grid-column: span 2;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            🔍 Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Total Card -->
        <div class="stats-grid mb-3">
            <div class="stat-card danger">
                <div class="stat-label">Total Pengeluaran</div>
                <div class="stat-value"><?php echo formatRupiah($total); ?></div>
                <div class="stat-subtitle"><?php echo getBulanIndo($bulan) . ' ' . $tahun; ?></div>
            </div>
        </div>

        <!-- Pengeluaran List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daftar Pengeluaran</h2>
                <button onclick="openModal('modalPengeluaran')" class="btn btn-primary">
                    ➕ Tambah Pengeluaran
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pengeluaran)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada pengeluaran</td>
                            </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($pengeluaran as $p): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo formatTanggal($p['tanggal_pengeluaran']); ?></td>
                                    <td><span class="badge badge-warning"><?php echo htmlspecialchars($p['kategori']); ?></span></td>
                                    <td class="text-danger"><strong><?php echo formatRupiah($p['jumlah']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['keterangan']); ?></td>
                                    <td>
                                        <button onclick="editPengeluaran(<?php echo htmlspecialchars(json_encode($p)); ?>)"
                                                class="btn btn-secondary btn-sm">
                                            ✏️
                                        </button>
                                        <button onclick="deletePengeluaran(<?php echo $p['id']; ?>)"
                                                class="btn btn-danger btn-sm">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: var(--bg-tertiary); font-weight: bold;">
                                    <td colspan="3" class="text-right">TOTAL:</td>
                                    <td class="text-danger"><?php echo formatRupiah($total); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Tambah/Edit Pengeluaran -->
    <div id="modalPengeluaran" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalPengeluaranTitle">Tambah Pengeluaran</h3>
                <button class="modal-close" onclick="closeModal('modalPengeluaran')">&times;</button>
            </div>
            <form id="formPengeluaran" onsubmit="handleSubmitPengeluaran(event)">
                <input type="hidden" id="pengeluaranId" name="id">

                <div class="form-group">
                    <label class="form-label">Tanggal *</label>
                    <input type="date" id="tanggalPengeluaran" name="tanggal_pengeluaran" class="form-control"
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kategori *</label>
                    <select id="kategori" name="kategori" class="form-control" required>
                        <option value="">Pilih Kategori</option>
                        <option value="Kebersihan">Kebersihan</option>
                        <option value="Keamanan">Keamanan</option>
                        <option value="Pemeliharaan">Pemeliharaan</option>
                        <option value="Acara RT">Acara RT</option>
                        <option value="Administrasi">Administrasi</option>
                        <option value="Lain-lain">Lain-lain</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Jumlah (Rp) *</label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control"
                           min="0" step="1000" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan *</label>
                    <textarea id="keterangan" name="keterangan" class="form-control" rows="3" required></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modalPengeluaran')" class="btn btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        let isEditMode = false;

        function editPengeluaran(data) {
            isEditMode = true;
            document.getElementById('modalPengeluaranTitle').textContent = 'Edit Pengeluaran';
            document.getElementById('pengeluaranId').value = data.id;
            document.getElementById('tanggalPengeluaran').value = data.tanggal_pengeluaran;
            document.getElementById('kategori').value = data.kategori;
            document.getElementById('jumlah').value = data.jumlah;
            document.getElementById('keterangan').value = data.keterangan;
            openModal('modalPengeluaran');
        }

        async function deletePengeluaran(id) {
            if (!confirm('Yakin ingin menghapus pengeluaran ini?')) return;

            try {
                const response = await apiCall('api/pengeluaran.php', 'DELETE', { id });

                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function handleSubmitPengeluaran(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const method = isEditMode ? 'PUT' : 'POST';
                const response = await apiCall('api/pengeluaran.php', method, data);

                if (response.success) {
                    showToast(response.message, 'success');
                    closeModal('modalPengeluaran');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        function resetForm() {
            isEditMode = false;
            document.getElementById('modalPengeluaranTitle').textContent = 'Tambah Pengeluaran';
            document.getElementById('formPengeluaran').reset();
            document.getElementById('pengeluaranId').value = '';
            document.getElementById('tanggalPengeluaran').value = '<?php echo date('Y-m-d'); ?>';
        }

        const originalOpenModal = window.openModal;
        window.openModal = function(modalId) {
            if (modalId === 'modalPengeluaran' && !isEditMode) {
                resetForm();
            }
            originalOpenModal(modalId);
        };
    </script>
</body>
</html>
