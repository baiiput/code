<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();
requireLevel(USER_LEVEL_MANAGER);

$pageTitle = 'Laporan Kas & Transaksi';

// Get filter
$filter_tipe = $_GET['tipe'] ?? 'all';
$filter_kategori = $_GET['kategori'] ?? 'all';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Build query
$where = [];
if ($filter_tipe !== 'all') {
    $where[] = "tipe = '$filter_tipe'";
}
if ($filter_kategori !== 'all') {
    $where[] = "kategori = '$filter_kategori'";
}
if (!empty($filter_date_from)) {
    $where[] = "tanggal_transaksi >= '$filter_date_from'";
}
if (!empty($filter_date_to)) {
    $where[] = "tanggal_transaksi <= '$filter_date_to'";
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get transactions
$kas_trans = $conn->query("
    SELECT * FROM kas_transactions
    $where_sql
    ORDER BY tanggal_transaksi DESC, created_at DESC
    LIMIT 100
");

// Get kas settings (current balances)
$kas_settings = $conn->query("SELECT * FROM kas_settings");
$settings = [];
while ($row = $kas_settings->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include '../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Laporan Kas & Transaksi</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Riwayat semua transaksi kas & modal investor</p>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Kas Koperasi</div>
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">Rp <?php echo number_format($settings['kas_koperasi'] ?? 0, 0, ',', '.'); ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Total Modal Investor</div>
        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">Rp <?php echo number_format($settings['total_modal_investor'] ?? 0, 0, ',', '.'); ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Modal Tersedia</div>
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">Rp <?php echo number_format($settings['modal_tersedia'] ?? 0, 0, ',', '.'); ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Modal Dialokasi</div>
        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">Rp <?php echo number_format($settings['modal_allocated'] ?? 0, 0, ',', '.'); ?></div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipe</label>
            <select name="tipe" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <option value="all" <?php echo $filter_tipe === 'all' ? 'selected' : ''; ?>>Semua</option>
                <option value="masuk" <?php echo $filter_tipe === 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                <option value="keluar" <?php echo $filter_tipe === 'keluar' ? 'selected' : ''; ?>>Keluar</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kategori</label>
            <select name="kategori" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <option value="all" <?php echo $filter_kategori === 'all' ? 'selected' : ''; ?>>Semua</option>
                <option value="investor_in" <?php echo $filter_kategori === 'investor_in' ? 'selected' : ''; ?>>Modal Investor Masuk</option>
                <option value="investor_allocation" <?php echo $filter_kategori === 'investor_allocation' ? 'selected' : ''; ?>>Alokasi ke Transaksi</option>
                <option value="investor_return" <?php echo $filter_kategori === 'investor_return' ? 'selected' : ''; ?>>Return Modal + Profit</option>
                <option value="investor_out" <?php echo $filter_kategori === 'investor_out' ? 'selected' : ''; ?>>Penarikan Investor</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dari Tanggal</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sampai Tanggal</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">Filter</button>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kategori</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nominal</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Saldo Before</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Saldo After</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Keterangan</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($kas_trans->num_rows > 0): ?>
                    <?php while ($trans = $kas_trans->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo date('d/m/Y', strtotime($trans['tanggal_transaksi'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo $trans['tipe'] === 'masuk' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'; ?>">
                                    <?php echo ucfirst($trans['tipe']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($trans['kategori']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold <?php echo $trans['tipe'] === 'masuk' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                Rp <?php echo number_format($trans['nominal'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                Rp <?php echo number_format($trans['saldo_before'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                                Rp <?php echo number_format($trans['saldo_after'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <?php echo htmlspecialchars($trans['keterangan'] ?? '-'); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada transaksi kas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400">Menampilkan 100 transaksi terakhir</p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
