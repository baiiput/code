<?php
/**
 * Branch API - Laporan Keuangan Dimsum
 */
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

switch ($method) {
    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID tidak valid'], 400);
        }

        // Check if branch has transactions
        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE branch_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['success' => false, 'message' => 'Cabang tidak dapat dihapus karena masih memiliki transaksi'], 400);
        }

        try {
            $stmt = $db->prepare("DELETE FROM branches WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'Cabang berhasil dihapus']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Cabang tidak ditemukan'], 404);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
        break;

    case 'GET':
        $branches = getBranches();
        jsonResponse(['success' => true, 'data' => $branches]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
