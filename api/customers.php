<?php
/**
 * Customers API
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
            $search = $_GET['search'] ?? '';

            $where = '1=1';
            $params = [];

            if ($search) {
                $where = '(code LIKE ? OR name LIKE ? OR email LIKE ? OR phone LIKE ?)';
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
            }

            $stmt = $db->prepare("
                SELECT * FROM customers
                WHERE $where
                ORDER BY name ASC
            ");
            $stmt->execute($params);
            $customers = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'data' => $customers
            ]);
            break;

        case 'get':
            $id = $_GET['id'] ?? 0;

            $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();

            if (!$customer) {
                throw new Exception('Customer tidak ditemukan');
            }

            jsonResponse([
                'success' => true,
                'data' => $customer
            ]);
            break;

        case 'create':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name'])) {
                throw new Exception('Nama customer harus diisi');
            }

            // Generate code
            $stmt = $db->query("SELECT MAX(id) as max_id FROM customers");
            $maxId = $stmt->fetch()['max_id'] ?? 0;
            $code = 'CUST-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);

            $stmt = $db->prepare("
                INSERT INTO customers (
                    code, name, email, phone, address, city, province,
                    postal_code, tax_id, customer_type, credit_limit, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $code,
                $data['name'],
                $data['email'] ?? '',
                $data['phone'] ?? '',
                $data['address'] ?? '',
                $data['city'] ?? '',
                $data['province'] ?? '',
                $data['postal_code'] ?? '',
                $data['tax_id'] ?? '',
                $data['customer_type'] ?? 'individual',
                $data['credit_limit'] ?? 0,
                $data['notes'] ?? ''
            ]);

            jsonResponse([
                'success' => true,
                'message' => 'Customer berhasil ditambahkan',
                'id' => $db->lastInsertId(),
                'code' => $code
            ], 201);
            break;

        case 'update':
            if ($method !== 'POST' && $method !== 'PUT') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;

            $stmt = $db->prepare("SELECT id FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('Customer tidak ditemukan');
            }

            $updates = [];
            $params = [];

            $fields = ['name', 'email', 'phone', 'address', 'city', 'province', 'postal_code', 'tax_id', 'customer_type', 'credit_limit', 'notes'];
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

            $stmt = $db->prepare("UPDATE customers SET $updateClause WHERE id = ?");
            $stmt->execute($params);

            jsonResponse([
                'success' => true,
                'message' => 'Customer berhasil diupdate'
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
