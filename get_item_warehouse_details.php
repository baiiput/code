<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if (!$item_id) {
    echo json_encode([]);
    exit;
}

$query = "
    SELECT w.warehouse_id, w.warehouse_code, w.warehouse_name,
           wi.current_stock, wi.average_cost, wi.min_stock
    FROM warehouse_items wi
    JOIN warehouses w ON wi.warehouse_id = w.warehouse_id
    WHERE wi.item_id = ? AND w.is_active = 1
    ORDER BY w.warehouse_name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

$warehouses = [];
while ($row = $result->fetch_assoc()) {
    $warehouses[] = $row;
}

header('Content-Type: application/json');
echo json_encode($warehouses);
?>
