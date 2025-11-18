<?php
require_once 'config.php';
requireRole(['super_admin']);
$pageTitle = 'Pembayaran';

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    setFlash('success', 'Pembayaran berhasil dihapus.');
    redirect('payments.php');
}

// Get filters
$customerId = $_GET['customer_id'] ?? '';
$periode = $_GET['periode'] ?? '';

// Build query
$where = [];
$params = [];

if ($customerId) {
    $where[] = "p.customer_id = ?";
    $params[] = $customerId;
}

if ($periode) {
    $where[] = "p.periode_bulan = ?";
    $params[] = $periode;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get payments
$stmt = $pdo->prepare("
    SELECT p.*, c.nama as customer_nama, u.name as user_name
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    JOIN users u ON p.user_id = u.id
    $whereClause
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get customers for filter
$customers = $pdo->query("SELECT id, nama FROM customers ORDER BY nama")->fetchAll();

// Get periods for filter
$periodes = $pdo->query("SELECT DISTINCT periode_bulan FROM payments ORDER BY periode_bulan DESC")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>

<div class="space-y-6">
    <!-- Filters -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <select name="customer_id" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">Semua Pelanggan</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $customerId == $c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:w-48">
                <select name="periode" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">Semua Periode</option>
                    <?php foreach ($periodes as $p): ?>
                    <option value="<?= e($p) ?>" <?= $periode == $p ? 'selected' : '' ?>><?= e($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    Filter
                </button>
                <?php if ($customerId || $periode): ?>
                <a href="payments.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700">
                    Reset
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Action Button -->
    <div class="flex justify-end">
        <a href="payment_form.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            + Input Pembayaran
        </a>
    </div>

    <!-- Table -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pelanggan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Periode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nominal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal Bayar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Diinput Oleh</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data pembayaran</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= e($payment['customer_nama']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= e($payment['periode_bulan']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= formatRupiah($payment['nominal']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= formatDate($payment['tanggal_bayar']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= e($payment['user_name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="payments.php?delete=<?= $payment['id'] ?>" onclick="return confirm('Yakin ingin menghapus?')" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
