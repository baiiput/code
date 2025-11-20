<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Dashboard Admin';

// Get statistics
$totalCustomers = $conn->query("SELECT COUNT(*) as total FROM customers")->fetch_assoc()['total'];
$totalProducts = $conn->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1")->fetch_assoc()['total'];
$totalActiveTransactions = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'aktif'")->fetch_assoc()['total'];
$totalLunasTransactions = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE status = 'lunas'")->fetch_assoc()['total'];

// Get total outstanding debt
$totalOutstanding = $conn->query("SELECT SUM(sisa_hutang) as total FROM transactions WHERE status = 'aktif'")->fetch_assoc()['total'] ?? 0;

// Get recent transactions
$recentTransactions = $conn->query("
    SELECT t.*, c.nama_lengkap, p.nama_barang
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    JOIN products p ON t.product_id = p.id
    ORDER BY t.created_at DESC
    LIMIT 5
");

// Get recent payments
$recentPayments = $conn->query("
    SELECT p.*, t.nomor_kontrak, c.nama_lengkap
    FROM payments p
    JOIN transactions t ON p.transaction_id = t.id
    JOIN customers c ON t.customer_id = c.id
    WHERE p.status = 'success'
    ORDER BY p.tanggal_bayar DESC
    LIMIT 5
");

include '../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard Admin</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Selamat datang di sistem Koperasi Syariah</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Pelanggan -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Pelanggan</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $totalCustomers; ?></p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Barang -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Barang</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $totalProducts; ?></p>
            </div>
            <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Cicilan Aktif -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Cicilan Aktif</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $totalActiveTransactions; ?></p>
            </div>
            <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Outstanding -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Piutang</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2"><?php echo formatRupiah($totalOutstanding); ?></p>
            </div>
            <div class="bg-red-100 dark:bg-red-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Transaksi Terbaru</h2>
        </div>
        <div class="p-6">
            <?php if ($recentTransactions->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($trans = $recentTransactions->fetch_assoc()): ?>
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_lengkap']); ?></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($trans['nama_barang']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-500"><?php echo htmlspecialchars($trans['nomor_kontrak']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($trans['sisa_hutang']); ?></p>
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($trans['status']); ?>">
                                    <?php echo ucfirst($trans['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="mt-4">
                    <a href="/admin/transactions.php" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">Lihat Semua →</a>
                </div>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-400 text-center py-4">Belum ada transaksi</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Pembayaran Terbaru</h2>
        </div>
        <div class="p-6">
            <?php if ($recentPayments->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($pay = $recentPayments->fetch_assoc()): ?>
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($pay['nama_lengkap']); ?></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($pay['nomor_kontrak']); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-500"><?php echo formatDateTime($pay['tanggal_bayar']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600 dark:text-green-400"><?php echo formatRupiah($pay['nominal']); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="mt-4">
                    <a href="/admin/payments.php" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">Lihat Semua →</a>
                </div>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-400 text-center py-4">Belum ada pembayaran</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
