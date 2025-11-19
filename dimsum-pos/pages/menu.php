<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'owner', 'admin_cabang']);

$pageTitle = 'Kelola Menu';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $gambar = null;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $upload = Helper::uploadImage($_FILES['gambar'], 'menu');
            if ($upload['success']) {
                $gambar = $upload['filename'];
            }
        }

        if ($action === 'create') {
            $menuId = $db->insert('menu', [
                'kategori_id' => $_POST['kategori_id'],
                'nama' => $_POST['nama'],
                'deskripsi' => $_POST['deskripsi'],
                'gambar' => $gambar
            ]);

            // Insert variasi
            if (!empty($_POST['variasi_nama'])) {
                foreach ($_POST['variasi_nama'] as $i => $nama) {
                    if (!empty($nama) && isset($_POST['variasi_harga'][$i])) {
                        $db->insert('menu_variasi', [
                            'menu_id' => $menuId,
                            'nama_variasi' => $nama,
                            'harga' => $_POST['variasi_harga'][$i]
                        ]);
                    }
                }
            }
            Helper::setFlash('success', 'Menu berhasil ditambahkan!');
        } else {
            $data = [
                'kategori_id' => $_POST['kategori_id'],
                'nama' => $_POST['nama'],
                'deskripsi' => $_POST['deskripsi']
            ];
            if ($gambar) {
                // Delete old image
                $oldMenu = $db->fetch("SELECT gambar FROM menu WHERE id = ?", [$_POST['id']]);
                if ($oldMenu['gambar']) {
                    Helper::deleteImage($oldMenu['gambar'], 'menu');
                }
                $data['gambar'] = $gambar;
            }
            $db->update('menu', $data, 'id = ?', [$_POST['id']]);

            // Update variasi
            $db->delete('menu_variasi', 'menu_id = ?', [$_POST['id']]);
            if (!empty($_POST['variasi_nama'])) {
                foreach ($_POST['variasi_nama'] as $i => $nama) {
                    if (!empty($nama) && isset($_POST['variasi_harga'][$i])) {
                        $db->insert('menu_variasi', [
                            'menu_id' => $_POST['id'],
                            'nama_variasi' => $nama,
                            'harga' => $_POST['variasi_harga'][$i]
                        ]);
                    }
                }
            }
            Helper::setFlash('success', 'Menu berhasil diperbarui!');
        }
    } elseif ($action === 'toggle') {
        $menu = $db->fetch("SELECT is_active FROM menu WHERE id = ?", [$_POST['id']]);
        $db->update('menu', ['is_active' => !$menu['is_active']], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Status menu berhasil diubah!');
    }

    header('Location: menu.php');
    exit;
}

// Get variasi for AJAX
if (isset($_GET['get_variasi'])) {
    $variasi = $db->fetchAll("SELECT * FROM menu_variasi WHERE menu_id = ?", [$_GET['get_variasi']]);
    header('Content-Type: application/json');
    echo json_encode($variasi);
    exit;
}

// Get all menus
$menus = $db->fetchAll("
    SELECT m.*, k.nama as kategori_nama,
           GROUP_CONCAT(CONCAT(mv.nama_variasi, ':', mv.harga) SEPARATOR '|') as variasi
    FROM menu m
    JOIN kategori k ON m.kategori_id = k.id
    LEFT JOIN menu_variasi mv ON m.id = mv.menu_id
    GROUP BY m.id
    ORDER BY k.urutan ASC, m.nama ASC
");

// Get kategori for dropdown
$kategoris = $db->fetchAll("SELECT id, nama FROM kategori WHERE is_active = 1 ORDER BY urutan ASC, nama ASC");

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Daftar Menu</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMenu" onclick="resetForm()">
        <i class="bi bi-plus-lg me-1"></i> Tambah Menu
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th width="60">Foto</th>
                    <th>Nama Menu</th>
                    <th>Kategori</th>
                    <th>Variasi & Harga</th>
                    <th>Status</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menus)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data menu</td>
                </tr>
                <?php else: ?>
                <?php foreach ($menus as $menu): ?>
                <tr>
                    <td>
                        <?php if ($menu['gambar']): ?>
                        <img src="<?= BASE_URL ?>uploads/menu/<?= $menu['gambar'] ?>" class="rounded" width="50" height="50" style="object-fit:cover">
                        <?php else: ?>
                        <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                            <i class="bi bi-image text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-medium"><?= htmlspecialchars($menu['nama']) ?></div>
                        <?php if ($menu['deskripsi']): ?>
                        <small class="text-muted"><?= htmlspecialchars(substr($menu['deskripsi'], 0, 50)) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($menu['kategori_nama']) ?></span></td>
                    <td>
                        <?php if ($menu['variasi']): ?>
                        <?php foreach (explode('|', $menu['variasi']) as $v): ?>
                        <?php list($nama, $harga) = explode(':', $v); ?>
                        <div class="small"><?= htmlspecialchars($nama) ?>: <strong><?= Helper::rupiah($harga) ?></strong></div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $menu['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $menu['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editMenu(<?= $menu['id'] ?>, <?= htmlspecialchars(json_encode($menu)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengubah status?')">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $menu['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $menu['is_active'] ? 'warning' : 'success' ?>">
                                <i class="bi bi-<?= $menu['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
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
<div class="modal fade" id="modalMenu" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formMenu" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Menu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama" id="inputNama" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" name="kategori_id" id="inputKategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($kategoris as $kat): ?>
                                    <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" id="inputDeskripsi" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gambar</label>
                                <input type="file" class="form-control" name="gambar" accept="image/*">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Variasi & Harga <span class="text-danger">*</span></label>
                            <div id="variasiContainer">
                                <div class="variasi-item mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="variasi_nama[]" placeholder="Nama variasi (cth: Isi 3)" required>
                                        <input type="number" class="form-control" name="variasi_harga[]" placeholder="Harga" required>
                                        <button type="button" class="btn btn-outline-danger" onclick="removeVariasi(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariasi()">
                                <i class="bi bi-plus me-1"></i> Tambah Variasi
                            </button>
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
function addVariasi() {
    const container = document.getElementById("variasiContainer");
    const item = document.createElement("div");
    item.className = "variasi-item mb-2";
    item.innerHTML = `
        <div class="input-group">
            <input type="text" class="form-control" name="variasi_nama[]" placeholder="Nama variasi (cth: Isi 5)" required>
            <input type="number" class="form-control" name="variasi_harga[]" placeholder="Harga" required>
            <button type="button" class="btn btn-outline-danger" onclick="removeVariasi(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(item);
}

function removeVariasi(btn) {
    const container = document.getElementById("variasiContainer");
    if (container.children.length > 1) {
        btn.closest(".variasi-item").remove();
    }
}

function resetForm() {
    document.getElementById("formMenu").reset();
    document.getElementById("formAction").value = "create";
    document.getElementById("formId").value = "";
    document.getElementById("modalTitle").textContent = "Tambah Menu";
    // Reset variasi to single item
    document.getElementById("variasiContainer").innerHTML = `
        <div class="variasi-item mb-2">
            <div class="input-group">
                <input type="text" class="form-control" name="variasi_nama[]" placeholder="Nama variasi (cth: Isi 3)" required>
                <input type="number" class="form-control" name="variasi_harga[]" placeholder="Harga" required>
                <button type="button" class="btn btn-outline-danger" onclick="removeVariasi(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
}

async function editMenu(id, menu) {
    document.getElementById("formAction").value = "update";
    document.getElementById("formId").value = id;
    document.getElementById("inputNama").value = menu.nama;
    document.getElementById("inputKategori").value = menu.kategori_id;
    document.getElementById("inputDeskripsi").value = menu.deskripsi || "";
    document.getElementById("modalTitle").textContent = "Edit Menu";

    // Load variasi
    const response = await fetch("?get_variasi=" + id);
    const variasi = await response.json();

    const container = document.getElementById("variasiContainer");
    container.innerHTML = "";
    variasi.forEach(v => {
        const item = document.createElement("div");
        item.className = "variasi-item mb-2";
        item.innerHTML = `
            <div class="input-group">
                <input type="text" class="form-control" name="variasi_nama[]" value="${v.nama_variasi}" required>
                <input type="number" class="form-control" name="variasi_harga[]" value="${v.harga}" required>
                <button type="button" class="btn btn-outline-danger" onclick="removeVariasi(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(item);
    });

    if (variasi.length === 0) {
        addVariasi();
    }

    new bootstrap.Modal(document.getElementById("modalMenu")).show();
}
</script>';

include '../includes/footer.php';
?>
