<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse']);

$conn = getDBConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stock_adjustment.php');
    exit;
}

$conn->begin_transaction();

try {
    $adjustment_date = $_POST['adjustment_date'];
    $item_id = intval($_POST['item_id']);
    $new_stock = floatval($_POST['new_stock']);
    $reason = clean($_POST['reason']);
    
    if ($item_id <= 0 || empty($reason)) {
        throw new Exception('Data tidak lengkap');
    }
    
    // Get current stock
    $result = $conn->query("SELECT current_stock FROM items WHERE item_id = $item_id");
    $item = $result->fetch_assoc();
    $old_stock = $item['current_stock'];
    $difference = $new_stock - $old_stock;
    
    $transaction_code = generateTransactionCode('SA');
    
    // Insert adjustment
    $stmt = $conn->prepare("INSERT INTO stock_adjustment (transaction_code, adjustment_date, item_id, old_stock, new_stock, difference, reason, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidddsi", $transaction_code, $adjustment_date, $item_id, $old_stock, $new_stock, $difference, $reason, $user['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Update item stock
    $stmt = $conn->prepare("UPDATE items SET current_stock = ? WHERE item_id = ?");
    $stmt->bind_param("di", $new_stock, $item_id);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    
    $_SESSION['success_message'] = "Koreksi stok berhasil disimpan. Kode: $transaction_code";
    header('Location: stock_adjustment.php');
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: stock_adjustment.php');
    exit;
}
?>
