<?php
/**
 * API Endpoints for Transactions
 * Handles CRUD operations
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
setSecurityHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet(PDO $pdo): void {
    // Get summary statistics
    if (isset($_GET['summary'])) {
        // Get current month range
        $currentYear = date('Y');
        $currentMonth = date('m');
        $startDate = "$currentYear-$currentMonth-01";
        $lastDay = date('t'); // Get last day of current month
        $endDate = "$currentYear-$currentMonth-$lastDay";

        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(pemasukan), 0) as total_pemasukan,
                COALESCE(SUM(pengeluaran), 0) as total_pengeluaran,
                COALESCE(SUM(pemasukan) - SUM(pengeluaran), 0) as saldo,
                COUNT(*) as total_transaksi,
                SUM(CASE WHEN pemasukan > 0 THEN 1 ELSE 0 END) as transaksi_masuk,
                SUM(CASE WHEN pengeluaran > 0 THEN 1 ELSE 0 END) as transaksi_keluar
            FROM transactions
            WHERE tanggal >= ? AND tanggal <= ?
        ");
        $stmt->execute([$startDate, $endDate]);
        echo json_encode($stmt->fetch());
        return;
    }

    // Get monthly summary
    if (isset($_GET['monthly'])) {
        $year = $_GET['year'] ?? date('Y');
        $stmt = $pdo->prepare("
            SELECT
                MONTH(tanggal) as bulan,
                COALESCE(SUM(pemasukan), 0) as pemasukan,
                COALESCE(SUM(pengeluaran), 0) as pengeluaran
            FROM transactions
            WHERE YEAR(tanggal) = ?
            GROUP BY MONTH(tanggal)
            ORDER BY bulan
        ");
        $stmt->execute([$year]);
        echo json_encode($stmt->fetchAll());
        return;
    }

    // Get single transaction
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $result = $stmt->fetch();
        if ($result) {
            echo json_encode($result);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Transaction not found']);
        }
        return;
    }

    // Get all transactions with filters
    $where = [];
    $params = [];

    // Date range filter
    if (!empty($_GET['start_date'])) {
        $where[] = "tanggal >= ?";
        $params[] = $_GET['start_date'];
    }
    if (!empty($_GET['end_date'])) {
        $where[] = "tanggal <= ?";
        $params[] = $_GET['end_date'];
    }

    // Search filter
    if (!empty($_GET['search'])) {
        $where[] = "deskripsi LIKE ?";
        $params[] = '%' . $_GET['search'] . '%';
    }

    // Type filter
    if (!empty($_GET['type'])) {
        if ($_GET['type'] === 'pemasukan') {
            $where[] = "pemasukan > 0";
        } elseif ($_GET['type'] === 'pengeluaran') {
            $where[] = "pengeluaran > 0";
        }
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(50000, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Get transactions sorted by date DESC
    $sql = "
        SELECT
            id,
            tanggal,
            deskripsi,
            pemasukan,
            pengeluaran,
            created_at,
            updated_at
        FROM transactions
        $whereClause
        ORDER BY tanggal DESC, id DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Calculate running balance for each transaction
    // Balance = sum of all transactions up to and including this one (chronologically)
    foreach ($transactions as &$trans) {
        $balanceStmt = $pdo->prepare("
            SELECT COALESCE(SUM(pemasukan) - SUM(pengeluaran), 0) as balance
            FROM transactions
            WHERE tanggal < ? OR (tanggal = ? AND id <= ?)
        ");
        $balanceStmt->execute([$trans['tanggal'], $trans['tanggal'], $trans['id']]);
        $trans['saldo'] = $balanceStmt->fetchColumn();
    }

    echo json_encode([
        'data' => $transactions,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'pages' => max(1, ceil($total / $limit))
    ]);
}

function handlePost(PDO $pdo): void {
    // Check if this is an import request
    if (isset($_GET['action']) && $_GET['action'] === 'import') {
        handleImport($pdo);
        return;
    }

    // Require add permission (admin or editor)
    requireAddPermission();

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }

    // Validation
    if (empty($input['tanggal']) || empty($input['deskripsi'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Tanggal and deskripsi are required']);
        return;
    }

    $pemasukan = floatval($input['pemasukan'] ?? 0);
    $pengeluaran = floatval($input['pengeluaran'] ?? 0);

    if ($pemasukan < 0 || $pengeluaran < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Values cannot be negative']);
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO transactions (tanggal, deskripsi, pemasukan, pengeluaran)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $input['tanggal'],
        trim($input['deskripsi']),
        $pemasukan,
        $pengeluaran
    ]);

    $id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'id' => $id,
        'message' => 'Transaction created successfully'
    ]);
}

function handlePut(PDO $pdo): void {
    // Require admin access for updating transactions
    requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input or missing ID']);
        return;
    }

    // Check if exists
    $check = $pdo->prepare("SELECT id FROM transactions WHERE id = ?");
    $check->execute([$input['id']]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        return;
    }

    // Validation
    if (empty($input['tanggal']) || empty($input['deskripsi'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Tanggal and deskripsi are required']);
        return;
    }

    $pemasukan = floatval($input['pemasukan'] ?? 0);
    $pengeluaran = floatval($input['pengeluaran'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE transactions
        SET tanggal = ?, deskripsi = ?, pemasukan = ?, pengeluaran = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $input['tanggal'],
        trim($input['deskripsi']),
        $pemasukan,
        $pengeluaran,
        $input['id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Transaction updated successfully'
    ]);
}

function handleDelete(PDO $pdo): void {
    // Require admin access for deleting transactions
    requireAdmin();

    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Transaction deleted successfully'
    ]);
}

function handleImport(PDO $pdo): void {
    // Require admin access for importing
    requireAdmin();

    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File CSV harus diupload']);
        return;
    }

    $file = $_FILES['csv_file'];

    // Validate file type
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExt !== 'csv') {
        http_response_code(400);
        echo json_encode(['error' => 'File harus berformat CSV']);
        return;
    }

    // Read and parse CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal membaca file CSV']);
        return;
    }

    // Skip header row
    $header = fgetcsv($handle);

    // Expected headers: tanggal, deskripsi, pemasukan, pengeluaran
    if (!$header || count($header) < 4) {
        fclose($handle);
        http_response_code(400);
        echo json_encode(['error' => 'Format CSV tidak valid. Header harus: tanggal,deskripsi,pemasukan,pengeluaran']);
        return;
    }

    $pdo->beginTransaction();
    $imported = 0;
    $errors = [];
    $lineNumber = 1; // Start from 1 (after header)

    try {
        $stmt = $pdo->prepare("
            INSERT INTO transactions (tanggal, deskripsi, pemasukan, pengeluaran)
            VALUES (?, ?, ?, ?)
        ");

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Validate row has enough columns
            if (count($row) < 4) {
                $errors[] = "Baris $lineNumber: Data tidak lengkap";
                continue;
            }

            $tanggal = trim($row[0]);
            $deskripsi = trim($row[1]);
            $pemasukan = floatval(str_replace([',', '.'], ['', '.'], $row[2]));
            $pengeluaran = floatval(str_replace([',', '.'], ['', '.'], $row[3]));

            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                $errors[] = "Baris $lineNumber: Format tanggal harus YYYY-MM-DD";
                continue;
            }

            // Validate description
            if (empty($deskripsi)) {
                $errors[] = "Baris $lineNumber: Deskripsi tidak boleh kosong";
                continue;
            }

            // Validate amounts
            if ($pemasukan < 0 || $pengeluaran < 0) {
                $errors[] = "Baris $lineNumber: Pemasukan/pengeluaran tidak boleh negatif";
                continue;
            }

            // Insert data
            try {
                $stmt->execute([$tanggal, $deskripsi, $pemasukan, $pengeluaran]);
                $imported++;
            } catch (PDOException $e) {
                $errors[] = "Baris $lineNumber: " . $e->getMessage();
            }
        }

        fclose($handle);
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Berhasil import $imported transaksi",
            'imported' => $imported,
            'errors' => $errors
        ]);

    } catch (Exception $e) {
        fclose($handle);
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Gagal import data: ' . $e->getMessage()]);
    }
}
