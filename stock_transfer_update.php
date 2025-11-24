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

    // 2.5. VALIDATE stock availability in destination warehouse BEFORE reverting
    $insufficient_items = [];
    foreach ($old_details as $detail) {
        $item_id = $detail['item_id'];
        $old_qty = $detail['quantity'];

        // Check current stock in old destination warehouse
        $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $old_to_warehouse, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $current_stock = $result->fetch_assoc()['current_stock'];

            // If current stock is less than what we need to revert, record it
            if ($current_stock < $old_qty) {
                $insufficient_items[] = [
                    'item_code' => $detail['item_code'],
                    'item_name' => $detail['item_name'],
                    'required' => $old_qty,
                    'available' => $current_stock,
                    'shortage' => $old_qty - $current_stock
                ];
            }
        } else {
            // Item doesn't exist in warehouse anymore
            $insufficient_items[] = [
                'item_code' => $detail['item_code'],
                'item_name' => $detail['item_name'],
                'required' => $old_qty,
                'available' => 0,
                'shortage' => $old_qty
            ];
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
            $error_msg .= "  Dibutuhkan: " . number_format($item['required'], 2) . "\n";
            $error_msg .= "  Tersedia: " . number_format($item['available'], 2) . "\n";
            $error_msg .= "  Kekurangan: " . number_format($item['shortage'], 2) . "\n\n";
        }

        $error_msg .= "SOLUSI:\n";
        $error_msg .= "1. Batalkan distribusi/transaksi keluar dari warehouse tujuan terlebih dahulu\n";
        $error_msg .= "2. Atau gunakan Stock Adjustment untuk memperbaiki stok";

        throw new Exception($error_msg);
    }

    // 3. REVERT old transfer effects (now safe because we validated above)
    foreach ($old_details as $detail) {
        $item_id = $detail['item_id'];
        $old_qty = $detail['quantity'];

        // Add back to from_warehouse
        $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $old_from_warehouse, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $wh_item = $result->fetch_assoc();
            $new_stock = $wh_item['current_stock'] + $old_qty;

            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("dii", $new_stock, $old_from_warehouse, $item_id);
            $stmt->execute();
            $stmt->close();
        }

        // Remove from to_warehouse
        $stmt = $conn->prepare("SELECT current_stock FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $old_to_warehouse, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $wh_item = $result->fetch_assoc();
            $new_stock = $wh_item['current_stock'] - $old_qty;

            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("dii", $new_stock, $old_to_warehouse, $item_id);
            $stmt->execute();
            $stmt->close();
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

    // 6. Insert new details and update warehouse_items
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $quantity = floatval($item['quantity']);

        // Insert detail
        $stmt = $conn->prepare("INSERT INTO stock_transfer_detail (transfer_id, item_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $transfer_id, $item_id, $quantity);
        $stmt->execute();
        $stmt->close();

        // Deduct from from_warehouse
        $stmt = $conn->prepare("SELECT current_stock, average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $from_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $wh_item = $result->fetch_assoc();
            $new_stock = $wh_item['current_stock'] - $quantity;

            if ($new_stock < 0) {
                throw new Exception("Stok tidak cukup untuk item ID: $item_id di warehouse asal");
            }

            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("dii", $new_stock, $from_warehouse_id, $item_id);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception("Item ID: $item_id tidak ditemukan di warehouse asal");
        }

        // Add to to_warehouse
        $stmt = $conn->prepare("SELECT current_stock, average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
        $stmt->bind_param("ii", $to_warehouse_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            // Item exists in destination warehouse
            $wh_item = $result->fetch_assoc();
            $new_stock = $wh_item['current_stock'] + $quantity;

            $stmt = $conn->prepare("UPDATE warehouse_items SET current_stock = ? WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("dii", $new_stock, $to_warehouse_id, $item_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Item doesn't exist in destination warehouse - create new
            // Get average_cost from source warehouse
            $stmt = $conn->prepare("SELECT average_cost FROM warehouse_items WHERE warehouse_id = ? AND item_id = ?");
            $stmt->bind_param("ii", $from_warehouse_id, $item_id);
            $stmt->execute();
            $avg_cost_result = $stmt->get_result();
            $stmt->close();

            $avg_cost = 0;
            if ($avg_cost_result->num_rows > 0) {
                $avg_cost = $avg_cost_result->fetch_assoc()['average_cost'];
            }

            // Get min_stock from items table
            $result = $conn->query("SELECT min_stock FROM items WHERE item_id = $item_id");
            $min_stock = 0;
            if ($row = $result->fetch_assoc()) {
                $min_stock = $row['min_stock'];
            }

            $stmt = $conn->prepare("INSERT INTO warehouse_items (warehouse_id, item_id, current_stock, average_cost, min_stock) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddd", $to_warehouse_id, $item_id, $quantity, $avg_cost, $min_stock);
            $stmt->execute();
            $stmt->close();
        }
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
