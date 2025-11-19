<?php
/**
 * Transaction API - Laporan Keuangan Dimsum
 */
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

switch ($method) {
    case 'DELETE':
        // Check permission
        if (!canDelete()) {
            jsonResponse(['success' => false, 'message' => 'Hanya Admin yang dapat menghapus transaksi'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID tidak valid'], 400);
        }

        try {
            $stmt = $db->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'Transaksi berhasil dihapus']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Transaksi tidak ditemukan'], 404);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
        break;

    case 'GET':
        // Get transactions with optional filters
        $branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

        $whereConditions = ["transaction_date BETWEEN :start_date AND :end_date"];
        $params = [':start_date' => $startDate, ':end_date' => $endDate];

        if ($branchId > 0) {
            $whereConditions[] = "branch_id = :branch_id";
            $params[':branch_id'] = $branchId;
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "SELECT t.*, b.name as branch_name
                FROM transactions t
                JOIN branches b ON t.branch_id = b.id
                WHERE $whereClause
                ORDER BY t.transaction_date DESC, t.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        jsonResponse(['success' => true, 'data' => $transactions]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
