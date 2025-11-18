<?php
require_once 'config.php';
requireRole(['super_admin']);
$pageTitle = 'Input Pembayaran';

$errors = [];
$customers = $pdo->query("SELECT id, nama, email_client, nomor_cs FROM customers ORDER BY nama")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'] ?? '';
    $nominal = $_POST['nominal'] ?? '';
    $periodeBulan = trim($_POST['periode_bulan'] ?? '');
    $tanggalBayar = $_POST['tanggal_bayar'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validation
    if (empty($customerId)) $errors[] = 'Pilih pelanggan.';
    if (empty($nominal) || $nominal < 0) $errors[] = 'Nominal harus diisi.';
    if (empty($periodeBulan)) $errors[] = 'Periode bulan harus diisi.';
    if (empty($tanggalBayar)) $errors[] = 'Tanggal bayar harus diisi.';

    if (empty($errors)) {
        // Insert payment
        $stmt = $pdo->prepare("INSERT INTO payments (customer_id, user_id, nominal, periode_bulan, tanggal_bayar, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$customerId, $_SESSION['user_id'], $nominal, $periodeBulan, $tanggalBayar, $keterangan]);

        // Update customer status to lunas
        $stmt = $pdo->prepare("UPDATE customers SET status_langganan = 'lunas' WHERE id = ?");
        $stmt->execute([$customerId]);

        setFlash('success', 'Pembayaran berhasil dicatat dan status pelanggan diperbarui menjadi LUNAS.');
        redirect('payments.php');
    }
}

require_once 'includes/header.php';
?>

<div class="max-w-2xl">
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
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Pelanggan *</label>
                    <select name="customer_id" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="">Pilih Pelanggan</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nama']) ?> - <?= e($c['email_client'] ?? $c['nomor_cs'] ?? 'N/A') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nominal (Rp) *</label>
                    <input type="number" name="nominal" value="<?= e($_POST['nominal'] ?? '') ?>" required min="0" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Periode Bulan *</label>
                    <input type="text" name="periode_bulan" value="<?= e($_POST['periode_bulan'] ?? date('F Y')) ?>" required placeholder="November 2024" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Bayar *</label>
                    <input type="date" name="tanggal_bayar" value="<?= e($_POST['tanggal_bayar'] ?? date('Y-m-d')) ?>" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan</label>
                    <textarea name="keterangan" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"><?= e($_POST['keterangan'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/50 rounded-lg p-4">
            <p class="text-sm text-yellow-700 dark:text-yellow-200">
                Setelah menyimpan pembayaran, status pelanggan akan otomatis berubah menjadi <strong>LUNAS</strong>.
            </p>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="payments.php" class="text-sm font-semibold text-gray-700 dark:text-gray-200">Batal</a>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                Simpan Pembayaran
            </button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
