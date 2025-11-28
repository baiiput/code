<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$conn = getDBConnection();
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// Get item details
$stmt = $conn->prepare("SELECT item_name, unit FROM items WHERE item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

// Get stock per warehouse
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

$stocks = [];
$total_stock = 0;
$total_value = 0;

while ($row = $result->fetch_assoc()) {
    $row['unit'] = $item['unit'];
    $stocks[] = $row;
    $total_stock += $row['current_stock'];
    $total_value += $row['current_stock'] * $row['average_cost'];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'stocks' => $stocks,
    'total_stock' => $total_stock,
    'total_value' => $total_value,
    'unit' => $item['unit']
]);
