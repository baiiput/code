<?php
$pageTitle = 'Penjualan Baru';
require_once __DIR__ . '/../../templates/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $db = getDB();
    $db->beginTransaction();

    try {
        $totalProfit = 0;
        $subtotal = 0;

        // Calculate first to get totals
        $serialIds = $_POST['serial_id'] ?? [];
        $sellPrices = $_POST['sell_price'] ?? [];
        $discounts = $_POST['item_discount'] ?? [];

        $itemsData = [];
        foreach ($serialIds as $i => $serialId) {
            if (empty($serialId)) continue;

            $serial = getById('product_serials', $serialId);
            $sellPrice = (float)str_replace(['.', ','], ['', '.'], $sellPrices[$i]);
            $discount = (float)str_replace(['.', ','], ['', '.'], $discounts[$i] ?? 0);
            $itemSubtotal = $sellPrice - $discount;
            $profit = $itemSubtotal - $serial['buy_price'];

            $subtotal += $itemSubtotal;
            $totalProfit += $profit;

            $itemsData[] = [
                'serial' => $serial,
                'sell_price' => $sellPrice,
                'discount' => $discount,
                'subtotal' => $itemSubtotal,
                'profit' => $profit,
            ];
        }

        $globalDiscount = (float)str_replace(['.', ','], ['', '.'], $_POST['discount'] ?? 0);
        $tax = (float)str_replace(['.', ','], ['', '.'], $_POST['tax'] ?? 0);
        $grandTotal = $subtotal - $globalDiscount + $tax;

        // Insert sale header
        $saleData = [
            'invoice_number' => generateInvoiceNumber('sale'),
            'customer_id' => $_POST['customer_id'] ?: null,
            'date' => $_POST['date'],
            'subtotal' => $subtotal,
            'discount' => $globalDiscount,
            'tax' => $tax,
            'grand_total' => $grandTotal,
            'profit' => $totalProfit,
            'payment_method' => $_POST['payment_method'],
            'marketplace_name' => $_POST['marketplace_name'] ?? null,
            'payment_status' => $_POST['payment_status'],
            'paid_amount' => (float)str_replace(['.', ','], ['', '.'], $_POST['paid_amount'] ?? 0),
            'due_date' => $_POST['due_date'] ?: null,
            'notes' => trim($_POST['notes']),
            'created_by' => $_SESSION['user_id'],
        ];

        $saleId = insert('sales', $saleData);

        // Insert sale items and update serial status
        foreach ($itemsData as $item) {
            $itemData = [
                'sale_id' => $saleId,
                'product_id' => $item['serial']['product_id'],
                'serial_id' => $item['serial']['id'],
                'quantity' => 1,
                'buy_price' => $item['serial']['buy_price'],
                'sell_price' => $item['sell_price'],
                'discount' => $item['discount'],
                'subtotal' => $item['subtotal'],
                'profit' => $item['profit'],
            ];
            insert('sale_items', $itemData);

            // Update serial status to sold
            updateSerialStatus($item['serial']['id'], 'sold');
        }

        $db->commit();

        logActivity('create', 'sales', $saleId, 'Sale created: ' . $saleData['invoice_number']);
        setFlash('success', 'Penjualan berhasil disimpan');
        header('Location: ' . BASE_URL . 'modules/stock-out/view.php?id=' . $saleId);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Error: ' . $e->getMessage());
    }
}

// Get data for dropdowns
$customers = getAll('customers', ['is_active' => 1], 'name ASC');
$products = getAll('products', ['is_active' => 1], 'name ASC');

// Get available serials for each product
$db = getDB();
$productSerials = [];
foreach ($products as $product) {
    $serials = getAvailableSerials($product['id']);
    $productSerials[$product['id']] = $serials;
}
?>

<div class="page-header">
    <h1>Penjualan Baru</h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Penjualan' => BASE_URL . 'modules/stock-out/', 'Baru' => '']) ?>
</div>

<form method="POST" data-validate id="saleForm">
    <?= csrfField() ?>

    <div class="row">
        <div class="col-12" style="width: 66.666%;">
            <!-- Header Info -->
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-info-circle me-2"></i>Informasi Penjualan</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-6">
                            <div class="form-group">
                                <label class="form-label">Customer</label>
                                <select name="customer_id" class="form-control form-select">
                                    <option value="">Walk-in Customer</option>
                                    <?= selectOptions($customers) ?>
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
                    <span><i class="fas fa-shopping-cart me-2"></i>Item Penjualan</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addSaleItem()">
                        <i class="fas fa-plus me-2"></i>Tambah Item
                    </button>
                </div>
                <div class="card-body" id="itemsContainer">
                    <!-- Items will be added here -->
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
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" class="form-control form-select" onchange="toggleMarketplace(this)">
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="tempo">Tempo</option>
                            <option value="marketplace">Marketplace</option>
                        </select>
                    </div>

                    <div class="form-group" id="marketplaceField" style="display: none;">
                        <label class="form-label">Nama Marketplace</label>
                        <input type="text" name="marketplace_name" class="form-control" placeholder="Tokopedia, Shopee, dll">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Diskon</label>
                        <input type="text" name="discount" class="form-control" value="0" onchange="calculateSaleTotal()">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pajak</label>
                        <input type="text" name="tax" class="form-control" value="0" onchange="calculateSaleTotal()">
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

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="subtotal">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-semibold">Grand Total:</span>
                        <span class="fw-bold text-primary" id="grandTotal">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Profit:</span>
                        <span class="text-success" id="totalProfit">Rp 0</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Simpan Penjualan
                    </button>
                    <a href="<?= BASE_URL ?>modules/stock-out/" class="btn btn-secondary w-100 mt-2">
                        <i class="fas fa-times me-2"></i>Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$productsJson = json_encode($products);
$serialsJson = json_encode($productSerials);
$pageScripts = <<<SCRIPT
<script>
const products = {$productsJson};
const productSerials = {$serialsJson};
let saleItemCount = 0;

function toggleMarketplace(select) {
    const field = document.getElementById('marketplaceField');
    field.style.display = select.value === 'marketplace' ? 'block' : 'none';
}

function addSaleItem() {
    saleItemCount++;
    const container = document.getElementById('itemsContainer');

    const html = \`
        <div class="item-row mb-3 p-3" style="border: 1px solid var(--border-color); border-radius: 8px;" id="sale-item-\${saleItemCount}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>Item #\${saleItemCount}</strong>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeSaleItem(\${saleItemCount})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <div class="row">
                <div class="col-12 col-4">
                    <div class="form-group">
                        <label class="form-label">Produk</label>
                        <select class="form-control form-select product-select" onchange="loadSerials(this, \${saleItemCount})">
                            <option value="">-- Pilih Produk --</option>
                            \${products.map(p => '<option value="'+p.id+'" data-price="'+p.default_sell_price+'">'+p.name+' ('+getStock(p.id)+')</option>').join('')}
                        </select>
                    </div>
                </div>
                <div class="col-12 col-4">
                    <div class="form-group">
                        <label class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <select name="serial_id[]" class="form-control form-select serial-select" required onchange="serialSelected(this)">
                            <option value="">-- Pilih Serial --</option>
                        </select>
                        <small class="text-muted buy-price-info"></small>
                    </div>
                </div>
                <div class="col-6 col-2">
                    <div class="form-group">
                        <label class="form-label">Harga Jual</label>
                        <input type="text" name="sell_price[]" class="form-control sell-price" value="0" onchange="calculateSaleTotal()">
                    </div>
                </div>
                <div class="col-6 col-2">
                    <div class="form-group">
                        <label class="form-label">Diskon</label>
                        <input type="text" name="item_discount[]" class="form-control" value="0" onchange="calculateSaleTotal()">
                    </div>
                </div>
            </div>
        </div>
    \`;

    container.insertAdjacentHTML('beforeend', html);
}

function removeSaleItem(id) {
    document.getElementById('sale-item-' + id).remove();
    calculateSaleTotal();
}

function getStock(productId) {
    return productSerials[productId] ? productSerials[productId].length : 0;
}

function loadSerials(select, itemId) {
    const productId = select.value;
    const serialSelect = select.closest('.item-row').querySelector('.serial-select');
    const priceInput = select.closest('.item-row').querySelector('.sell-price');

    serialSelect.innerHTML = '<option value="">-- Pilih Serial --</option>';

    if (productId && productSerials[productId]) {
        productSerials[productId].forEach(serial => {
            const opt = document.createElement('option');
            opt.value = serial.id;
            opt.dataset.buyPrice = serial.buy_price;
            opt.textContent = serial.serial_number + (serial.mac_address ? ' (' + serial.mac_address + ')' : '');
            serialSelect.appendChild(opt);
        });

        // Set default sell price
        const option = select.options[select.selectedIndex];
        priceInput.value = parseInt(option.dataset.price || 0).toLocaleString('id-ID');
    }

    calculateSaleTotal();
}

function serialSelected(select) {
    const option = select.options[select.selectedIndex];
    const buyPrice = option.dataset.buyPrice || 0;
    const infoEl = select.closest('.form-group').querySelector('.buy-price-info');
    infoEl.textContent = buyPrice > 0 ? 'Harga beli: Rp ' + parseInt(buyPrice).toLocaleString('id-ID') : '';
    calculateSaleTotal();
}

function calculateSaleTotal() {
    let subtotal = 0;
    let profit = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const serialSelect = row.querySelector('.serial-select');
        const option = serialSelect.options[serialSelect.selectedIndex];
        const buyPrice = parseInt(option?.dataset?.buyPrice || 0);
        const sellPrice = parseInt(row.querySelector('.sell-price').value.replace(/\\D/g, '')) || 0;
        const discount = parseInt(row.querySelector('input[name="item_discount[]"]').value.replace(/\\D/g, '')) || 0;

        const itemTotal = sellPrice - discount;
        subtotal += itemTotal;
        profit += itemTotal - buyPrice;
    });

    const globalDiscount = parseInt(document.querySelector('input[name="discount"]').value.replace(/\\D/g, '')) || 0;
    const tax = parseInt(document.querySelector('input[name="tax"]').value.replace(/\\D/g, '')) || 0;
    const grandTotal = subtotal - globalDiscount + tax;

    document.getElementById('subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('grandTotal').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    document.getElementById('totalProfit').textContent = 'Rp ' + profit.toLocaleString('id-ID');
}

// Add first item on load
addSaleItem();
</script>
SCRIPT;
?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
