<?php
$pageTitle = 'Form Supplier';
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? null;
$supplier = $id ? getById('suppliers', $id) : null;
$isEdit = $supplier !== null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'name' => trim($_POST['name']),
        'contact_person' => trim($_POST['contact_person']),
        'phone' => trim($_POST['phone']),
        'email' => trim($_POST['email']),
        'address' => trim($_POST['address']),
        'city' => trim($_POST['city']),
        'notes' => trim($_POST['notes']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($isEdit) {
        update('suppliers', $data, $id);
        setFlash('success', 'Supplier berhasil diupdate');
    } else {
        $data['code'] = generateCode('SUP', 'suppliers');
        insert('suppliers', $data);
        setFlash('success', 'Supplier berhasil ditambahkan');
    }

    header('Location: ' . BASE_URL . 'modules/suppliers/');
    exit;
}
?>

<div class="page-header">
    <h1><?= $isEdit ? 'Edit Supplier' : 'Tambah Supplier' ?></h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Supplier' => BASE_URL . 'modules/suppliers/', ($isEdit ? 'Edit' : 'Tambah') => '']) ?>
</div>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form method="POST" data-validate>
            <?= csrfField() ?>

            <div class="row">
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($supplier['name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control"
                               value="<?= e($supplier['contact_person'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= e($supplier['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= e($supplier['email'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="2"><?= e($supplier['address'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Kota</label>
                <input type="text" name="city" class="form-control"
                       value="<?= e($supplier['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($supplier['notes'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="is_active" value="1"
                           <?= ($supplier['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <span>Aktif</span>
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan
                </button>
                <a href="<?= BASE_URL ?>modules/suppliers/" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
