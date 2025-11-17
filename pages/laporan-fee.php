<?php
/**
 * Laporan Fee
 * Menampilkan laporan perhitungan fee dengan export Excel
 */

require_once '../config/config.php';
checkLogin();
checkRole(['super_admin', 'admin', 'finance']);

$pageTitle = 'Laporan Fee';

// Get filter parameters
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');
$export = $_GET['export'] ?? false;

// Get data
$data = [];
$summary = [
    'total_transaksi' => 0,
    'total_nominal' => 0,
    'total_fee' => 0,
    'total_keseluruhan' => 0
];

try {
    $query = "SELECT
                pb.id,
                pb.tanggal_bayar,
                p.nama as nama_pelanggan,
                p.kit_number,
                p.paket,
                pb.nominal,
                pb.fee,
                pb.total,
                pb.metode_bayar,
                pb.keterangan,
                u.nama_lengkap as input_by
              FROM pembayaran pb
              LEFT JOIN pelanggan p ON pb.pelanggan_id = p.id
              LEFT JOIN users u ON pb.created_by = u.id
              WHERE MONTH(pb.tanggal_bayar) = :bulan
              AND YEAR(pb.tanggal_bayar) = :tahun
              AND pb.status = 'lunas'
              ORDER BY pb.tanggal_bayar DESC, pb.id DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':bulan', $bulan);
    $stmt->bindParam(':tahun', $tahun);
    $stmt->execute();
    $data = $stmt->fetchAll();

    // Calculate summary
    foreach ($data as $row) {
        $summary['total_transaksi']++;
        $summary['total_nominal'] += $row['nominal'];
        $summary['total_fee'] += $row['fee'];
        $summary['total_keseluruhan'] += $row['total'];
    }

} catch (PDOException $e) {
    error_log("Laporan Fee Error: " . $e->getMessage());
}

// Export to Excel
if ($export && count($data) > 0) {
    $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                   'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    $filename = "Laporan_Fee_" . $bulan_nama[(int)$bulan] . "_" . $tahun . ".xls";

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr>";
    echo "<th colspan='9' style='text-align:center; font-size:16px; font-weight:bold;'>";
    echo "LAPORAN PERHITUNGAN FEE<br>";
    echo "PERIODE: " . strtoupper($bulan_nama[(int)$bulan]) . " " . $tahun;
    echo "</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<th>No</th>";
    echo "<th>Tanggal</th>";
    echo "<th>Nama Pelanggan</th>";
    echo "<th>KIT Number</th>";
    echo "<th>Paket</th>";
    echo "<th>Nominal</th>";
    echo "<th>Fee</th>";
    echo "<th>Total</th>";
    echo "<th>Input By</th>";
    echo "</tr>";

    foreach ($data as $i => $row) {
        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($row['tanggal_bayar'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_pelanggan']) . "</td>";
        echo "<td>" . htmlspecialchars($row['kit_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['paket']) . "</td>";
        echo "<td style='text-align:right;'>" . number_format($row['nominal'], 0, ',', '.') . "</td>";
        echo "<td style='text-align:right;'>" . number_format($row['fee'], 0, ',', '.') . "</td>";
        echo "<td style='text-align:right;'>" . number_format($row['total'], 0, ',', '.') . "</td>";
        echo "<td>" . htmlspecialchars($row['input_by']) . "</td>";
        echo "</tr>";
    }

    echo "<tr style='font-weight:bold; background-color:#f0f0f0;'>";
    echo "<td colspan='5' style='text-align:right;'>TOTAL:</td>";
    echo "<td style='text-align:right;'>" . number_format($summary['total_nominal'], 0, ',', '.') . "</td>";
    echo "<td style='text-align:right;'>" . number_format($summary['total_fee'], 0, ',', '.') . "</td>";
    echo "<td style='text-align:right;'>" . number_format($summary['total_keseluruhan'], 0, ',', '.') . "</td>";
    echo "<td></td>";
    echo "</tr>";
    echo "</table>";

    exit();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-fill">
    <div class="container-fluid p-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="fas fa-chart-line"></i> Laporan Fee</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Laporan Fee</li>
                </ol>
            </nav>
        </div>

        <?= getFlashMessage() ?>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Periode</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-auto">
                        <label class="form-label">Bulan:</label>
                            <select name="bulan" class="form-control">
                                <?php
                                $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                for ($i = 1; $i <= 12; $i++) {
                                    $selected = ($bulan == sprintf('%02d', $i)) ? 'selected' : '';
                                    echo "<option value='" . sprintf('%02d', $i) . "' $selected>{$bulan_nama[$i]}</option>";
                                }
                                ?>
                            </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label">Tahun:</label>
                        <select name="tahun" class="form-select">
                            <?php
                            $current_year = date('Y');
                            for ($y = $current_year; $y >= $current_year - 3; $y--) {
                                $selected = ($tahun == $y) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tampilkan
                        </button>
                    </div>
                    <?php if (count($data) > 0): ?>
                    <div class="col-auto">
                        <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&export=1" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $summary['total_transaksi'] ?></h3>
                            <p>Total Transaksi</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= formatRupiah($summary['total_nominal']) ?></h3>
                            <p>Total Nominal</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= formatRupiah($summary['total_fee']) ?></h3>
                            <p>Total Fee</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= formatRupiah($summary['total_keseluruhan']) ?></h3>
                            <p>Total Keseluruhan</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-table"></i> Detail Laporan Fee -
                    <?= $bulan_nama[(int)$bulan] ?> <?= $tahun ?>
                </h5>
            </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>KIT Number</th>
                                <th>Paket</th>
                                <th>Nominal</th>
                                <th>Fee</th>
                                <th>Total</th>
                                <th>Input By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data) > 0): ?>
                                <?php foreach ($data as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= formatTanggal($row['tanggal_bayar']) ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($row['kit_number']) ?></code></td>
                                        <td><?= htmlspecialchars($row['paket']) ?></td>
                                        <td><?= formatRupiah($row['nominal']) ?></td>
                                        <td class="text-success"><strong><?= formatRupiah($row['fee']) ?></strong></td>
                                        <td><strong><?= formatRupiah($row['total']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['input_by']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="fas fa-info-circle fa-3x mb-3 d-block"></i>
                                        <p>Tidak ada data untuk periode ini</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (count($data) > 0): ?>
                            <tfoot>
                                <tr class="fw-bold table-light">
                                    <td colspan="5" class="text-end">TOTAL:</td>
                                    <td><?= formatRupiah($summary['total_nominal']) ?></td>
                                    <td class="text-success"><?= formatRupiah($summary['total_fee']) ?></td>
                                    <td><?= formatRupiah($summary['total_keseluruhan']) ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

    </div>

<?php include '../includes/footer.php'; ?>
