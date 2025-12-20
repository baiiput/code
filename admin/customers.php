<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Kelola Pelanggan';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $nik = sanitize($_POST['nik']);
        $alamat = sanitize($_POST['alamat']);
        $telepon = sanitize($_POST['telepon']);
        $email = sanitize($_POST['email']);
        $pekerjaan = sanitize($_POST['pekerjaan']);
        $keterangan = sanitize($_POST['keterangan']);

        if ($action === 'add') {
            // Create customer
            $stmt = $conn->prepare("INSERT INTO customers (nama_lengkap, nik, alamat, telepon, email, pekerjaan, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $nama_lengkap, $nik, $alamat, $telepon, $email, $pekerjaan, $keterangan);

            if ($stmt->execute()) {
                $customer_id = $conn->insert_id;

                // Get username and password from form, or generate automatically
                $username_input = sanitize($_POST['username'] ?? '');
                $password_input = sanitize($_POST['password'] ?? '');

                // Generate username if not provided
                if (empty($username_input)) {
                    $username = strtolower(str_replace(' ', '', $nama_lengkap)) . $customer_id;
                } else {
                    $username = $username_input;
                }

                // Use default password if not provided
                $plain_password = empty($password_input) ? '12345' : $password_input;
                $password = password_hash($plain_password, PASSWORD_DEFAULT);
                $user_level = USER_LEVEL_CUSTOMER; // Level 4

                // Check if username already exists
                $check = $conn->query("SELECT COUNT(*) as total FROM users WHERE username = '$username'");
                if ($check->fetch_assoc()['total'] > 0) {
                    setFlashMessage('error', 'Username sudah digunakan. Silakan gunakan username lain.');
                    header('Location: /admin/customers.php');
                    exit;
                }

                $stmt2 = $conn->prepare("INSERT INTO users (username, password, role, customer_id, user_level, full_name) VALUES (?, ?, 'customer', ?, ?, ?)");
                $stmt2->bind_param("ssiis", $username, $password, $customer_id, $user_level, $nama_lengkap);
                $stmt2->execute();
                $stmt2->close();

                setFlashMessage('success', "Pelanggan berhasil ditambahkan. Username: $username, Password: $plain_password");
            } else {
                setFlashMessage('error', 'Gagal menambahkan pelanggan');
            }
            $stmt->close();
        } else {
            // Edit customer
            $stmt = $conn->prepare("UPDATE customers SET nama_lengkap = ?, nik = ?, alamat = ?, telepon = ?, email = ?, pekerjaan = ?, keterangan = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $nama_lengkap, $nik, $alamat, $telepon, $email, $pekerjaan, $keterangan, $id);

            if ($stmt->execute()) {
                setFlashMessage('success', 'Pelanggan berhasil diupdate');
            } else {
                setFlashMessage('error', 'Gagal mengupdate pelanggan');
            }
            $stmt->close();
        }

        header('Location: /admin/customers.php');
        exit;
    } elseif ($action === 'deactivate') {
        // Soft delete - set is_active = 0
        // Staff tidak boleh nonaktifkan
        if (isStaff()) {
            setFlashMessage('error', 'Staff tidak memiliki akses untuk menonaktifkan pelanggan');
            header('Location: /admin/customers.php');
            exit;
        }

        $id = $_POST['id'];

        // Check if customer has active transactions
        $active_check = $conn->query("
            SELECT COUNT(*) as total
            FROM transactions
            WHERE customer_id = $id
            AND status NOT IN ('batal', 'lunas')
        ");
        $active_trans = $active_check->fetch_assoc()['total'];

        if ($active_trans > 0) {
            setFlashMessage('error', 'Tidak dapat menonaktifkan pelanggan yang memiliki transaksi aktif. Selesaikan atau batalkan transaksi terlebih dahulu.');
            header('Location: /admin/customers.php');
            exit;
        }

        // Deactivate customer (soft delete)
        if ($conn->query("UPDATE customers SET is_active = 0 WHERE id = $id")) {
            // Also deactivate user account
            $conn->query("UPDATE users SET is_active = 0 WHERE customer_id = $id");
            setFlashMessage('success', 'Pelanggan berhasil dinonaktifkan. Data dan history transaksi tetap tersimpan.');
        } else {
            setFlashMessage('error', 'Gagal menonaktifkan pelanggan');
        }

        header('Location: /admin/customers.php');
        exit;
    } elseif ($action === 'activate') {
        // Restore - set is_active = 1
        // Staff tidak boleh aktivasi
        if (isStaff()) {
            setFlashMessage('error', 'Staff tidak memiliki akses untuk mengaktifkan pelanggan');
            header('Location: /admin/customers.php');
            exit;
        }

        $id = $_POST['id'];

        // Activate customer
        if ($conn->query("UPDATE customers SET is_active = 1 WHERE id = $id")) {
            // Also activate user account
            $conn->query("UPDATE users SET is_active = 1 WHERE customer_id = $id");
            setFlashMessage('success', 'Pelanggan berhasil diaktifkan kembali!');
        } else {
            setFlashMessage('error', 'Gagal mengaktifkan pelanggan');
        }

        header('Location: /admin/customers.php?filter=active');
        exit;
    }
}

// Get filter from URL (default: active only)
$filter = $_GET['filter'] ?? 'active';

// Build WHERE clause based on filter
$where_clause = '';
if ($filter === 'active') {
    $where_clause = 'WHERE c.is_active = 1';
} elseif ($filter === 'inactive') {
    $where_clause = 'WHERE c.is_active = 0';
}
// 'all' filter has no WHERE clause

// Get customers based on filter
$customers = $conn->query("
    SELECT c.*, u.username
    FROM customers c
    LEFT JOIN users u ON u.customer_id = c.id
    $where_clause
    ORDER BY c.created_at DESC
");

include '../includes/header.php';
?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kelola Pelanggan</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Daftar pelanggan koperasi</p>
    </div>
    <button onclick="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
        + Tambah Pelanggan
    </button>
</div>

<!-- Filter Tabs -->
<div class="mb-6 border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex space-x-8">
        <a href="?filter=active" class="<?php echo $filter === 'active' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            ✓ Aktif
        </a>
        <a href="?filter=inactive" class="<?php echo $filter === 'inactive' ? 'border-red-500 text-red-600 dark:text-red-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            ✗ Nonaktif
        </a>
        <a href="?filter=all" class="<?php echo $filter === 'all' ? 'border-gray-500 text-gray-600 dark:text-gray-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            📋 Semua
        </a>
    </nav>
</div>

<!-- Customers Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">NIK</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Telepon</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($customers->num_rows > 0): ?>
                    <?php while ($customer = $customers->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($customer['nama_lengkap']); ?></div>
                                    <?php if ($customer['is_active'] == 0): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Nonaktif</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($customer['email'] ?? '-'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($customer['nik'] ?? '-'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($customer['telepon'] ?? '-'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($customer['username'] ?? '-'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick='openModal("edit", <?php echo json_encode($customer); ?>)' class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">Edit</button>
                                <?php if (!isStaff()): ?>
                                    <?php if ($customer['is_active'] == 1): ?>
                                        <button onclick="confirmDeactivate(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['nama_lengkap']); ?>')" class="text-orange-600 hover:text-orange-900 dark:text-orange-400">Nonaktifkan</button>
                                    <?php else: ?>
                                        <button onclick="confirmActivate(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['nama_lengkap']); ?>')" class="text-green-600 hover:text-green-900 dark:text-green-400">✓ Aktifkan Kembali</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada data pelanggan</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit -->
<div id="customerModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Tambah Pelanggan</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="customerForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="customerId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Nama Lengkap *</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">NIK</label>
                    <input type="text" name="nik" id="nik" maxlength="16" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Telepon</label>
                    <input type="text" name="telepon" id="telepon" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" id="email" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Pekerjaan</label>
                    <input type="text" name="pekerjaan" id="pekerjaan" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Alamat</label>
                    <textarea name="alamat" id="alamat" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <!-- Login Credentials Section (only show when adding new customer) -->
                <div id="loginSection" class="md:col-span-2 border-t border-gray-200 dark:border-gray-600 pt-4 mt-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-3">Akun Login Customer</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 mb-2">Username</label>
                            <input type="text" name="username" id="username" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Kosongkan untuk otomatis">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kosongkan untuk generate otomatis dari nama</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 mb-2">Password</label>
                            <input type="text" name="password" id="password" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Kosongkan untuk default (12345)">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Default: 12345 (jika dikosongkan)</p>
                        </div>
                    </div>
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

<!-- Action Form (Deactivate/Activate) -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="actionFormAction" value="">
    <input type="hidden" name="id" id="actionFormId" value="">
</form>

<script>
function openModal(action, data = null) {
    const modal = document.getElementById('customerModal');
    const form = document.getElementById('customerForm');
    const title = document.getElementById('modalTitle');
    const loginSection = document.getElementById('loginSection');

    form.reset();
    document.getElementById('formAction').value = action;

    if (action === 'edit' && data) {
        title.textContent = 'Edit Pelanggan';
        document.getElementById('customerId').value = data.id;
        document.getElementById('nama_lengkap').value = data.nama_lengkap;
        document.getElementById('nik').value = data.nik || '';
        document.getElementById('alamat').value = data.alamat || '';
        document.getElementById('telepon').value = data.telepon || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('pekerjaan').value = data.pekerjaan || '';
        document.getElementById('keterangan').value = data.keterangan || '';

        // Hide login section when editing
        loginSection.classList.add('hidden');
    } else {
        title.textContent = 'Tambah Pelanggan';

        // Show login section when adding
        loginSection.classList.remove('hidden');
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('customerModal').classList.add('hidden');
}

function confirmDeactivate(id, name) {
    if (confirm(`Apakah Anda yakin ingin menonaktifkan pelanggan "${name}"?\n\nPelanggan akan disembunyikan dari list aktif, tapi data dan history transaksi tetap tersimpan.\n\nAnda bisa mengaktifkan kembali kapan saja.`)) {
        document.getElementById('actionFormAction').value = 'deactivate';
        document.getElementById('actionFormId').value = id;
        document.getElementById('actionForm').submit();
    }
}

function confirmActivate(id, name) {
    if (confirm(`Aktifkan kembali pelanggan "${name}"?\n\nPelanggan akan muncul di list aktif dan bisa melakukan transaksi.`)) {
        document.getElementById('actionFormAction').value = 'activate';
        document.getElementById('actionFormId').value = id;
        document.getElementById('actionForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
