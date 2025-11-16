<?php
require_once 'config.php';
requireRole(['admin']);

$conn = getDBConnection();
$user = getCurrentUser();

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "ID adjustment tidak valid";
    header('Location: stock_adjustment.php');
    exit;
}

$adjustment_id = intval($_GET['id']);

$conn->begin_transaction();
try {
    // Get adjustment data
    $stmt = $conn->prepare("SELECT * FROM stock_adjustment WHERE adjustment_id = ?");
    $stmt->bind_param("i", $adjustment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $adjustment = $result->fetch_assoc();
    $stmt->close();

    if (!$adjustment) {
        throw new Exception("Adjustment tidak ditemukan");
    }

    $transaction_code = $adjustment['transaction_code'];
    $item_id = $adjustment['item_id'];
    $old_stock = $adjustment['old_stock'];
    $new_stock = $adjustment['new_stock'];

    // Reverse adjustment - set stock back to old_stock
    $stmt = $conn->prepare("UPDATE items SET current_stock = ? WHERE item_id = ?");
    $stmt->bind_param("di", $old_stock, $item_id);
    $stmt->execute();
    $stmt->close();

    // Delete from stock_adjustment
    $stmt = $conn->prepare("DELETE FROM stock_adjustment WHERE adjustment_id = ?");
    $stmt->bind_param("i", $adjustment_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION['success_message'] = "Koreksi stok $transaction_code berhasil dihapus dan stok telah dikembalikan";
    header('Location: stock_adjustment.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error menghapus adjustment: " . $e->getMessage();
    header('Location: stock_adjustment.php');
    exit;
}
?>
