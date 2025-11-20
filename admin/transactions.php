<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Kelola Transaksi Cicilan';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $customer_id = intval($_POST['customer_id']);
        $product_id = intval($_POST['product_id']);
        $harga_modal = floatval($_POST['harga_modal']);
        $margin = floatval($_POST['margin']);
        $tenor = intval($_POST['tenor']);
        $tanggal_akad = sanitize($_POST['tanggal_akad']);
        $keterangan = sanitize($_POST['keterangan']);

        // Calculate
        $total_harga = $harga_modal + $margin;
        $angsuran_perbulan = $total_harga / $tenor;
        $sisa_hutang = $total_harga;
        $nomor_kontrak = generateNomorKontrak();

        $stmt = $conn->prepare("INSERT INTO transactions (nomor_kontrak, customer_id, product_id, harga_modal, margin, total_harga, tenor, angsuran_perbulan, sisa_hutang, tanggal_akad, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siidddiidss", $nomor_kontrak, $customer_id, $product_id, $harga_modal, $margin, $total_harga, $tenor, $angsuran_perbulan, $sisa_hutang, $tanggal_akad, $keterangan);

        if ($stmt->execute()) {
            setFlashMessage('success', "Transaksi cicilan berhasil dibuat. Nomor Kontrak: $nomor_kontrak");
        } else {
            setFlashMessage('error', 'Gagal membuat transaksi');
        }
        $stmt->close();

        header('Location: /admin/transactions.php');
        exit;
    } elseif ($action === 'update_status') {
        $id = intval($_POST['id']);
        $status = sanitize($_POST['status']);

        if ($conn->query("UPDATE transactions SET status = '$status' WHERE id = $id")) {
            setFlashMessage('success', 'Status transaksi berhasil diubah');
        } else {
            setFlashMessage('error', 'Gagal mengubah status transaksi');
        }

        header('Location: /admin/transactions.php');
        exit;
    }
}

// Get filter
$filter_status = $_GET['status'] ?? 'all';
$filter_customer = $_GET['customer'] ?? '';

// Build query
$where = [];
if ($filter_status !== 'all') {
    $where[] = "t.status = '$filter_status'";
}
if (!empty($filter_customer)) {
    $where[] = "c.nama_lengkap LIKE '%$filter_customer%'";
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get transactions
$transactions = $conn->query("
    SELECT t.*, c.nama_lengkap, c.telepon, p.nama_barang
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    JOIN products p ON t.product_id = p.id
    $where_sql
    ORDER BY t.created_at DESC
");

// Get customers and products for dropdown
$customers = $conn->query("SELECT id, nama_lengkap FROM customers ORDER BY nama_lengkap");
$products = $conn->query("SELECT id, nama_barang, harga_modal FROM products WHERE is_active = 1 ORDER BY nama_barang");

include '../includes/header.php';
?>

<div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kelola Transaksi Cicilan</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Akad Murabahah</p>
    </div>
    <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
        + Buat Transaksi Baru
    </button>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Semua</option>
                <option value="aktif" <?php echo $filter_status === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="lunas" <?php echo $filter_status === 'lunas' ? 'selected' : ''; ?>>Lunas</option>
                <option value="batal" <?php echo $filter_status === 'batal' ? 'selected' : ''; ?>>Batal</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Pelanggan</label>
            <input type="text" name="customer" value="<?php echo htmlspecialchars($filter_customer); ?>" placeholder="Cari nama..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nomor Kontrak</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pelanggan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Barang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sisa Hutang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while ($trans = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nomor_kontrak']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo formatTanggal($trans['tanggal_akad']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_lengkap']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($trans['telepon']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_barang']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo $trans['tenor']; ?>x angsuran</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo formatRupiah($trans['total_harga']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo formatRupiah($trans['angsuran_perbulan']); ?>/bln</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-red-600 dark:text-red-400">
                                <?php echo formatRupiah($trans['sisa_hutang']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($trans['status']); ?>">
                                    <?php echo ucfirst($trans['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="/admin/transaction-detail.php?id=<?php echo $trans['id']; ?>" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">Detail</a>
                                <?php if ($trans['status'] === 'aktif'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?php echo $trans['id']; ?>">
                                    <input type="hidden" name="status" value="batal">
                                    <button type="submit" onclick="return confirm('Batalkan transaksi ini?')" class="text-red-600 hover:text-red-900 dark:text-red-400">Batalkan</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada transaksi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Transaction -->
<div id="transactionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Buat Transaksi Cicilan Baru</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form method="POST" onsubmit="return validateForm()">
            <input type="hidden" name="action" value="add">

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-2">Pelanggan *</label>
                        <select name="customer_id" id="customer_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Pilih Pelanggan</option>
                            <?php $customers->data_seek(0); while ($cust = $customers->fetch_assoc()): ?>
                                <option value="<?php echo $cust['id']; ?>"><?php echo htmlspecialchars($cust['nama_lengkap']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-2">Barang *</label>
                        <select name="product_id" id="product_id" required onchange="updateHargaModal()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Pilih Barang</option>
                            <?php $products->data_seek(0); while ($prod = $products->fetch_assoc()): ?>
                                <option value="<?php echo $prod['id']; ?>" data-modal="<?php echo $prod['harga_modal']; ?>">
                                    <?php echo htmlspecialchars($prod['nama_barang']); ?> - <?php echo formatRupiah($prod['harga_modal']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-2">Harga Modal *</label>
                        <input type="number" name="harga_modal" id="harga_modal" step="0.01" required readonly class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-2">Margin (Rp) *</label>
                        <input type="number" name="margin" id="margin" step="0.01" required onkeyup="calculateTotal()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-2">Tenor (Bulan) *</label>
                        <select name="tenor" id="tenor" required onchange="calculateTotal()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Pilih</option>
                            <option value="6">6 Bulan</option>
                            <option value="12">12 Bulan</option>
                            <option value="18">18 Bulan</option>
                            <option value="24">24 Bulan</option>
                            <option value="36">36 Bulan</option>
                        </select>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Total Harga (Modal + Margin)</p>
                            <p id="display_total" class="text-2xl font-bold text-gray-900 dark:text-white">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">Angsuran Per Bulan</p>
                            <p id="display_angsuran" class="text-2xl font-bold text-blue-600 dark:text-blue-400">Rp 0</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Tanggal Akad *</label>
                    <input type="date" name="tanggal_akad" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Keterangan</label>
                    <textarea name="keterangan" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                    Buat Transaksi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('transactionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('transactionModal').classList.add('hidden');
}

function updateHargaModal() {
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    const hargaModal = option.getAttribute('data-modal') || 0;
    document.getElementById('harga_modal').value = hargaModal;
    calculateTotal();
}

function calculateTotal() {
    const hargaModal = parseFloat(document.getElementById('harga_modal').value) || 0;
    const margin = parseFloat(document.getElementById('margin').value) || 0;
    const tenor = parseInt(document.getElementById('tenor').value) || 0;

    const total = hargaModal + margin;
    const angsuran = tenor > 0 ? total / tenor : 0;

    document.getElementById('display_total').textContent = formatRupiah(total);
    document.getElementById('display_angsuran').textContent = formatRupiah(angsuran);
}

function formatRupiah(amount) {
    return 'Rp ' + amount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function validateForm() {
    const margin = parseFloat(document.getElementById('margin').value) || 0;
    if (margin <= 0) {
        alert('Margin harus lebih dari 0');
        return false;
    }
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>
