<?php
session_start();
require_once '../config/database.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$method = getRequestMethod();

try {
    switch ($method) {
        case 'GET':
            // Get all transaksi or filter by dawis/warga
            $dawisId = $_GET['dawis_id'] ?? null;
            $wargaId = $_GET['warga_id'] ?? null;
            $bulan = $_GET['bulan'] ?? null;
            $tahun = $_GET['tahun'] ?? null;

            $sql = "
                SELECT t.*, w.nama_lengkap, w.dawis_id, d.nama_dawis
                FROM transaksi t
                JOIN warga w ON t.warga_id = w.id
                JOIN dawis d ON w.dawis_id = d.id
                WHERE 1=1
            ";
            $params = [];

            if ($dawisId) {
                $sql .= " AND w.dawis_id = :dawis_id";
                $params['dawis_id'] = $dawisId;
            }

            if ($wargaId) {
                $sql .= " AND t.warga_id = :warga_id";
                $params['warga_id'] = $wargaId;
            }

            if ($bulan) {
                $sql .= " AND MONTH(t.tanggal_transaksi) = :bulan";
                $params['bulan'] = $bulan;
            }

            if ($tahun) {
                $sql .= " AND YEAR(t.tanggal_transaksi) = :tahun";
                $params['tahun'] = $tahun;
            }

            $sql .= " ORDER BY t.tanggal_transaksi DESC, t.created_at DESC";

            $transaksi = $db->fetchAll($sql, $params);

            jsonResponse([
                'success' => true,
                'data' => $transaksi
            ]);
            break;

        case 'POST':
            // Create new transaksi
            $data = getPostData();

            $required = ['warga_id', 'tanggal_transaksi', 'jumlah', 'jenis_transaksi'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    jsonResponse([
                        'success' => false,
                        'message' => "Field {$field} wajib diisi"
                    ], 400);
                }
            }

            $insertData = [
                'warga_id' => $data['warga_id'],
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'jumlah' => $data['jumlah'],
                'jenis_transaksi' => $data['jenis_transaksi'],
                'keterangan' => $data['keterangan'] ?? '',
                'created_by' => $_SESSION['username'] ?? 'system'
            ];

            $id = $db->insert('transaksi', $insertData);

            jsonResponse([
                'success' => true,
                'message' => 'Transaksi berhasil ditambahkan',
                'data' => ['id' => $id]
            ], 201);
            break;

        case 'PUT':
            // Update transaksi
            $data = getPostData();

            if (!isset($data['id'])) {
                jsonResponse([
                    'success' => false,
                    'message' => 'ID transaksi tidak ditemukan'
                ], 400);
            }

            $updateData = [];
            $allowedFields = ['warga_id', 'tanggal_transaksi', 'jumlah', 'jenis_transaksi', 'keterangan'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Tidak ada data yang diupdate'
                ], 400);
            }

            $db->update('transaksi', $updateData, 'id = :id', ['id' => $data['id']]);

            jsonResponse([
                'success' => true,
                'message' => 'Transaksi berhasil diupdate'
            ]);
            break;

        case 'DELETE':
            // Delete transaksi
            $data = getPostData();

            if (!isset($data['id'])) {
                jsonResponse([
                    'success' => false,
                    'message' => 'ID transaksi tidak ditemukan'
                ], 400);
            }

            $db->delete('transaksi', 'id = :id', ['id' => $data['id']]);

            jsonResponse([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus'
            ]);
            break;

        default:
            jsonResponse([
                'success' => false,
                'message' => 'Method tidak didukung'
            ], 405);
    }

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ], 500);
}
