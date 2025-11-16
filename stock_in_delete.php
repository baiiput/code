<?php
require_once 'config.php';
requireRole(['admin']);

$conn = getDBConnection();
$user = getCurrentUser();

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "ID transaksi tidak valid";
    header('Location: stock_in.php');
    exit;
}

$stock_in_id = intval($_GET['id']);

$conn->begin_transaction();
try {
    // Get stock in data
    $stmt = $conn->prepare("SELECT * FROM stock_in WHERE stock_in_id = ?");
    $stmt->bind_param("i", $stock_in_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock_in = $result->fetch_assoc();
    $stmt->close();

    if (!$stock_in) {
        throw new Exception("Transaksi tidak ditemukan");
    }

    $transaction_code = $stock_in['transaction_code'];

    // Get details to reverse stock
    $stmt = $conn->prepare("SELECT * FROM stock_in_detail WHERE stock_in_id = ?");
    $stmt->bind_param("i", $stock_in_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    $stmt->close();

    // Reverse stock - reduce from current stock
    foreach ($details as $detail) {
        $item_id = $detail['item_id'];
        $quantity = $detail['quantity'];
        $subtotal = $detail['subtotal'];

        // Get current stock and average cost
        $stmt = $conn->prepare("SELECT current_stock, average_cost FROM items WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $item_result = $stmt->get_result();
        $item = $item_result->fetch_assoc();
        $stmt->close();

        $current_stock = $item['current_stock'];
        $current_avg_cost = $item['average_cost'];

        // Calculate new stock
        $new_stock = $current_stock - $quantity;

        // Calculate new average cost (weighted average reversal)
        $new_avg_cost = $current_avg_cost; // Keep current average cost
        if ($new_stock > 0) {
            // Recalculate average cost by removing this purchase
            $total_value = $current_stock * $current_avg_cost;
            $removed_value = $subtotal;
            $new_total_value = $total_value - $removed_value;
            $new_avg_cost = $new_total_value / $new_stock;
        } else {
            $new_avg_cost = 0;
        }

        // Update item stock and average cost
        $stmt = $conn->prepare("UPDATE items SET current_stock = ?, average_cost = ? WHERE item_id = ?");
        $stmt->bind_param("ddi", $new_stock, $new_avg_cost, $item_id);
        $stmt->execute();
        $stmt->close();
    }

    // Delete from stock_in_detail (CASCADE will handle this, but let's be explicit)
    $stmt = $conn->prepare("DELETE FROM stock_in_detail WHERE stock_in_id = ?");
    $stmt->bind_param("i", $stock_in_id);
    $stmt->execute();
    $stmt->close();

    // Delete from stock_in
    $stmt = $conn->prepare("DELETE FROM stock_in WHERE stock_in_id = ?");
    $stmt->bind_param("i", $stock_in_id);
    $stmt->execute();
    $stmt->close();

    // Delete financial transaction
    $stmt = $conn->prepare("DELETE FROM financial_transactions WHERE reference_code = ? AND transaction_type = 'stock_in'");
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
    header('Location: stock_in.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error menghapus transaksi: " . $e->getMessage();
    header('Location: stock_in.php');
    exit;
}
?>
