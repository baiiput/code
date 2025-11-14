<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Get all warga
$warga = $db->fetchAll("
    SELECT w.*, d.nama_dawis,
    COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END), 0) as total_saldo
    FROM warga w
    JOIN dawis d ON w.dawis_id = d.id
    LEFT JOIN transaksi t ON w.id = t.warga_id
    GROUP BY w.id
    ORDER BY d.nama_dawis, w.nama_lengkap
");

// Get all dawis for form
$allDawis = $db->fetchAll("SELECT * FROM dawis ORDER BY id");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Warga - <?php echo APP_NAME; ?></title>
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
                <li><a href="warga.php" class="active">Data Warga</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li><a href="pengeluaran.php">Pengeluaran</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Data Warga</h2>
                <button onclick="openModal('modalWarga')" class="btn btn-primary">
                    ➕ Tambah Warga
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Dawis</th>
                                <th>No. KK</th>
                                <th>Alamat</th>
                                <th>No. Telepon</th>
                                <th>Saldo</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="wargaTableBody">
                            <?php if (empty($warga)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Belum ada data warga</td>
                            </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($warga as $w): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($w['nama_lengkap']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($w['nama_dawis']); ?></td>
                                    <td><?php echo htmlspecialchars($w['nomor_kk'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($w['alamat'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($w['no_telepon'] ?? '-'); ?></td>
                                    <td><strong><?php echo formatRupiah($w['total_saldo']); ?></strong></td>
                                    <td>
                                        <?php if ($w['status'] == 'aktif'): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="editWarga(<?php echo htmlspecialchars(json_encode($w)); ?>)"
                                                class="btn btn-secondary btn-sm">
                                            ✏️ Edit
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

    <!-- Modal Tambah/Edit Warga -->
    <div id="modalWarga" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalWargaTitle">Tambah Warga</h3>
                <button class="modal-close" onclick="closeModal('modalWarga')">&times;</button>
            </div>
            <form id="formWarga" onsubmit="handleSubmitWarga(event)">
                <input type="hidden" id="wargaId" name="id">

                <div class="form-group">
                    <label class="form-label">Dawis *</label>
                    <select id="dawisId" name="dawis_id" class="form-control" required>
                        <option value="">Pilih Dawis</option>
                        <?php foreach ($allDawis as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nama_dawis']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" id="namaLengkap" name="nama_lengkap" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">No. KK</label>
                    <input type="text" id="nomorKK" name="nomor_kk" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea id="alamat" name="alamat" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="tel" id="noTelepon" name="no_telepon" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modalWarga')" class="btn btn-secondary">
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

        function editWarga(data) {
            isEditMode = true;
            document.getElementById('modalWargaTitle').textContent = 'Edit Warga';
            document.getElementById('wargaId').value = data.id;
            document.getElementById('dawisId').value = data.dawis_id;
            document.getElementById('namaLengkap').value = data.nama_lengkap;
            document.getElementById('nomorKK').value = data.nomor_kk || '';
            document.getElementById('alamat').value = data.alamat || '';
            document.getElementById('noTelepon').value = data.no_telepon || '';
            document.getElementById('status').value = data.status;
            openModal('modalWarga');
        }

        async function handleSubmitWarga(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const method = isEditMode ? 'PUT' : 'POST';
                const response = await apiCall('api/warga.php', method, data);

                if (response.success) {
                    showToast(response.message, 'success');
                    closeModal('modalWarga');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        // Reset form when opening modal for new entry
        document.getElementById('modalWarga').addEventListener('click', function(e) {
            if (e.target === this) {
                resetForm();
            }
        });

        function resetForm() {
            isEditMode = false;
            document.getElementById('modalWargaTitle').textContent = 'Tambah Warga';
            document.getElementById('formWarga').reset();
            document.getElementById('wargaId').value = '';
        }

        // Override openModal for warga to reset form
        const originalOpenModal = window.openModal;
        window.openModal = function(modalId) {
            if (modalId === 'modalWarga' && !isEditMode) {
                resetForm();
            }
            originalOpenModal(modalId);
        };
    </script>
</body>
</html>
