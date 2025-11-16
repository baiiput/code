<?php
require_once 'config.php';

// Check if user is logged in
requireLogin();

// Check if user is cabang role
requireRole('cabang');

// Get current user info
$current_user = getCurrentUser();
$user_id = $current_user['user_id'];
$username = $current_user['username'];
$branch_id = $current_user['cabang_id'];

// Check if branch_id exists
if (empty($branch_id)) {
    die("Error: User tidak terhubung dengan cabang manapun. Silakan hubungi administrator.");
}

// Get database connection
$conn = getDBConnection();

// Get branch name
$stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = ?");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$branch_result = $stmt->get_result();
if ($branch_result->num_rows == 0) {
    die("Error: Cabang tidak ditemukan.");
}
$branch_data = $branch_result->fetch_assoc();
$branch_name = $branch_data['branch_name'];

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter settings
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query - Filter stock_out where destination is current branch
$where_conditions = ["so.branch_id = ?"];
$params = [$branch_id];
$types = "i";

if ($date_from) {
    $where_conditions[] = "DATE(so.transaction_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $where_conditions[] = "DATE(so.transaction_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if ($search) {
    $where_conditions[] = "(so.transaction_code LIKE ? OR so.notes LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total records
$count_sql = "SELECT COUNT(*) as total 
              FROM stock_out so
              WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get stock out transactions (distributions to this branch)
$sql = "SELECT so.*,
        u.username as dibuat_oleh_nama,
        u.full_name as dibuat_oleh_lengkap,
        COUNT(DISTINCT sod.detail_id) as jumlah_item,
        SUM(sod.quantity) as total_qty,
        SUM(sod.subtotal) as total_nilai
        FROM stock_out so
        LEFT JOIN users u ON so.created_by = u.user_id
        LEFT JOIN stock_out_detail sod ON so.stock_out_id = sod.stock_out_id
        WHERE $where_clause
        GROUP BY so.stock_out_id
        ORDER BY so.transaction_date DESC, so.created_at DESC
        LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT
              COUNT(*) as total,
              SUM(CASE WHEN DATE(so.transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as minggu_ini,
              SUM(CASE WHEN DATE(so.transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as bulan_ini,
              SUM(so.total_amount) as total_nilai
              FROM stock_out so
              WHERE so.branch_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $branch_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribusi Masuk - Warehouse System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 0;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 25px 30px;
            margin-bottom: 0;
        }
        
        .content-section {
            padding: 25px 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--secondary-color);
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .stat-card.primary {
            border-left-color: var(--primary-color);
        }
        
        .stat-card.success {
            border-left-color: var(--success-color);
        }
        
        .stat-card.warning {
            border-left-color: var(--warning-color);
        }
        
        .stat-card.info {
            border-left-color: var(--info-color);
        }
        
        .stat-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .table thead th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px 10px;
            white-space: nowrap;
        }
        
        .table tbody tr {
            transition: background-color 0.3s;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .table tbody td {
            vertical-align: middle;
            padding: 12px 10px;
        }
        
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        
        .btn-action {
            padding: 5px 12px;
            font-size: 0.85rem;
            margin: 2px;
            white-space: nowrap;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        
        .pagination {
            margin-top: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 5rem;
            opacity: 0.2;
            color: var(--secondary-color);
        }
        
        .empty-state h5 {
            margin-top: 20px;
            color: #6c757d;
        }
        
        .empty-state p {
            color: #adb5bd;
        }
        
        @media (max-width: 768px) {
            .content-section {
                padding: 15px;
            }
            
            .header-section {
                padding: 20px 15px;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .btn-action {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
            
            .stat-card {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <div class="header-section">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h4 class="mb-1">
                            <i class="bi bi-truck"></i> Distribusi Masuk
                        </h4>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($branch_name); ?>
                        </p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="index.php" class="btn btn-light">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 col-6">
                        <div class="stat-card primary">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Total Distribusi</div>
                                    <h3 class="mb-0"><?php echo number_format($stats['total'] ?? 0); ?></h3>
                                </div>
                                <div class="text-primary stat-icon">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="stat-card success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Minggu Ini</div>
                                    <h3 class="mb-0"><?php echo number_format($stats['minggu_ini'] ?? 0); ?></h3>
                                </div>
                                <div class="text-success stat-icon">
                                    <i class="bi bi-calendar-week"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="stat-card warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Bulan Ini</div>
                                    <h3 class="mb-0"><?php echo number_format($stats['bulan_ini'] ?? 0); ?></h3>
                                </div>
                                <div class="text-warning stat-icon">
                                    <i class="bi bi-calendar-month"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-6">
                        <div class="stat-card info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Total Nilai</div>
                                    <h5 class="mb-0"><?php echo formatRupiah($stats['total_nilai'] ?? 0); ?></h5>
                                </div>
                                <div class="text-info stat-icon">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">
                                <i class="bi bi-calendar-range"></i> Dari Tanggal
                            </label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">
                                <i class="bi bi-calendar-check"></i> Sampai Tanggal
                            </label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">
                                <i class="bi bi-search"></i> Cari
                            </label>
                            <input type="text" name="search" class="form-control form-control-sm" 
                                   placeholder="Nomor transaksi / Keterangan" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                                <a href="my_distributions.php" class="btn btn-secondary btn-sm" title="Reset Filter">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Table Section -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 4%;">No</th>
                                    <th style="width: 15%;">Kode Transaksi</th>
                                    <th style="width: 13%;">Tanggal</th>
                                    <th style="width: 10%;">Items</th>
                                    <th style="width: 12%;">Qty Total</th>
                                    <th style="width: 15%;">Total Nilai</th>
                                    <th style="width: 20%;">Keterangan</th>
                                    <th style="width: 11%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php
                                    $no = $offset + 1;
                                    while($row = $result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <strong class="text-primary">
                                                <?php echo htmlspecialchars($row['transaction_code']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="bi bi-calendar3 text-muted"></i>
                                                <?php echo date('d/m/Y', strtotime($row['transaction_date'])); ?>
                                                <br>
                                                <span class="text-muted">
                                                    <?php echo date('H:i', strtotime($row['transaction_date'])); ?>
                                                </span>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo number_format($row['jumlah_item']); ?> item
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo formatNumber($row['total_qty'], 0); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success">
                                                <?php echo formatRupiah($row['total_nilai']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php 
                                                $notes = $row['notes'] ?? '-';
                                                echo htmlspecialchars(strlen($notes) > 40 ? substr($notes, 0, 40) . '...' : $notes); 
                                                ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info btn-action"
                                                    onclick="viewDetail(<?php echo $row['stock_out_id']; ?>)"
                                                    title="Lihat Detail">
                                                <i class="bi bi-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="p-0">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <h5>Tidak Ada Data Distribusi</h5>
                                                <p>Belum ada barang yang dikirim ke cabang ini</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page-1); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page+1); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center text-muted small mt-2">
                    Menampilkan <?php echo number_format($offset + 1); ?> - <?php echo number_format(min($offset + $records_per_page, $total_records)); ?> 
                    dari <?php echo number_format($total_records); ?> data
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailModalLabel">
                        <i class="bi bi-file-invoice"></i> Detail Distribusi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded via fetch -->
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewDetail(id) {
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        modal.show();

        document.getElementById('detailContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        fetch('stock_out_detail.php?id=' + id)
            .then(response => response.text())
            .then(html => {
                document.getElementById('detailContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('detailContent').innerHTML = '<div class="alert alert-danger">Error loading detail</div>';
            });
    }
    </script>
</body>
</html>
<?php
$conn->close();
?>
