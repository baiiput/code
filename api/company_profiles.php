<?php
// File: api/company_profiles.php
// API untuk Company Profiles Management

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
            // Get all company profiles
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';

            $sql = "SELECT * FROM company_profiles";
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            $sql .= " ORDER BY is_default DESC, nama_perusahaan ASC";

            $stmt = $pdo->query($sql);
            $profiles = $stmt->fetchAll();

            sendResponse(array_map(function($p) {
                return [
                    'id' => (int)$p['id'],
                    'namaPerusahaan' => $p['nama_perusahaan'],
                    'alamat' => $p['alamat'],
                    'telepon' => $p['telepon'],
                    'email' => $p['email'],
                    'website' => $p['website'],
                    'logoUrl' => $p['logo_url'],
                    'isDefault' => (bool)$p['is_default'],
                    'isActive' => (bool)$p['is_active'],
                    'createdAt' => $p['created_at'],
                    'updatedAt' => $p['updated_at']
                ];
            }, $profiles));
            break;

        case 'get':
            // Get single profile
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid profile ID');

            $stmt = $pdo->prepare("SELECT * FROM company_profiles WHERE id = ?");
            $stmt->execute([$id]);
            $profile = $stmt->fetch();

            if (!$profile) sendError('Company profile not found', 404);

            sendResponse([
                'id' => (int)$profile['id'],
                'namaPerusahaan' => $profile['nama_perusahaan'],
                'alamat' => $profile['alamat'],
                'telepon' => $profile['telepon'],
                'email' => $profile['email'],
                'website' => $profile['website'],
                'logoUrl' => $profile['logo_url'],
                'isDefault' => (bool)$profile['is_default'],
                'isActive' => (bool)$profile['is_active'],
                'createdAt' => $profile['created_at'],
                'updatedAt' => $profile['updated_at']
            ]);
            break;

        case 'get_default':
            // Get default company profile
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $stmt = $pdo->query("SELECT * FROM company_profiles WHERE is_default = 1 AND is_active = 1 LIMIT 1");
            $profile = $stmt->fetch();

            if (!$profile) {
                // Fallback ke yang pertama jika tidak ada default
                $stmt = $pdo->query("SELECT * FROM company_profiles WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
                $profile = $stmt->fetch();
            }

            if (!$profile) sendError('No company profile found', 404);

            sendResponse([
                'id' => (int)$profile['id'],
                'namaPerusahaan' => $profile['nama_perusahaan'],
                'alamat' => $profile['alamat'],
                'telepon' => $profile['telepon'],
                'email' => $profile['email'],
                'website' => $profile['website'],
                'logoUrl' => $profile['logo_url'],
                'isDefault' => (bool)$profile['is_default'],
                'isActive' => (bool)$profile['is_active']
            ]);
            break;

        case 'create':
            // Create new profile
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON input');

            if (!isset($input['namaPerusahaan']) || empty(trim($input['namaPerusahaan']))) {
                sendError('Company name is required');
            }

            $namaPerusahaan = trim($input['namaPerusahaan']);
            $alamat = trim($input['alamat'] ?? '');
            $telepon = trim($input['telepon'] ?? '');
            $email = trim($input['email'] ?? '');
            $website = trim($input['website'] ?? '');
            $logoUrl = trim($input['logoUrl'] ?? '');
            $isDefault = isset($input['isDefault']) && $input['isDefault'] ? 1 : 0;
            $isActive = isset($input['isActive']) && $input['isActive'] ? 1 : 0;

            // Check duplicate name
            $stmt = $pdo->prepare("SELECT id FROM company_profiles WHERE nama_perusahaan = ?");
            $stmt->execute([$namaPerusahaan]);
            if ($stmt->fetch()) {
                sendError('Company profile dengan nama yang sama sudah ada');
            }

            // If setting as default, unset other defaults
            if ($isDefault) {
                $pdo->exec("UPDATE company_profiles SET is_default = 0");
            }

            $stmt = $pdo->prepare("
                INSERT INTO company_profiles
                (nama_perusahaan, alamat, telepon, email, website, logo_url, is_default, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$namaPerusahaan, $alamat, $telepon, $email, $website, $logoUrl, $isDefault, $isActive]);

            $profileId = $pdo->lastInsertId();

            sendResponse([
                'id' => (int)$profileId,
                'namaPerusahaan' => $namaPerusahaan,
                'message' => 'Company profile berhasil dibuat'
            ]);
            break;

        case 'update':
            // Update profile
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid profile ID');

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON input');

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM company_profiles WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) sendError('Company profile not found', 404);

            $namaPerusahaan = trim($input['namaPerusahaan'] ?? '');
            $alamat = trim($input['alamat'] ?? '');
            $telepon = trim($input['telepon'] ?? '');
            $email = trim($input['email'] ?? '');
            $website = trim($input['website'] ?? '');
            $logoUrl = trim($input['logoUrl'] ?? '');
            $isDefault = isset($input['isDefault']) && $input['isDefault'] ? 1 : 0;
            $isActive = isset($input['isActive']) && $input['isActive'] ? 1 : 0;

            if (empty($namaPerusahaan)) sendError('Company name is required');

            // Check duplicate name (exclude current)
            $stmt = $pdo->prepare("SELECT id FROM company_profiles WHERE nama_perusahaan = ? AND id != ?");
            $stmt->execute([$namaPerusahaan, $id]);
            if ($stmt->fetch()) {
                sendError('Company profile dengan nama yang sama sudah ada');
            }

            // If setting as default, unset other defaults
            if ($isDefault) {
                $pdo->exec("UPDATE company_profiles SET is_default = 0 WHERE id != $id");
            }

            $stmt = $pdo->prepare("
                UPDATE company_profiles
                SET nama_perusahaan = ?, alamat = ?, telepon = ?, email = ?,
                    website = ?, logo_url = ?, is_default = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$namaPerusahaan, $alamat, $telepon, $email, $website, $logoUrl, $isDefault, $isActive, $id]);

            sendResponse([
                'id' => (int)$id,
                'message' => 'Company profile berhasil diupdate'
            ]);
            break;

        case 'set_default':
            // Set as default profile
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid profile ID');

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM company_profiles WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) sendError('Company profile not found', 404);

            // Unset all defaults
            $pdo->exec("UPDATE company_profiles SET is_default = 0");

            // Set this as default
            $stmt = $pdo->prepare("UPDATE company_profiles SET is_default = 1 WHERE id = ?");
            $stmt->execute([$id]);

            sendResponse([
                'id' => (int)$id,
                'message' => 'Default company profile berhasil diset'
            ]);
            break;

        case 'delete':
            // Delete profile
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid profile ID');

            // Check if used in penawaran_history
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM penawaran_history WHERE company_profile_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch()['count'];

            if ($count > 0) {
                sendError("Company profile tidak bisa dihapus karena sudah digunakan di $count penawaran");
            }

            // Check if default
            $stmt = $pdo->prepare("SELECT is_default FROM company_profiles WHERE id = ?");
            $stmt->execute([$id]);
            $profile = $stmt->fetch();

            if (!$profile) sendError('Company profile not found', 404);

            if ($profile['is_default']) {
                sendError('Company profile default tidak bisa dihapus. Silakan set default ke profile lain terlebih dahulu.');
            }

            $stmt = $pdo->prepare("DELETE FROM company_profiles WHERE id = ?");
            $stmt->execute([$id]);

            sendResponse([
                'id' => (int)$id,
                'message' => 'Company profile berhasil dihapus'
            ]);
            break;

        default:
            sendError('Invalid action. Available: list, get, get_default, create, update, set_default, delete');
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
?>
