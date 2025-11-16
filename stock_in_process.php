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
    $notes = clean($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];
    
    // Validate
    if (empty($items) || $supplier_id <= 0) {
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
    
    // Insert stock_in header
    $stmt = $conn->prepare("INSERT INTO stock_in (transaction_code, transaction_date, supplier_id, total_amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidsi", $transaction_code, $transaction_date, $supplier_id, $total_amount, $notes, $user['user_id']);
    $stmt->execute();
    $stock_in_id = $conn->insert_id;
    $stmt->close();
    
    // Insert details and update stock + average cost
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
        
        // Get current stock and average cost
        $result = $conn->query("SELECT current_stock, average_cost FROM items WHERE item_id = $item_id");
        $current = $result->fetch_assoc();
        $old_stock = $current['current_stock'];
        $old_avg_cost = $current['average_cost'];
        
        // Calculate new average cost (Weighted Average)
        // Formula: ((old_stock * old_avg_cost) + (new_qty * new_price)) / (old_stock + new_qty)
        $old_value = $old_stock * $old_avg_cost;
        $new_value = $quantity * $unit_price;
        $new_stock = $old_stock + $quantity;
        $new_avg_cost = ($old_value + $new_value) / $new_stock;
        
        // Update item stock and average cost
        $stmt = $conn->prepare("UPDATE items SET current_stock = ?, average_cost = ? WHERE item_id = ?");
        $stmt->bind_param("ddi", $new_stock, $new_avg_cost, $item_id);
        $stmt->execute();
        $stmt->close();
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
