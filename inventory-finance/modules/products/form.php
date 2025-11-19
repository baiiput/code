<?php
$pageTitle = 'Form Produk';
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? null;
$product = $id ? getById('products', $id) : null;
$isEdit = $product !== null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'name' => trim($_POST['name']),
        'barcode' => trim($_POST['barcode']),
        'category_id' => $_POST['category_id'] ?: null,
        'brand' => trim($_POST['brand']),
        'model' => trim($_POST['model']),
        'description' => trim($_POST['description']),
        'unit' => trim($_POST['unit']) ?: 'pcs',
        'min_stock' => (int)$_POST['min_stock'],
        'default_buy_price' => (float)str_replace(['.', ','], ['', '.'], $_POST['default_buy_price']),
        'default_sell_price' => (float)str_replace(['.', ','], ['', '.'], $_POST['default_sell_price']),
        'warranty_months' => (int)$_POST['warranty_months'],
        'has_serial_number' => isset($_POST['has_serial_number']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($isEdit) {
        update('products', $data, $id);
        setFlash('success', 'Produk berhasil diupdate');
    } else {
        $data['code'] = generateCode('PRD', 'products');
        insert('products', $data);
        setFlash('success', 'Produk berhasil ditambahkan');
    }

    header('Location: ' . BASE_URL . 'modules/products/');
    exit;
}

// Get categories
$categories = getAll('categories', ['is_active' => 1], 'name ASC');
?>

<div class="page-header">
    <h1><?= $isEdit ? 'Edit Produk' : 'Tambah Produk' ?></h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Produk' => BASE_URL . 'modules/products/', ($isEdit ? 'Edit' : 'Tambah') => '']) ?>
</div>

<form method="POST" data-validate>
    <?= csrfField() ?>

    <div class="row">
        <div class="col-12" style="width: 66.666%;">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-box me-2"></i>Informasi Produk</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                       value="<?= e($product['name'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Barcode</label>
                                <input type="text" name="barcode" class="form-control"
                                       value="<?= e($product['barcode'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Kategori</label>
                                <select name="category_id" class="form-control form-select">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?= selectOptions($categories, $product['category_id'] ?? '') ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Satuan</label>
                                <input type="text" name="unit" class="form-control"
                                       value="<?= e($product['unit'] ?? 'pcs') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Brand/Merk</label>
                                <input type="text" name="brand" class="form-control"
                                       value="<?= e($product['brand'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Model</label>
                                <input type="text" name="model" class="form-control"
                                       value="<?= e($product['model'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"><?= e($product['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12" style="width: 33.333%;">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-tags me-2"></i>Harga & Stok</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Harga Beli Default</label>
                        <div class="input-group">
                            <input type="text" name="default_buy_price" class="form-control"
                                   value="<?= number_format($product['default_buy_price'] ?? 0, 0, ',', '.') ?>">
                            <span class="input-group-text">Rp</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Harga Jual Default</label>
                        <div class="input-group">
                            <input type="text" name="default_sell_price" class="form-control"
                                   value="<?= number_format($product['default_sell_price'] ?? 0, 0, ',', '.') ?>">
                            <span class="input-group-text">Rp</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Stok Minimum</label>
                        <input type="number" name="min_stock" class="form-control"
                               value="<?= $product['min_stock'] ?? 0 ?>">
                        <small class="text-muted">Alert jika stok kurang dari ini</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Garansi (bulan)</label>
                        <input type="number" name="warranty_months" class="form-control"
                               value="<?= $product['warranty_months'] ?? 0 ?>">
                    </div>

                    <div class="form-group">
                        <label class="d-flex align-items-center gap-2">
                            <input type="checkbox" name="has_serial_number" value="1"
                                   <?= ($product['has_serial_number'] ?? 1) ? 'checked' : '' ?>>
                            <span>Gunakan Serial Number</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="d-flex align-items-center gap-2">
                            <input type="checkbox" name="is_active" value="1"
                                   <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <span>Aktif</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save me-2"></i>Simpan
                </button>
            </div>
            <div class="mt-2">
                <a href="<?= BASE_URL ?>modules/products/" class="btn btn-secondary w-100">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
