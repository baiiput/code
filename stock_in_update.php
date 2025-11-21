<?php
require_once 'config.php';
requireRole('admin');

$conn = getDBConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stock_in.php');
    exit;
}

$stock_in_id = intval($_POST['stock_in_id']);
$transaction_date = $_POST['transaction_date'];
$supplier_id = intval($_POST['supplier_id']);
$new_warehouse_id = intval($_POST['warehouse_id']);
$notes = clean($_POST['notes'] ?? '');
$items = $_POST['items'] ?? [];

$conn->begin_transaction();

try {
    // 1. Get old transaction for reverting
    $stmt = $conn->prepare("SELECT * FROM stock_in WHERE stock_in_id = ?");
    $stmt->bind_param("i", $stock_in_id);
    $stmt->execute();
    $old_transaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $old_warehouse_id = $old_transaction['warehouse_id'];

    // 2. Get old details
    $stmt = $conn->prepare("SELECT * FROM stock_in_detail WHERE stock_in_id = ?");
    $stmt->bind_param("i", $stock_in_id);
    $stmt->execute();
    $old_details = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $old_details[] = $row;
    }
    $stmt->close();

    // 3. REVERT old transaction effects from old warehouse
    foreach ($old_details as $detail) {
        $item_id = $detail['item_id'];
        $old_qty = $detail['quantity'];
        $old_price = $detail['unit_price'];

        // Get current warehouse_item data
        $stmt = $conn->prepare("SELECT current_stock, average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $old_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $wh_item = $result->fetch_assoc();
            $current_stock = $wh_item['current_stock'];
            $current_avg = $wh_item['average_cost'];

            // Recalculate: Remove this purchase from average
            $new_stock = $current_stock - $old_qty;
            if ($new_stock > 0) {
                $total_value = ($current_stock * $current_avg) - ($old_qty * $old_price);
                $new_avg = $total_value / $new_stock;
            } else {
                $new_avg = 0;
            }

            // Update warehouse_item
            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ?, average_cost = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("ddii", $new_stock, $new_avg, $old_warehouse_id, $item_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Revert balance
    $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = balance_amount + ? WHERE balance_id = 1");
    $stmt->bind_param("d", $old_transaction['total_amount']);
    $stmt->execute();
    $stmt->close();
    
    // Delete old financial log
    $stmt = $conn->prepare("DELETE FROM financial_transactions WHERE reference_code = ?");
    $stmt->bind_param("s", $old_transaction['transaction_code']);
    $stmt->execute();
    $stmt->close();
    
    // 4. Delete old details
    $stmt = $conn->prepare("DELETE FROM stock_in_detail WHERE stock_in_id = ?");
    $stmt->bind_param("i", $stock_in_id);
    $stmt->execute();
    $stmt->close();
    
    // 5. Calculate new total
    $new_total = 0;
    foreach ($items as $item) {
        $qty = floatval($item['quantity']);
        $price = floatval($item['unit_price']);
        $new_total += ($qty * $price);
    }
    
    // 6. Update header with new warehouse_id
    $stmt = $conn->prepare("UPDATE stock_in SET transaction_date=?, supplier_id=?, warehouse_id=?, total_amount=?, notes=? WHERE stock_in_id=?");
    $stmt->bind_param("sisdsi", $transaction_date, $supplier_id, $new_warehouse_id, $new_total, $notes, $stock_in_id);
    $stmt->execute();
    $stmt->close();

    // 7. Insert new details and update warehouse_items stock
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $quantity = floatval($item['quantity']);
        $unit_price = floatval($item['unit_price']);
        $subtotal = $quantity * $unit_price;

        // Insert detail
        $stmt = $conn->prepare("INSERT INTO stock_in_detail (stock_in_id, item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiddd", $stock_in_id, $item_id, $quantity, $unit_price, $subtotal);
        $stmt->execute();
        $stmt->close();

        // Check if warehouse_item exists for NEW warehouse
        $stmt = $conn->prepare("SELECT current_stock, average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $new_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            // Exists - calculate weighted average
            $current = $result->fetch_assoc();
            $old_stock = $current['current_stock'];
            $old_avg_cost = $current['average_cost'];

            $old_value = $old_stock * $old_avg_cost;
            $new_value = $quantity * $unit_price;
            $new_stock = $old_stock + $quantity;
            $new_avg_cost = ($old_value + $new_value) / $new_stock;

            // Update warehouse_item
            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ?, average_cost = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("ddii", $new_stock, $new_avg_cost, $new_warehouse_id, $item_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Doesn't exist - create new
            $result = $conn->query("SELECT min_stock FROM items WHERE item_id = $item_id");
            $min_stock = 0;
            if ($row = $result->fetch_assoc()) {
                $min_stock = $row['min_stock'];
            }

            $stmt = $conn->prepare("INSERT INTO warehouse_items (warehouse_id, item_id, current_stock, average_cost, min_stock) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddd", $new_warehouse_id, $item_id, $quantity, $unit_price, $min_stock);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // 8. Update balance
    $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = balance_amount - ? WHERE balance_id = 1");
    $stmt->bind_param("d", $new_total);
    $stmt->execute();
    $stmt->close();
    
    // 9. Get new balance and log
    $result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
    $balance_after = $result->fetch_assoc()['balance_amount'];
    
    $description = "Pembelian dari supplier (Updated) - " . $old_transaction['transaction_code'];
    $stmt = $conn->prepare("INSERT INTO financial_transactions (transaction_date, transaction_type, reference_code, description, debit, credit, balance_after, created_by) VALUES (?, 'stock_in', ?, ?, 0, ?, ?, ?)");
    $stmt->bind_param("sssddi", $transaction_date, $old_transaction['transaction_code'], $description, $new_total, $balance_after, $user['user_id']);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    
    $_SESSION['success_message'] = "Transaksi berhasil diupdate. Stok dan keuangan telah di-recalculate.";
    header('Location: stock_in.php');
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: stock_in_edit.php?id=' . $stock_in_id);
    exit;
}
?>
