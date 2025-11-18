<?php
require_once 'config.php';
requireRole(['admin', 'super_admin']);

$id = $_GET['id'] ?? null;
$customer = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        redirect('customers.php');
    }
    $pageTitle = 'Edit Pelanggan';
} else {
    $pageTitle = 'Tambah Pelanggan';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nama' => trim($_POST['nama'] ?? ''),
        'gmail_email' => trim($_POST['gmail_email'] ?? ''),
        'gmail_password' => trim($_POST['gmail_password'] ?? ''),
        'starlink_email' => trim($_POST['starlink_email'] ?? ''),
        'starlink_password' => trim($_POST['starlink_password'] ?? ''),
        'login_alternatif' => trim($_POST['login_alternatif'] ?? ''),
        'acc_no' => trim($_POST['acc_no'] ?? ''),
        'email_client' => trim($_POST['email_client'] ?? ''),
        'nomor_cs' => trim($_POST['nomor_cs'] ?? ''),
        'alamat' => trim($_POST['alamat'] ?? ''),
        'kit_number' => trim($_POST['kit_number'] ?? ''),
        'serial_number' => trim($_POST['serial_number'] ?? ''),
        'tanggal_jatuh_tempo' => $_POST['tanggal_jatuh_tempo'] ?: null,
        'kode' => trim($_POST['kode'] ?? ''),
        'status_langganan' => $_POST['status_langganan'] ?? 'aktif',
        'paket' => trim($_POST['paket'] ?? ''),
        'last_4_digit' => trim($_POST['last_4_digit'] ?? ''),
        'no_aktivasi' => trim($_POST['no_aktivasi'] ?? ''),
        'koordinat_lokasi' => trim($_POST['koordinat_lokasi'] ?? ''),
        'catatan' => trim($_POST['catatan'] ?? ''),
    ];

    // Validation
    if (empty($data['nama'])) {
        $errors[] = 'Nama harus diisi.';
    }

    if (empty($errors)) {
        if ($id) {
            // Update
            $sql = "UPDATE customers SET nama=?, gmail_email=?, gmail_password=?, starlink_email=?, starlink_password=?, login_alternatif=?, acc_no=?, email_client=?, nomor_cs=?, alamat=?, kit_number=?, serial_number=?, tanggal_jatuh_tempo=?, kode=?, status_langganan=?, paket=?, last_4_digit=?, no_aktivasi=?, koordinat_lokasi=?, catatan=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($data), $id]);
            setFlash('success', 'Data pelanggan berhasil diperbarui.');
        } else {
            // Insert
            $sql = "INSERT INTO customers (nama, gmail_email, gmail_password, starlink_email, starlink_password, login_alternatif, acc_no, email_client, nomor_cs, alamat, kit_number, serial_number, tanggal_jatuh_tempo, kode, status_langganan, paket, last_4_digit, no_aktivasi, koordinat_lokasi, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            setFlash('success', 'Pelanggan berhasil ditambahkan.');
        }
        redirect('customers.php');
    }

    $customer = $data;
}

require_once 'includes/header.php';
?>

<div class="max-w-4xl">
    <?php if ($errors): ?>
    <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/50 p-4 text-sm text-red-800 dark:text-red-200">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <!-- Basic Info -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Dasar</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nama *</label>
                    <input type="text" name="nama" value="<?= e($customer['nama'] ?? '') ?>" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email Client</label>
                    <input type="email" name="email_client" value="<?= e($customer['email_client'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nomor CS</label>
                    <input type="text" name="nomor_cs" value="<?= e($customer['nomor_cs'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Alamat</label>
                    <textarea name="alamat" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"><?= e($customer['alamat'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Login Credentials -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Login Credentials</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gmail Email</label>
                    <input type="email" name="gmail_email" value="<?= e($customer['gmail_email'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gmail Password</label>
                    <input type="text" name="gmail_password" value="<?= e($customer['gmail_password'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Starlink Email</label>
                    <input type="email" name="starlink_email" value="<?= e($customer['starlink_email'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Starlink Password</label>
                    <input type="text" name="starlink_password" value="<?= e($customer['starlink_password'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Login Alternatif</label>
                    <textarea name="login_alternatif" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"><?= e($customer['login_alternatif'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Starlink Info -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Starlink</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">ACC No.</label>
                    <input type="text" name="acc_no" value="<?= e($customer['acc_no'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">KIT Number</label>
                    <input type="text" name="kit_number" value="<?= e($customer['kit_number'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Serial Number</label>
                    <input type="text" name="serial_number" value="<?= e($customer['serial_number'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Kode</label>
                    <input type="text" name="kode" value="<?= e($customer['kode'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Last 4 Digit</label>
                    <input type="text" name="last_4_digit" maxlength="4" value="<?= e($customer['last_4_digit'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">No Aktivasi</label>
                    <input type="text" name="no_aktivasi" value="<?= e($customer['no_aktivasi'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Koordinat Lokasi</label>
                    <input type="text" name="koordinat_lokasi" value="<?= e($customer['koordinat_lokasi'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Subscription -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Langganan</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Paket</label>
                    <input type="text" name="paket" value="<?= e($customer['paket'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status Langganan *</label>
                    <select name="status_langganan" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="aktif" <?= ($customer['status_langganan'] ?? 'aktif') == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= ($customer['status_langganan'] ?? '') == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        <option value="lunas" <?= ($customer['status_langganan'] ?? '') == 'lunas' ? 'selected' : '' ?>>Lunas</option>
                        <option value="belum_bayar" <?= ($customer['status_langganan'] ?? '') == 'belum_bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Jatuh Tempo</label>
                    <input type="date" name="tanggal_jatuh_tempo" value="<?= e($customer['tanggal_jatuh_tempo'] ?? '') ?>" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Catatan</h3>
            <textarea name="catatan" rows="3" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"><?= e($customer['catatan'] ?? '') ?></textarea>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="customers.php" class="text-sm font-semibold text-gray-700 dark:text-gray-200">Batal</a>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                <?= $id ? 'Update' : 'Simpan' ?> Pelanggan
            </button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
