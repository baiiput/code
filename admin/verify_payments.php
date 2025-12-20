<?php
require_once '../config/database.php';
require_once '../config/base_path.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$pageTitle = 'Verifikasi Pembayaran Manual';

// Handle verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $payment_id = intval($_POST['payment_id']);
    $user_id = getCurrentUser()['id'];

    if ($action === 'approve') {
        $conn->begin_transaction();

        try {
            // Get payment info
            $payment = $conn->query("SELECT p.*, t.customer_id, t.total_harga, t.total_dibayar, t.sisa_hutang FROM payments p JOIN transactions t ON p.transaction_id = t.id WHERE p.id = $payment_id AND p.payment_type = 'manual' AND p.status = 'waiting_verification'")->fetch_assoc();

            if (!$payment) {
                throw new Exception("Pembayaran tidak ditemukan atau sudah diverifikasi");
            }

            // Update payment status
            $tanggal_bayar = date('Y-m-d H:i:s');
            $conn->query("UPDATE payments SET status = 'success', verified_by = $user_id, verified_at = '$tanggal_bayar', tanggal_bayar = '$tanggal_bayar' WHERE id = $payment_id");

            // Update transaction
            $transaction_id = $payment['transaction_id'];
            $nominal = $payment['nominal'];
            $new_total_dibayar = $payment['total_dibayar'] + $nominal;
            $new_sisa_hutang = $payment['total_harga'] - $new_total_dibayar;

            // If sisa_hutang <= 0, mark as lunas
            if ($new_sisa_hutang <= 0) {
                $new_sisa_hutang = 0;
                $new_status = 'lunas';
                $conn->query("UPDATE transactions SET total_dibayar = $new_total_dibayar, sisa_hutang = $new_sisa_hutang, status = '$new_status' WHERE id = $transaction_id");

                // Distribute profit to investors automatically
                try {
                    distributeInvestorProfit($transaction_id, $conn);
                } catch (Exception $e) {
                    error_log("Profit distribution error for transaction $transaction_id: " . $e->getMessage());
                }
            } else {
                $conn->query("UPDATE transactions SET total_dibayar = $new_total_dibayar, sisa_hutang = $new_sisa_hutang WHERE id = $transaction_id");
            }

            $conn->commit();
            setFlashMessage('success', 'Pembayaran berhasil diverifikasi dan disetujui');
        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Gagal memverifikasi pembayaran: ' . $e->getMessage());
        }
    } elseif ($action === 'reject') {
        $rejection_reason = sanitize($_POST['rejection_reason']);

        if (empty($rejection_reason)) {
            setFlashMessage('error', 'Alasan penolakan harus diisi');
        } else {
            $verified_at = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE payments SET status = 'rejected', verified_by = ?, verified_at = ?, rejection_reason = ? WHERE id = ? AND payment_type = 'manual' AND status = 'waiting_verification'");
            $stmt->bind_param("issi", $user_id, $verified_at, $rejection_reason, $payment_id);

            if ($stmt->execute()) {
                setFlashMessage('success', 'Pembayaran ditolak');
            } else {
                setFlashMessage('error', 'Gagal menolak pembayaran');
            }
            $stmt->close();
        }
    }

    header('Location: ' . baseUrl('admin/verify_payments.php'));
    exit;
}

// Get filter
$filter_status = $_GET['status'] ?? 'waiting_verification';

// Build query
$where = "p.payment_type = 'manual'";
if ($filter_status !== 'all') {
    $where .= " AND p.status = '$filter_status'";
}

// Get manual payments
$payments = $conn->query("
    SELECT p.*, t.nomor_kontrak, c.nama_lengkap, u.full_name as verified_by_name
    FROM payments p
    JOIN transactions t ON p.transaction_id = t.id
    JOIN customers c ON t.customer_id = c.id
    LEFT JOIN users u ON p.verified_by = u.id
    WHERE $where
    ORDER BY p.created_at DESC
");

include '../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Verifikasi Pembayaran Manual</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Verifikasi bukti transfer dari customer</p>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 mb-6">
    <form method="GET" class="flex items-center space-x-4">
        <div class="flex-1">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <option value="waiting_verification" <?php echo $filter_status === 'waiting_verification' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                <option value="success" <?php echo $filter_status === 'success' ? 'selected' : ''; ?>>Disetujui</option>
                <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Semua</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">Filter</button>
    </form>
</div>

<!-- Payments Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No. Kontrak</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nominal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($payments->num_rows > 0): ?>
                    <?php while ($pay = $payments->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo date('d/m/Y H:i', strtotime($pay['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($pay['nama_lengkap']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($pay['nomor_kontrak']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-blue-600 dark:text-blue-400">
                                Rp <?php echo number_format($pay['nominal'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                switch ($pay['status']) {
                                    case 'waiting_verification':
                                        $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                                        $statusText = 'Menunggu';
                                        break;
                                    case 'success':
                                        $statusClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                                        $statusText = 'Disetujui';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
                                        $statusText = 'Ditolak';
                                        break;
                                }
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                                <?php if ($pay['status'] === 'success' && $pay['verified_by_name']): ?>
                                    <div class="text-xs text-gray-500 mt-1">Oleh: <?php echo htmlspecialchars($pay['verified_by_name']); ?></div>
                                <?php endif; ?>
                                <?php if ($pay['status'] === 'rejected' && $pay['rejection_reason']): ?>
                                    <div class="text-xs text-red-600 mt-1"><?php echo htmlspecialchars($pay['rejection_reason']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="viewProof('<?php echo $pay['bukti_transfer']; ?>', '<?php echo htmlspecialchars($pay['nama_lengkap']); ?>')" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">
                                    Lihat Bukti
                                </button>
                                <?php if ($pay['status'] === 'waiting_verification'): ?>
                                    <button onclick="approvePayment(<?php echo $pay['id']; ?>, '<?php echo htmlspecialchars($pay['nama_lengkap']); ?>', <?php echo $pay['nominal']; ?>)" class="text-green-600 hover:text-green-900 dark:text-green-400 mr-3">
                                        Setujui
                                    </button>
                                    <button onclick="openRejectModal(<?php echo $pay['id']; ?>, '<?php echo htmlspecialchars($pay['nama_lengkap']); ?>')" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                        Tolak
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada pembayaran manual</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal View Proof -->
<div id="proofModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 id="proofTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Bukti Transfer</h3>
            <button onclick="closeProofModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="proofContent" class="text-center"></div>
    </div>
</div>

<!-- Modal Reject -->
<div id="rejectModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Tolak Pembayaran</h3>
            <button onclick="closeRejectModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="rejectForm" method="POST">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="payment_id" id="reject_payment_id">

            <div class="mb-4">
                <p class="text-gray-700 dark:text-gray-300 mb-2">Customer: <strong id="reject_customer_name"></strong></p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Alasan Penolakan *</label>
                <textarea name="rejection_reason" required rows="4" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" placeholder="Jelaskan alasan penolakan (contoh: Bukti tidak jelas, nominal tidak sesuai, dll)"></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition duration-200">
                    Tolak Pembayaran
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Forms -->
<form id="approveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="payment_id" id="approve_payment_id">
</form>

<script>
function viewProof(filename, customerName) {
    const modal = document.getElementById('proofModal');
    const title = document.getElementById('proofTitle');
    const content = document.getElementById('proofContent');

    title.textContent = `Bukti Transfer - ${customerName}`;

    const ext = filename.split('.').pop().toLowerCase();
    const baseUrl = '<?php echo baseUrl("uploads/bukti_transfer/"); ?>';

    if (ext === 'pdf') {
        content.innerHTML = `<embed src="${baseUrl}${filename}" type="application/pdf" width="100%" height="600px" />`;
    } else {
        content.innerHTML = `<img src="${baseUrl}${filename}" alt="Bukti Transfer" class="max-w-full h-auto rounded-lg shadow-lg" />`;
    }

    modal.classList.remove('hidden');
}

function closeProofModal() {
    document.getElementById('proofModal').classList.add('hidden');
}

function approvePayment(paymentId, customerName, nominal) {
    const nominalFormat = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(nominal);
    if (confirm(`Setujui pembayaran dari ${customerName} sebesar ${nominalFormat}?`)) {
        document.getElementById('approve_payment_id').value = paymentId;
        document.getElementById('approveForm').submit();
    }
}

function openRejectModal(paymentId, customerName) {
    document.getElementById('reject_payment_id').value = paymentId;
    document.getElementById('reject_customer_name').textContent = customerName;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
