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
    $warehouse_id = intval($_POST['warehouse_id']);
    $item_id = intval($_POST['item_id']);
    $new_stock = floatval($_POST['new_stock']);
    $reason = clean($_POST['reason']);

    if ($item_id <= 0 || $warehouse_id <= 0 || empty($reason)) {
        throw new Exception('Data tidak lengkap');
    }

    // Get current stock from warehouse_items
    $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
    $stmt->bind_param("ii", $warehouse_id, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $wh_item = $result->fetch_assoc();
    $stmt->close();

    if (!$wh_item) {
        throw new Exception('Item tidak ditemukan di warehouse ini');
    }

    $old_stock = $wh_item['current_stock'];
    $difference = $new_stock - $old_stock;

    $transaction_code = generateTransactionCode('SA');

    // Insert adjustment with warehouse_id
    $stmt = $conn->prepare("INSERT INTO stock_adjustment (transaction_code, adjustment_date, warehouse_id, item_id, old_stock, new_stock, difference, reason, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiidddsi", $transaction_code, $adjustment_date, $warehouse_id, $item_id, $old_stock, $new_stock, $difference, $reason, $user['user_id']);
    $stmt->execute();
    $stmt->close();

    // Update warehouse_items stock
    $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
    $stmt->bind_param("dii", $new_stock, $warehouse_id, $item_id);
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
