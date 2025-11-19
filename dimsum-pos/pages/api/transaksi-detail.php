<?php
require_once '../../config/config.php';

if (!Auth::check()) {
    echo '<div class="alert alert-danger">Unauthorized</div>';
    exit;
}

$id = $_GET['id'] ?? 0;

$transaksi = $db->fetch("
    SELECT t.*, c.nama as cabang_nama, u.nama_lengkap as kasir,
           mp.nama as metode_pembayaran, d.nama as diskon_nama
    FROM transaksi t
    JOIN cabang c ON t.cabang_id = c.id
    JOIN users u ON t.user_id = u.id
    JOIN metode_pembayaran mp ON t.metode_pembayaran_id = mp.id
    LEFT JOIN diskon d ON t.diskon_id = d.id
    WHERE t.id = ?
", [$id]);

if (!$transaksi) {
    echo '<div class="alert alert-danger">Transaksi tidak ditemukan</div>';
    exit;
}

$details = $db->fetchAll("SELECT * FROM transaksi_detail WHERE transaksi_id = ?", [$id]);
?>

<div class="row mb-3">
    <div class="col-6">
        <small class="text-muted">No. Transaksi</small>
        <div class="fw-bold"><?= $transaksi['no_transaksi'] ?></div>
    </div>
    <div class="col-6 text-end">
        <small class="text-muted">Waktu</small>
        <div class="fw-bold"><?= Helper::tanggal($transaksi['created_at'], true) ?></div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-6">
        <small class="text-muted">Cabang</small>
        <div><?= htmlspecialchars($transaksi['cabang_nama']) ?></div>
    </div>
    <div class="col-6">
        <small class="text-muted">Kasir</small>
        <div><?= htmlspecialchars($transaksi['kasir']) ?></div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-6">
        <small class="text-muted">Tipe Order</small>
        <div>
            <span class="badge bg-<?= $transaksi['tipe_order'] === 'dine_in' ? 'info' : 'secondary' ?>">
                <?= $transaksi['tipe_order'] === 'dine_in' ? 'Dine In' : 'Take Away' ?>
            </span>
        </div>
    </div>
    <div class="col-6">
        <small class="text-muted">Pembayaran</small>
        <div><?= htmlspecialchars($transaksi['metode_pembayaran']) ?></div>
    </div>
</div>

<hr>

<h6>Item Pesanan</h6>
<table class="table table-sm">
    <thead>
        <tr>
            <th>Menu</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Harga</th>
            <th class="text-end">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($details as $item): ?>
        <tr>
            <td>
                <?= htmlspecialchars($item['nama_menu']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($item['nama_variasi']) ?></small>
            </td>
            <td class="text-center"><?= $item['qty'] ?></td>
            <td class="text-end"><?= Helper::rupiah($item['harga']) ?></td>
            <td class="text-end"><?= Helper::rupiah($item['subtotal']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<hr>

<div class="row">
    <div class="col-6"></div>
    <div class="col-6">
        <div class="d-flex justify-content-between">
            <span>Subtotal</span>
            <span><?= Helper::rupiah($transaksi['subtotal']) ?></span>
        </div>
        <?php if ($transaksi['diskon_nominal'] > 0): ?>
        <div class="d-flex justify-content-between text-success">
            <span>Diskon <?= $transaksi['diskon_nama'] ? "({$transaksi['diskon_nama']})" : '' ?></span>
            <span>- <?= Helper::rupiah($transaksi['diskon_nominal']) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($transaksi['pajak_nominal'] > 0): ?>
        <div class="d-flex justify-content-between">
            <span>Pajak (<?= $transaksi['pajak_persen'] ?>%)</span>
            <span><?= Helper::rupiah($transaksi['pajak_nominal']) ?></span>
        </div>
        <?php endif; ?>
        <hr class="my-2">
        <div class="d-flex justify-content-between fw-bold">
            <span>Total</span>
            <span class="text-primary"><?= Helper::rupiah($transaksi['total']) ?></span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Uang Diterima</span>
            <span><?= Helper::rupiah($transaksi['uang_diterima']) ?></span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Kembalian</span>
            <span><?= Helper::rupiah($transaksi['kembalian']) ?></span>
        </div>
    </div>
</div>
