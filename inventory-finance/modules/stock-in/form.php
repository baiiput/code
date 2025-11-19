<?php
$pageTitle = 'Tambah Stok Masuk';
require_once __DIR__ . '/../../templates/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $db = getDB();
    $db->beginTransaction();

    try {
        // Insert stock_in header
        $stockInData = [
            'invoice_number' => generateInvoiceNumber('stock_in'),
            'supplier_id' => $_POST['supplier_id'] ?: null,
            'date' => $_POST['date'],
            'total_amount' => 0,
            'discount' => (float)str_replace(['.', ','], ['', '.'], $_POST['discount'] ?? 0),
            'tax' => (float)str_replace(['.', ','], ['', '.'], $_POST['tax'] ?? 0),
            'grand_total' => 0,
            'payment_status' => $_POST['payment_status'],
            'paid_amount' => (float)str_replace(['.', ','], ['', '.'], $_POST['paid_amount'] ?? 0),
            'due_date' => $_POST['due_date'] ?: null,
            'notes' => trim($_POST['notes']),
            'created_by' => $_SESSION['user_id'],
        ];

        $stockInId = insert('stock_in', $stockInData);

        // Process items
        $totalAmount = 0;
        $products = $_POST['product_id'] ?? [];

        for ($i = 0; $i < count($products); $i++) {
            if (empty($products[$i])) continue;

            $productId = $products[$i];
            $quantity = (int)$_POST['quantity'][$i];
            $buyPrice = (float)str_replace(['.', ','], ['', '.'], $_POST['buy_price'][$i]);
            $subtotal = $quantity * $buyPrice;
            $totalAmount += $subtotal;

            // Insert stock_in_item
            $itemData = [
                'stock_in_id' => $stockInId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'buy_price' => $buyPrice,
                'subtotal' => $subtotal,
            ];
            $itemId = insert('stock_in_items', $itemData);

            // Get product info for warranty
            $product = getById('products', $productId);

            // Insert serial numbers
            $serialNumbers = $_POST['serial_numbers'][$i] ?? [];
            $macAddresses = $_POST['mac_addresses'][$i] ?? [];
            $ipDefaults = $_POST['ip_defaults'][$i] ?? [];
            $conditions = $_POST['conditions'][$i] ?? [];

            for ($j = 0; $j < count($serialNumbers); $j++) {
                if (empty($serialNumbers[$j])) continue;

                $warrantyStart = $_POST['date'];
                $warrantyEnd = null;
                if ($product['warranty_months'] > 0) {
                    $warrantyEnd = date('Y-m-d', strtotime("+{$product['warranty_months']} months", strtotime($warrantyStart)));
                }

                $serialData = [
                    'stock_in_item_id' => $itemId,
                    'product_id' => $productId,
                    'serial_number' => trim($serialNumbers[$j]),
                    'mac_address' => trim($macAddresses[$j] ?? ''),
                    'ip_default' => trim($ipDefaults[$j] ?? ''),
                    'condition' => $conditions[$j] ?? 'new',
                    'buy_price' => $buyPrice,
                    'warranty_start' => $warrantyStart,
                    'warranty_end' => $warrantyEnd,
                    'status' => 'available',
                ];
                insert('product_serials', $serialData);
            }
        }

        // Update totals
        $grandTotal = $totalAmount - $stockInData['discount'] + $stockInData['tax'];
        $db->prepare("UPDATE stock_in SET total_amount = ?, grand_total = ? WHERE id = ?")
           ->execute([$totalAmount, $grandTotal, $stockInId]);

        $db->commit();

        // Record cash transaction if paid
        $paidAmount = $stockInData['paid_amount'];
        if ($stockInData['payment_status'] === 'paid') {
            $paidAmount = $grandTotal;
        }
        if ($paidAmount > 0) {
            recordCashTransaction(
                'out',
                'Pembelian',
                'Pembelian stok: ' . $stockInData['invoice_number'],
                $paidAmount,
                'stock_in',
                $stockInId
            );
        }

        logActivity('create', 'stock_in', $stockInId, 'Stock in created: ' . $stockInData['invoice_number']);
        setFlash('success', 'Stok masuk berhasil disimpan');
        header('Location: ' . BASE_URL . 'modules/stock-in/view.php?id=' . $stockInId);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Error: ' . $e->getMessage());
    }
}

// Get data for dropdowns
$suppliers = getAll('suppliers', ['is_active' => 1], 'name ASC');
$products = getAll('products', ['is_active' => 1], 'name ASC');
?>

<div class="page-header">
    <h1>Tambah Stok Masuk</h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Stok Masuk' => BASE_URL . 'modules/stock-in/', 'Tambah' => '']) ?>
</div>

<form method="POST" data-validate id="stockInForm">
    <?= csrfField() ?>

    <div class="row">
        <div class="col-12" style="width: 66.666%;">
            <!-- Header Info -->
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-info-circle me-2"></i>Informasi Pembelian</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-control form-select">
                                    <option value="">-- Pilih Supplier --</option>
                                    <?= selectOptions($suppliers) ?>
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
                </div>
            </div>

            <!-- Items -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-boxes me-2"></i>Item Produk</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                        <i class="fas fa-plus me-2"></i>Tambah Item
                    </button>
                </div>
                <div class="card-body" id="itemsContainer">
                    <!-- Items will be added here dynamically -->
                </div>
            </div>
        </div>

        <div class="col-12" style="width: 33.333%;">
            <!-- Payment Info -->
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-money-bill me-2"></i>Pembayaran</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Diskon</label>
                        <input type="text" name="discount" class="form-control" value="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pajak</label>
                        <input type="text" name="tax" class="form-control" value="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Pembayaran</label>
                        <select name="payment_status" class="form-control form-select">
                            <option value="paid">Lunas</option>
                            <option value="partial">Sebagian</option>
                            <option value="unpaid">Belum Bayar</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jumlah Dibayar</label>
                        <input type="text" name="paid_amount" class="form-control" value="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-semibold">Grand Total:</span>
                        <span class="fw-bold text-primary" id="grandTotal">Rp 0</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Simpan Stok Masuk
                    </button>
                    <a href="<?= BASE_URL ?>modules/stock-in/" class="btn btn-secondary w-100 mt-2">
                        <i class="fas fa-times me-2"></i>Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$productsJson = json_encode($products);
$pageScripts = <<<SCRIPT
<script>
const products = {$productsJson};
let itemCount = 0;

function addItem() {
    itemCount++;
    const container = document.getElementById('itemsContainer');

    const html = `
        <div class="item-block mb-4 p-3" style="border: 1px solid var(--border-color); border-radius: 8px;" id="item-\${itemCount}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>Item #\${itemCount}</strong>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(\${itemCount})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <div class="row">
                <div class="col-12 col-6">
                    <div class="form-group">
                        <label class="form-label">Produk <span class="text-danger">*</span></label>
                        <select name="product_id[]" class="form-control form-select" required onchange="productChanged(this, \${itemCount})">
                            <option value="">-- Pilih Produk --</option>
                            \${products.map(p => '<option value="'+p.id+'" data-price="'+p.default_buy_price+'" data-serial="'+p.has_serial_number+'">'+p.name+'</option>').join('')}
                        </select>
                    </div>
                </div>
                <div class="col-6 col-3">
                    <div class="form-group">
                        <label class="form-label">Qty <span class="text-danger">*</span></label>
                        <input type="number" name="quantity[]" class="form-control" value="1" min="1" required
                               onchange="qtyChanged(this, \${itemCount})">
                    </div>
                </div>
                <div class="col-6 col-3">
                    <div class="form-group">
                        <label class="form-label">Harga Beli <span class="text-danger">*</span></label>
                        <input type="text" name="buy_price[]" class="form-control" value="0" required
                               onchange="calculateTotal()">
                    </div>
                </div>
            </div>

            <div class="serial-container" id="serials-\${itemCount}">
                <!-- Serial numbers will be added here -->
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
}

function removeItem(id) {
    document.getElementById('item-' + id).remove();
    calculateTotal();
}

function productChanged(select, itemId) {
    const option = select.options[select.selectedIndex];
    const price = option.dataset.price || 0;
    const hasSn = option.dataset.serial == 1;

    // Set price
    const priceInput = select.closest('.item-block').querySelector('input[name="buy_price[]"]');
    priceInput.value = parseInt(price).toLocaleString('id-ID');

    // Generate serial inputs
    const qty = select.closest('.item-block').querySelector('input[name="quantity[]"]').value;
    generateSerialInputs(itemId, parseInt(qty), hasSn);

    calculateTotal();
}

function qtyChanged(input, itemId) {
    const select = input.closest('.item-block').querySelector('select[name="product_id[]"]');
    const option = select.options[select.selectedIndex];
    const hasSn = option.dataset.serial == 1;

    generateSerialInputs(itemId, parseInt(input.value), hasSn);
    calculateTotal();
}

function generateSerialInputs(itemId, qty, hasSn) {
    const container = document.getElementById('serials-' + itemId);
    container.innerHTML = '';

    if (!hasSn || qty < 1) return;

    let html = '<div class="mt-3"><label class="form-label fw-semibold">Serial Numbers:</label>';

    for (let i = 0; i < qty; i++) {
        html += \`
            <div class="row mb-2" style="font-size: 0.85rem;">
                <div class="col-3">
                    <input type="text" name="serial_numbers[\${itemId-1}][]" class="form-control form-control-sm"
                           placeholder="Serial Number" required>
                </div>
                <div class="col-3">
                    <input type="text" name="mac_addresses[\${itemId-1}][]" class="form-control form-control-sm"
                           placeholder="MAC Address">
                </div>
                <div class="col-3">
                    <input type="text" name="ip_defaults[\${itemId-1}][]" class="form-control form-control-sm"
                           placeholder="IP Default">
                </div>
                <div class="col-3">
                    <select name="conditions[\${itemId-1}][]" class="form-control form-select form-control-sm">
                        <option value="new">Baru</option>
                        <option value="used">Bekas</option>
                        <option value="refurbished">Refurbished</option>
                    </select>
                </div>
            </div>
        \`;
    }

    html += '</div>';
    container.innerHTML = html;
}

function calculateTotal() {
    let total = 0;
    const items = document.querySelectorAll('.item-block');

    items.forEach(item => {
        const qty = parseInt(item.querySelector('input[name="quantity[]"]').value) || 0;
        const price = parseInt(item.querySelector('input[name="buy_price[]"]').value.replace(/\\D/g, '')) || 0;
        total += qty * price;
    });

    const discount = parseInt(document.querySelector('input[name="discount"]').value.replace(/\\D/g, '')) || 0;
    const tax = parseInt(document.querySelector('input[name="tax"]').value.replace(/\\D/g, '')) || 0;

    const grandTotal = total - discount + tax;
    document.getElementById('grandTotal').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
}

// Event listeners
document.querySelector('input[name="discount"]').addEventListener('input', calculateTotal);
document.querySelector('input[name="tax"]').addEventListener('input', calculateTotal);

// Add first item on load
addItem();
</script>
SCRIPT;
?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
