<?php
// File: api/penawaran.php
// API untuk Penawaran History & Tracking

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

// Helper function to generate nomor penawaran
function generateNomorPenawaran($pdo, $tanggal) {
    $year = date('Y', strtotime($tanggal));
    $month = date('m', strtotime($tanggal));
    $day = date('d', strtotime($tanggal));

    // Count penawaran for today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM penawaran_history WHERE DATE(tanggal) = ?");
    $stmt->execute([$tanggal]);
    $count = $stmt->fetch()['count'] + 1;

    $sequence = str_pad($count, 3, '0', STR_PAD_LEFT);
    return "PNW-$year$month$day-$sequence";
}

// Helper function to calculate totals
function calculateTotals($items, $diskonPersen, $ppnPersen) {
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['total'];
    }

    $diskonNominal = $subtotal * ($diskonPersen / 100);
    $afterDiskon = $subtotal - $diskonNominal;
    $ppnNominal = $afterDiskon * ($ppnPersen / 100);
    $total = $afterDiskon + $ppnNominal;

    return [
        'subtotal' => $subtotal,
        'diskonNominal' => $diskonNominal,
        'ppnNominal' => $ppnNominal,
        'total' => $total
    ];
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'list':
            // Get penawaran list with filters
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $status = $_GET['status'] ?? '';
            $customerId = $_GET['customer_id'] ?? '';
            $search = $_GET['search'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $sql = "SELECT
                ph.*,
                c.nama as customer_nama,
                cp.nama_perusahaan as company_nama,
                u.full_name as created_by_nama
            FROM penawaran_history ph
            LEFT JOIN customers c ON ph.customer_id = c.id
            LEFT JOIN company_profiles cp ON ph.company_profile_id = cp.id
            LEFT JOIN users u ON ph.created_by = u.id
            WHERE 1=1";

            $params = [];

            if ($status) {
                $sql .= " AND ph.status = ?";
                $params[] = $status;
            }

            if ($customerId) {
                $sql .= " AND ph.customer_id = ?";
                $params[] = $customerId;
            }

            if ($search) {
                $sql .= " AND (ph.nomor_penawaran LIKE ? OR ph.nama_pelanggan LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            if ($dateFrom) {
                $sql .= " AND ph.tanggal >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo) {
                $sql .= " AND ph.tanggal <= ?";
                $params[] = $dateTo;
            }

            $sql .= " ORDER BY ph.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $penawaran = $stmt->fetchAll();

            // Get total count
            $countSql = str_replace("SELECT ph.*, c.nama as customer_nama, cp.nama_perusahaan as company_nama, u.full_name as created_by_nama", "SELECT COUNT(*) as total", $sql);
            $countSql = preg_replace('/ORDER BY.*$/', '', $countSql);
            $countSql = preg_replace('/LIMIT.*$/', '', $countSql);
            $countParams = array_slice($params, 0, count($params) - 2); // Remove limit & offset
            $stmt = $pdo->prepare($countSql);
            $stmt->execute($countParams);
            $total = $stmt->fetch()['total'];

            sendResponse([
                'penawaran' => array_map(function($p) {
                    return [
                        'id' => (int)$p['id'],
                        'nomorPenawaran' => $p['nomor_penawaran'],
                        'customerId' => $p['customer_id'] ? (int)$p['customer_id'] : null,
                        'customerNama' => $p['customer_nama'],
                        'namaPelanggan' => $p['nama_pelanggan'],
                        'companyNama' => $p['company_nama'],
                        'tanggal' => $p['tanggal'],
                        'berlakuHingga' => $p['berlaku_hingga'],
                        'total' => (float)$p['total'],
                        'status' => $p['status'],
                        'version' => (int)$p['version'],
                        'createdByNama' => $p['created_by_nama'],
                        'createdAt' => $p['created_at'],
                        'updatedAt' => $p['updated_at']
                    ];
                }, $penawaran),
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;

        case 'get':
            // Get single penawaran with full data
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid penawaran ID');

            $stmt = $pdo->prepare("
                SELECT ph.*,
                    c.nama as customer_nama,
                    cp.nama_perusahaan as company_nama,
                    u.full_name as created_by_nama
                FROM penawaran_history ph
                LEFT JOIN customers c ON ph.customer_id = c.id
                LEFT JOIN company_profiles cp ON ph.company_profile_id = cp.id
                LEFT JOIN users u ON ph.created_by = u.id
                WHERE ph.id = ?
            ");
            $stmt->execute([$id]);
            $penawaran = $stmt->fetch();

            if (!$penawaran) sendError('Penawaran not found', 404);

            $penawaran['data'] = json_decode($penawaran['data'], true);

            sendResponse([
                'id' => (int)$penawaran['id'],
                'nomorPenawaran' => $penawaran['nomor_penawaran'],
                'customerId' => $penawaran['customer_id'] ? (int)$penawaran['customer_id'] : null,
                'companyProfileId' => $penawaran['company_profile_id'] ? (int)$penawaran['company_profile_id'] : null,
                'namaPelanggan' => $penawaran['nama_pelanggan'],
                'alamatPelanggan' => $penawaran['alamat_pelanggan'],
                'teleponPelanggan' => $penawaran['telepon_pelanggan'],
                'emailPelanggan' => $penawaran['email_pelanggan'],
                'namaPerusahaan' => $penawaran['nama_perusahaan'],
                'alamatPerusahaan' => $penawaran['alamat_perusahaan'],
                'teleponPerusahaan' => $penawaran['telepon_perusahaan'],
                'emailPerusahaan' => $penawaran['email_perusahaan'],
                'tanggal' => $penawaran['tanggal'],
                'berlakuHingga' => $penawaran['berlaku_hingga'],
                'data' => $penawaran['data'],
                'subtotal' => (float)$penawaran['subtotal'],
                'diskonPersen' => (float)$penawaran['diskon_persen'],
                'diskonNominal' => (float)$penawaran['diskon_nominal'],
                'ppnPersen' => (float)$penawaran['ppn_persen'],
                'ppnNominal' => (float)$penawaran['ppn_nominal'],
                'total' => (float)$penawaran['total'],
                'status' => $penawaran['status'],
                'version' => (int)$penawaran['version'],
                'parentId' => $penawaran['parent_id'] ? (int)$penawaran['parent_id'] : null,
                'createdBy' => $penawaran['created_by'] ? (int)$penawaran['created_by'] : null,
                'createdAt' => $penawaran['created_at'],
                'updatedAt' => $penawaran['updated_at']
            ]);
            break;

        case 'create':
            // Create new penawaran
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON input');

            // Required fields
            if (!isset($input['tanggal']) || !isset($input['namaPelanggan']) || !isset($input['data']['items'])) {
                sendError('Required fields: tanggal, namaPelanggan, data.items');
            }

            $customerId = $input['customerId'] ?? null;
            $companyProfileId = $input['companyProfileId'] ?? null;
            $namaPelanggan = trim($input['namaPelanggan']);
            $alamatPelanggan = trim($input['alamatPelanggan'] ?? '');
            $teleponPelanggan = trim($input['teleponPelanggan'] ?? '');
            $emailPelanggan = trim($input['emailPelanggan'] ?? '');
            $namaPerusahaan = trim($input['namaPerusahaan'] ?? '');
            $alamatPerusahaan = trim($input['alamatPerusahaan'] ?? '');
            $teleponPerusahaan = trim($input['teleponPerusahaan'] ?? '');
            $emailPerusahaan = trim($input['emailPerusahaan'] ?? '');
            $tanggal = $input['tanggal'];
            $berlakuHingga = $input['berlakuHingga'] ?? null;
            $data = $input['data'];
            $diskonPersen = (float)($input['diskonPersen'] ?? 0);
            $ppnPersen = (float)($input['ppnPersen'] ?? 0);
            $status = $input['status'] ?? 'draft';
            $createdBy = $input['createdBy'] ?? null;

            // Generate nomor penawaran
            $nomorPenawaran = $input['nomorPenawaran'] ?? generateNomorPenawaran($pdo, $tanggal);

            // Check duplicate nomor
            $stmt = $pdo->prepare("SELECT id FROM penawaran_history WHERE nomor_penawaran = ?");
            $stmt->execute([$nomorPenawaran]);
            if ($stmt->fetch()) {
                // Regenerate
                $nomorPenawaran = generateNomorPenawaran($pdo, $tanggal);
            }

            // Calculate totals
            $totals = calculateTotals($data['items'], $diskonPersen, $ppnPersen);

            $stmt = $pdo->prepare("
                INSERT INTO penawaran_history (
                    nomor_penawaran, customer_id, company_profile_id,
                    nama_pelanggan, alamat_pelanggan, telepon_pelanggan, email_pelanggan,
                    nama_perusahaan, alamat_perusahaan, telepon_perusahaan, email_perusahaan,
                    tanggal, berlaku_hingga, data,
                    subtotal, diskon_persen, diskon_nominal, ppn_persen, ppn_nominal, total,
                    status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $nomorPenawaran, $customerId, $companyProfileId,
                $namaPelanggan, $alamatPelanggan, $teleponPelanggan, $emailPelanggan,
                $namaPerusahaan, $alamatPerusahaan, $teleponPerusahaan, $emailPerusahaan,
                $tanggal, $berlakuHingga, json_encode($data, JSON_UNESCAPED_UNICODE),
                $totals['subtotal'], $diskonPersen, $totals['diskonNominal'],
                $ppnPersen, $totals['ppnNominal'], $totals['total'],
                $status, $createdBy
            ]);

            $penawaranId = $pdo->lastInsertId();

            sendResponse([
                'id' => (int)$penawaranId,
                'nomorPenawaran' => $nomorPenawaran,
                'message' => 'Penawaran berhasil dibuat'
            ]);
            break;

        case 'update':
            // Update penawaran
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid penawaran ID');

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON input');

            // Check if exists
            $stmt = $pdo->prepare("SELECT * FROM penawaran_history WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) sendError('Penawaran not found', 404);

            // Save current version to revisions if data changed
            $createRevision = isset($input['data']) && $input['data'] !== json_decode($existing['data'], true);

            // Update fields
            $updates = [];
            $params = [];

            if (isset($input['namaPelanggan'])) {
                $updates[] = "nama_pelanggan = ?";
                $params[] = trim($input['namaPelanggan']);
            }
            if (isset($input['alamatPelanggan'])) {
                $updates[] = "alamat_pelanggan = ?";
                $params[] = trim($input['alamatPelanggan']);
            }
            if (isset($input['teleponPelanggan'])) {
                $updates[] = "telepon_pelanggan = ?";
                $params[] = trim($input['teleponPelanggan']);
            }
            if (isset($input['emailPelanggan'])) {
                $updates[] = "email_pelanggan = ?";
                $params[] = trim($input['emailPelanggan']);
            }
            if (isset($input['namaPerusahaan'])) {
                $updates[] = "nama_perusahaan = ?";
                $params[] = trim($input['namaPerusahaan']);
            }
            if (isset($input['alamatPerusahaan'])) {
                $updates[] = "alamat_perusahaan = ?";
                $params[] = trim($input['alamatPerusahaan']);
            }
            if (isset($input['teleponPerusahaan'])) {
                $updates[] = "telepon_perusahaan = ?";
                $params[] = trim($input['teleponPerusahaan']);
            }
            if (isset($input['emailPerusahaan'])) {
                $updates[] = "email_perusahaan = ?";
                $params[] = trim($input['emailPerusahaan']);
            }
            if (isset($input['berlakuHingga'])) {
                $updates[] = "berlaku_hingga = ?";
                $params[] = $input['berlakuHingga'];
            }
            if (isset($input['status'])) {
                $updates[] = "status = ?";
                $params[] = $input['status'];

                // Update timestamp based on status
                if ($input['status'] === 'sent' && !$existing['sent_at']) {
                    $updates[] = "sent_at = NOW()";
                } elseif ($input['status'] === 'approved' && !$existing['approved_at']) {
                    $updates[] = "approved_at = NOW()";
                } elseif ($input['status'] === 'rejected' && !$existing['rejected_at']) {
                    $updates[] = "rejected_at = NOW()";
                }
            }

            if (isset($input['data'])) {
                $diskonPersen = (float)($input['diskonPersen'] ?? $existing['diskon_persen']);
                $ppnPersen = (float)($input['ppnPersen'] ?? $existing['ppn_persen']);
                $totals = calculateTotals($input['data']['items'], $diskonPersen, $ppnPersen);

                $updates[] = "data = ?";
                $params[] = json_encode($input['data'], JSON_UNESCAPED_UNICODE);
                $updates[] = "subtotal = ?";
                $params[] = $totals['subtotal'];
                $updates[] = "diskon_persen = ?";
                $params[] = $diskonPersen;
                $updates[] = "diskon_nominal = ?";
                $params[] = $totals['diskonNominal'];
                $updates[] = "ppn_persen = ?";
                $params[] = $ppnPersen;
                $updates[] = "ppn_nominal = ?";
                $params[] = $totals['ppnNominal'];
                $updates[] = "total = ?";
                $params[] = $totals['total'];
            }

            if (empty($updates)) sendError('Nothing to update');

            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE penawaran_history SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);

            // Create revision if needed
            if ($createRevision) {
                $newVersion = (int)$existing['version'] + 1;
                $stmt = $pdo->prepare("
                    INSERT INTO penawaran_revisions (penawaran_id, version, data_snapshot, changes_summary, revised_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id,
                    $existing['version'],
                    $existing['data'],
                    'Data penawaran diupdate',
                    $input['revisedBy'] ?? null
                ]);

                // Update version
                $pdo->prepare("UPDATE penawaran_history SET version = ? WHERE id = ?")->execute([$newVersion, $id]);
            }

            sendResponse([
                'id' => (int)$id,
                'message' => 'Penawaran berhasil diupdate'
            ]);
            break;

        case 'delete':
            // Delete penawaran
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid penawaran ID');

            $stmt = $pdo->prepare("DELETE FROM penawaran_history WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) sendError('Penawaran not found', 404);

            sendResponse([
                'id' => (int)$id,
                'message' => 'Penawaran berhasil dihapus'
            ]);
            break;

        case 'update_status':
            // Update status only
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid penawaran ID');

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON input');

            if (!isset($input['status'])) sendError('Status is required');

            $status = $input['status'];
            $reason = trim($input['reason'] ?? '');
            $userId = $input['userId'] ?? null;

            $updates = ["status = ?"];
            $params = [$status];

            if ($status === 'sent') {
                $updates[] = "sent_at = NOW()";
                $updates[] = "sent_by = ?";
                $params[] = $userId;
            } elseif ($status === 'approved') {
                $updates[] = "approved_at = NOW()";
                $updates[] = "approved_by = ?";
                $params[] = $userId;
            } elseif ($status === 'rejected') {
                $updates[] = "rejected_at = NOW()";
                $updates[] = "rejection_reason = ?";
                $params[] = $reason;
            }

            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE penawaran_history SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);

            sendResponse([
                'id' => (int)$id,
                'status' => $status,
                'message' => "Status berhasil diupdate menjadi: $status"
            ]);
            break;

        case 'get_revisions':
            // Get all revisions for a penawaran
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid penawaran ID');

            $stmt = $pdo->prepare("
                SELECT pr.*, u.full_name as revised_by_nama
                FROM penawaran_revisions pr
                LEFT JOIN users u ON pr.revised_by = u.id
                WHERE pr.penawaran_id = ?
                ORDER BY pr.version DESC
            ");
            $stmt->execute([$id]);
            $revisions = $stmt->fetchAll();

            sendResponse(array_map(function($r) {
                return [
                    'id' => (int)$r['id'],
                    'penawaranId' => (int)$r['penawaran_id'],
                    'version' => (int)$r['version'],
                    'dataSnapshot' => json_decode($r['data_snapshot'], true),
                    'changesSummary' => $r['changes_summary'],
                    'revisedByNama' => $r['revised_by_nama'],
                    'createdAt' => $r['created_at']
                ];
            }, $revisions));
            break;

        case 'stats':
            // Get statistics
            if ($method !== 'GET') sendError('Method not allowed', 405);

            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-t');

            // Count by status
            $stmt = $pdo->prepare("
                SELECT status, COUNT(*) as count, SUM(total) as total_value
                FROM penawaran_history
                WHERE tanggal BETWEEN ? AND ?
                GROUP BY status
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $byStatus = $stmt->fetchAll();

            // Total
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count, SUM(total) as total_value
                FROM penawaran_history
                WHERE tanggal BETWEEN ? AND ?
            ");
            $stmt->execute([$dateFrom, $dateTo]);
            $total = $stmt->fetch();

            sendResponse([
                'byStatus' => array_map(function($s) {
                    return [
                        'status' => $s['status'],
                        'count' => (int)$s['count'],
                        'totalValue' => (float)$s['total_value']
                    ];
                }, $byStatus),
                'total' => [
                    'count' => (int)$total['count'],
                    'totalValue' => (float)$total['total_value']
                ],
                'dateRange' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ]
            ]);
            break;

        default:
            sendError('Invalid action. Available: list, get, create, update, delete, update_status, get_revisions, stats');
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
?>
