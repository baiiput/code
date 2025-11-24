<?php
session_start();
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Reports';
$current_page = 'reports';
include '../includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
            <i class="fas fa-chart-bar" style="margin-right: 0.5rem; color: var(--primary-color);"></i>
            Laporan
        </h1>
        <p style="color: var(--text-secondary); font-size: 0.875rem;">
            Akses berbagai laporan untuk analisis dan monitoring
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">

        <!-- Stock Report -->
        <a href="stock_report.php" style="text-decoration: none;">
            <div class="report-card" style="background: var(--bg-card); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--border-color); transition: all 0.3s ease; cursor: pointer;">
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--primary-light); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-boxes" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                            Laporan Stok
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.5;">
                            Lihat stok barang di semua warehouse, termasuk nilai total dan detail per item
                        </p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Distribution Report -->
        <a href="distribution_report.php" style="text-decoration: none;">
            <div class="report-card" style="background: var(--bg-card); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--border-color); transition: all 0.3s ease; cursor: pointer;">
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--success-light); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-truck" style="font-size: 1.5rem; color: var(--success-color);"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                            Laporan Distribusi
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.5;">
                            Monitor distribusi barang antar warehouse dan cabang
                        </p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Movement Report -->
        <a href="movement_report.php" style="text-decoration: none;">
            <div class="report-card" style="background: var(--bg-card); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--border-color); transition: all 0.3s ease; cursor: pointer;">
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--info-light); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-exchange-alt" style="font-size: 1.5rem; color: var(--info-color);"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                            Laporan Mutasi
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.5;">
                            Rincian pergerakan stok (masuk, keluar, transfer)
                        </p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Financial Report -->
        <a href="financial_report.php" style="text-decoration: none;">
            <div class="report-card" style="background: var(--bg-card); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--border-color); transition: all 0.3s ease; cursor: pointer;">
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--warning-light); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-money-bill-wave" style="font-size: 1.5rem; color: var(--warning-color);"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                            Laporan Keuangan
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.5;">
                            Laporan transaksi keuangan dan saldo warehouse
                        </p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Low Stock Report -->
        <a href="low_stock_report.php" style="text-decoration: none;">
            <div class="report-card" style="background: var(--bg-card); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow); border: 1px solid var(--border-color); transition: all 0.3s ease; cursor: pointer;">
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <div style="background: var(--danger-light); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; color: var(--danger-color);"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                            Stok Menipis
                        </h3>
                        <p style="color: var(--text-secondary); font-size: 0.875rem; line-height: 1.5;">
                            Alert untuk barang dengan stok di bawah minimum
                        </p>
                    </div>
                </div>
            </div>
        </a>

    </div>
</div>

<style>
.report-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg) !important;
    border-color: var(--primary-color) !important;
}

.report-card:active {
    transform: translateY(-2px);
}
</style>

<?php include '../includes/footer.php'; ?>
