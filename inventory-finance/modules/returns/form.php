<?php
$pageTitle = 'Buat Return';
require_once __DIR__ . '/../../templates/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $db = getDB();
    $db->beginTransaction();

    try {
        $returnData = [
            'return_number' => generateInvoiceNumber('return'),
            'type' => $_POST['type'],
            'customer_id' => $_POST['type'] == 'customer' ? ($_POST['customer_id'] ?: null) : null,
            'supplier_id' => $_POST['type'] == 'supplier' ? ($_POST['supplier_id'] ?: null) : null,
            'date' => $_POST['date'],
            'reason' => trim($_POST['reason']),
            'total_amount' => 0,
            'status' => 'approved',
            'action' => $_POST['action'],
            'notes' => trim($_POST['notes']),
            'created_by' => $_SESSION['user_id'],
        ];

        $returnId = insert('returns', $returnData);

        // Process items
        $totalAmount = 0;
        $serialIds = $_POST['serial_id'] ?? [];
        $prices = $_POST['price'] ?? [];

        foreach ($serialIds as $i => $serialId) {
            if (empty($serialId)) continue;

            $serial = getById('product_serials', $serialId);
            $price = (float)str_replace(['.', ','], ['', '.'], $prices[$i]);
            $totalAmount += $price;

            $itemData = [
                'return_id' => $returnId,
                'product_id' => $serial['product_id'],
                'serial_id' => $serialId,
                'quantity' => 1,
                'price' => $price,
                'subtotal' => $price,
                'condition_notes' => '',
            ];
            insert('return_items', $itemData);

            // Mark serial as returned
            updateSerialStatus($serialId, 'returned');
        }

        // Update total
        $db->prepare("UPDATE returns SET total_amount = ? WHERE id = ?")
           ->execute([$totalAmount, $returnId]);

        $db->commit();

        setFlash('success', 'Return berhasil dibuat');
        header('Location: ' . BASE_URL . 'modules/returns/');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Error: ' . $e->getMessage());
    }
}

$customers = getAll('customers', ['is_active' => 1], 'name ASC');
$suppliers = getAll('suppliers', ['is_active' => 1], 'name ASC');

// Get sold serials for customer return
$db = getDB();
$soldSerials = $db->query("SELECT ps.*, p.name as product_name, p.code as product_code
    FROM product_serials ps
    JOIN products p ON ps.product_id = p.id
    WHERE ps.status = 'sold'
    ORDER BY p.name")->fetchAll();
?>

<div class="page-header">
    <h1>Buat Return</h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Return' => BASE_URL . 'modules/returns/', 'Buat' => '']) ?>
</div>

<form method="POST" data-validate>
    <?= csrfField() ?>

    <div class="row">
        <div class="col-12" style="width: 66.666%;">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-info-circle me-2"></i>Informasi Return</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Tipe Return <span class="text-danger">*</span></label>
                                <select name="type" class="form-control form-select" required>
                                    <option value="customer">Return dari Customer</option>
                                    <option value="supplier">Return ke Supplier</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Customer</label>
                                <select name="customer_id" class="form-control form-select">
                                    <option value="">-- Pilih Customer --</option>
                                    <?= selectOptions($customers) ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-control form-select">
                                    <option value="">-- Pilih Supplier --</option>
                                    <?= selectOptions($suppliers) ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alasan Return <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-undo me-2"></i>Item Return</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addReturnItem()">
                        <i class="fas fa-plus me-2"></i>Tambah Item
                    </button>
                </div>
                <div class="card-body" id="returnItemsContainer">
                    <!-- Items -->
                </div>
            </div>
        </div>

        <div class="col-12" style="width: 33.333%;">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-cogs me-2"></i>Aksi</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Tindakan</label>
                        <select name="action" class="form-control form-select">
                            <option value="refund">Refund</option>
                            <option value="replace">Ganti Barang</option>
                            <option value="repair">Perbaikan</option>
                            <option value="credit">Kredit Nota</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Simpan Return
                    </button>
                    <a href="<?= BASE_URL ?>modules/returns/" class="btn btn-secondary w-100 mt-2">
                        <i class="fas fa-times me-2"></i>Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$serialsJson = json_encode($soldSerials);
$pageScripts = <<<SCRIPT
<script>
const soldSerials = {$serialsJson};
let returnItemCount = 0;

function addReturnItem() {
    returnItemCount++;
    const container = document.getElementById('returnItemsContainer');

    const html = \`
        <div class="item-row mb-3 p-3" style="border: 1px solid var(--border-color); border-radius: 8px;" id="return-item-\${returnItemCount}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>Item #\${returnItemCount}</strong>
                <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.item-row').remove()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-12 col-8">
                    <div class="form-group">
                        <label class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <select name="serial_id[]" class="form-control form-select" required onchange="setReturnPrice(this)">
                            <option value="">-- Pilih Serial --</option>
                            \${soldSerials.map(s => '<option value="'+s.id+'" data-price="'+s.buy_price+'">'+s.serial_number+' - '+s.product_name+'</option>').join('')}
                        </select>
                    </div>
                </div>
                <div class="col-12 col-4">
                    <div class="form-group">
                        <label class="form-label">Nilai Return</label>
                        <input type="text" name="price[]" class="form-control" value="0">
                    </div>
                </div>
            </div>
        </div>
    \`;

    container.insertAdjacentHTML('beforeend', html);
}

function setReturnPrice(select) {
    const price = select.options[select.selectedIndex].dataset.price || 0;
    select.closest('.item-row').querySelector('input[name="price[]"]').value = parseInt(price).toLocaleString('id-ID');
}

addReturnItem();
</script>
SCRIPT;
?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
