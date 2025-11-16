<?php
require_once 'config.php';
requireRole('admin');

$conn = getDBConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: stock_adjustment.php");
    exit();
}

// Get POST data
$adjustment_id = intval($_POST['adjustment_id']);
$adjustment_date = $_POST['adjustment_date'];
$item_id = intval($_POST['item_id']);
$new_stock = floatval($_POST['new_stock']);
$reason = $_POST['reason'] ?? '';

// Validate
if (!$adjustment_id || !$item_id) {
    $_SESSION['error_message'] = "Data tidak lengkap!";
    header("Location: stock_adjustment_edit.php?id=" . $adjustment_id);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get old adjustment data
    $query = "SELECT * FROM stock_adjustment WHERE adjustment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $adjustment_id);
    $stmt->execute();
    $old_adjustment = $stmt->get_result()->fetch_assoc();
    
    if (!$old_adjustment) {
        throw new Exception("Adjustment tidak ditemukan!");
    }
    
    // Revert old stock adjustment (kembalikan ke stok sebelum adjustment)
    $update_stock = "UPDATE items SET current_stock = ? WHERE item_id = ?";
    $stmt = $conn->prepare($update_stock);
    $stmt->bind_param("di", $old_adjustment['old_stock'], $old_adjustment['item_id']);
    $stmt->execute();
    
    // Get current stock after revert (this is the real old stock)
    $get_stock = "SELECT current_stock FROM items WHERE item_id = ?";
    $stmt = $conn->prepare($get_stock);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $old_stock = $result['current_stock'];
    
    // Calculate difference
    $difference = $new_stock - $old_stock;
    
    // Update adjustment record
    $update_main = "UPDATE stock_adjustment SET 
                    item_id = ?,
                    adjustment_date = ?, 
                    old_stock = ?,
                    new_stock = ?,
                    difference = ?,
                    reason = ?
                    WHERE adjustment_id = ?";
    $stmt = $conn->prepare($update_main);
    $stmt->bind_param("isdddsi", $item_id, $adjustment_date, $old_stock, $new_stock, $difference, $reason, $adjustment_id);
    $stmt->execute();
    
    // Apply new stock value
    $update_stock = "UPDATE items SET current_stock = ? WHERE item_id = ?";
    $stmt = $conn->prepare($update_stock);
    $stmt->bind_param("di", $new_stock, $item_id);
    $stmt->execute();
    
    // Note: Stock adjustment tidak mempengaruhi balance atau financial logs
    // Karena ini adalah koreksi stok fisik, bukan transaksi keuangan
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Stock Adjustment berhasil diupdate!";
    header("Location: stock_adjustment.php");
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: stock_adjustment_edit.php?id=" . $adjustment_id);
}

$conn->close();
?>
