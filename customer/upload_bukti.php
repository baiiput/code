<?php
require_once '../config/database.php';
require_once '../config/base_path.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCustomer();

$pageTitle = 'Upload Bukti Pembayaran';

$currentUser = getCurrentUser();
$customer_id = $currentUser['customer_id'];
$transaction_id = intval($_GET['id'] ?? 0);

// Verify transaction belongs to customer
$trans = $conn->query("SELECT t.*, p.nama_barang FROM transactions t JOIN products p ON t.product_id = p.id WHERE t.id = $transaction_id AND t.customer_id = $customer_id AND t.status = 'aktif'")->fetch_assoc();

if (!$trans) {
    setFlashMessage('error', 'Transaksi tidak ditemukan');
    redirectTo('customer/index.php');
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = floatval($_POST['nominal']);
    $tanggal_bayar = sanitize($_POST['tanggal_bayar']);
    $keterangan = sanitize($_POST['keterangan']);

    // Validate nominal
    $min_payment = $trans['angsuran_perbulan'];
    $max_payment = $trans['sisa_hutang'];

    if ($nominal < $min_payment) {
        setFlashMessage('error', 'Nominal pembayaran minimal ' . formatRupiah($min_payment));
    } elseif ($nominal > $max_payment) {
        setFlashMessage('error', 'Nominal pembayaran maksimal ' . formatRupiah($max_payment));
    } else {
        // Handle file upload
        if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $filename = $_FILES['bukti_transfer']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($filetype, $allowed)) {
                setFlashMessage('error', 'Format file harus JPG, PNG, atau PDF');
            } else {
                // Create uploads directory if not exists
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . baseUrl('uploads/bukti_transfer/');
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Generate unique filename
                $new_filename = 'bukti_' . $transaction_id . '_' . time() . '.' . $filetype;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $upload_path)) {
                    // Insert payment record
                    $conn->begin_transaction();

                    try {
                        $stmt = $conn->prepare("INSERT INTO payments (transaction_id, nominal, metode_pembayaran, payment_type, bukti_transfer, status, keterangan) VALUES (?, ?, 'manual', 'manual', ?, 'waiting_verification', ?)");
                        $stmt->bind_param("idss", $transaction_id, $nominal, $new_filename, $keterangan);
                        $stmt->execute();
                        $payment_id = $stmt->insert_id;
                        $stmt->close();

                        $conn->commit();
                        setFlashMessage('success', 'Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.');
                        redirectTo('customer/index.php');
                    } catch (Exception $e) {
                        $conn->rollback();
                        unlink($upload_path); // Delete uploaded file
                        setFlashMessage('error', 'Gagal menyimpan data pembayaran');
                    }
                } else {
                    setFlashMessage('error', 'Gagal mengupload file');
                }
            }
        } else {
            setFlashMessage('error', 'Bukti transfer harus diupload');
        }
    }
}

// Get bank info from settings (you can create a settings table for this)
$bank_info = [
    ['bank' => 'BCA', 'rekening' => '1234567890', 'atas_nama' => 'PT Koperasi Syariah'],
    ['bank' => 'Mandiri', 'rekening' => '0987654321', 'atas_nama' => 'PT Koperasi Syariah'],
];

include '../includes/header.php';
?>

<div class="mb-8">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Upload Bukti Pembayaran</h1>
        <a href="<?php echo baseUrl('customer/index.php'); ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
            ← Kembali
        </a>
    </div>
</div>

<!-- Transaction Info -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Detail Transaksi</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Nomor Kontrak</p>
            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nomor_kontrak']); ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Barang</p>
            <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_barang']); ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Angsuran Per Bulan</p>
            <p class="font-semibold text-blue-600 dark:text-blue-400"><?php echo formatRupiah($trans['angsuran_perbulan']); ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Sisa Hutang</p>
            <p class="font-semibold text-red-600 dark:text-red-400"><?php echo formatRupiah($trans['sisa_hutang']); ?></p>
        </div>
    </div>
</div>

<!-- Bank Info -->
<div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6 mb-6">
    <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
        </svg>
        Rekening Tujuan Transfer
    </h3>
    <?php foreach ($bank_info as $bank): ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-3">
        <div class="font-bold text-gray-900 dark:text-white"><?php echo $bank['bank']; ?></div>
        <div class="text-lg font-mono text-gray-900 dark:text-white"><?php echo $bank['rekening']; ?></div>
        <div class="text-sm text-gray-600 dark:text-gray-400">a.n. <?php echo $bank['atas_nama']; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Upload Form -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Form Upload Bukti Transfer</h2>

    <form method="POST" enctype="multipart/form-data">
        <div class="space-y-4">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Nominal Pembayaran *</label>
                <input type="number" name="nominal" required min="<?php echo $trans['angsuran_perbulan']; ?>" max="<?php echo $trans['sisa_hutang']; ?>" step="1000" value="<?php echo $trans['angsuran_perbulan']; ?>" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <p class="text-xs text-gray-500 mt-1">Min: <?php echo formatRupiah($trans['angsuran_perbulan']); ?> - Max: <?php echo formatRupiah($trans['sisa_hutang']); ?></p>
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Tanggal Transfer *</label>
                <input type="date" name="tanggal_bayar" required value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Bukti Transfer (JPG, PNG, atau PDF) *</label>
                <input type="file" name="bukti_transfer" required accept=".jpg,.jpeg,.png,.pdf" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <p class="text-xs text-gray-500 mt-1">Upload screenshot/foto bukti transfer. Max 5MB</p>
            </div>

            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Keterangan</label>
                <textarea name="keterangan" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Tambahkan catatan jika ada (opsional)"></textarea>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Catatan:</strong> Setelah upload, pembayaran Anda akan diverifikasi oleh admin. Status pembayaran dapat Anda lihat di dashboard.
                </p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="<?php echo baseUrl('customer/index.php'); ?>" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition duration-200">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                    Upload Bukti Transfer
                </button>
            </div>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
