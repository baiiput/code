<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Get filter parameters
$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');

// Get transaksi with filters
$params = [];
$sql = "
    SELECT t.*, w.nama_lengkap, d.nama_dawis
    FROM transaksi t
    JOIN warga w ON t.warga_id = w.id
    JOIN dawis d ON w.dawis_id = d.id
    WHERE 1=1
";

if ($bulan) {
    $sql .= " AND MONTH(t.tanggal_transaksi) = :bulan";
    $params['bulan'] = $bulan;
}

if ($tahun) {
    $sql .= " AND YEAR(t.tanggal_transaksi) = :tahun";
    $params['tahun'] = $tahun;
}

$sql .= " ORDER BY t.tanggal_transaksi DESC, t.created_at DESC";

$transaksi = $db->fetchAll($sql, $params);

// Get all warga for form
$warga = $db->fetchAll("
    SELECT w.*, d.nama_dawis
    FROM warga w
    JOIN dawis d ON w.dawis_id = d.id
    WHERE w.status = 'aktif'
    ORDER BY d.nama_dawis, w.nama_lengkap
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Jimpitan - <?php echo APP_NAME; ?></title>
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
                <li><a href="transaksi.php" class="active">Transaksi</a></li>
                <li><a href="pengeluaran.php">Pengeluaran</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">Filter Transaksi</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="transaksi.php" class="grid grid-2">
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

        <!-- Transaksi List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daftar Transaksi</h2>
                <button onclick="openModal('modalTransaksi')" class="btn btn-primary">
                    ➕ Tambah Transaksi
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Warga</th>
                                <th>Dawis</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksi)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Belum ada transaksi</td>
                            </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($transaksi as $t): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo formatTanggal($t['tanggal_transaksi']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($t['nama_lengkap']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t['nama_dawis']); ?></td>
                                    <td>
                                        <?php if ($t['jenis_transaksi'] == 'setoran'): ?>
                                            <span class="badge badge-success">Setoran</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Penarikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?php echo $t['jenis_transaksi'] == 'setoran' ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo formatRupiah($t['jumlah']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['keterangan'] ?? '-'); ?></td>
                                    <td>
                                        <button onclick="editTransaksi(<?php echo htmlspecialchars(json_encode($t)); ?>)"
                                                class="btn btn-secondary btn-sm">
                                            ✏️
                                        </button>
                                        <button onclick="deleteTransaksi(<?php echo $t['id']; ?>)"
                                                class="btn btn-danger btn-sm">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Tambah/Edit Transaksi -->
    <div id="modalTransaksi" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTransaksiTitle">Tambah Transaksi</h3>
                <button class="modal-close" onclick="closeModal('modalTransaksi')">&times;</button>
            </div>
            <form id="formTransaksi" onsubmit="handleSubmitTransaksi(event)">
                <input type="hidden" id="transaksiId" name="id">

                <div class="form-group">
                    <label class="form-label">Tanggal *</label>
                    <input type="date" id="tanggalTransaksi" name="tanggal_transaksi" class="form-control"
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Warga *</label>
                    <select id="wargaId" name="warga_id" class="form-control" required>
                        <option value="">Pilih Warga</option>
                        <?php
                        $currentDawis = '';
                        foreach ($warga as $w):
                            if ($currentDawis != $w['nama_dawis']):
                                if ($currentDawis != '') echo '</optgroup>';
                                echo '<optgroup label="' . htmlspecialchars($w['nama_dawis']) . '">';
                                $currentDawis = $w['nama_dawis'];
                            endif;
                        ?>
                            <option value="<?php echo $w['id']; ?>">
                                <?php echo htmlspecialchars($w['nama_lengkap']); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($currentDawis != '') echo '</optgroup>'; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Jenis Transaksi *</label>
                    <select id="jenisTransaksi" name="jenis_transaksi" class="form-control" required>
                        <option value="setoran">Setoran</option>
                        <option value="penarikan">Penarikan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Jumlah (Rp) *</label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control"
                           min="0" step="1000" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" class="form-control" rows="3"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modalTransaksi')" class="btn btn-secondary">
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

        function editTransaksi(data) {
            isEditMode = true;
            document.getElementById('modalTransaksiTitle').textContent = 'Edit Transaksi';
            document.getElementById('transaksiId').value = data.id;
            document.getElementById('tanggalTransaksi').value = data.tanggal_transaksi;
            document.getElementById('wargaId').value = data.warga_id;
            document.getElementById('jenisTransaksi').value = data.jenis_transaksi;
            document.getElementById('jumlah').value = data.jumlah;
            document.getElementById('keterangan').value = data.keterangan || '';
            openModal('modalTransaksi');
        }

        async function deleteTransaksi(id) {
            if (!confirm('Yakin ingin menghapus transaksi ini?')) return;

            try {
                const response = await apiCall('api/transaksi.php', 'DELETE', { id });

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

        async function handleSubmitTransaksi(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const method = isEditMode ? 'PUT' : 'POST';
                const response = await apiCall('api/transaksi.php', method, data);

                if (response.success) {
                    showToast(response.message, 'success');
                    closeModal('modalTransaksi');
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
            document.getElementById('modalTransaksiTitle').textContent = 'Tambah Transaksi';
            document.getElementById('formTransaksi').reset();
            document.getElementById('transaksiId').value = '';
            document.getElementById('tanggalTransaksi').value = '<?php echo date('Y-m-d'); ?>';
        }

        const originalOpenModal = window.openModal;
        window.openModal = function(modalId) {
            if (modalId === 'modalTransaksi' && !isEditMode) {
                resetForm();
            }
            originalOpenModal(modalId);
        };
    </script>
</body>
</html>
