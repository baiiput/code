<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'owner', 'admin_cabang']);

// Get filters
$periode = $_GET['periode'] ?? 'harian';
$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tanggalSelesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');
$cabangFilter = $_GET['cabang'] ?? '';
$format = $_GET['format'] ?? 'excel';

// Adjust dates based on periode
if ($periode === 'mingguan') {
    $tanggalMulai = date('Y-m-d', strtotime('monday this week'));
    $tanggalSelesai = date('Y-m-d', strtotime('sunday this week'));
} elseif ($periode === 'bulanan') {
    $tanggalMulai = date('Y-m-01');
    $tanggalSelesai = date('Y-m-t');
}

// Build query conditions
$where = "t.created_at BETWEEN ? AND ? AND t.status = 'completed'";
$params = [$tanggalMulai . ' 00:00:00', $tanggalSelesai . ' 23:59:59'];

// Filter by cabang
$userCabangId = Auth::cabangId();
if ($userCabangId) {
    $where .= " AND t.cabang_id = ?";
    $params[] = $userCabangId;
} elseif ($cabangFilter) {
    $where .= " AND t.cabang_id = ?";
    $params[] = $cabangFilter;
}

// Get summary
$summary = $db->fetch("
    SELECT
        COUNT(*) as total_transaksi,
        COALESCE(SUM(total), 0) as total_penjualan,
        COALESCE(SUM(diskon_nominal), 0) as total_diskon,
        COALESCE(SUM(pajak_nominal), 0) as total_pajak
    FROM transaksi t
    WHERE $where
", $params);

// Get transactions
$transaksis = $db->fetchAll("
    SELECT
        t.no_transaksi,
        t.created_at,
        c.nama as cabang,
        u.nama_lengkap as kasir,
        CASE WHEN t.tipe_order = 'dine_in' THEN 'Dine In' ELSE 'Take Away' END as tipe_order,
        mp.nama as metode_pembayaran,
        t.subtotal,
        t.diskon_nominal,
        t.pajak_nominal,
        t.total
    FROM transaksi t
    JOIN cabang c ON t.cabang_id = c.id
    JOIN users u ON t.user_id = u.id
    JOIN metode_pembayaran mp ON t.metode_pembayaran_id = mp.id
    WHERE $where
    ORDER BY t.created_at ASC
", $params);

// Get best sellers
$bestSellers = $db->fetchAll("
    SELECT
        td.nama_menu,
        td.nama_variasi,
        SUM(td.qty) as total_qty,
        SUM(td.subtotal) as total_sales
    FROM transaksi_detail td
    JOIN transaksi t ON td.transaksi_id = t.id
    WHERE $where
    GROUP BY td.nama_menu, td.nama_variasi
    ORDER BY total_qty DESC
    LIMIT 20
", $params);

$filename = 'Laporan_Penjualan_' . $tanggalMulai . '_' . $tanggalSelesai;

if ($format === 'excel') {
    // Export to Excel (CSV format for simplicity)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');

    echo "<html><head><meta charset='UTF-8'></head><body>";
    echo "<h2>Laporan Penjualan - " . APP_NAME . "</h2>";
    echo "<p>Periode: " . Helper::tanggal($tanggalMulai) . " - " . Helper::tanggal($tanggalSelesai) . "</p>";

    // Summary
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th colspan='2' style='background:#f0f0f0'>Ringkasan</th></tr>";
    echo "<tr><td>Total Transaksi</td><td>" . number_format($summary['total_transaksi']) . "</td></tr>";
    echo "<tr><td>Total Penjualan</td><td>" . Helper::rupiah($summary['total_penjualan']) . "</td></tr>";
    echo "<tr><td>Total Diskon</td><td>" . Helper::rupiah($summary['total_diskon']) . "</td></tr>";
    echo "<tr><td>Total Pajak</td><td>" . Helper::rupiah($summary['total_pajak']) . "</td></tr>";
    echo "</table><br>";

    // Transactions
    echo "<table border='1' cellpadding='5'>";
    echo "<tr style='background:#f0f0f0'>";
    echo "<th>No. Transaksi</th><th>Tanggal</th><th>Cabang</th><th>Kasir</th>";
    echo "<th>Tipe</th><th>Pembayaran</th><th>Subtotal</th><th>Diskon</th><th>Pajak</th><th>Total</th>";
    echo "</tr>";

    foreach ($transaksis as $trx) {
        echo "<tr>";
        echo "<td>" . $trx['no_transaksi'] . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($trx['created_at'])) . "</td>";
        echo "<td>" . htmlspecialchars($trx['cabang']) . "</td>";
        echo "<td>" . htmlspecialchars($trx['kasir']) . "</td>";
        echo "<td>" . $trx['tipe_order'] . "</td>";
        echo "<td>" . htmlspecialchars($trx['metode_pembayaran']) . "</td>";
        echo "<td style='text-align:right'>" . Helper::rupiah($trx['subtotal'], false) . "</td>";
        echo "<td style='text-align:right'>" . Helper::rupiah($trx['diskon_nominal'], false) . "</td>";
        echo "<td style='text-align:right'>" . Helper::rupiah($trx['pajak_nominal'], false) . "</td>";
        echo "<td style='text-align:right'>" . Helper::rupiah($trx['total'], false) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // Best Sellers
    echo "<table border='1' cellpadding='5'>";
    echo "<tr style='background:#f0f0f0'><th colspan='4'>Produk Terlaris</th></tr>";
    echo "<tr style='background:#f0f0f0'><th>No</th><th>Menu</th><th>Qty</th><th>Total</th></tr>";

    foreach ($bestSellers as $i => $item) {
        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td>" . htmlspecialchars($item['nama_menu']) . " - " . htmlspecialchars($item['nama_variasi']) . "</td>";
        echo "<td style='text-align:center'>" . number_format($item['total_qty']) . "</td>";
        echo "<td style='text-align:right'>" . Helper::rupiah($item['total_sales'], false) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "</body></html>";

} else {
    // Export to PDF (HTML format that can be printed as PDF)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?= $filename ?></title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
            h1 { font-size: 18px; margin-bottom: 5px; }
            h2 { font-size: 14px; margin: 15px 0 10px; }
            .meta { color: #666; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f5f5f5; font-weight: bold; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .summary-table { width: 300px; }
            .total-row { font-weight: bold; background: #f5f5f5; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom:20px">
            <button onclick="window.print()" style="padding:10px 20px;cursor:pointer">
                Cetak / Simpan PDF
            </button>
        </div>

        <h1>Laporan Penjualan - <?= APP_NAME ?></h1>
        <p class="meta">
            Periode: <?= Helper::tanggal($tanggalMulai) ?> - <?= Helper::tanggal($tanggalSelesai) ?><br>
            Dicetak: <?= Helper::tanggal(date('Y-m-d H:i:s'), true) ?>
        </p>

        <h2>Ringkasan</h2>
        <table class="summary-table">
            <tr><td>Total Transaksi</td><td class="text-right"><?= number_format($summary['total_transaksi']) ?></td></tr>
            <tr><td>Total Penjualan</td><td class="text-right"><?= Helper::rupiah($summary['total_penjualan']) ?></td></tr>
            <tr><td>Total Diskon</td><td class="text-right"><?= Helper::rupiah($summary['total_diskon']) ?></td></tr>
            <tr><td>Total Pajak</td><td class="text-right"><?= Helper::rupiah($summary['total_pajak']) ?></td></tr>
        </table>

        <h2>Detail Transaksi</h2>
        <table>
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Cabang</th>
                    <th>Kasir</th>
                    <th>Tipe</th>
                    <th>Pembayaran</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transaksis as $trx): ?>
                <tr>
                    <td><?= $trx['no_transaksi'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
                    <td><?= htmlspecialchars($trx['cabang']) ?></td>
                    <td><?= htmlspecialchars($trx['kasir']) ?></td>
                    <td><?= $trx['tipe_order'] ?></td>
                    <td><?= htmlspecialchars($trx['metode_pembayaran']) ?></td>
                    <td class="text-right"><?= Helper::rupiah($trx['total']) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="6">TOTAL</td>
                    <td class="text-right"><?= Helper::rupiah($summary['total_penjualan']) ?></td>
                </tr>
            </tbody>
        </table>

        <h2>Produk Terlaris</h2>
        <table style="width:50%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Menu</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bestSellers as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($item['nama_menu']) ?> - <?= htmlspecialchars($item['nama_variasi']) ?></td>
                    <td class="text-center"><?= number_format($item['total_qty']) ?></td>
                    <td class="text-right"><?= Helper::rupiah($item['total_sales']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
