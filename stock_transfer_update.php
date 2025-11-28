<?php
require_once 'config.php';
requireRole('admin');

$conn = getDBConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: stock_transfer.php');
    exit;
}

$transfer_id = intval($_POST['transfer_id']);
$transfer_date = $_POST['transfer_date'];
$from_warehouse_id = intval($_POST['from_warehouse_id']);
$to_warehouse_id = intval($_POST['to_warehouse_id']);
$notes = clean($_POST['notes'] ?? '');
$items = $_POST['items'] ?? [];

// Validation
if ($from_warehouse_id === $to_warehouse_id) {
    $_SESSION['error_message'] = "Warehouse asal dan tujuan tidak boleh sama!";
    header('Location: stock_transfer_edit.php?id=' . $transfer_id);
    exit;
}

if (empty($items)) {
    $_SESSION['error_message'] = "Minimal harus ada 1 item!";
    header('Location: stock_transfer_edit.php?id=' . $transfer_id);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Get old transfer data
    $stmt = $conn->prepare("SELECT * FROM stock_transfers WHERE transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $old_transfer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $old_from_warehouse = $old_transfer['from_warehouse_id'];
    $old_to_warehouse = $old_transfer['to_warehouse_id'];

    // 2. Get old details
    $stmt = $conn->prepare("SELECT std.*, i.item_code, i.item_name FROM stock_transfer_detail std JOIN items i ON std.item_id = i.item_id WHERE std.transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $old_details = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $old_details[] = $row;
    }
    $stmt->close();

    // 2.5. SMART VALIDATION - Check if we can revert based on NET CHANGE
    // If user reduces quantity (60→50), we only need to revert the difference (10)
    // If user increases quantity (60→70), we need full revert (60) + add more (10)

    $insufficient_items = [];

    // Build map of new quantities by item_id
    $new_qty_map = [];
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $new_qty_map[$item_id] = floatval($item['quantity']);
    }

    foreach ($old_details as $detail) {
        $item_id = $detail['item_id'];
        $old_qty = $detail['quantity'];
        $new_qty = $new_qty_map[$item_id] ?? 0;

        // Calculate NET change that needs to be reverted from destination
        // If new_qty < old_qty: we need to revert (old_qty - new_qty)
        // If new_qty >= old_qty: we need to revert full old_qty then add new
        $qty_to_revert = ($new_qty < $old_qty) ? ($old_qty - $new_qty) : $old_qty;

        // Check current stock in old destination warehouse
        $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $old_to_warehouse, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $current_stock = $result->fetch_assoc()['current_stock'];

            // Check if we have enough to revert
            if ($current_stock < $qty_to_revert) {
                $insufficient_items[] = [
                    'item_code' => $detail['item_code'],
                    'item_name' => $detail['item_name'],
                    'old_qty' => $old_qty,
                    'new_qty' => $new_qty,
                    'required' => $qty_to_revert,
                    'available' => $current_stock,
                    'shortage' => $qty_to_revert - $current_stock
                ];
            }
        } else {
            // Item doesn't exist in warehouse anymore
            if ($qty_to_revert > 0) {
                $insufficient_items[] = [
                    'item_code' => $detail['item_code'],
                    'item_name' => $detail['item_name'],
                    'old_qty' => $old_qty,
                    'new_qty' => $new_qty,
                    'required' => $qty_to_revert,
                    'available' => 0,
                    'shortage' => $qty_to_revert
                ];
            }
        }
    }

    // If there are insufficient items, throw detailed error
    if (!empty($insufficient_items)) {
        $error_msg = "TIDAK DAPAT UPDATE TRANSFER!\n\n";
        $error_msg .= "Stok di warehouse tujuan tidak mencukupi untuk di-revert.\n";
        $error_msg .= "Kemungkinan stok sudah didistribusikan/digunakan.\n\n";
        $error_msg .= "Detail item yang tidak mencukupi:\n\n";

        foreach ($insufficient_items as $item) {
            $error_msg .= "• {$item['item_code']} - {$item['item_name']}\n";
            $error_msg .= "  Transfer Lama: " . number_format($item['old_qty'], 2) . "\n";
            $error_msg .= "  Transfer Baru: " . number_format($item['new_qty'], 2) . "\n";
            $error_msg .= "  Perlu Revert: " . number_format($item['required'], 2) . "\n";
            $error_msg .= "  Stok Tersedia: " . number_format($item['available'], 2) . "\n";
            $error_msg .= "  Kekurangan: " . number_format($item['shortage'], 2) . "\n\n";
        }

        $error_msg .= "SOLUSI:\n";
        $error_msg .= "1. Batalkan distribusi/transaksi keluar dari warehouse tujuan terlebih dahulu\n";
        $error_msg .= "2. Atau gunakan Stock Adjustment untuk memperbaiki stok\n";
        $error_msg .= "3. Atau kurangi quantity lebih banyak lagi agar sesuai dengan stok tersedia";

        throw new Exception($error_msg);
    }

    // 3. INCREMENTAL UPDATE - Apply delta instead of full revert
    // This is safer when stock has been distributed
    foreach ($old_details as $detail) {
        $item_id = $detail['item_id'];
        $old_qty = $detail['quantity'];
        $new_qty = $new_qty_map[$item_id] ?? 0;

        // Calculate delta (difference between new and old)
        $delta = $new_qty - $old_qty;

        if ($delta != 0) {
            // Apply delta to from_warehouse (opposite of transfer direction)
            // If delta is negative (reduced): add back to source
            // If delta is positive (increased): remove more from source
            $from_delta = -$delta;

            $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("ii", $old_from_warehouse, $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
                $wh_item = $result->fetch_assoc();
                $new_stock = $wh_item['current_stock'] + $from_delta;

                $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
                $stmt->bind_param("dii", $new_stock, $old_from_warehouse, $item_id);
                $stmt->execute();
                $stmt->close();
            }

            // Apply delta to to_warehouse (same as transfer direction)
            // If delta is negative (reduced): remove from destination
            // If delta is positive (increased): add more to destination
            $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("ii", $old_to_warehouse, $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
                $wh_item = $result->fetch_assoc();
                $new_stock = $wh_item['current_stock'] + $delta;

                $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
                $stmt->bind_param("dii", $new_stock, $old_to_warehouse, $item_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // 4. Delete old details
    $stmt = $conn->prepare("DELETE FROM stock_transfer_detail WHERE transfer_id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $stmt->close();

    // 5. Update header
    $stmt = $conn->prepare("UPDATE stock_transfers SET transfer_date=?, from_warehouse_id=?, to_warehouse_id=?, notes=? WHERE transfer_id=?");
    $stmt->bind_param("siisi", $transfer_date, $from_warehouse_id, $to_warehouse_id, $notes, $transfer_id);
    $stmt->execute();
    $stmt->close();

    // 6. Insert new details (stock already updated via incremental approach above)
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $quantity = floatval($item['quantity']);

        // Insert detail
        $stmt = $conn->prepare("INSERT INTO stock_transfer_detail (transfer_id, item_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $transfer_id, $item_id, $quantity);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    $_SESSION['success_message'] = "Transfer berhasil diupdate. Stok di kedua warehouse telah di-recalculate.";
    header('Location: stock_transfer.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: stock_transfer_edit.php?id=' . $transfer_id);
    exit;
}
?>