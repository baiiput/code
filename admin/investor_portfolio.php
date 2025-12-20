<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
requireLevel(USER_LEVEL_MANAGER);

$investor_id = $_GET['id'] ?? 0;

// Get investor data
$investor_query = $conn->query("SELECT * FROM investors WHERE id = $investor_id");
if ($investor_query->num_rows === 0) {
    setFlashMessage('error', 'Investor tidak ditemukan');
    header('Location: /admin/investors.php');
    exit;
}
$investor = $investor_query->fetch_assoc();

$pageTitle = 'Portfolio - ' . $investor['nama_investor'];

// Get all transactions for this investor
$transactions = $conn->query("
    SELECT
        ti.*,
        t.nomor_kontrak,
        t.total_harga,
        t.status as trans_status,
        t.tanggal_akad,
        c.nama_lengkap as customer_name,
        p.nama_barang as product_name
    FROM transaction_investors ti
    JOIN transactions t ON ti.transaction_id = t.id
    JOIN customers c ON t.customer_id = c.id
    JOIN products p ON t.product_id = p.id
    WHERE ti.investor_id = $investor_id
    ORDER BY t.tanggal_akad DESC
");

// Get profit history
$profit_history = $conn->query("
    SELECT
        iph.*,
        t.nomor_kontrak
    FROM investor_profit_history iph
    JOIN transactions t ON iph.transaction_id = t.id
    WHERE iph.investor_id = $investor_id
    ORDER BY iph.tanggal_profit DESC
    LIMIT 10
");

include '../includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Portfolio Investor</h1>
        <a href="/admin/investors.php" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
            ← Kembali
        </a>
    </div>
</div>

<!-- Investor Info Card -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Nama Investor</h3>
            <p class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($investor['nama_investor']); ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($investor['kode_investor']); ?></p>
        </div>
        <div>
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Modal</h3>
            <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">Rp <?php echo number_format($investor['total_modal'], 0, ',', '.'); ?></p>
        </div>
        <div>
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Modal Tersedia / Dialokasi</h3>
            <p class="text-lg font-semibold text-green-600 dark:text-green-400">Rp <?php echo number_format($investor['modal_tersedia'], 0, ',', '.'); ?></p>
            <p class="text-sm text-orange-600 dark:text-orange-400">Dialokasi: Rp <?php echo number_format($investor['modal_allocated'], 0, ',', '.'); ?></p>
        </div>
        <div>
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Profit</h3>
            <p class="text-lg font-semibold text-purple-600 dark:text-purple-400">Rp <?php echo number_format($investor['total_profit'], 0, ',', '.'); ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nisbah: <?php echo $investor['nisbah_investor']; ?>% / <?php echo $investor['nisbah_koperasi']; ?>%</p>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Partisipasi Transaksi</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No. Kontrak</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Customer / Barang</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Transaksi</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Modal Dialokasi</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Proporsi</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Profit Share</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while ($trans = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nomor_kontrak']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y', strtotime($trans['tanggal_akad'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['customer_name']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($trans['product_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                Rp <?php echo number_format($trans['total_harga'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-blue-600 dark:text-blue-400">
                                Rp <?php echo number_format($trans['modal_dialokasi'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                <?php echo number_format($trans['proporsi'], 2); ?>%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-purple-600 dark:text-purple-400">
                                Rp <?php echo number_format($trans['profit_share'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                if ($trans['modal_returned']) {
                                    $statusClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                                    $statusText = 'Kembali';
                                } else {
                                    $statusClass = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300';
                                    $statusText = 'Dialokasi';
                                }
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada partisipasi transaksi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Profit History -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Riwayat Profit (10 Terakhir)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No. Kontrak</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Modal Dialokasi</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Proporsi</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nisbah</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Profit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Keterangan</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($profit_history->num_rows > 0): ?>
                    <?php while ($profit = $profit_history->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo date('d/m/Y', strtotime($profit['tanggal_profit'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($profit['nomor_kontrak']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                Rp <?php echo number_format($profit['modal_dialokasi'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                <?php echo number_format($profit['proporsi'], 2); ?>%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                <?php echo $profit['nisbah_investor']; ?>%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-purple-600 dark:text-purple-400">
                                Rp <?php echo number_format($profit['profit_amount'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($profit['keterangan'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada riwayat profit</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
