<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin(); // Manager and above can view
requireLevel(USER_LEVEL_MANAGER); // Manager and above

$pageTitle = 'Kelola Investor';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $kode_investor = sanitize($_POST['kode_investor']);
        $nama_investor = sanitize($_POST['nama_investor']);
        $email = sanitize($_POST['email']);
        $telepon = sanitize($_POST['telepon']);
        $alamat = sanitize($_POST['alamat']);
        $total_modal = floatval($_POST['total_modal']);
        $nisbah_investor = intval($_POST['nisbah_investor']);
        $nisbah_koperasi = intval($_POST['nisbah_koperasi']);
        $kontrak_mulai = $_POST['kontrak_mulai'];
        $kontrak_selesai = $_POST['kontrak_selesai'];
        $minimal_alokasi = floatval($_POST['minimal_alokasi']);
        $keterangan = sanitize($_POST['keterangan']);

        // Validate nisbah total = 100
        if (($nisbah_investor + $nisbah_koperasi) != 100) {
            setFlashMessage('error', 'Total Nisbah harus 100% (Investor + Koperasi)');
            header('Location: /admin/investors.php');
            exit;
        }

        if ($action === 'add') {
            // Check kode exists
            $check = $conn->query("SELECT COUNT(*) as total FROM investors WHERE kode_investor = '$kode_investor'");
            if ($check->fetch_assoc()['total'] > 0) {
                setFlashMessage('error', 'Kode investor sudah digunakan');
                header('Location: /admin/investors.php');
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO investors (kode_investor, nama_investor, email, telepon, alamat, total_modal, modal_tersedia, nisbah_investor, nisbah_koperasi, kontrak_mulai, kontrak_selesai, minimal_alokasi, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssddiiisds", $kode_investor, $nama_investor, $email, $telepon, $alamat, $total_modal, $total_modal, $nisbah_investor, $nisbah_koperasi, $kontrak_mulai, $kontrak_selesai, $minimal_alokasi, $keterangan);

            if ($stmt->execute()) {
                // Log to kas_transactions
                $investor_id = $stmt->insert_id;
                $saldo_before = 0;
                $saldo_after = $total_modal;

                // Get current kas setting
                $kas_query = $conn->query("SELECT setting_value FROM kas_settings WHERE setting_key = 'total_modal_investor'");
                if ($kas_query) {
                    $saldo_before = floatval($kas_query->fetch_assoc()['setting_value']);
                    $saldo_after = $saldo_before + $total_modal;
                }

                $user_id = getCurrentUser()['id'];
                $today = date('Y-m-d');
                $ket = "Modal masuk dari investor: $nama_investor ($kode_investor)";

                $stmt2 = $conn->prepare("INSERT INTO kas_transactions (tipe, kategori, nominal, saldo_before, saldo_after, referensi_type, referensi_id, keterangan, tanggal_transaksi, created_by) VALUES ('masuk', 'investor_in', ?, ?, ?, 'investor', ?, ?, ?, ?)");
                $stmt2->bind_param("dddissi", $total_modal, $saldo_before, $saldo_after, $investor_id, $ket, $today, $user_id);
                $stmt2->execute();
                $stmt2->close();

                // Update kas settings
                $conn->query("UPDATE kas_settings SET setting_value = setting_value + $total_modal WHERE setting_key IN ('total_modal_investor', 'modal_tersedia')");

                setFlashMessage('success', 'Investor berhasil ditambahkan');
            } else {
                setFlashMessage('error', 'Gagal menambahkan investor');
            }
            $stmt->close();
        } else {
            // Edit investor - cannot change total_modal (use withdrawal/top-up feature)
            $stmt = $conn->prepare("UPDATE investors SET nama_investor = ?, email = ?, telepon = ?, alamat = ?, nisbah_investor = ?, nisbah_koperasi = ?, kontrak_mulai = ?, kontrak_selesai = ?, minimal_alokasi = ?, keterangan = ? WHERE id = ?");
            $stmt->bind_param("sssiisssdsi", $nama_investor, $email, $telepon, $alamat, $nisbah_investor, $nisbah_koperasi, $kontrak_mulai, $kontrak_selesai, $minimal_alokasi, $keterangan, $id);

            if ($stmt->execute()) {
                setFlashMessage('success', 'Data investor berhasil diupdate');
            } else {
                setFlashMessage('error', 'Gagal mengupdate investor');
            }
            $stmt->close();
        }

        header('Location: /admin/investors.php');
        exit;
    } elseif ($action === 'delete') {
        // Only Manager and Super Admin can delete
        if (isStaff()) {
            setFlashMessage('error', 'Staff tidak memiliki akses untuk menghapus data');
            header('Location: /admin/investors.php');
            exit;
        }

        $id = $_POST['id'];

        // Check if investor has allocated funds
        $investor = $conn->query("SELECT modal_allocated, nama_investor FROM investors WHERE id = $id")->fetch_assoc();
        if ($investor['modal_allocated'] > 0) {
            setFlashMessage('error', 'Tidak dapat menghapus investor dengan modal yang masih dialokasikan');
            header('Location: /admin/investors.php');
            exit;
        }

        // Check if investor has active transactions (not cancelled/completed)
        $active_check = $conn->query("
            SELECT COUNT(*) as total
            FROM transaction_investors ti
            JOIN transactions t ON ti.transaction_id = t.id
            WHERE ti.investor_id = $id
            AND t.status NOT IN ('batal', 'lunas')
        ");

        if ($active_check->fetch_assoc()['total'] > 0) {
            setFlashMessage('error', 'Tidak dapat menghapus investor yang masih memiliki transaksi aktif');
            header('Location: /admin/investors.php');
            exit;
        }

        $conn->begin_transaction();
        try {
            // Delete transaction_investors records for cancelled/completed transactions
            $conn->query("
                DELETE ti FROM transaction_investors ti
                JOIN transactions t ON ti.transaction_id = t.id
                WHERE ti.investor_id = $id
                AND t.status IN ('batal', 'lunas')
            ");

            // Delete investor
            if ($conn->query("DELETE FROM investors WHERE id = $id")) {
                $conn->commit();
                setFlashMessage('success', 'Investor berhasil dihapus');
            } else {
                $conn->rollback();
                setFlashMessage('error', 'Gagal menghapus investor');
            }
        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Gagal menghapus investor: ' . $e->getMessage());
        }

        header('Location: /admin/investors.php');
        exit;
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];

        // Toggle between aktif and nonaktif (selesai is set when contract ends)
        $new_status = $status === 'aktif' ? 'nonaktif' : 'aktif';

        if ($conn->query("UPDATE investors SET status = '$new_status' WHERE id = $id")) {
            setFlashMessage('success', 'Status investor berhasil diubah');
        } else {
            setFlashMessage('error', 'Gagal mengubah status investor');
        }

        header('Location: /admin/investors.php');
        exit;
    }
}

// Get all investors
$investors = $conn->query("
    SELECT * FROM investors
    ORDER BY status ASC, created_at DESC
");

// Get total statistics
$stats = $conn->query("
    SELECT
        SUM(total_modal) as total_modal_all,
        SUM(modal_tersedia) as total_tersedia,
        SUM(modal_allocated) as total_allocated,
        SUM(total_profit) as total_profit_all,
        COUNT(*) as total_investor
    FROM investors
    WHERE status = 'aktif'
")->fetch_assoc();

include '../includes/header.php';
?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kelola Investor</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Manajemen investor dengan tracking modal & profit otomatis</p>
    </div>
    <button onclick="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
        + Tambah Investor
    </button>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Total Investor</div>
        <div class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total_investor']); ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Total Modal</div>
        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">Rp <?php echo number_format($stats['total_modal_all'], 0, ',', '.'); ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Modal Tersedia</div>
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">Rp <?php echo number_format($stats['total_tersedia'], 0, ',', '.'); ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Modal Dialokasi</div>
        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">Rp <?php echo number_format($stats['total_allocated'], 0, ',', '.'); ?></div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="text-sm text-gray-500 dark:text-gray-400">Total Profit</div>
        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">Rp <?php echo number_format($stats['total_profit_all'], 0, ',', '.'); ?></div>
    </div>
</div>

<!-- Info Panel -->
<div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 mb-6">
    <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Informasi Investor System:</h3>
    <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
        <li><strong>Modal Tersedia:</strong> Modal yang bisa dialokasikan ke transaksi baru</li>
        <li><strong>Modal Dialokasi:</strong> Modal yang sedang digunakan di transaksi aktif</li>
        <li><strong>Nisbah:</strong> Persentase bagi hasil (Investor + Koperasi = 100%)</li>
        <li><strong>Minimal Alokasi:</strong> Minimal modal yang harus dialokasikan per transaksi</li>
    </ul>
</div>

<!-- Investors Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode & Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kontak</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Modal</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Modal Tersedia</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Modal Dialokasi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nisbah</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($investors->num_rows > 0): ?>
                    <?php while ($inv = $investors->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($inv['nama_investor']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($inv['kode_investor']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($inv['telepon'] ?? '-'); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($inv['email'] ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">Rp <?php echo number_format($inv['total_modal'], 0, ',', '.'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-semibold text-green-600 dark:text-green-400">Rp <?php echo number_format($inv['modal_tersedia'], 0, ',', '.'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm font-semibold text-orange-600 dark:text-orange-400">Rp <?php echo number_format($inv['modal_allocated'], 0, ',', '.'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white"><?php echo $inv['nisbah_investor']; ?>% : <?php echo $inv['nisbah_koperasi']; ?>%</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Inv : Kop</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white"><?php echo date('d/m/Y', strtotime($inv['kontrak_mulai'])); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">s/d <?php echo date('d/m/Y', strtotime($inv['kontrak_selesai'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $inv['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $inv['status']; ?>">
                                    <button type="submit" class="px-2 py-1 text-xs font-medium rounded <?php
                                        echo $inv['status'] === 'aktif' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' :
                                             ($inv['status'] === 'selesai' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' :
                                             'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300');
                                    ?>">
                                        <?php echo ucfirst($inv['status']); ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick='openModal("edit", <?php echo json_encode($inv); ?>)' class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">Edit</button>
                                <button onclick="viewPortfolio(<?php echo $inv['id']; ?>, '<?php echo htmlspecialchars($inv['nama_investor']); ?>')" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 mr-3">Portfolio</button>
                                <?php if (!isStaff()): ?>
                                <button onclick="confirmDelete(<?php echo $inv['id']; ?>, '<?php echo htmlspecialchars($inv['nama_investor']); ?>')" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada data investor</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit -->
<div id="investorModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Tambah Investor</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="investorForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="investorId">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Kode Investor *</label>
                    <input type="text" name="kode_investor" id="kode_investor" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Nama Investor *</label>
                    <input type="text" name="nama_investor" id="nama_investor" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" id="email" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Telepon</label>
                    <input type="text" name="telepon" id="telepon" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="col-span-2">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Alamat</label>
                    <textarea name="alamat" id="alamat" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <div id="modalFieldDiv">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Total Modal *</label>
                    <input type="number" name="total_modal" id="total_modal" required min="0" step="0.01" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-500 mt-1">Modal awal investor (tidak bisa diubah setelah dibuat)</p>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Minimal Alokasi *</label>
                    <input type="number" name="minimal_alokasi" id="minimal_alokasi" required min="0" step="0.01" value="1000000" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Nisbah Investor (%) *</label>
                    <input type="number" name="nisbah_investor" id="nisbah_investor" required min="0" max="100" value="60" onchange="updateNisbahKoperasi()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Nisbah Koperasi (%) *</label>
                    <input type="number" name="nisbah_koperasi" id="nisbah_koperasi" required min="0" max="100" value="40" readonly class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-600 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Kontrak Mulai *</label>
                    <input type="date" name="kontrak_mulai" id="kontrak_mulai" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Kontrak Selesai *</label>
                    <input type="date" name="kontrak_selesai" id="kontrak_selesai" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="col-span-2">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function openModal(action, data = null) {
    const modal = document.getElementById('investorModal');
    const form = document.getElementById('investorForm');
    const title = document.getElementById('modalTitle');
    const modalFieldDiv = document.getElementById('modalFieldDiv');

    form.reset();
    document.getElementById('formAction').value = action;

    if (action === 'edit' && data) {
        title.textContent = 'Edit Investor';
        document.getElementById('investorId').value = data.id;
        document.getElementById('kode_investor').value = data.kode_investor;
        document.getElementById('kode_investor').readOnly = true;
        document.getElementById('nama_investor').value = data.nama_investor;
        document.getElementById('email').value = data.email || '';
        document.getElementById('telepon').value = data.telepon || '';
        document.getElementById('alamat').value = data.alamat || '';
        document.getElementById('nisbah_investor').value = data.nisbah_investor;
        document.getElementById('nisbah_koperasi').value = data.nisbah_koperasi;
        document.getElementById('kontrak_mulai').value = data.kontrak_mulai;
        document.getElementById('kontrak_selesai').value = data.kontrak_selesai;
        document.getElementById('minimal_alokasi').value = data.minimal_alokasi;
        document.getElementById('keterangan').value = data.keterangan || '';

        // Hide total modal field on edit
        modalFieldDiv.style.display = 'none';
    } else {
        title.textContent = 'Tambah Investor';
        document.getElementById('kode_investor').readOnly = false;
        modalFieldDiv.style.display = 'block';
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('investorModal').classList.add('hidden');
}

function updateNisbahKoperasi() {
    const nisbahInvestor = parseInt(document.getElementById('nisbah_investor').value) || 0;
    const nisbahKoperasi = 100 - nisbahInvestor;
    document.getElementById('nisbah_koperasi').value = nisbahKoperasi;
}

function confirmDelete(id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus investor "${name}"?\n\nPastikan tidak ada modal yang masih dialokasikan.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function viewPortfolio(id, name) {
    // Redirect to investor portfolio page (will be created next)
    window.location.href = `/admin/investor_portfolio.php?id=${id}`;
}
</script>

<?php include '../includes/footer.php'; ?>
