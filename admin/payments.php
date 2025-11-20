<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Monitoring Pembayaran';

// Get filter
$filter_status = $_GET['status'] ?? 'all';
$filter_search = $_GET['search'] ?? '';

// Build query
$where = [];
if ($filter_status !== 'all') {
    $where[] = "p.status = '$filter_status'";
}
if (!empty($filter_search)) {
    $where[] = "(c.nama_lengkap LIKE '%$filter_search%' OR t.nomor_kontrak LIKE '%$filter_search%')";
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get payments
$payments = $conn->query("
    SELECT p.*, t.nomor_kontrak, c.nama_lengkap,
           xp.xendit_invoice_url, xp.xendit_invoice_id, xp.status as xendit_status
    FROM payments p
    JOIN transactions t ON p.transaction_id = t.id
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN xendit_payments xp ON xp.payment_id = p.id
    $where_sql
    ORDER BY p.created_at DESC
");

// Get statistics
$totalPending = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'")->fetch_assoc()['total'];
$totalSuccess = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'success'")->fetch_assoc()['total'];
$totalAmount = $conn->query("SELECT SUM(nominal) as total FROM payments WHERE status = 'success'")->fetch_assoc()['total'] ?? 0;

include '../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Monitoring Pembayaran</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Daftar semua pembayaran cicilan</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Pembayaran Pending</p>
                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2"><?php echo $totalPending; ?></p>
            </div>
            <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Pembayaran Berhasil</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2"><?php echo $totalSuccess; ?></p>
            </div>
            <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Pembayaran</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo formatRupiah($totalAmount); ?></p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Semua</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="success" <?php echo $filter_status === 'success' ? 'selected' : ''; ?>>Success</option>
                <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cari</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Nama atau Nomor Kontrak..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">Filter</button>
        </div>
    </form>
</div>

<!-- Payments Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Payment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nomor Kontrak</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nominal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($payments->num_rows > 0): ?>
                    <?php while ($pay = $payments->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">#<?php echo $pay['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($pay['nama_lengkap']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($pay['nomor_kontrak']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($pay['nominal']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo $pay['tanggal_bayar'] ? formatDateTime($pay['tanggal_bayar']) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($pay['status']); ?>">
                                    <?php echo ucfirst($pay['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($pay['xendit_invoice_url'] && $pay['status'] === 'pending'): ?>
                                    <a href="<?php echo htmlspecialchars($pay['xendit_invoice_url']); ?>" target="_blank" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-2">
                                        Link Bayar
                                    </a>
                                <?php endif; ?>
                                <a href="/admin/transaction-detail.php?id=<?php echo $pay['transaction_id']; ?>" class="text-gray-600 hover:text-gray-900 dark:text-gray-400">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada pembayaran</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
