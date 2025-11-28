<?php
require_once 'config.php';
requireRole(['admin', 'manager']);

$conn = getDBConnection();
$user = getCurrentUser();

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "ID transaksi tidak valid";
    header('Location: stock_out.php');
    exit;
}

$stock_out_id = intval($_GET['id']);

$conn->begin_transaction();
try {
    // Get stock out data with warehouse_id
    $stmt = $conn->prepare("SELECT * FROM stock_out WHERE stock_out_id = ?");
    $stmt->bind_param("i", $stock_out_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock_out = $result->fetch_assoc();
    $stmt->close();

    if (!$stock_out) {
        throw new Exception("Transaksi tidak ditemukan");
    }

    $transaction_code = $stock_out['transaction_code'];
    $warehouse_id = $stock_out['warehouse_id'];

    // Get details to reverse stock
    $stmt = $conn->prepare("SELECT * FROM stock_out_detail WHERE stock_out_id = ?");
    $stmt->bind_param("i", $stock_out_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    $stmt->close();

    // Reverse stock - add back to warehouse_items current stock
    foreach ($details as $detail) {
        $item_id = $detail['item_id'];
        $quantity = $detail['quantity'];

        // Update warehouse_item stock (add back)
        $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = current_stock + ? WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("dii", $quantity, $warehouse_id, $item_id);
        $stmt->execute();
        $stmt->close();
    }

    // Delete from stock_out_detail
    $stmt = $conn->prepare("DELETE FROM stock_out_detail WHERE stock_out_id = ?");
    $stmt->bind_param("i", $stock_out_id);
    $stmt->execute();
    $stmt->close();

    // Delete from stock_out
    $stmt = $conn->prepare("DELETE FROM stock_out WHERE stock_out_id = ?");
    $stmt->bind_param("i", $stock_out_id);
    $stmt->execute();
    $stmt->close();

    // Delete financial transaction
    $stmt = $conn->prepare("DELETE FROM financial_transactions WHERE reference_code = ? AND transaction_type = 'stock_out'");
    $stmt->bind_param("s", $transaction_code);
    $stmt->execute();
    $stmt->close();

    // RECALCULATE ALL FINANCIAL BALANCES
    // Get all transactions ordered by date
    $result = $conn->query("
        SELECT transaction_id, debit, credit
        FROM financial_transactions
        ORDER BY transaction_date ASC, transaction_id ASC
    ");

    $balance = 0;
    while ($row = $result->fetch_assoc()) {
        $balance += $row['debit'] - $row['credit'];

        // Update balance_after for this transaction
        $stmt = $conn->prepare("UPDATE financial_transactions SET balance_after = ? WHERE transaction_id = ?");
        $stmt->bind_param("di", $balance, $row['transaction_id']);
        $stmt->execute();
        $stmt->close();
    }

    // Update warehouse balance
    $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = ? WHERE balance_id = 1");
    $stmt->bind_param("d", $balance);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION['success_message'] = "Transaksi $transaction_code berhasil dihapus dan keuangan telah dihitung ulang";
    header('Location: stock_out.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error menghapus transaksi: " . $e->getMessage();
    header('Location: stock_out.php');
    exit;
}
?>
