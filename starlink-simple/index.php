<?php
require_once 'config.php';
$pageTitle = 'Dashboard';

// Get statistics
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$activeCustomers = $pdo->query("SELECT COUNT(*) FROM customers WHERE status_langganan = 'aktif'")->fetchColumn();
$paidCustomers = $pdo->query("SELECT COUNT(*) FROM customers WHERE status_langganan = 'lunas'")->fetchColumn();
$unpaidCustomers = $pdo->query("SELECT COUNT(*) FROM customers WHERE status_langganan = 'belum_bayar'")->fetchColumn();

// Get recent payments
$recentPayments = $pdo->query("
    SELECT p.*, c.nama as customer_nama, u.name as user_name
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 10
")->fetchAll();

// Get upcoming due dates
$upcomingDue = $pdo->query("
    SELECT * FROM customers
    WHERE tanggal_jatuh_tempo >= CURDATE()
    AND tanggal_jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY tanggal_jatuh_tempo
    LIMIT 10
")->fetchAll();

// Monthly stats
$currentMonth = date('F Y');
$monthlyPayments = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM payments WHERE periode_bulan = ?");
$monthlyPayments->execute([$currentMonth]);
$monthlyTotal = $monthlyPayments->fetchColumn();

$monthlyCount = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE periode_bulan = ?");
$monthlyCount->execute([$currentMonth]);
$monthlyPaymentCount = $monthlyCount->fetchColumn();

require_once 'includes/header.php';
?>

<div class="space-y-6">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Customers -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-lg bg-primary-100 dark:bg-primary-900 p-3">
                    <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pelanggan</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($totalCustomers) ?></p>
                </div>
            </div>
        </div>

        <!-- Active -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-lg bg-green-100 dark:bg-green-900 p-3">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktif</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($activeCustomers) ?></p>
                </div>
            </div>
        </div>

        <!-- Paid -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-lg bg-blue-100 dark:bg-blue-900 p-3">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Lunas</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($paidCustomers) ?></p>
                </div>
            </div>
        </div>

        <!-- Unpaid -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-lg bg-red-100 dark:bg-red-900 p-3">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum Bayar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($unpaidCustomers) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Stats -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pembayaran Bulan Ini (<?= $currentMonth ?>)</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Nominal</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= formatRupiah($monthlyTotal) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Jumlah Transaksi</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($monthlyPaymentCount) ?></p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Payments -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pembayaran Terbaru</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($recentPayments)): ?>
                <div class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada pembayaran</div>
                <?php else: ?>
                <?php foreach ($recentPayments as $payment): ?>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= e($payment['customer_nama']) ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($payment['periode_bulan']) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= formatRupiah($payment['nominal']) ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= formatDate($payment['tanggal_bayar']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Due -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Jatuh Tempo Terdekat</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php if (empty($upcomingDue)): ?>
                <div class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada jatuh tempo dalam 7 hari ke depan</div>
                <?php else: ?>
                <?php foreach ($upcomingDue as $customer): ?>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= e($customer['nama']) ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($customer['paket'] ?? 'N/A') ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-orange-600 dark:text-orange-400"><?= formatDate($customer['tanggal_jatuh_tempo']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
