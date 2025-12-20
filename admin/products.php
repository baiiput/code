<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Kelola Barang';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $nama_barang = sanitize($_POST['nama_barang']);
        $kategori = sanitize($_POST['kategori']);
        $deskripsi = sanitize($_POST['deskripsi']);
        $harga_modal = floatval($_POST['harga_modal']);

        // Auto-generate kode barang based on kategori
        $kategori_codes = [
            'Elektronik' => 'ELK',
            'Furniture' => 'FRN',
            'Kendaraan' => 'KND',
            'Fashion' => 'FSH',
            'Peralatan Rumah Tangga' => 'PRT',
            'Gadget' => 'GDG',
            'Lainnya' => 'LAN'
        ];

        $prefix = $kategori_codes[$kategori] ?? 'LAN';

        if ($action === 'add') {
            // Get last number for this kategori
            $result = $conn->query("SELECT kode_barang FROM products WHERE kategori = '$kategori' ORDER BY id DESC LIMIT 1");
            $last_number = 0;

            if ($result && $result->num_rows > 0) {
                $last_code = $result->fetch_assoc()['kode_barang'];
                // Extract number from code (e.g., ELK-005 -> 5)
                if (preg_match('/-(\d+)$/', $last_code, $matches)) {
                    $last_number = intval($matches[1]);
                }
            }

            $new_number = $last_number + 1;
            $kode_barang = $prefix . '-' . str_pad($new_number, 3, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO products (kode_barang, nama_barang, kategori, deskripsi, harga_modal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssd", $kode_barang, $nama_barang, $kategori, $deskripsi, $harga_modal);

            if ($stmt->execute()) {
                setFlashMessage('success', "Barang berhasil ditambahkan dengan kode: $kode_barang");
            } else {
                setFlashMessage('error', 'Gagal menambahkan barang');
            }
            $stmt->close();
        } else {
            // When editing, keep existing kode_barang but allow kategori change
            $current = $conn->query("SELECT kode_barang, kategori FROM products WHERE id = $id")->fetch_assoc();

            // If kategori changed, generate new kode
            if ($current['kategori'] != $kategori) {
                $result = $conn->query("SELECT kode_barang FROM products WHERE kategori = '$kategori' ORDER BY id DESC LIMIT 1");
                $last_number = 0;

                if ($result && $result->num_rows > 0) {
                    $last_code = $result->fetch_assoc()['kode_barang'];
                    if (preg_match('/-(\d+)$/', $last_code, $matches)) {
                        $last_number = intval($matches[1]);
                    }
                }

                $new_number = $last_number + 1;
                $kode_barang = $prefix . '-' . str_pad($new_number, 3, '0', STR_PAD_LEFT);
            } else {
                $kode_barang = $current['kode_barang'];
            }

            $stmt = $conn->prepare("UPDATE products SET kode_barang = ?, nama_barang = ?, kategori = ?, deskripsi = ?, harga_modal = ? WHERE id = ?");
            $stmt->bind_param("ssssdi", $kode_barang, $nama_barang, $kategori, $deskripsi, $harga_modal, $id);

            if ($stmt->execute()) {
                setFlashMessage('success', 'Barang berhasil diupdate');
            } else {
                setFlashMessage('error', 'Gagal mengupdate barang');
            }
            $stmt->close();
        }

        header('Location: /admin/products.php');
        exit;
    } elseif ($action === 'delete') {
        // Staff tidak boleh hapus data
        if (isStaff()) {
            setFlashMessage('error', 'Staff tidak memiliki akses untuk menghapus data');
            header('Location: /admin/products.php');
            exit;
        }

        $id = $_POST['id'];

        // Check if product has ACTIVE transactions (not cancelled/completed)
        $check = $conn->query("
            SELECT COUNT(*) as total
            FROM transactions
            WHERE product_id = $id
            AND status NOT IN ('batal', 'lunas')
        ");

        if ($check->fetch_assoc()['total'] > 0) {
            setFlashMessage('error', 'Tidak dapat menghapus barang yang memiliki transaksi aktif');
        } else {
            if ($conn->query("DELETE FROM products WHERE id = $id")) {
                setFlashMessage('success', 'Barang berhasil dihapus');
            } else {
                setFlashMessage('error', 'Gagal menghapus barang');
            }
        }

        header('Location: /admin/products.php');
        exit;
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $is_active = $_POST['is_active'];
        $new_status = $is_active == 1 ? 0 : 1;

        if ($conn->query("UPDATE products SET is_active = $new_status WHERE id = $id")) {
            setFlashMessage('success', 'Status barang berhasil diubah');
        } else {
            setFlashMessage('error', 'Gagal mengubah status barang');
        }

        header('Location: /admin/products.php');
        exit;
    }
}

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");

include '../includes/header.php';
?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kelola Barang</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Daftar barang yang tersedia untuk cicilan</p>
    </div>
    <button onclick="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
        + Tambah Barang
    </button>
</div>

<!-- Products Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Barang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Modal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($products->num_rows > 0): ?>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($product['kode_barang']); ?></td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($product['nama_barang']); ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($product['deskripsi']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <span class="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    <?php echo htmlspecialchars($product['kategori'] ?? 'Lainnya'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo formatRupiah($product['harga_modal']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $product['is_active']; ?>">
                                    <button type="submit" class="px-2 py-1 text-xs font-medium rounded <?php echo $product['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'; ?>">
                                        <?php echo $product['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick='openModal("edit", <?php echo json_encode($product); ?>)' class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">Edit</button>
                                <?php if (!isStaff()): ?>
                                <button onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['nama_barang']); ?>')" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada data barang</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit -->
<div id="productModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Tambah Barang</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="productForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="productId">

            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Nama Barang *</label>
                    <input type="text" name="nama_barang" id="nama_barang" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-2">Kategori *</label>
                        <select name="kategori" id="kategori" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Elektronik">Elektronik</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Kendaraan">Kendaraan</option>
                            <option value="Fashion">Fashion</option>
                            <option value="Peralatan Rumah Tangga">Peralatan Rumah Tangga</option>
                            <option value="Gadget">Gadget</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kode barang akan dibuat otomatis berdasarkan kategori</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 dark:text-gray-300 mb-2">Harga Modal *</label>
                        <input type="number" name="harga_modal" id="harga_modal" step="0.01" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
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
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const title = document.getElementById('modalTitle');

    form.reset();
    document.getElementById('formAction').value = action;

    if (action === 'edit' && data) {
        title.textContent = 'Edit Barang';
        document.getElementById('productId').value = data.id;
        document.getElementById('nama_barang').value = data.nama_barang;
        document.getElementById('kategori').value = data.kategori || 'Lainnya';
        document.getElementById('deskripsi').value = data.deskripsi || '';
        document.getElementById('harga_modal').value = data.harga_modal || '';
    } else {
        title.textContent = 'Tambah Barang';
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('productModal').classList.add('hidden');
}

function confirmDelete(id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus barang "${name}"?`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
