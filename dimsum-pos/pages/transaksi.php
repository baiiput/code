<?php
require_once '../config/config.php';
Auth::requireLogin();

$pageTitle = 'Riwayat Transaksi';

// Get filters
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$cabangFilter = $_GET['cabang'] ?? '';

// Build query
$where = "DATE(t.created_at) = ?";
$params = [$tanggal];

// Filter by cabang
$userCabangId = Auth::cabangId();
if ($userCabangId) {
    $where .= " AND t.cabang_id = ?";
    $params[] = $userCabangId;
} elseif ($cabangFilter) {
    $where .= " AND t.cabang_id = ?";
    $params[] = $cabangFilter;
}

// Get transactions
$transaksis = $db->fetchAll("
    SELECT t.*, c.nama as cabang_nama, u.nama_lengkap as kasir,
           mp.nama as metode_pembayaran
    FROM transaksi t
    JOIN cabang c ON t.cabang_id = c.id
    JOIN users u ON t.user_id = u.id
    JOIN metode_pembayaran mp ON t.metode_pembayaran_id = mp.id
    WHERE $where
    ORDER BY t.created_at DESC
", $params);

// Get summary
$summary = $db->fetch("
    SELECT
        COUNT(*) as total_transaksi,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END), 0) as total_penjualan,
        COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as total_batal
    FROM transaksi t
    WHERE $where
", $params);

// Get cabang list for filter
$cabangs = $db->fetchAll("SELECT id, nama FROM cabang WHERE is_active = 1 ORDER BY nama ASC");

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Riwayat Transaksi</h4>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" class="form-control" name="tanggal" value="<?= $tanggal ?>">
            </div>
            <?php if (!$userCabangId): ?>
            <div class="col-md-3">
                <label class="form-label">Cabang</label>
                <select class="form-select" name="cabang">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($cabangs as $cabang): ?>
                    <option value="<?= $cabang['id'] ?>" <?= $cabangFilter == $cabang['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cabang['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="small opacity-75">Total Transaksi</div>
                <div class="fs-4 fw-bold"><?= $summary['total_transaksi'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="small opacity-75">Total Penjualan</div>
                <div class="fs-4 fw-bold"><?= Helper::rupiah($summary['total_penjualan']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="small opacity-75">Transaksi Batal</div>
                <div class="fs-4 fw-bold"><?= $summary['total_batal'] ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Waktu</th>
                    <?php if (!$userCabangId): ?><th>Cabang</th><?php endif; ?>
                    <th>Kasir</th>
                    <th>Tipe</th>
                    <th>Pembayaran</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th width="80">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transaksis)): ?>
                <tr>
                    <td colspan="<?= $userCabangId ? 8 : 9 ?>" class="text-center py-4 text-muted">
                        Tidak ada transaksi pada tanggal ini
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($transaksis as $trx): ?>
                <tr>
                    <td><code><?= $trx['no_transaksi'] ?></code></td>
                    <td><?= date('H:i', strtotime($trx['created_at'])) ?></td>
                    <?php if (!$userCabangId): ?>
                    <td><?= htmlspecialchars($trx['cabang_nama']) ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($trx['kasir']) ?></td>
                    <td>
                        <span class="badge bg-<?= $trx['tipe_order'] === 'dine_in' ? 'info' : 'secondary' ?>">
                            <?= $trx['tipe_order'] === 'dine_in' ? 'Dine In' : 'Take Away' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($trx['metode_pembayaran']) ?></td>
                    <td class="text-end fw-medium"><?= Helper::rupiah($trx['total']) ?></td>
                    <td><?= Helper::statusBadge($trx['status']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="showDetail(<?= $trx['id'] ?>)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '
<script>
async function showDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById("detailModal"));
    modal.show();

    const response = await fetch("api/transaksi-detail.php?id=" + id);
    const html = await response.text();
    document.getElementById("detailContent").innerHTML = html;
}
</script>';

include '../includes/footer.php';
?>
