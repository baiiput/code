<?php
require_once 'config.php';
requireRole(['admin', 'manager']);

$conn = getDBConnection();
$user = getCurrentUser();

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "ID transfer tidak valid";
    header('Location: stock_transfer.php');
    exit;
}

$transfer_id = intval($_GET['id']);

$conn->begin_transaction();
try {
    // Get transfer data
    $stmt = $conn->prepare("SELECT * FROM stock_transfers WHERE transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transfer = $result->fetch_assoc();
    $stmt->close();

    if (!$transfer) {
        throw new Exception("Transfer tidak ditemukan");
    }

    $transaction_code = $transfer['transaction_code'];
    $from_warehouse_id = $transfer['from_warehouse_id'];
    $to_warehouse_id = $transfer['to_warehouse_id'];

    // Get details to reverse stock
    $stmt = $conn->prepare("SELECT * FROM stock_transfer_detail WHERE transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    $stmt->close();

    // Reverse transfer - restore stock movements
    foreach ($details as $detail) {
        $item_id = $detail['item_id'];
        $quantity = $detail['quantity'];

        // 1. Add back stock to source warehouse (from_warehouse)
        $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = current_stock + ? WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("dii", $quantity, $from_warehouse_id, $item_id);
        $stmt->execute();
        $stmt->close();

        // 2. Reduce stock from destination warehouse (to_warehouse)
        // Get current stock and average cost from destination
        $stmt = $conn->prepare("SELECT current_stock, average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $to_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dest_item = $result->fetch_assoc();
        $stmt->close();

        if ($dest_item) {
            $new_stock = $dest_item['current_stock'] - $quantity;

            if ($new_stock > 0) {
                // Just reduce the quantity, keep the average cost
                $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
                $stmt->bind_param("dii", $new_stock, $to_warehouse_id, $item_id);
                $stmt->execute();
                $stmt->close();
            } else {
                // Stock becomes 0 or negative, set to 0
                $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = 0 WHERE warehouse_id = ? AND item_id = ?");
                $stmt->bind_param("ii", $to_warehouse_id, $item_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Delete transfer details
    $stmt = $conn->prepare("DELETE FROM stock_transfer_detail WHERE transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $stmt->close();

    // Delete transfer header
    $stmt = $conn->prepare("DELETE FROM stock_transfers WHERE transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION['success_message'] = "Transfer stok $transaction_code berhasil dihapus dan stok telah dikembalikan";
    header('Location: stock_transfer.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error menghapus transfer: " . $e->getMessage();
    header('Location: stock_transfer.php');
    exit;
}
?>
