<?php
// File: api/customers.php
// API untuk Customer Management

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database config
$DB_HOST = 'localhost';
$DB_NAME = 'db_offer';
$DB_USER = 'db_offer';
$DB_PASS = 'db_offer';

function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function sendError($message, $status = 400) {
    http_response_code($status);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    sendError('Database connection failed: ' . $e->getMessage(), 500);
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'list':
            // Get all customers
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $search = $_GET['search'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $sql = "SELECT * FROM customers WHERE 1=1";
            $params = [];

            if ($search) {
                $sql .= " AND (nama LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
                $searchParam = "%$search%";
                $params = [$searchParam, $searchParam, $searchParam];
            }

            $sql .= " ORDER BY nama ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll();

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM customers WHERE 1=1";
            $countParams = [];
            if ($search) {
                $countSql .= " AND (nama LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
                $countParams = [$searchParam, $searchParam, $searchParam];
            }
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($countParams);
            $total = $stmt->fetch()['total'];

            sendResponse([
                'customers' => array_map(function($c) {
                    return [
                        'id' => (int)$c['id'],
                        'nama' => $c['nama'],
                        'alamat' => $c['alamat'],
                        'telepon' => $c['telepon'],
                        'email' => $c['email'],
                        'contactPerson' => $c['contact_person'],
                        'companyType' => $c['company_type'],
                        'notes' => $c['notes'],
                        'createdAt' => $c['created_at'],
                        'updatedAt' => $c['updated_at']
                    ];
                }, $customers),
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;

        case 'get':
            // Get single customer
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid customer ID');

            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();

            if (!$customer) sendError('Customer not found', 404);

            sendResponse([
                'id' => (int)$customer['id'],
                'nama' => $customer['nama'],
                'alamat' => $customer['alamat'],
                'telepon' => $customer['telepon'],
                'email' => $customer['email'],
                'contactPerson' => $customer['contact_person'],
                'companyType' => $customer['company_type'],
                'notes' => $customer['notes'],
                'createdAt' => $customer['created_at'],
                'updatedAt' => $customer['updated_at']
            ]);
            break;

        case 'search':
            // Quick search for autocomplete
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $query = $_GET['q'] ?? '';
            if (strlen($query) < 2) sendError('Search query too short (min 2 characters)');

            $stmt = $pdo->prepare("
                SELECT id, nama, alamat, telepon, email, contact_person, company_type
                FROM customers
                WHERE nama LIKE ? OR contact_person LIKE ?
                ORDER BY nama ASC
                LIMIT 10
            ");
            $searchParam = "%$query%";
            $stmt->execute([$searchParam, $searchParam]);
            $customers = $stmt->fetchAll();

            sendResponse(array_map(function($c) {
                return [
                    'id' => (int)$c['id'],
                    'nama' => $c['nama'],
                    'alamat' => $c['alamat'],
                    'telepon' => $c['telepon'],
                    'email' => $c['email'],
                    'contactPerson' => $c['contact_person'],
                    'companyType' => $c['company_type']
                ];
            }, $customers));
            break;

        case 'create':
            // Create new customer
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON input');

            if (!isset($input['nama']) || empty(trim($input['nama']))) {
                sendError('Customer name is required');
            }

            $nama = trim($input['nama']);
            $alamat = trim($input['alamat'] ?? '');
            $telepon = trim($input['telepon'] ?? '');
            $email = trim($input['email'] ?? '');
            $contactPerson = trim($input['contactPerson'] ?? '');
            $companyType = $input['companyType'] ?? 'company';
            $notes = trim($input['notes'] ?? '');

            // Check duplicate name
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE nama = ?");
            $stmt->execute([$nama]);
            if ($stmt->fetch()) {
                sendError('Customer dengan nama yang sama sudah ada');
            }

            $stmt = $pdo->prepare("
                INSERT INTO customers (nama, alamat, telepon, email, contact_person, company_type, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nama, $alamat, $telepon, $email, $contactPerson, $companyType, $notes]);

            $customerId = $pdo->lastInsertId();

            sendResponse([
                'id' => (int)$customerId,
                'nama' => $nama,
                'message' => 'Customer berhasil dibuat'
            ]);
            break;

        case 'update':
            // Update customer
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid customer ID');

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON input');

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) sendError('Customer not found', 404);

            $nama = trim($input['nama'] ?? '');
            $alamat = trim($input['alamat'] ?? '');
            $telepon = trim($input['telepon'] ?? '');
            $email = trim($input['email'] ?? '');
            $contactPerson = trim($input['contactPerson'] ?? '');
            $companyType = $input['companyType'] ?? 'company';
            $notes = trim($input['notes'] ?? '');

            if (empty($nama)) sendError('Customer name is required');

            // Check duplicate name (exclude current)
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE nama = ? AND id != ?");
            $stmt->execute([$nama, $id]);
            if ($stmt->fetch()) {
                sendError('Customer dengan nama yang sama sudah ada');
            }

            $stmt = $pdo->prepare("
                UPDATE customers
                SET nama = ?, alamat = ?, telepon = ?, email = ?,
                    contact_person = ?, company_type = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$nama, $alamat, $telepon, $email, $contactPerson, $companyType, $notes, $id]);

            sendResponse([
                'id' => (int)$id,
                'message' => 'Customer berhasil diupdate'
            ]);
            break;

        case 'delete':
            // Delete customer
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid customer ID');

            // Check if customer is used in penawaran_history
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM penawaran_history WHERE customer_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['count'];

            if ($count > 0) {
                sendError("Customer tidak bisa dihapus karena sudah digunakan di $count penawaran");
            }

            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) sendError('Customer not found', 404);

            sendResponse([
                'id' => (int)$id,
                'message' => 'Customer berhasil dihapus'
            ]);
            break;

        default:
            sendError('Invalid action. Available: list, get, search, create, update, delete');
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
?>
