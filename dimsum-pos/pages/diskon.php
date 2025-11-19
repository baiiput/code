<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'owner', 'admin_cabang']);

$pageTitle = 'Kelola Diskon & Promo';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $data = [
            'nama' => $_POST['nama'],
            'tipe' => $_POST['tipe'],
            'nilai' => $_POST['nilai'] ?: 0,
            'min_pembelian' => $_POST['min_pembelian'] ?: 0,
            'beli_qty' => $_POST['beli_qty'] ?: 0,
            'gratis_qty' => $_POST['gratis_qty'] ?: 0,
            'jam_mulai' => $_POST['jam_mulai'] ?: null,
            'jam_selesai' => $_POST['jam_selesai'] ?: null,
            'tanggal_mulai' => $_POST['tanggal_mulai'] ?: null,
            'tanggal_selesai' => $_POST['tanggal_selesai'] ?: null
        ];

        if ($action === 'create') {
            $db->insert('diskon', $data);
            Helper::setFlash('success', 'Diskon berhasil ditambahkan!');
        } else {
            $db->update('diskon', $data, 'id = ?', [$_POST['id']]);
            Helper::setFlash('success', 'Diskon berhasil diperbarui!');
        }
    } elseif ($action === 'toggle') {
        $diskon = $db->fetch("SELECT is_active FROM diskon WHERE id = ?", [$_POST['id']]);
        $db->update('diskon', ['is_active' => !$diskon['is_active']], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Status diskon berhasil diubah!');
    } elseif ($action === 'delete') {
        $db->delete('diskon', 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Diskon berhasil dihapus!');
    }

    header('Location: diskon.php');
    exit;
}

// Get all diskon
$diskons = $db->fetchAll("SELECT * FROM diskon ORDER BY is_active DESC, nama ASC");

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Daftar Diskon & Promo</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDiskon" onclick="resetForm()">
        <i class="bi bi-plus-lg me-1"></i> Tambah Diskon
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nama Promo</th>
                    <th>Tipe</th>
                    <th>Nilai</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($diskons)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data diskon</td>
                </tr>
                <?php else: ?>
                <?php foreach ($diskons as $diskon): ?>
                <tr>
                    <td class="fw-medium"><?= htmlspecialchars($diskon['nama']) ?></td>
                    <td>
                        <?php
                        $tipeLabel = [
                            'persen' => 'Diskon %',
                            'nominal' => 'Potongan Rp',
                            'beli_x_gratis_y' => 'Beli X Gratis Y',
                            'member' => 'Member',
                            'happy_hour' => 'Happy Hour'
                        ];
                        ?>
                        <span class="badge bg-info"><?= $tipeLabel[$diskon['tipe']] ?? $diskon['tipe'] ?></span>
                    </td>
                    <td>
                        <?php if ($diskon['tipe'] === 'persen'): ?>
                            <?= $diskon['nilai'] ?>%
                        <?php elseif ($diskon['tipe'] === 'nominal'): ?>
                            <?= Helper::rupiah($diskon['nilai']) ?>
                        <?php elseif ($diskon['tipe'] === 'beli_x_gratis_y'): ?>
                            Beli <?= $diskon['beli_qty'] ?> Gratis <?= $diskon['gratis_qty'] ?>
                        <?php else: ?>
                            <?= $diskon['nilai'] ?>%
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($diskon['tanggal_mulai'] && $diskon['tanggal_selesai']): ?>
                        <small><?= date('d/m/Y', strtotime($diskon['tanggal_mulai'])) ?> - <?= date('d/m/Y', strtotime($diskon['tanggal_selesai'])) ?></small>
                        <?php elseif ($diskon['jam_mulai'] && $diskon['jam_selesai']): ?>
                        <small><?= $diskon['jam_mulai'] ?> - <?= $diskon['jam_selesai'] ?></small>
                        <?php else: ?>
                        <small class="text-muted">Tidak terbatas</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $diskon['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $diskon['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editDiskon(<?= htmlspecialchars(json_encode($diskon)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengubah status?')">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $diskon['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $diskon['is_active'] ? 'warning' : 'success' ?>">
                                <i class="bi bi-<?= $diskon['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                            </button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus diskon ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $diskon['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalDiskon" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formDiskon">
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Diskon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Promo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama" id="inputNama" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipe Diskon <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipe" id="inputTipe" required onchange="updateForm()">
                                    <option value="">Pilih Tipe</option>
                                    <option value="persen">Diskon Persen (%)</option>
                                    <option value="nominal">Potongan Nominal (Rp)</option>
                                    <option value="beli_x_gratis_y">Beli X Gratis Y</option>
                                    <option value="member">Diskon Member</option>
                                    <option value="happy_hour">Happy Hour</option>
                                </select>
                            </div>
                            <div class="mb-3" id="nilaiGroup">
                                <label class="form-label">Nilai Diskon <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="nilai" id="inputNilai" step="0.01">
                            </div>
                            <div class="mb-3" id="minPembelianGroup">
                                <label class="form-label">Minimal Pembelian (Rp)</label>
                                <input type="number" class="form-control" name="min_pembelian" id="inputMinPembelian" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="beliGratisGroup" style="display:none">
                                <label class="form-label">Beli & Gratis</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="beli_qty" id="inputBeliQty" placeholder="Beli">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="gratis_qty" id="inputGratisQty" placeholder="Gratis">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3" id="jamGroup" style="display:none">
                                <label class="form-label">Jam Berlaku</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="time" class="form-control" name="jam_mulai" id="inputJamMulai">
                                    </div>
                                    <div class="col-6">
                                        <input type="time" class="form-control" name="jam_selesai" id="inputJamSelesai">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Periode Berlaku</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" class="form-control" name="tanggal_mulai" id="inputTglMulai">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control" name="tanggal_selesai" id="inputTglSelesai">
                                    </div>
                                </div>
                                <small class="text-muted">Kosongkan jika tidak terbatas</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = '
<script>
function updateForm() {
    const tipe = document.getElementById("inputTipe").value;
    const beliGratis = document.getElementById("beliGratisGroup");
    const jamGroup = document.getElementById("jamGroup");
    const nilaiGroup = document.getElementById("nilaiGroup");

    beliGratis.style.display = tipe === "beli_x_gratis_y" ? "block" : "none";
    jamGroup.style.display = tipe === "happy_hour" ? "block" : "none";
    nilaiGroup.style.display = tipe === "beli_x_gratis_y" ? "none" : "block";
}

function resetForm() {
    document.getElementById("formDiskon").reset();
    document.getElementById("formAction").value = "create";
    document.getElementById("formId").value = "";
    document.getElementById("modalTitle").textContent = "Tambah Diskon";
    updateForm();
}

function editDiskon(diskon) {
    document.getElementById("formAction").value = "update";
    document.getElementById("formId").value = diskon.id;
    document.getElementById("inputNama").value = diskon.nama;
    document.getElementById("inputTipe").value = diskon.tipe;
    document.getElementById("inputNilai").value = diskon.nilai;
    document.getElementById("inputMinPembelian").value = diskon.min_pembelian;
    document.getElementById("inputBeliQty").value = diskon.beli_qty;
    document.getElementById("inputGratisQty").value = diskon.gratis_qty;
    document.getElementById("inputJamMulai").value = diskon.jam_mulai || "";
    document.getElementById("inputJamSelesai").value = diskon.jam_selesai || "";
    document.getElementById("inputTglMulai").value = diskon.tanggal_mulai || "";
    document.getElementById("inputTglSelesai").value = diskon.tanggal_selesai || "";
    document.getElementById("modalTitle").textContent = "Edit Diskon";
    updateForm();
    new bootstrap.Modal(document.getElementById("modalDiskon")).show();
}
</script>';

include '../includes/footer.php';
?>
