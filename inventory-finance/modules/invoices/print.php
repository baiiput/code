<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    die('Invalid request');
}

$db = getDB();

// Get sale data
$sale = $db->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone,
    c.address as customer_address, c.company as customer_company
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.id = ?");
$sale->execute([$id]);
$sale = $sale->fetch();

if (!$sale) {
    die('Data tidak ditemukan');
}

// Get items
$items = $db->prepare("SELECT si.*, p.name as product_name, p.code as product_code,
    ps.serial_number, ps.mac_address, ps.warranty_end
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    LEFT JOIN product_serials ps ON si.serial_id = ps.id
    WHERE si.sale_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= e($sale['invoice_number']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .company-info h1 {
            margin: 0 0 5px;
            font-size: 24px;
        }
        .company-info p {
            margin: 2px 0;
            color: #666;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h2 {
            margin: 0 0 10px;
            font-size: 20px;
            color: #333;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .customer-info, .payment-info {
            width: 48%;
        }
        .customer-info h3, .payment-info h3 {
            margin: 0 0 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f5f5f5;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals td {
            padding: 5px 10px;
        }
        .totals .grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature div {
            text-align: center;
            width: 150px;
        }
        .signature div span {
            display: block;
            margin-top: 60px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Invoice</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">Tutup</button>
    </div>

    <div class="invoice-header">
        <div class="company-info">
            <h1><?= e(getSetting('company_name', 'Toko Jaringan')) ?></h1>
            <p><?= e(getSetting('company_address')) ?></p>
            <p>Telp: <?= e(getSetting('company_phone')) ?></p>
            <p>Email: <?= e(getSetting('company_email')) ?></p>
        </div>
        <div class="invoice-info">
            <h2>INVOICE</h2>
            <p><strong><?= e($sale['invoice_number']) ?></strong></p>
            <p>Tanggal: <?= formatDate($sale['date']) ?></p>
        </div>
    </div>

    <div class="invoice-details">
        <div class="customer-info">
            <h3>Kepada:</h3>
            <?php if ($sale['customer_name']): ?>
            <p><strong><?= e($sale['customer_name']) ?></strong></p>
            <?php if ($sale['customer_company']): ?>
            <p><?= e($sale['customer_company']) ?></p>
            <?php endif; ?>
            <p><?= e($sale['customer_address']) ?></p>
            <p><?= e($sale['customer_phone']) ?></p>
            <?php else: ?>
            <p>Walk-in Customer</p>
            <?php endif; ?>
        </div>
        <div class="payment-info">
            <h3>Pembayaran:</h3>
            <p>Metode: <?= getPaymentMethodLabel($sale['payment_method']) ?></p>
            <p>Status: <?= $sale['payment_status'] == 'paid' ? 'Lunas' : ($sale['payment_status'] == 'partial' ? 'Sebagian' : 'Belum Bayar') ?></p>
            <?php if ($sale['due_date']): ?>
            <p>Jatuh Tempo: <?= formatDate($sale['due_date']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="30">#</th>
                <th>Produk</th>
                <th>Serial Number</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Diskon</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($items as $item): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td>
                    <?= e($item['product_name']) ?>
                    <br><small style="color: #666;"><?= e($item['product_code']) ?></small>
                </td>
                <td>
                    <?= e($item['serial_number']) ?>
                    <?php if ($item['warranty_end']): ?>
                    <br><small style="color: #666;">Garansi s/d <?= formatDate($item['warranty_end']) ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-right"><?= formatCurrency($item['sell_price']) ?></td>
                <td class="text-right"><?= $item['discount'] > 0 ? formatCurrency($item['discount']) : '-' ?></td>
                <td class="text-right"><?= formatCurrency($item['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-right"><?= formatCurrency($sale['subtotal']) ?></td>
        </tr>
        <?php if ($sale['discount'] > 0): ?>
        <tr>
            <td>Diskon</td>
            <td class="text-right">-<?= formatCurrency($sale['discount']) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($sale['tax'] > 0): ?>
        <tr>
            <td>Pajak</td>
            <td class="text-right"><?= formatCurrency($sale['tax']) ?></td>
        </tr>
        <?php endif; ?>
        <tr class="grand-total">
            <td>Grand Total</td>
            <td class="text-right"><?= formatCurrency($sale['grand_total']) ?></td>
        </tr>
    </table>

    <?php if ($sale['notes']): ?>
    <div style="margin-bottom: 20px;">
        <strong>Catatan:</strong>
        <p><?= nl2br(e($sale['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <div class="signature">
        <div>
            <span>Customer</span>
        </div>
        <div>
            <span>Hormat Kami</span>
        </div>
    </div>

    <div class="footer">
        <p>Terima kasih atas kepercayaan Anda</p>
        <p>Barang yang sudah dibeli tidak dapat dikembalikan kecuali ada perjanjian sebelumnya</p>
    </div>
</body>
</html>
