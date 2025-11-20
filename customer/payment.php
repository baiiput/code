<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCustomer();

$pageTitle = 'Pembayaran Cicilan';

$currentUser = getCurrentUser();
$customer_id = $currentUser['customer_id'];
$transaction_id = intval($_GET['id'] ?? 0);

// Get transaction details
$trans = $conn->query("
    SELECT t.*, c.nama_lengkap, c.email, p.nama_barang
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    JOIN products p ON t.product_id = p.id
    WHERE t.id = $transaction_id AND t.customer_id = $customer_id AND t.status = 'aktif'
")->fetch_assoc();

if (!$trans) {
    header('Location: /customer/index.php');
    exit;
}

// Get pending payments for this transaction
$pendingPayments = $conn->query("
    SELECT p.*, xp.xendit_invoice_url, xp.xendit_invoice_id, xp.status as xendit_status
    FROM payments p
    LEFT JOIN xendit_payments xp ON xp.payment_id = p.id
    WHERE p.transaction_id = $transaction_id AND p.status = 'pending'
    ORDER BY p.created_at DESC
");

// Calculate remaining installments
$sisaAngsuran = calculateRemainingInstallments($trans['total_harga'], $trans['total_dibayar'], $trans['angsuran_perbulan']);

include '../includes/header.php';
?>

<div class="mb-8">
    <a href="/customer/index.php" class="text-blue-600 dark:text-blue-400 hover:underline mb-4 inline-block">← Kembali ke Dashboard</a>
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Pembayaran Cicilan</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Payment Form -->
    <div class="lg:col-span-2">
        <!-- Transaction Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Detail Cicilan</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Barang</span>
                    <span class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nama_barang']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Nomor Kontrak</span>
                    <span class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($trans['nomor_kontrak']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Total Harga</span>
                    <span class="font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($trans['total_harga']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Angsuran/Bulan</span>
                    <span class="font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($trans['angsuran_perbulan']); ?></span>
                </div>
                <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-3">
                    <span class="text-gray-600 dark:text-gray-400">Total Dibayar</span>
                    <span class="font-semibold text-green-600 dark:text-green-400"><?php echo formatRupiah($trans['total_dibayar']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Sisa Hutang</span>
                    <span class="text-xl font-bold text-red-600 dark:text-red-400"><?php echo formatRupiah($trans['sisa_hutang']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Sisa Angsuran</span>
                    <span class="font-semibold text-gray-900 dark:text-white"><?php echo $sisaAngsuran; ?>x</span>
                </div>
            </div>
        </div>

        <!-- Create Payment -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Buat Pembayaran Baru</h2>

            <form id="paymentForm" action="/api/create_payment.php" method="POST">
                <input type="hidden" name="transaction_id" value="<?php echo $transaction_id; ?>">

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Jumlah Pembayaran *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500 dark:text-gray-400">Rp</span>
                        <input
                            type="number"
                            name="nominal"
                            id="nominal"
                            min="<?php echo $trans['angsuran_perbulan']; ?>"
                            max="<?php echo $trans['sisa_hutang']; ?>"
                            step="1000"
                            required
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="Minimal <?php echo number_format($trans['angsuran_perbulan'], 0, ',', '.'); ?>"
                        >
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Minimum: <?php echo formatRupiah($trans['angsuran_perbulan']); ?> | Maximum: <?php echo formatRupiah($trans['sisa_hutang']); ?>
                    </p>
                </div>

                <!-- Quick Amount Buttons -->
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Pilih Cepat</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <button type="button" onclick="setAmount(<?php echo $trans['angsuran_perbulan']; ?>)" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                            1x Angsuran
                        </button>
                        <button type="button" onclick="setAmount(<?php echo $trans['angsuran_perbulan'] * 2; ?>)" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                            2x Angsuran
                        </button>
                        <button type="button" onclick="setAmount(<?php echo $trans['angsuran_perbulan'] * 3; ?>)" class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                            3x Angsuran
                        </button>
                        <button type="button" onclick="setAmount(<?php echo $trans['sisa_hutang']; ?>)" class="px-3 py-2 text-sm bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 text-green-900 dark:text-green-100 rounded-lg transition">
                            Lunas
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Keterangan (Opsional)</label>
                    <textarea
                        name="keterangan"
                        rows="2"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="Catatan pembayaran..."
                    ></textarea>
                </div>

                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200"
                >
                    Buat Link Pembayaran
                </button>
            </form>

            <div id="loadingDiv" class="hidden text-center py-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Membuat payment link...</p>
            </div>

            <div id="resultDiv" class="hidden mt-4"></div>
        </div>

        <!-- Pending Payments -->
        <?php if ($pendingPayments->num_rows > 0): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Pembayaran Pending</h2>
            <div class="space-y-4">
                <?php while ($pay = $pendingPayments->fetch_assoc()): ?>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo formatRupiah($pay['nominal']); ?></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Dibuat: <?php echo formatDateTime($pay['created_at']); ?></p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded <?php echo getStatusBadge($pay['status']); ?>">
                                Pending
                            </span>
                        </div>

                        <?php if ($pay['xendit_invoice_url']): ?>
                            <div class="flex gap-2">
                                <a href="<?php echo htmlspecialchars($pay['xendit_invoice_url']); ?>" target="_blank" class="flex-1 text-center bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
                                    Bayar Sekarang
                                </a>
                                <button
                                    onclick="checkPaymentStatus(<?php echo $pay['id']; ?>, '<?php echo htmlspecialchars($pay['xendit_invoice_id']); ?>')"
                                    class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                                >
                                    Cek Status
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white sticky top-4">
            <h2 class="text-lg font-semibold mb-4">Informasi Pembayaran</h2>
            <div class="space-y-3 text-sm">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Anda bisa membayar sesuai nominal angsuran atau lebih</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Pembayaran lebih akan langsung mengurangi total hutang</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Pembayaran menggunakan Xendit (Virtual Account, E-Wallet, dll)</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Link pembayaran berlaku 24 jam</span>
                </div>
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Setelah bayar, klik "Cek Status" untuk update pembayaran</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setAmount(amount) {
    const maxAmount = <?php echo $trans['sisa_hutang']; ?>;
    const finalAmount = Math.min(amount, maxAmount);
    document.getElementById('nominal').value = finalAmount;
}

document.getElementById('paymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const loadingDiv = document.getElementById('loadingDiv');
    const resultDiv = document.getElementById('resultDiv');
    const form = e.target;

    submitBtn.classList.add('hidden');
    loadingDiv.classList.remove('hidden');
    resultDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            resultDiv.innerHTML = `
                <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg">
                    <p class="font-semibold mb-2">Payment link berhasil dibuat!</p>
                    <a href="${result.invoice_url}" target="_blank" class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 mt-2">
                        Bayar Sekarang
                    </a>
                </div>
            `;
            form.reset();
            setTimeout(() => window.location.reload(), 2000);
        } else {
            resultDiv.innerHTML = `
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg">
                    <p class="font-semibold">Error: ${result.message || 'Gagal membuat payment link'}</p>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg">
                <p class="font-semibold">Error: ${error.message}</p>
            </div>
        `;
    } finally {
        submitBtn.classList.remove('hidden');
        loadingDiv.classList.add('hidden');
        resultDiv.classList.remove('hidden');
    }
});

async function checkPaymentStatus(paymentId, invoiceId) {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Mengecek...';

    try {
        const response = await fetch('/api/check_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                payment_id: paymentId,
                invoice_id: invoiceId
            })
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Cek Status';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
