<?php
require_once 'config.php';
requireRole(['admin', 'staff_warehouse']);

$conn = getDBConnection();

header('Content-Type: application/json');

$warehouse_id = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;

if (!$warehouse_id) {
    echo json_encode([]);
    exit;
}

// Get items from warehouse_items for the selected warehouse
$query = "SELECT i.item_id, i.item_code, i.item_name, i.unit,
                 wi.current_stock, wi.average_cost
          FROM warehouse_items wi
          JOIN items i ON wi.item_id = i.item_id
          WHERE wi.warehouse_id = ?
          ORDER BY i.item_code";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);
?>
