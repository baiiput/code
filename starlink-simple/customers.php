<?php
require_once 'config.php';
$pageTitle = 'Data Pelanggan';

// Handle delete
if (isset($_GET['delete']) && canManageCustomers()) {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    setFlash('success', 'Pelanggan berhasil dihapus.');
    redirect('customers.php');
}

// Get filters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$paket = $_GET['paket'] ?? '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(nama LIKE ? OR email_client LIKE ? OR nomor_cs LIKE ? OR kit_number LIKE ? OR serial_number LIKE ? OR acc_no LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status) {
    $where[] = "status_langganan = ?";
    $params[] = $status;
}

if ($paket) {
    $where[] = "paket = ?";
    $params[] = $paket;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get customers
$stmt = $pdo->prepare("SELECT * FROM customers $whereClause ORDER BY created_at DESC");
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get unique pakets for filter
$pakets = $pdo->query("SELECT DISTINCT paket FROM customers WHERE paket IS NOT NULL AND paket != '' ORDER BY paket")->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>

<div class="space-y-6">
    <!-- Search and Filter -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama, email, nomor CS, kit number..." class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            </div>
            <div class="sm:w-48">
                <select name="status" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= $status == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $status == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    <option value="lunas" <?= $status == 'lunas' ? 'selected' : '' ?>>Lunas</option>
                    <option value="belum_bayar" <?= $status == 'belum_bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                </select>
            </div>
            <div class="sm:w-48">
                <select name="paket" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">Semua Paket</option>
                    <?php foreach ($pakets as $p): ?>
                    <option value="<?= e($p) ?>" <?= $paket == $p ? 'selected' : '' ?>><?= e($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    Cari
                </button>
                <?php if ($search || $status || $paket): ?>
                <a href="customers.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Reset
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Action Button -->
    <?php if (canManageCustomers()): ?>
    <div class="flex justify-end">
        <a href="customer_form.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            + Tambah Pelanggan
        </a>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Nama</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Email Client</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Nomor CS</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Paket</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Jatuh Tempo</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-base text-gray-500 dark:text-gray-400">Tidak ada data pelanggan</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-base font-medium text-gray-900 dark:text-white"><?= e($customer['nama']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-base text-gray-600 dark:text-gray-400"><?= e($customer['email_client'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-base text-gray-600 dark:text-gray-400"><?= e($customer['nomor_cs'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-base text-gray-600 dark:text-gray-400"><?= e($customer['paket'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $statusColors = [
                                'aktif' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'nonaktif' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                'lunas' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'belum_bayar' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            ];
                            $color = $statusColors[$customer['status_langganan']] ?? $statusColors['aktif'];
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $color ?>">
                                <?= ucfirst(str_replace('_', ' ', $customer['status_langganan'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-base text-gray-600 dark:text-gray-400"><?= formatDate($customer['tanggal_jatuh_tempo']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-base font-medium">
                            <a href="customer_view.php?id=<?= $customer['id'] ?>" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 mr-3">Lihat</a>
                            <?php if (canManageCustomers()): ?>
                            <a href="customer_form.php?id=<?= $customer['id'] ?>" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 mr-3">Edit</a>
                            <a href="customers.php?delete=<?= $customer['id'] ?>" onclick="return confirm('Yakin ingin menghapus?')" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</a>
                            <?php endif; ?>
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
