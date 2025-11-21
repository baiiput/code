<?php
require_once 'config.php';
requireRole('admin');

$conn = getDBConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: stock_out.php");
    exit();
}

// Get POST data
$stock_out_id = intval($_POST['stock_out_id']);
$branch_id = intval($_POST['branch_id']);
$new_warehouse_id = intval($_POST['warehouse_id']);
$transaction_date = $_POST['transaction_date'];
$notes = $_POST['notes'] ?? '';
$items = $_POST['items'] ?? [];

// Validate
if (!$stock_out_id || !$branch_id || !$new_warehouse_id || empty($items)) {
    $_SESSION['error_message'] = "Data tidak lengkap!";
    header("Location: stock_out_edit.php?id=" . $stock_out_id);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // ========== PHASE 1: REVERT OLD TRANSACTION ==========

    // Get old transaction details and info
    $query = "SELECT so.transaction_code, so.total_amount, so.transaction_date, so.warehouse_id
              FROM stock_out so
              WHERE so.stock_out_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $stock_out_id);
    $stmt->execute();
    $old_transaction = $stmt->get_result()->fetch_assoc();
    $old_total = $old_transaction['total_amount'];
    $old_code = $old_transaction['transaction_code'];
    $old_warehouse_id = $old_transaction['warehouse_id'];
    $stmt->close();

    // Get old detail items
    $query = "SELECT sod.*
              FROM stock_out_detail sod
              WHERE sod.stock_out_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $stock_out_id);
    $stmt->execute();
    $old_details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Revert stock changes to OLD warehouse (kembalikan stok yang sudah keluar)
    foreach ($old_details as $detail) {
        // Stock OUT: Revert dengan MENAMBAH stok kembali ke old_warehouse
        $update_stock = "UPDATE warehouse_items SET current_stock = current_stock + ?
                        WHERE warehouse_id = ? AND item_id = ?";
        $stmt = $conn->prepare($update_stock);
        $stmt->bind_param("dii", $detail['quantity'], $old_warehouse_id, $detail['item_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Revert warehouse balance (kurangi karena distribusi dibatalkan)
    $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = balance_amount - ? WHERE balance_id = 1");
    $stmt->bind_param("d", $old_total);
    $stmt->execute();
    
    // Delete old financial transaction
    $stmt = $conn->prepare("DELETE FROM financial_transactions WHERE reference_code = ? AND transaction_type = 'stock_out'");
    $stmt->bind_param("s", $old_code);
    $stmt->execute();
    
    // Delete old details
    $delete_details = "DELETE FROM stock_out_detail WHERE stock_out_id = ?";
    $stmt = $conn->prepare($delete_details);
    $stmt->bind_param("i", $stock_out_id);
    $stmt->execute();
    
    // ========== PHASE 2: APPLY NEW TRANSACTION ==========
    
    // Calculate new total
    $new_total = 0;
    $processed_items = [];
    
    foreach ($items as $item) {
        if (empty($item['item_id']) || empty($item['quantity'])) continue;
        
        $item_id = intval($item['item_id']);
        $quantity = floatval($item['quantity']);
        $price = floatval($item['price']);
        $subtotal = $quantity * $price;
        
        $new_total += $subtotal;
        
        $processed_items[] = [
            'item_id' => $item_id,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal
        ];
    }
    
    // Update main transaction with new warehouse_id
    $update_main = "UPDATE stock_out SET
                    branch_id = ?,
                    warehouse_id = ?,
                    transaction_date = ?,
                    total_amount = ?,
                    notes = ?
                    WHERE stock_out_id = ?";
    $stmt = $conn->prepare($update_main);
    $stmt->bind_param("iisdsi", $branch_id, $new_warehouse_id, $transaction_date, $new_total, $notes, $stock_out_id);
    $stmt->execute();
    $stmt->close();
    
    // Insert new details and update NEW warehouse stock
    foreach ($processed_items as $item) {
        // Insert detail
        $insert = "INSERT INTO stock_out_detail (stock_out_id, item_id, quantity, unit_price, subtotal)
                   VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("iiddd", $stock_out_id, $item['item_id'], $item['quantity'],
                         $item['price'], $item['subtotal']);
        $stmt->execute();
        $stmt->close();

        // Update NEW warehouse stock (KURANGI stok karena keluar)
        $update_stock = "UPDATE warehouse_items SET current_stock = current_stock - ?
                        WHERE warehouse_id = ? AND item_id = ?";
        $stmt = $conn->prepare($update_stock);
        $stmt->bind_param("dii", $item['quantity'], $new_warehouse_id, $item['item_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update warehouse balance (tambah karena distribusi baru)
    $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = balance_amount + ? WHERE balance_id = 1");
    $stmt->bind_param("d", $new_total);
    $stmt->execute();
    
    // Get new balance
    $result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
    $balance_after = $result->fetch_assoc()['balance_amount'];
    
    // Create new financial transaction
    $description = "Distribusi ke cabang - " . $old_code;
    $stmt = $conn->prepare("INSERT INTO financial_transactions 
                           (transaction_date, transaction_type, reference_code, description, debit, credit, balance_after, created_by) 
                           VALUES (?, 'stock_out', ?, ?, ?, 0, ?, ?)");
    $stmt->bind_param("sssddi", $transaction_date, $old_code, $description, $new_total, $balance_after, $user['user_id']);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Stock Out berhasil diupdate!";
    header("Location: stock_out.php");
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: stock_out_edit.php?id=" . $stock_out_id);
}

$conn->close();
?>
