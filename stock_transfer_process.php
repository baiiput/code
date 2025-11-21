<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stock_transfer.php');
    exit;
}

$conn->begin_transaction();

try {
    $transfer_date = $_POST['transfer_date'];
    $from_warehouse_id = intval($_POST['from_warehouse_id']);
    $to_warehouse_id = intval($_POST['to_warehouse_id']);
    $notes = clean($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];

    // Validate
    if (empty($items) || $from_warehouse_id <= 0 || $to_warehouse_id <= 0) {
        throw new Exception('Data tidak lengkap');
    }

    if ($from_warehouse_id === $to_warehouse_id) {
        throw new Exception('Warehouse asal dan tujuan tidak boleh sama');
    }

    // Validate stock availability from source warehouse
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $qty = floatval($item['quantity']);

        $stmt = $conn->prepare("SELECT wi.current_stock, i.item_name FROM warehouse_items wi
                               JOIN items i ON wi.item_id = i.item_id
                               WHERE wi.warehouse_id = ? AND wi.item_id = ?");
        $stmt->bind_param("ii", $from_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock = $result->fetch_assoc();
        $stmt->close();

        if (!$stock || $qty > $stock['current_stock']) {
            throw new Exception("Stok {$stock['item_name']} di warehouse asal tidak mencukupi!");
        }
    }

    $transaction_code = generateTransactionCode('ST');

    // Insert stock_transfers header
    $stmt = $conn->prepare("INSERT INTO stock_transfers (transaction_code, transfer_date, from_warehouse_id, to_warehouse_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisi", $transaction_code, $transfer_date, $from_warehouse_id, $to_warehouse_id, $notes, $user['user_id']);
    $stmt->execute();
    $transfer_id = $conn->insert_id;
    $stmt->close();

    // Insert details and update warehouse_items stock
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $quantity = floatval($item['quantity']);

        // Insert detail
        $stmt = $conn->prepare("INSERT INTO stock_transfer_detail (transfer_id, item_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $transfer_id, $item_id, $quantity);
        $stmt->execute();
        $stmt->close();

        // Get average cost from source warehouse for the destination
        $stmt = $conn->prepare("SELECT average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $from_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $source_item = $result->fetch_assoc();
        $stmt->close();

        $transfer_cost = $source_item['average_cost'];

        // 1. Reduce stock from source warehouse
        $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = current_stock - ? WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("dii", $quantity, $from_warehouse_id, $item_id);
        $stmt->execute();
        $stmt->close();

        // 2. Add stock to destination warehouse
        // Check if item exists in destination warehouse
        $stmt = $conn->prepare("SELECT current_stock, average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $to_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            // Item exists - calculate weighted average
            $dest_item = $result->fetch_assoc();
            $old_stock = $dest_item['current_stock'];
            $old_avg_cost = $dest_item['average_cost'];

            $old_value = $old_stock * $old_avg_cost;
            $new_value = $quantity * $transfer_cost;
            $new_stock = $old_stock + $quantity;
            $new_avg_cost = ($old_value + $new_value) / $new_stock;

            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ?, average_cost = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("ddii", $new_stock, $new_avg_cost, $to_warehouse_id, $item_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Item doesn't exist - create new entry
            $result = $conn->query("SELECT min_stock FROM items WHERE item_id = $item_id");
            $min_stock = 0;
            if ($row = $result->fetch_assoc()) {
                $min_stock = $row['min_stock'];
            }

            $stmt = $conn->prepare("INSERT INTO warehouse_items (warehouse_id, item_id, current_stock, average_cost, min_stock) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddd", $to_warehouse_id, $item_id, $quantity, $transfer_cost, $min_stock);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();

    $_SESSION['success_message'] = "Transfer stok berhasil diproses. Kode: $transaction_code";
    header('Location: stock_transfer.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: stock_transfer.php');
    exit;
}
?>
