<?php
$pageTitle = 'Pengaturan';
require_once __DIR__ . '/../../templates/header.php';

requireRole(['admin']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $settings = [
        'company_name' => $_POST['company_name'],
        'company_address' => $_POST['company_address'],
        'company_phone' => $_POST['company_phone'],
        'company_email' => $_POST['company_email'],
        'tax_percentage' => $_POST['tax_percentage'],
        'invoice_prefix' => $_POST['invoice_prefix'],
        'stock_in_prefix' => $_POST['stock_in_prefix'],
        'return_prefix' => $_POST['return_prefix'],
        'low_stock_alert' => $_POST['low_stock_alert'],
    ];

    foreach ($settings as $key => $value) {
        setSetting($key, $value);
    }

    logActivity('update', 'settings', null, 'Settings updated');
    setFlash('success', 'Pengaturan berhasil disimpan');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<div class="page-header">
    <h1>Pengaturan Aplikasi</h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Pengaturan' => '']) ?>
</div>

<form method="POST">
    <?= csrfField() ?>

    <div class="row">
        <div class="col-12 col-6">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-building me-2"></i>Informasi Perusahaan</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Nama Perusahaan</label>
                        <input type="text" name="company_name" class="form-control"
                               value="<?= e(getSetting('company_name')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea name="company_address" class="form-control" rows="3"><?= e(getSetting('company_address')) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="company_phone" class="form-control"
                               value="<?= e(getSetting('company_phone')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="company_email" class="form-control"
                               value="<?= e(getSetting('company_email')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-6">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-cogs me-2"></i>Pengaturan Sistem</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Pajak Default (%)</label>
                        <input type="number" name="tax_percentage" class="form-control"
                               value="<?= e(getSetting('tax_percentage', '11')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prefix Invoice</label>
                        <input type="text" name="invoice_prefix" class="form-control"
                               value="<?= e(getSetting('invoice_prefix', 'INV')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prefix Stok Masuk</label>
                        <input type="text" name="stock_in_prefix" class="form-control"
                               value="<?= e(getSetting('stock_in_prefix', 'SI')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prefix Return</label>
                        <input type="text" name="return_prefix" class="form-control"
                               value="<?= e(getSetting('return_prefix', 'RET')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alert Stok Minimum</label>
                        <input type="number" name="low_stock_alert" class="form-control"
                               value="<?= e(getSetting('low_stock_alert', '5')) ?>">
                        <small class="text-muted">Notifikasi jika stok kurang dari nilai ini</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Simpan Pengaturan
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
