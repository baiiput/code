<?php
$pageTitle = 'Form Customer';
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? null;
$customer = $id ? getById('customers', $id) : null;
$isEdit = $customer !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'name' => trim($_POST['name']),
        'company' => trim($_POST['company']),
        'phone' => trim($_POST['phone']),
        'email' => trim($_POST['email']),
        'address' => trim($_POST['address']),
        'city' => trim($_POST['city']),
        'credit_limit' => (float)str_replace(['.', ','], ['', '.'], $_POST['credit_limit'] ?? 0),
        'notes' => trim($_POST['notes']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($isEdit) {
        update('customers', $data, $id);
        setFlash('success', 'Customer berhasil diupdate');
    } else {
        $data['code'] = generateCode('CUS', 'customers');
        insert('customers', $data);
        setFlash('success', 'Customer berhasil ditambahkan');
    }

    header('Location: ' . BASE_URL . 'modules/customers/');
    exit;
}
?>

<div class="page-header">
    <h1><?= $isEdit ? 'Edit Customer' : 'Tambah Customer' ?></h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Customer' => BASE_URL . 'modules/customers/', ($isEdit ? 'Edit' : 'Tambah') => '']) ?>
</div>

<div class="card" style="max-width: 700px;">
    <div class="card-body">
        <form method="POST" data-validate>
            <?= csrfField() ?>

            <div class="row">
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($customer['name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Perusahaan</label>
                        <input type="text" name="company" class="form-control"
                               value="<?= e($customer['company'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= e($customer['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= e($customer['email'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="2"><?= e($customer['address'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Kota</label>
                        <input type="text" name="city" class="form-control"
                               value="<?= e($customer['city'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Limit Kredit</label>
                        <input type="text" name="credit_limit" class="form-control"
                               value="<?= number_format($customer['credit_limit'] ?? 0, 0, ',', '.') ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($customer['notes'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="is_active" value="1"
                           <?= ($customer['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <span>Aktif</span>
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan
                </button>
                <a href="<?= BASE_URL ?>modules/customers/" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
