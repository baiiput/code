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

$currentUser = getCurrentUser();
$customer_id = $currentUser['customer_id'];

// Get form data
$transaction_id = intval($_POST['transaction_id']);
$nominal = floatval($_POST['nominal']);
$keterangan = sanitize($_POST['keterangan'] ?? '');

// Validate transaction belongs to customer
$trans = $conn->query("
    SELECT t.*, c.email, c.nama_lengkap
    FROM transactions t
    JOIN customers c ON t.customer_id = c.id
    WHERE t.id = $transaction_id AND t.customer_id = $customer_id AND t.status = 'aktif'
")->fetch_assoc();

if (!$trans) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found or invalid']);
    exit;
}

// Validate nominal
if ($nominal < $trans['angsuran_perbulan']) {
    echo json_encode(['success' => false, 'message' => 'Nominal kurang dari angsuran bulanan']);
    exit;
}

if ($nominal > $trans['sisa_hutang']) {
    echo json_encode(['success' => false, 'message' => 'Nominal melebihi sisa hutang']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Create payment record
    $stmt = $conn->prepare("INSERT INTO payments (transaction_id, nominal, metode_pembayaran, status, keterangan) VALUES (?, ?, 'xendit', 'pending', ?)");
    $stmt->bind_param("ids", $transaction_id, $nominal, $keterangan);
    $stmt->execute();
    $payment_id = $conn->insert_id;
    $stmt->close();

    // Create Xendit invoice
    $external_id = 'PAY-' . $payment_id . '-' . time();
    $payer_email = !empty($trans['email']) ? $trans['email'] : 'customer@koperasi.com';
    $description = "Pembayaran Cicilan - " . $trans['nomor_kontrak'] . " - " . $trans['nama_lengkap'];

    $xenditResponse = createXenditInvoice($external_id, $nominal, $payer_email, $description);

    if (isset($xenditResponse['error'])) {
        throw new Exception($xenditResponse['message'] ?? 'Failed to create Xendit invoice');
    }

    // Save Xendit payment info
    $xendit_invoice_id = $xenditResponse['id'];
    $xendit_invoice_url = $xenditResponse['invoice_url'];
    $xendit_status = $xenditResponse['status'];
    $xendit_response_json = json_encode($xenditResponse);

    $stmt = $conn->prepare("INSERT INTO xendit_payments (payment_id, xendit_invoice_id, xendit_invoice_url, xendit_external_id, amount, status, xendit_response) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdss", $payment_id, $xendit_invoice_id, $xendit_invoice_url, $external_id, $nominal, $xendit_status, $xendit_response_json);
    $stmt->execute();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment link created successfully',
        'payment_id' => $payment_id,
        'invoice_url' => $xendit_invoice_url,
        'invoice_id' => $xendit_invoice_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
