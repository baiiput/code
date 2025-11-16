<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse', 'staff_keuangan']);

$conn = getDBConnection();
$user = getCurrentUser();

$report_type = $_GET['type'] ?? 'stock';

$page_title = 'Laporan';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Laporan</h1>
        <button class="btn btn-success btn-export" onclick="exportCurrentReport()">
            <i class="fas fa-file-excel"></i> <span class="btn-text">Export ke Excel</span>
        </button>
    </div>
    
    <!-- Report Type Tabs -->
    <div class="report-tabs">
        <a href="?type=stock" class="tab-item <?php echo $report_type === 'stock' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i> Laporan Stok
        </a>
        <a href="?type=low_stock" class="tab-item <?php echo $report_type === 'low_stock' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Stok Rendah
        </a>
        <a href="?type=stock_movement" class="tab-item <?php echo $report_type === 'stock_movement' ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt"></i> Pergerakan Stok
        </a>
        <a href="?type=distribution" class="tab-item <?php echo $report_type === 'distribution' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i> Distribusi Cabang
        </a>
        <?php if (hasRole(['admin', 'staff_keuangan'])): ?>
        <a href="?type=financial" class="tab-item <?php echo $report_type === 'financial' ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i> Laporan Keuangan
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Report Content -->
    <div class="report-content">
        <?php
        switch ($report_type) {
            case 'stock':
                include 'reports/stock_report.php';
                break;
            case 'low_stock':
                include 'reports/low_stock_report.php';
                break;
            case 'stock_movement':
                include 'reports/movement_report.php';
                break;
            case 'distribution':
                include 'reports/distribution_report.php';
                break;
            case 'financial':
                if (hasRole(['admin', 'staff_keuangan'])) {
                    include 'reports/financial_report.php';
                }
                break;
            default:
                echo '<p class="text-center" style="padding: 40px;">Pilih tipe laporan</p>';
        }
        ?>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 12px;
    flex-wrap: wrap;
}

.page-header h1 {
    font-size: 24px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-export {
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    font-size: 14px;
}

.report-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    background: var(--bg-card);
    padding: 12px;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
}

.tab-item {
    padding: 12px 20px;
    text-decoration: none;
    color: var(--text-secondary);
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.tab-item:hover {
    background: var(--bg-primary);
    color: var(--text-primary);
}

.tab-item.active {
    background: var(--primary-color);
    color: white;
}

.report-content {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 24px;
    box-shadow: var(--shadow-sm);
}

.report-content table {
    width: 100%;
    border-collapse: collapse;
}

.report-content th,
.report-content td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

/* Mobile Optimization */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .page-header h1 {
        font-size: 20px;
        text-align: center;
        justify-content: center;
    }
    
    .btn-export {
        width: 100%;
        justify-content: center;
        padding: 12px;
        font-size: 15px;
    }
    
    .report-tabs {
        flex-direction: column;
        padding: 8px;
        gap: 6px;
    }
    
    .tab-item {
        padding: 14px 12px;
        font-size: 15px;
        width: 100%;
        justify-content: center;
        min-height: 48px;
    }
    
    .tab-item i {
        font-size: 16px;
    }
    
    .report-content {
        padding: 16px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Wrapper untuk scroll dengan shadow indicator */
    .report-content::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 40px;
        background: linear-gradient(to left, rgba(0,0,0,0.1), transparent);
        pointer-events: none;
        z-index: 1;
    }
    
    .report-content table {
        font-size: 12px;
        min-width: 100%;
        display: table;
    }
    
    .report-content th,
    .report-content td {
        padding: 10px 8px;
        white-space: nowrap;
        font-size: 12px;
    }
    
    .report-content th {
        font-weight: 600;
        background: var(--bg-primary);
        position: sticky;
        top: 0;
        z-index: 2;
    }
}

@media (max-width: 480px) {
    .page-header h1 {
        font-size: 18px;
    }
    
    .page-header h1 i {
        font-size: 18px;
    }
    
    .btn-export {
        font-size: 14px;
        padding: 12px;
    }
    
    .tab-item {
        padding: 12px 10px;
        font-size: 14px;
    }
    
    .report-content {
        padding: 12px;
    }
    
    .report-content table {
        font-size: 11px;
    }
    
    .report-content th,
    .report-content td {
        padding: 8px 6px;
        font-size: 11px;
    }
}

/* Touch optimization */
@media (hover: none) and (pointer: coarse) {
    .tab-item {
        min-height: 48px;
    }
    
    .tab-item:active {
        transform: scale(0.98);
        opacity: 0.9;
    }
}

/* Financial Report Cards - Compact & Detailed */
.financial-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.financial-card {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 16px;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid var(--primary-color);
}

.financial-card.debit {
    border-left-color: #28a745;
}

.financial-card.kredit {
    border-left-color: #dc3545;
}

.financial-card.net {
    border-left-color: #007bff;
}

.financial-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.financial-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.financial-card.debit .financial-card-icon {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.financial-card.kredit .financial-card-icon {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.financial-card.net .financial-card-icon {
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.financial-card-title {
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 600;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.financial-card-amount {
    font-size: 22px;
    font-weight: 700;
    margin: 4px 0 0 0;
    line-height: 1.2;
}

.financial-card.debit .financial-card-amount {
    color: #28a745;
}

.financial-card.kredit .financial-card-amount {
    color: #dc3545;
}

.financial-card-detail {
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--border-color);
    line-height: 1.4;
}

/* Bottom Summary */
.financial-bottom-summary {
    margin-top: 20px;
    padding: 16px;
    background: var(--bg-primary);
    border-radius: 12px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    font-weight: 600;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.summary-label {
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 500;
}

.summary-value {
    font-size: 18px;
    font-weight: 700;
}

.summary-value.debit-value {
    color: var(--success-color);
}

.summary-value.kredit-value {
    color: var(--danger-color);
}

/* Mobile Financial Cards */
@media (max-width: 768px) {
    .financial-summary {
        grid-template-columns: 1fr;
        gap: 12px;
        margin-bottom: 20px;
    }
    
    .financial-card {
        padding: 14px;
    }
    
    .financial-card-header {
        flex-direction: row;
        align-items: center;
        margin-bottom: 6px;
    }
    
    .financial-card-icon {
        width: 32px;
        height: 32px;
        font-size: 14px;
        flex-shrink: 0;
    }
    
    .financial-card-title {
        font-size: 11px;
    }
    
    .financial-card-amount {
        font-size: 18px;
        margin: 2px 0 0 0;
    }
    
    /* WAJIB: Rp dan nominal 1 baris */
    .financial-card-amount span {
        display: inline-block;
        white-space: nowrap;
    }
    
    .financial-card-detail {
        font-size: 10px;
        margin-top: 6px;
        padding-top: 6px;
    }
    
    /* Bottom Summary Mobile */
    .financial-bottom-summary {
        grid-template-columns: 1fr;
        gap: 12px;
        padding: 14px;
    }
    
    .summary-item {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: var(--bg-card);
        border-radius: 8px;
    }
    
    .summary-label {
        font-size: 13px;
    }
    
    .summary-value {
        font-size: 16px;
        white-space: nowrap;
    }
}

@media (max-width: 480px) {
    .financial-card {
        padding: 12px;
    }
    
    .financial-card-header {
        gap: 8px;
    }
    
    .financial-card-icon {
        width: 28px;
        height: 28px;
        font-size: 13px;
    }
    
    .financial-card-title {
        font-size: 10px;
    }
    
    .financial-card-amount {
        font-size: 16px;
    }
    
    .financial-card-detail {
        font-size: 9px;
    }
    
    /* Bottom Summary Small Mobile */
    .financial-bottom-summary {
        padding: 12px;
        gap: 10px;
    }
    
    .summary-item {
        padding: 8px;
    }
    
    .summary-label {
        font-size: 12px;
    }
    
    .summary-value {
        font-size: 15px;
    }
}
</style>

<script>
function exportCurrentReport() {
    const reportType = '<?php echo $report_type; ?>';
    const table = document.querySelector('.report-content table');
    
    if (table) {
        const filename = 'laporan-' + reportType + '-<?php echo date('Y-m-d'); ?>.csv';
        exportTableToCSV(table.id || 'reportTable', filename);
    } else {
        alert('Tidak ada data untuk di-export');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
