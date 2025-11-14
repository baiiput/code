<?php
/**
 * Products API
 */

require_once '../config/database.php';
require_once '../config/config.php';

requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $db = Database::getInstance()->getConnection();

    switch ($action) {
        case 'list':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;

            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';

            $where = ['p.is_active = 1'];
            $params = [];

            if ($search) {
                $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)';
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if ($category) {
                $where[] = 'p.category_id = ?';
                $params[] = $category;
            }

            $whereClause = implode(' AND ', $where);

            // Get total count
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM products p WHERE $whereClause");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            // Get products
            $stmt = $db->prepare("
                SELECT p.*, c.name as category_name
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE $whereClause
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $products = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'data' => $products,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'get':
            $id = $_GET['id'] ?? 0;

            $stmt = $db->prepare("
                SELECT p.*, c.name as category_name
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception('Produk tidak ditemukan');
            }

            jsonResponse([
                'success' => true,
                'data' => $product
            ]);
            break;

        case 'create':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $required = ['category_id', 'sku', 'name', 'purchase_price', 'selling_price'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    throw new Exception("Field $field harus diisi");
                }
            }

            // Check if SKU already exists
            $stmt = $db->prepare("SELECT id FROM products WHERE sku = ?");
            $stmt->execute([$data['sku']]);
            if ($stmt->fetch()) {
                throw new Exception('SKU sudah digunakan');
            }

            $stmt = $db->prepare("
                INSERT INTO products (
                    category_id, sku, name, description, unit,
                    purchase_price, selling_price, stock, min_stock
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['category_id'],
                $data['sku'],
                $data['name'],
                $data['description'] ?? '',
                $data['unit'] ?? 'pcs',
                $data['purchase_price'],
                $data['selling_price'],
                $data['stock'] ?? 0,
                $data['min_stock'] ?? 0
            ]);

            jsonResponse([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan',
                'id' => $db->lastInsertId()
            ], 201);
            break;

        case 'update':
            if ($method !== 'POST' && $method !== 'PUT') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;

            $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('Produk tidak ditemukan');
            }

            // Check SKU uniqueness
            if (isset($data['sku'])) {
                $stmt = $db->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
                $stmt->execute([$data['sku'], $id]);
                if ($stmt->fetch()) {
                    throw new Exception('SKU sudah digunakan');
                }
            }

            $updates = [];
            $params = [];

            $fields = ['category_id', 'sku', 'name', 'description', 'unit', 'purchase_price', 'selling_price', 'stock', 'min_stock'];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updates)) {
                throw new Exception('Tidak ada data yang diupdate');
            }

            $params[] = $id;
            $updateClause = implode(', ', $updates);

            $stmt = $db->prepare("UPDATE products SET $updateClause WHERE id = ?");
            $stmt->execute($params);

            jsonResponse([
                'success' => true,
                'message' => 'Produk berhasil diupdate'
            ]);
            break;

        case 'delete':
            if ($method !== 'POST' && $method !== 'DELETE') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? $_GET['id'] ?? 0;

            // Soft delete
            $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);

            jsonResponse([
                'success' => true,
                'message' => 'Produk berhasil dihapus'
            ]);
            break;

        case 'categories':
            $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
            $categories = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'data' => $categories
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
