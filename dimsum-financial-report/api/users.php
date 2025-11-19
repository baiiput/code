<?php
/**
 * User API - Laporan Keuangan Dimsum
 */
require_once '../config.php';

header('Content-Type: application/json');

// Check admin permission
if (!canManageUsers()) {
    jsonResponse(['success' => false, 'message' => 'Akses ditolak'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

switch ($method) {
    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID tidak valid'], 400);
        }

        // Prevent deleting yourself
        $currentUser = getCurrentUser();
        if ($id == $currentUser['id']) {
            jsonResponse(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri'], 400);
        }

        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'User berhasil dihapus']);
            } else {
                jsonResponse(['success' => false, 'message' => 'User tidak ditemukan'], 404);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Terjadi kesalahan'], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
