<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Detail Transaksi';

$id = intval($_GET['id'] ?? 0);

// Get transaction details
$trans = $conn->query("
    SELECT t.*, c.nama_lengkap, c.nik, c.telepon, c.alamat, p.nama_barang, p.deskripsi
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    JOIN products p ON t.product_id = p.id
    WHERE t.id = $id
")->fetch_assoc();

if (!$trans) {
    header('Location: /admin/transactions.php');
    exit;
}

// Get payment history
$payments = $conn->query("
    SELECT * FROM payments
    WHERE transaction_id = $id
    ORDER BY created_at DESC
");

// Calculate remaining installments
$sisaAngsuran = calculateRemainingInstallments($trans['total_harga'], $trans['total_dibayar'], $trans['angsuran_perbulan']);

include '../includes/header.php';
?>

<div class="mb-8">
    <a href="/admin/transactions.php" class="text-blue-600 dark:text-blue-400 hover:underline mb-4 inline-block">← Kembali</a>
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Detail Transaksi</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Contract Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Informasi Kontrak</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Nomor Kontrak</p>
                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nomor_kontrak']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tanggal Akad</p>
                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo formatTanggal($trans['tanggal_akad']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                    <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($trans['status']); ?>">
                        <?php echo ucfirst($trans['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Data Pelanggan</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Nama Lengkap</p>
                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_lengkap']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">NIK</p>
                    <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nik']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Telepon</p>
                    <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['telepon']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Alamat</p>
                    <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['alamat']); ?></p>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Data Barang</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Nama Barang</p>
                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_barang']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Deskripsi</p>
                    <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['deskripsi']); ?></p>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Riwayat Pembayaran</h2>
            <?php if ($payments->num_rows > 0): ?>
                <div class="space-y-3">
                    <?php while ($pay = $payments->fetch_assoc()): ?>
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($pay['nominal']); ?></p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <?php echo $pay['tanggal_bayar'] ? formatDateTime($pay['tanggal_bayar']) : 'Menunggu pembayaran'; ?>
                                    </p>
                                    <?php if ($pay['keterangan']): ?>
                                        <p class="text-xs text-gray-500 dark:text-gray-500"><?php echo htmlspecialchars($pay['keterangan']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($pay['status']); ?>">
                                    <?php echo ucfirst($pay['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-400 text-center py-4">Belum ada pembayaran</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Financial Summary -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <h2 class="text-lg font-semibold mb-4">Ringkasan Keuangan</h2>
            <div class="space-y-4">
                <div>
                    <p class="text-sm opacity-90">Harga Modal</p>
                    <p class="text-xl font-bold"><?php echo formatRupiah($trans['harga_modal']); ?></p>
                </div>
                <div>
                    <p class="text-sm opacity-90">Margin</p>
                    <p class="text-xl font-bold"><?php echo formatRupiah($trans['margin']); ?></p>
                </div>
                <div class="border-t border-white border-opacity-20 pt-3">
                    <p class="text-sm opacity-90">Total Harga</p>
                    <p class="text-2xl font-bold"><?php echo formatRupiah($trans['total_harga']); ?></p>
                </div>
            </div>
        </div>

        <!-- Installment Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Info Cicilan</h2>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tenor</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white"><?php echo $trans['tenor']; ?> Bulan</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Angsuran/Bulan</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white"><?php echo formatRupiah($trans['angsuran_perbulan']); ?></p>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Dibayar</p>
                    <p class="text-lg font-bold text-green-600 dark:text-green-400"><?php echo formatRupiah($trans['total_dibayar']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Sisa Hutang</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?php echo formatRupiah($trans['sisa_hutang']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Sisa Angsuran</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white"><?php echo $sisaAngsuran; ?>x</p>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Progress Pembayaran</h2>
            <?php
            $progress = $trans['total_harga'] > 0 ? ($trans['total_dibayar'] / $trans['total_harga']) * 100 : 0;
            ?>
            <div class="mb-2">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                    <div class="bg-green-600 h-4 rounded-full transition-all" style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>
            <p class="text-sm text-center text-gray-600 dark:text-gray-400"><?php echo number_format($progress, 1); ?>% Terbayar</p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
