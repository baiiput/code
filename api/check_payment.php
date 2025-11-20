<?php
require_once '../config/database.php';
require_once '../config/xendit.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$payment_id = intval($input['payment_id'] ?? 0);
$invoice_id = $input['invoice_id'] ?? '';

if (!$payment_id || !$invoice_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Get payment info
$payment = $conn->query("
    SELECT p.*, t.customer_id, t.total_harga, t.total_dibayar, t.sisa_hutang
    FROM payments p
    JOIN transactions t ON p.transaction_id = t.id
    WHERE p.id = $payment_id
")->fetch_assoc();

if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Payment not found']);
    exit;
}

// Verify customer ownership (for customer role)
$currentUser = getCurrentUser();
if (isCustomer() && $payment['customer_id'] != $currentUser['customer_id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check Xendit invoice status
$xenditStatus = checkXenditInvoiceStatus($invoice_id);

if (isset($xenditStatus['error'])) {
    echo json_encode(['success' => false, 'message' => 'Failed to check invoice status']);
    exit;
}

$status = $xenditStatus['status'];
$paid_at = $xenditStatus['paid_at'] ?? null;

// Update xendit_payments table
$xendit_response_json = json_encode($xenditStatus);
$conn->query("UPDATE xendit_payments SET status = '$status', paid_at = " . ($paid_at ? "'$paid_at'" : "NULL") . ", xendit_response = '$xendit_response_json' WHERE payment_id = $payment_id");

// If payment is PAID/SETTLED, update payment and transaction
if ($status === 'PAID' || $status === 'SETTLED') {
    // Check if not already processed
    if ($payment['status'] !== 'success') {
        $conn->begin_transaction();

        try {
            // Update payment status
            $tanggal_bayar = $paid_at ? date('Y-m-d H:i:s', strtotime($paid_at)) : date('Y-m-d H:i:s');
            $conn->query("UPDATE payments SET status = 'success', tanggal_bayar = '$tanggal_bayar' WHERE id = $payment_id");

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
            } else {
                $conn->query("UPDATE transactions SET total_dibayar = $new_total_dibayar, sisa_hutang = $new_sisa_hutang WHERE id = $transaction_id");
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Pembayaran berhasil! Sisa hutang: Rp ' . number_format($new_sisa_hutang, 0, ',', '.'),
                'status' => 'paid',
                'new_sisa_hutang' => $new_sisa_hutang
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to update payment: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Pembayaran sudah berhasil sebelumnya',
            'status' => 'already_paid'
        ]);
    }
} elseif ($status === 'EXPIRED') {
    $conn->query("UPDATE payments SET status = 'failed' WHERE id = $payment_id");
    echo json_encode([
        'success' => false,
        'message' => 'Invoice telah expired. Silakan buat pembayaran baru.',
        'status' => 'expired'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Status pembayaran: ' . $status . '. Menunggu pembayaran.',
        'status' => strtolower($status)
    ]);
}
?>
