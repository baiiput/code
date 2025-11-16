<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stock_out.php');
    exit;
}

$conn->begin_transaction();

try {
    $transaction_date = $_POST['transaction_date'];
    $branch_id = intval($_POST['branch_id']);
    $notes = clean($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];
    
    if (empty($items) || $branch_id <= 0) {
        throw new Exception('Data tidak lengkap');
    }
    
    // Validate stock availability
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $qty = floatval($item['quantity']);
        
        $result = $conn->query("SELECT current_stock, item_name FROM items WHERE item_id = $item_id");
        $stock = $result->fetch_assoc();
        
        if ($qty > $stock['current_stock']) {
            throw new Exception("Stok {$stock['item_name']} tidak mencukupi!");
        }
    }
    
    // Calculate total
    $total_amount = 0;
    foreach ($items as $item) {
        $qty = floatval($item['quantity']);
        $price = floatval($item['unit_price']);
        $total_amount += ($qty * $price);
    }
    
    $transaction_code = generateTransactionCode('SO');
    
    // Insert stock_out header
    $stmt = $conn->prepare("INSERT INTO stock_out (transaction_code, transaction_date, branch_id, total_amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidsi", $transaction_code, $transaction_date, $branch_id, $total_amount, $notes, $user['user_id']);
    $stmt->execute();
    $stock_out_id = $conn->insert_id;
    $stmt->close();
    
    // Insert details and update stock
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $quantity = floatval($item['quantity']);
        $unit_price = floatval($item['unit_price']);
        $subtotal = $quantity * $unit_price;
        
        // Insert detail
        $stmt = $conn->prepare("INSERT INTO stock_out_detail (stock_out_id, item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiddd", $stock_out_id, $item_id, $quantity, $unit_price, $subtotal);
        $stmt->execute();
        $stmt->close();
        
        // Update item stock (decrease)
        $stmt = $conn->prepare("UPDATE items SET current_stock = current_stock - ? WHERE item_id = ?");
        $stmt->bind_param("di", $quantity, $item_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update warehouse balance (increase because branch pays us)
    $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = balance_amount + ? WHERE balance_id = 1");
    $stmt->bind_param("d", $total_amount);
    $stmt->execute();
    $stmt->close();
    
    // Get new balance
    $result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
    $balance_after = $result->fetch_assoc()['balance_amount'];
    
    // Log financial transaction
    $description = "Distribusi ke cabang - " . $transaction_code;
    $stmt = $conn->prepare("INSERT INTO financial_transactions (transaction_date, transaction_type, reference_code, description, debit, credit, balance_after, created_by) VALUES (?, 'stock_out', ?, ?, ?, 0, ?, ?)");
    $stmt->bind_param("sssddi", $transaction_date, $transaction_code, $description, $total_amount, $balance_after, $user['user_id']);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    
    $_SESSION['success_message'] = "Distribusi stok berhasil disimpan. Kode: $transaction_code";
    header('Location: stock_out.php');
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: stock_out.php');
    exit;
}
?>
