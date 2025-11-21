<?php
/**
 * Helper Functions
 * Koperasi Syariah Online
 */

/**
 * Format currency to Rupiah
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format date to Indonesian format
 */
function formatTanggal($date) {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('j', $timestamp);
    $m = date('n', $timestamp);
    $y = date('Y', $timestamp);
    return $d . ' ' . $bulan[$m] . ' ' . $y;
}

/**
 * Format datetime to Indonesian format
 */
function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return formatTanggal($datetime) . ' ' . date('H:i', strtotime($datetime));
}

/**
 * Generate nomor kontrak
 */
function generateNomorKontrak() {
    return 'KSO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Sanitize input
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'];
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Get status badge color
 */
function getStatusBadge($status) {
    $badges = [
        'aktif' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'lunas' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'batal' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    ];
    return $badges[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
}

/**
 * Calculate remaining installments
 */
function calculateRemainingInstallments($totalHarga, $totalDibayar, $angsuranPerbulan) {
    $sisaHutang = $totalHarga - $totalDibayar;
    if ($sisaHutang <= 0) return 0;
    return ceil($sisaHutang / $angsuranPerbulan);
}

/**
 * Distribute profit to investors when transaction is lunas
 * This function is called automatically when a transaction status becomes 'lunas'
 */
function distributeInvestorProfit($transaction_id, $conn) {
    global $conn;

    // Get transaction info
    $trans = $conn->query("SELECT * FROM transactions WHERE id = $transaction_id")->fetch_assoc();
    if (!$trans) {
        throw new Exception("Transaction not found");
    }

    // Check if funded by investor
    if ($trans['funded_by_investor'] != 1) {
        return; // Not funded by investor, skip profit distribution
    }

    // Get all investors for this transaction
    $investors = $conn->query("
        SELECT ti.*, i.nisbah_investor, i.nisbah_koperasi, i.nama_investor
        FROM transaction_investors ti
        JOIN investors i ON ti.investor_id = i.id
        WHERE ti.transaction_id = $transaction_id
          AND ti.modal_returned = 0
    ");

    if ($investors->num_rows === 0) {
        return; // No investors or already distributed
    }

    $total_profit = $trans['margin']; // Total profit from margin
    $total_modal_allocated = 0;
    $today = date('Y-m-d');

    // Calculate total modal for this transaction
    while ($inv = $investors->fetch_assoc()) {
        $total_modal_allocated += $inv['modal_dialokasi'];
    }
    $investors->data_seek(0); // Reset pointer

    // Distribute to each investor
    while ($inv = $investors->fetch_assoc()) {
        $investor_id = $inv['investor_id'];
        $modal_dialokasi = $inv['modal_dialokasi'];
        $proporsi = $inv['proporsi'];
        $nisbah_investor = $inv['nisbah_investor'];
        $nisbah_koperasi = $inv['nisbah_koperasi'];

        // Calculate profit share for this investor
        // Formula: (modal_dialokasi / total_modal_pool) × nisbah_investor% × total_profit
        $investor_profit = ($proporsi / 100) * ($nisbah_investor / 100) * $total_profit;

        // Update transaction_investors
        $stmt = $conn->prepare("UPDATE transaction_investors SET profit_share = ?, modal_returned = 1, profit_distributed = 1, tanggal_return = ? WHERE id = ?");
        $stmt->bind_param("dsi", $investor_profit, $today, $inv['id']);
        $stmt->execute();
        $stmt->close();

        // Return modal + profit to investor
        $conn->query("UPDATE investors SET modal_tersedia = modal_tersedia + $modal_dialokasi + $investor_profit, modal_allocated = modal_allocated - $modal_dialokasi, total_profit = total_profit + $investor_profit WHERE id = $investor_id");

        // Log to investor_profit_history
        $keterangan = "Profit dari transaksi {$trans['nomor_kontrak']} - Customer: {$trans['customer_id']}";
        $stmt = $conn->prepare("INSERT INTO investor_profit_history (investor_id, transaction_id, transaction_investor_id, modal_dialokasi, profit_amount, nisbah_investor, proporsi, tanggal_profit, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiddiiss", $investor_id, $transaction_id, $inv['id'], $modal_dialokasi, $investor_profit, $nisbah_investor, $proporsi, $today, $keterangan);
        $stmt->execute();
        $stmt->close();

        // Log to kas_transactions - return modal + profit
        $total_return = $modal_dialokasi + $investor_profit;
        $kas_before = floatval($conn->query("SELECT setting_value FROM kas_settings WHERE setting_key = 'modal_allocated'")->fetch_assoc()['setting_value']);
        $kas_after = $kas_before - $modal_dialokasi;
        $ket_kas = "Return modal + profit ke {$inv['nama_investor']} dari transaksi {$trans['nomor_kontrak']} (Modal: " . number_format($modal_dialokasi, 0, ',', '.') . " + Profit: " . number_format($investor_profit, 0, ',', '.') . ")";

        $user_id = getCurrentUser()['id'] ?? NULL;
        $stmt = $conn->prepare("INSERT INTO kas_transactions (tipe, kategori, nominal, saldo_before, saldo_after, referensi_type, referensi_id, keterangan, tanggal_transaksi, created_by) VALUES ('masuk', 'investor_return', ?, ?, ?, 'transaction', ?, ?, ?, ?)");
        $stmt->bind_param("dddissi", $total_return, $kas_before, $kas_after, $transaction_id, $ket_kas, $today, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Update kas settings
    $conn->query("UPDATE kas_settings SET setting_value = setting_value + $total_modal_allocated WHERE setting_key = 'modal_tersedia'");
    $conn->query("UPDATE kas_settings SET setting_value = setting_value - $total_modal_allocated WHERE setting_key = 'modal_allocated'");

    return true;
}
?>
