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
        $funded_by_investor = intval($_POST['funded_by_investor'] ?? 1);

        // Calculate
        $total_harga = $harga_modal + $margin;
        $angsuran_perbulan = $total_harga / $tenor;
        $sisa_hutang = $total_harga;
        $nomor_kontrak = generateNomorKontrak();
        $created_by = getCurrentUser()['id'];

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert transaction
            $stmt = $conn->prepare("INSERT INTO transactions (nomor_kontrak, customer_id, product_id, harga_modal, margin, total_harga, tenor, angsuran_perbulan, sisa_hutang, tanggal_akad, keterangan, created_by, funded_by_investor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siidddiidssii", $nomor_kontrak, $customer_id, $product_id, $harga_modal, $margin, $total_harga, $tenor, $angsuran_perbulan, $sisa_hutang, $tanggal_akad, $keterangan, $created_by, $funded_by_investor);
            $stmt->execute();
            $transaction_id = $stmt->insert_id;
            $stmt->close();

            // If funded by investor, process investor allocations
            if ($funded_by_investor == 1 && isset($_POST['investors'])) {
                $investors = $_POST['investors'];
                $allocations = $_POST['allocations'];
                $total_allocated = 0;

                foreach ($investors as $index => $investor_id) {
                    if (empty($investor_id) || empty($allocations[$index])) continue;

                    $investor_id = intval($investor_id);
                    $modal_dialokasi = floatval($allocations[$index]);
                    $total_allocated += $modal_dialokasi;

                    // Check investor modal tersedia
                    $inv_check = $conn->query("SELECT modal_tersedia, minimal_alokasi, nama_investor FROM investors WHERE id = $investor_id AND status = 'aktif'");
                    if ($inv_check->num_rows === 0) {
                        throw new Exception("Investor ID $investor_id tidak ditemukan atau tidak aktif");
                    }
                    $inv = $inv_check->fetch_assoc();

                    if ($modal_dialokasi < $inv['minimal_alokasi']) {
                        throw new Exception("Alokasi untuk {$inv['nama_investor']} kurang dari minimal alokasi (Rp " . number_format($inv['minimal_alokasi'], 0, ',', '.') . ")");
                    }

                    if ($modal_dialokasi > $inv['modal_tersedia']) {
                        throw new Exception("Modal tersedia {$inv['nama_investor']} tidak mencukupi (Tersedia: Rp " . number_format($inv['modal_tersedia'], 0, ',', '.') . ")");
                    }
                }

                // Validate total allocation equals harga_modal
                if (abs($total_allocated - $harga_modal) > 0.01) {
                    throw new Exception("Total alokasi investor (Rp " . number_format($total_allocated, 0, ',', '.') . ") harus sama dengan harga modal (Rp " . number_format($harga_modal, 0, ',', '.') . ")");
                }

                // Insert investor allocations and update investor balances
                foreach ($investors as $index => $investor_id) {
                    if (empty($investor_id) || empty($allocations[$index])) continue;

                    $investor_id = intval($investor_id);
                    $modal_dialokasi = floatval($allocations[$index]);
                    $proporsi = ($modal_dialokasi / $total_allocated) * 100;

                    // Insert to transaction_investors
                    $stmt = $conn->prepare("INSERT INTO transaction_investors (transaction_id, investor_id, modal_dialokasi, proporsi) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iidd", $transaction_id, $investor_id, $modal_dialokasi, $proporsi);
                    $stmt->execute();
                    $stmt->close();

                    // Update investor balance
                    $conn->query("UPDATE investors SET modal_tersedia = modal_tersedia - $modal_dialokasi, modal_allocated = modal_allocated + $modal_dialokasi WHERE id = $investor_id");

                    // Log to kas_transactions
                    $inv_info = $conn->query("SELECT nama_investor FROM investors WHERE id = $investor_id")->fetch_assoc();
                    $kas_before = floatval($conn->query("SELECT setting_value FROM kas_settings WHERE setting_key = 'modal_allocated'")->fetch_assoc()['setting_value']);
                    $kas_after = $kas_before + $modal_dialokasi;
                    $ket_kas = "Alokasi modal investor {$inv_info['nama_investor']} ke transaksi $nomor_kontrak";

                    $stmt = $conn->prepare("INSERT INTO kas_transactions (tipe, kategori, nominal, saldo_before, saldo_after, referensi_type, referensi_id, keterangan, tanggal_transaksi, created_by) VALUES ('keluar', 'investor_allocation', ?, ?, ?, 'transaction', ?, ?, ?, ?)");
                    $stmt->bind_param("dddissi", $modal_dialokasi, $kas_before, $kas_after, $transaction_id, $ket_kas, $tanggal_akad, $created_by);
                    $stmt->execute();
                    $stmt->close();
                }

                // Update kas settings
                $conn->query("UPDATE kas_settings SET setting_value = setting_value - $total_allocated WHERE setting_key = 'modal_tersedia'");
                $conn->query("UPDATE kas_settings SET setting_value = setting_value + $total_allocated WHERE setting_key = 'modal_allocated'");
            }

            $conn->commit();
            setFlashMessage('success', "Transaksi cicilan berhasil dibuat. Nomor Kontrak: $nomor_kontrak");
        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Gagal membuat transaksi: ' . $e->getMessage());
        }

        header('Location: /admin/transactions.php');
        exit;
    } elseif ($action === 'update_status') {
        // Staff tidak boleh update status (batalkan)
        if (isStaff()) {
            setFlashMessage('error', 'Staff tidak memiliki akses untuk mengubah status transaksi');
            header('Location: /admin/transactions.php');
            exit;
        }

        $id = intval($_POST['id']);
        $status = sanitize($_POST['status']);

        // Get transaction details
        $trans = $conn->query("SELECT funded_by_investor, total_harga, status as current_status FROM transactions WHERE id = $id")->fetch_assoc();

        if (!$trans) {
            setFlashMessage('error', 'Transaksi tidak ditemukan');
            header('Location: /admin/transactions.php');
            exit;
        }

        $conn->begin_transaction();
        try {
            // If cancelling transaction that was funded by investor, return the allocated funds
            if ($status === 'batal' && $trans['funded_by_investor'] == 1 && $trans['current_status'] !== 'batal') {
                // Get investor allocations for this transaction
                $allocations = $conn->query("SELECT investor_id, modal_dialokasi FROM transaction_investors WHERE transaction_id = $id");

                while ($alloc = $allocations->fetch_assoc()) {
                    $investor_id = $alloc['investor_id'];
                    $modal_dialokasi = $alloc['modal_dialokasi'];

                    // Return funds to investor
                    $conn->query("UPDATE investors SET modal_allocated = modal_allocated - $modal_dialokasi, modal_tersedia = modal_tersedia + $modal_dialokasi WHERE id = $investor_id");
                }

                // Delete investor allocations
                $conn->query("DELETE FROM transaction_investors WHERE transaction_id = $id");
            }

            // Update transaction status
            if ($conn->query("UPDATE transactions SET status = '$status' WHERE id = $id")) {
                $conn->commit();
                setFlashMessage('success', 'Status transaksi berhasil diubah. Modal investor dikembalikan (jika applicable).');
            } else {
                $conn->rollback();
                setFlashMessage('error', 'Gagal mengubah status transaksi');
            }
        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Gagal mengubah status: ' . $e->getMessage());
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

// Get active investors
$investors_list = $conn->query("SELECT id, kode_investor, nama_investor, modal_tersedia, minimal_alokasi, nisbah_investor FROM investors WHERE status = 'aktif' ORDER BY nama_investor");

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
                                <?php if ($trans['status'] === 'aktif' && !isStaff()): ?>
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
<div id="transactionModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
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

                <!-- Investor Funding Section -->
                <div class="border-t border-gray-300 dark:border-gray-600 pt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="funded_by_investor" id="funded_by_investor" value="1" checked onchange="toggleInvestorSection()" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-gray-700 dark:text-gray-300 font-medium">Dibiayai oleh Investor</span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">Jika tidak dicentang, transaksi dibiayai dari kas koperasi</p>
                </div>

                <div id="investorAllocationSection" class="space-y-4">
                    <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                        <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">Alokasi Modal Investor</h4>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">Pilih investor dan masukkan nominal alokasi. Total harus sama dengan Harga Modal.</p>
                    </div>

                    <div id="investorRows">
                        <div class="investor-row grid grid-cols-12 gap-2 items-end mb-2">
                            <div class="col-span-6">
                                <label class="block text-gray-700 dark:text-gray-300 text-sm mb-1">Investor</label>
                                <select name="investors[]" class="investor-select w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" onchange="updateInvestorInfo(this)">
                                    <option value="">Pilih Investor</option>
                                    <?php while ($inv = $investors_list->fetch_assoc()): ?>
                                        <option value="<?php echo $inv['id']; ?>"
                                                data-modal="<?php echo $inv['modal_tersedia']; ?>"
                                                data-min="<?php echo $inv['minimal_alokasi']; ?>"
                                                data-nisbah="<?php echo $inv['nisbah_investor']; ?>">
                                            <?php echo htmlspecialchars($inv['nama_investor']); ?> - Tersedia: <?php echo formatRupiah($inv['modal_tersedia']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-span-4">
                                <label class="block text-gray-700 dark:text-gray-300 text-sm mb-1">Nominal Alokasi</label>
                                <input type="number" name="allocations[]" class="allocation-input w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" step="0.01" min="0" onkeyup="validateInvestorAllocation()">
                            </div>
                            <div class="col-span-2">
                                <button type="button" onclick="removeInvestorRow(this)" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm rounded-lg">Hapus</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="addInvestorRow()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition duration-200">
                        + Tambah Investor
                    </button>

                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Total Alokasi:</span>
                            <span id="totalAllocation" class="text-lg font-bold text-blue-600 dark:text-blue-400">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Harga Modal:</span>
                            <span id="targetModal" class="text-lg font-bold text-gray-900 dark:text-white">Rp 0</span>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-300 dark:border-gray-600">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">Selisih:</span>
                                <span id="diffAllocation" class="text-lg font-bold">Rp 0</span>
                            </div>
                        </div>
                    </div>
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
// Investor options template (cached for cloning)
let investorOptionsHtml = '';

function openModal() {
    document.getElementById('transactionModal').classList.remove('hidden');

    // Cache investor options from first select
    if (!investorOptionsHtml) {
        const firstSelect = document.querySelector('.investor-select');
        if (firstSelect) {
            investorOptionsHtml = firstSelect.innerHTML;
        }
    }
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
    validateInvestorAllocation();
}

function calculateTotal() {
    const hargaModal = parseFloat(document.getElementById('harga_modal').value) || 0;
    const margin = parseFloat(document.getElementById('margin').value) || 0;
    const tenor = parseInt(document.getElementById('tenor').value) || 0;

    const total = hargaModal + margin;
    const angsuran = tenor > 0 ? total / tenor : 0;

    document.getElementById('display_total').textContent = formatRupiah(total);
    document.getElementById('display_angsuran').textContent = formatRupiah(angsuran);
    document.getElementById('targetModal').textContent = formatRupiah(hargaModal);
}

function formatRupiah(amount) {
    return 'Rp ' + amount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function toggleInvestorSection() {
    const checkbox = document.getElementById('funded_by_investor');
    const section = document.getElementById('investorAllocationSection');

    if (checkbox.checked) {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}

function addInvestorRow() {
    const container = document.getElementById('investorRows');
    const newRow = document.createElement('div');
    newRow.className = 'investor-row grid grid-cols-12 gap-2 items-end mb-2';
    newRow.innerHTML = `
        <div class="col-span-6">
            <label class="block text-gray-700 dark:text-gray-300 text-sm mb-1">Investor</label>
            <select name="investors[]" class="investor-select w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" onchange="updateInvestorInfo(this)">
                ${investorOptionsHtml}
            </select>
        </div>
        <div class="col-span-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm mb-1">Nominal Alokasi</label>
            <input type="number" name="allocations[]" class="allocation-input w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" step="0.01" min="0" onkeyup="validateInvestorAllocation()">
        </div>
        <div class="col-span-2">
            <button type="button" onclick="removeInvestorRow(this)" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white text-sm rounded-lg">Hapus</button>
        </div>
    `;
    container.appendChild(newRow);
}

function removeInvestorRow(button) {
    const rows = document.querySelectorAll('.investor-row');
    if (rows.length > 1) {
        button.closest('.investor-row').remove();
        validateInvestorAllocation();
    } else {
        alert('Minimal harus ada 1 investor');
    }
}

function updateInvestorInfo(select) {
    validateInvestorAllocation();
}

function validateInvestorAllocation() {
    const hargaModal = parseFloat(document.getElementById('harga_modal').value) || 0;
    const allocationInputs = document.querySelectorAll('.allocation-input');

    let totalAllocation = 0;
    allocationInputs.forEach(input => {
        totalAllocation += parseFloat(input.value) || 0;
    });

    const diff = hargaModal - totalAllocation;

    document.getElementById('totalAllocation').textContent = formatRupiah(totalAllocation);
    document.getElementById('targetModal').textContent = formatRupiah(hargaModal);
    document.getElementById('diffAllocation').textContent = formatRupiah(Math.abs(diff));

    const diffElement = document.getElementById('diffAllocation');
    if (Math.abs(diff) < 0.01) {
        diffElement.className = 'text-lg font-bold text-green-600 dark:text-green-400';
    } else if (diff > 0) {
        diffElement.className = 'text-lg font-bold text-red-600 dark:text-red-400';
    } else {
        diffElement.className = 'text-lg font-bold text-orange-600 dark:text-orange-400';
    }
}

function validateForm() {
    const margin = parseFloat(document.getElementById('margin').value) || 0;
    if (margin <= 0) {
        alert('Margin harus lebih dari 0');
        return false;
    }

    // Validate investor allocation if funded by investor
    const fundedByInvestor = document.getElementById('funded_by_investor').checked;
    if (fundedByInvestor) {
        const hargaModal = parseFloat(document.getElementById('harga_modal').value) || 0;
        const allocationInputs = document.querySelectorAll('.allocation-input');
        const investorSelects = document.querySelectorAll('.investor-select');

        let totalAllocation = 0;
        let hasEmptyInvestor = false;
        let hasEmptyAllocation = false;

        investorSelects.forEach((select, index) => {
            if (select.value === '') {
                hasEmptyInvestor = true;
            }
            const allocation = parseFloat(allocationInputs[index].value) || 0;
            if (allocation <= 0) {
                hasEmptyAllocation = true;
            }
            totalAllocation += allocation;
        });

        if (hasEmptyInvestor) {
            alert('Semua investor harus dipilih. Hapus baris yang tidak digunakan.');
            return false;
        }

        if (hasEmptyAllocation) {
            alert('Semua nominal alokasi harus lebih dari 0');
            return false;
        }

        const diff = Math.abs(hargaModal - totalAllocation);
        if (diff > 0.01) {
            alert(`Total alokasi investor (${formatRupiah(totalAllocation)}) harus sama dengan harga modal (${formatRupiah(hargaModal)}). Selisih: ${formatRupiah(diff)}`);
            return false;
        }

        // Check investor modal tersedia and minimal alokasi
        for (let i = 0; i < investorSelects.length; i++) {
            const select = investorSelects[i];
            const option = select.options[select.selectedIndex];
            const modalTersedia = parseFloat(option.getAttribute('data-modal')) || 0;
            const minimalAlokasi = parseFloat(option.getAttribute('data-min')) || 0;
            const allocation = parseFloat(allocationInputs[i].value) || 0;

            if (allocation > modalTersedia) {
                alert(`Alokasi untuk ${option.text.split(' - ')[0]} melebihi modal tersedia (${formatRupiah(modalTersedia)})`);
                return false;
            }

            if (allocation < minimalAlokasi) {
                alert(`Alokasi untuk ${option.text.split(' - ')[0]} kurang dari minimal alokasi (${formatRupiah(minimalAlokasi)})`);
                return false;
            }
        }
    }

    return true;
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    toggleInvestorSection();
});
</script>

<?php include '../includes/footer.php'; ?>
