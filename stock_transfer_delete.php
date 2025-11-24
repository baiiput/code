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
    $stmt = $conn->prepare("SELECT std.*, i.item_code, i.item_name FROM stock_transfer_detail std JOIN items i ON std.item_id = i.item_id WHERE std.transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }
    $stmt->close();

    // VALIDATE stock availability in destination warehouse BEFORE deleting
    $insufficient_items = [];
    foreach ($details as $detail) {
        $item_id = $detail['item_id'];
        $quantity = $detail['quantity'];

        // Check current stock in destination warehouse
        $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $to_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $current_stock = $result->fetch_assoc()['current_stock'];

            // If current stock is less than what we transferred, record it
            if ($current_stock < $quantity) {
                $insufficient_items[] = [
                    'item_code' => $detail['item_code'],
                    'item_name' => $detail['item_name'],
                    'required' => $quantity,
                    'available' => $current_stock,
                    'shortage' => $quantity - $current_stock
                ];
            }
        } else {
            // Item doesn't exist in warehouse anymore
            $insufficient_items[] = [
                'item_code' => $detail['item_code'],
                'item_name' => $detail['item_name'],
                'required' => $quantity,
                'available' => 0,
                'shortage' => $quantity
            ];
        }
    }

    // If there are insufficient items, throw detailed error
    if (!empty($insufficient_items)) {
        $error_msg = "TIDAK DAPAT HAPUS TRANSFER $transaction_code!\n\n";
        $error_msg .= "Stok di warehouse tujuan tidak mencukupi untuk di-revert.\n";
        $error_msg .= "Kemungkinan stok sudah didistribusikan/digunakan.\n\n";
        $error_msg .= "Detail item yang tidak mencukupi:\n\n";

        foreach ($insufficient_items as $item) {
            $error_msg .= "• {$item['item_code']} - {$item['item_name']}\n";
            $error_msg .= "  Dibutuhkan: " . number_format($item['required'], 2) . "\n";
            $error_msg .= "  Tersedia: " . number_format($item['available'], 2) . "\n";
            $error_msg .= "  Kekurangan: " . number_format($item['shortage'], 2) . "\n\n";
        }

        $error_msg .= "SOLUSI:\n";
        $error_msg .= "1. Batalkan distribusi/transaksi keluar dari warehouse tujuan terlebih dahulu\n";
        $error_msg .= "2. Atau gunakan Stock Adjustment untuk memperbaiki stok";

        throw new Exception($error_msg);
    }

    // Reverse transfer - restore stock movements (now safe because we validated above)
    foreach ($details as $detail) {
        $item_id = $detail['item_id'];
        $quantity = $detail['quantity'];

        // 1. Add back stock to source warehouse (from_warehouse)
        $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = current_stock + ? WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("dii", $quantity, $from_warehouse_id, $item_id);
        $stmt->execute();
        $stmt->close();

        // 2. Reduce stock from destination warehouse (to_warehouse)
        $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $to_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dest_item = $result->fetch_assoc();
        $stmt->close();

        if ($dest_item) {
            $new_stock = $dest_item['current_stock'] - $quantity;

            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("dii", $new_stock, $to_warehouse_id, $item_id);
            $stmt->execute();
            $stmt->close();
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

    // Log activity
    logActivity('DELETE', 'stock_transfer', "Deleted transfer $transaction_code");

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
