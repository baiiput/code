<?php
require_once '../config/database.php';
require_once '../config/base_path.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCustomer();

$pageTitle = 'Dashboard Pelanggan';

$currentUser = getCurrentUser();
$customer_id = $currentUser['customer_id'];

// Get customer info
$customer = $conn->query("SELECT * FROM customers WHERE id = $customer_id")->fetch_assoc();

// Get active transactions
$activeTransactions = $conn->query("
    SELECT t.*, p.nama_barang
    FROM transactions t
    JOIN products p ON t.product_id = p.id
    WHERE t.customer_id = $customer_id AND t.status = 'aktif'
    ORDER BY t.tanggal_akad DESC
");

// Get completed transactions
$completedTransactions = $conn->query("
    SELECT t.*, p.nama_barang
    FROM transactions t
    JOIN products p ON t.product_id = p.id
    WHERE t.customer_id = $customer_id AND t.status = 'lunas'
    ORDER BY t.updated_at DESC
    LIMIT 3
");

// Get recent payments
$recentPayments = $conn->query("
    SELECT p.*, t.nomor_kontrak
    FROM payments p
    JOIN transactions t ON p.transaction_id = t.id
    WHERE t.customer_id = $customer_id AND p.status = 'success'
    ORDER BY p.tanggal_bayar DESC
    LIMIT 5
");

// Calculate total debt
$totalDebt = $conn->query("SELECT SUM(sisa_hutang) as total FROM transactions WHERE customer_id = $customer_id AND status = 'aktif'")->fetch_assoc()['total'] ?? 0;

include '../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Selamat Datang, <?php echo htmlspecialchars($customer['nama_lengkap']); ?></h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Dashboard Cicilan Anda</p>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Cicilan Aktif</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo $activeTransactions->num_rows; ?></p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Sisa Hutang</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-2"><?php echo formatRupiah($totalDebt); ?></p>
            </div>
            <div class="bg-red-100 dark:bg-red-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Cicilan Lunas</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2"><?php echo $conn->query("SELECT COUNT(*) as total FROM transactions WHERE customer_id = $customer_id AND status = 'lunas'")->fetch_assoc()['total']; ?></p>
            </div>
            <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Active Transactions -->
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Cicilan Aktif</h2>
    <?php if ($activeTransactions->num_rows > 0): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php while ($trans = $activeTransactions->fetch_assoc()):
                $sisaAngsuran = calculateRemainingInstallments($trans['total_harga'], $trans['total_dibayar'], $trans['angsuran_perbulan']);
                $progress = $trans['total_harga'] > 0 ? ($trans['total_dibayar'] / $trans['total_harga']) * 100 : 0;
            ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_barang']); ?></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($trans['nomor_kontrak']); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($trans['status']); ?>">
                            <?php echo ucfirst($trans['status']); ?>
                        </span>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Harga</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($trans['total_harga']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Angsuran/Bulan</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($trans['angsuran_perbulan']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Sisa Angsuran</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo $sisaAngsuran; ?>x</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Sisa Hutang</span>
                            <span class="text-lg font-bold text-red-600 dark:text-red-400"><?php echo formatRupiah($trans['sisa_hutang']); ?></span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>Progress</span>
                            <span><?php echo number_format($progress, 1); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full transition-all" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <a href="<?php echo baseUrl('customer/payment.php?id=' . $trans['id']); ?>" class="text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                            Bayar Online
                        </a>
                        <a href="<?php echo baseUrl('customer/upload_bukti.php?id=' . $trans['id']); ?>" class="text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                            Upload Bukti
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-600 dark:text-gray-400">Anda belum memiliki cicilan aktif</p>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Payments -->
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Riwayat Pembayaran Terakhir</h2>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <?php if ($recentPayments->num_rows > 0): ?>
            <div class="space-y-4">
                <?php while ($pay = $recentPayments->fetch_assoc()): ?>
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white"><?php echo formatRupiah($pay['nominal']); ?></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($pay['nomor_kontrak']); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-500"><?php echo formatDateTime($pay['tanggal_bayar']); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($pay['status']); ?>">
                            <?php echo ucfirst($pay['status']); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600 dark:text-gray-400 text-center py-4">Belum ada riwayat pembayaran</p>
        <?php endif; ?>
    </div>
</div>

<!-- Completed Transactions -->
<?php if ($completedTransactions->num_rows > 0): ?>
<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Cicilan Lunas</h2>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="space-y-4">
            <?php while ($trans = $completedTransactions->fetch_assoc()): ?>
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_barang']); ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($trans['nomor_kontrak']); ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Lunas: <?php echo formatTanggal($trans['updated_at']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($trans['total_harga']); ?></p>
                        <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($trans['status']); ?>">
                            Lunas
                        </span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
