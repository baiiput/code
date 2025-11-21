<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stock_in.php');
    exit;
}

$conn->begin_transaction();

try {
    // Get form data
    $transaction_date = $_POST['transaction_date'];
    $supplier_id = intval($_POST['supplier_id']);
    $warehouse_id = intval($_POST['warehouse_id']);
    $notes = clean($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];

    // Validate
    if (empty($items) || $supplier_id <= 0 || $warehouse_id <= 0) {
        throw new Exception('Data tidak lengkap');
    }

    // Calculate total
    $total_amount = 0;
    foreach ($items as $item) {
        $qty = floatval($item['quantity']);
        $price = floatval($item['unit_price']);
        $total_amount += ($qty * $price);
    }

    // Generate transaction code
    $transaction_code = generateTransactionCode('SI');

    // Insert stock_in header with warehouse_id
    $stmt = $conn->prepare("INSERT INTO stock_in (transaction_code, transaction_date, supplier_id, warehouse_id, total_amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiidsi", $transaction_code, $transaction_date, $supplier_id, $warehouse_id, $total_amount, $notes, $user['user_id']);
    $stmt->execute();
    $stock_in_id = $conn->insert_id;
    $stmt->close();

    // Insert details and update warehouse_items stock + average cost
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

        // Check if warehouse_item exists
        $stmt = $conn->prepare("SELECT current_stock, average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            // Warehouse item exists - update with weighted average
            $current = $result->fetch_assoc();
            $old_stock = $current['current_stock'];
            $old_avg_cost = $current['average_cost'];

            // Calculate new average cost (Weighted Average)
            // Formula: ((old_stock * old_avg_cost) + (new_qty * new_price)) / (old_stock + new_qty)
            $old_value = $old_stock * $old_avg_cost;
            $new_value = $quantity * $unit_price;
            $new_stock = $old_stock + $quantity;
            $new_avg_cost = ($old_value + $new_value) / $new_stock;

            // Update warehouse_item
            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ?, average_cost = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("ddii", $new_stock, $new_avg_cost, $warehouse_id, $item_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Warehouse item doesn't exist - create new with min_stock from items table
            $result = $conn->query("SELECT min_stock FROM items WHERE item_id = $item_id");
            $min_stock = 0;
            if ($row = $result->fetch_assoc()) {
                $min_stock = $row['min_stock'];
            }

            $stmt = $conn->prepare("INSERT INTO warehouse_items (warehouse_id, item_id, current_stock, average_cost, min_stock) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddd", $warehouse_id, $item_id, $quantity, $unit_price, $min_stock);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Update warehouse balance (decrease because we pay supplier)
    $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = balance_amount - ? WHERE balance_id = 1");
    $stmt->bind_param("d", $total_amount);
    $stmt->execute();
    $stmt->close();
    
    // Get new balance
    $result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
    $balance_after = $result->fetch_assoc()['balance_amount'];
    
    // Log financial transaction
    $description = "Pembelian dari supplier - " . $transaction_code;
    $stmt = $conn->prepare("INSERT INTO financial_transactions (transaction_date, transaction_type, reference_code, description, debit, credit, balance_after, created_by) VALUES (?, 'stock_in', ?, ?, 0, ?, ?, ?)");
    $stmt->bind_param("sssddi", $transaction_date, $transaction_code, $description, $total_amount, $balance_after, $user['user_id']);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    
    $_SESSION['success_message'] = "Transaksi stok masuk berhasil disimpan. Kode: $transaction_code";
    header('Location: stock_in.php');
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: stock_in.php');
    exit;
}
?>
