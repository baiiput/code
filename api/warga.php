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
            // Get all warga or filter by dawis
            $dawisId = $_GET['dawis_id'] ?? null;

            $sql = "
                SELECT w.*, d.nama_dawis,
                COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END), 0) as total_saldo
                FROM warga w
                JOIN dawis d ON w.dawis_id = d.id
                LEFT JOIN transaksi t ON w.id = t.warga_id
                WHERE 1=1
            ";
            $params = [];

            if ($dawisId) {
                $sql .= " AND w.dawis_id = :dawis_id";
                $params['dawis_id'] = $dawisId;
            }

            $sql .= " GROUP BY w.id ORDER BY d.nama_dawis, w.nama_lengkap";

            $warga = $db->fetchAll($sql, $params);

            jsonResponse([
                'success' => true,
                'data' => $warga
            ]);
            break;

        case 'POST':
            // Create new warga
            $data = getPostData();

            $required = ['dawis_id', 'nama_lengkap'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    jsonResponse([
                        'success' => false,
                        'message' => "Field {$field} wajib diisi"
                    ], 400);
                }
            }

            $insertData = [
                'dawis_id' => $data['dawis_id'],
                'nama_lengkap' => $data['nama_lengkap'],
                'nomor_kk' => $data['nomor_kk'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'no_telepon' => $data['no_telepon'] ?? null,
                'status' => $data['status'] ?? 'aktif'
            ];

            $id = $db->insert('warga', $insertData);

            jsonResponse([
                'success' => true,
                'message' => 'Data warga berhasil ditambahkan',
                'data' => ['id' => $id]
            ], 201);
            break;

        case 'PUT':
            // Update warga
            $data = getPostData();

            if (!isset($data['id'])) {
                jsonResponse([
                    'success' => false,
                    'message' => 'ID warga tidak ditemukan'
                ], 400);
            }

            $updateData = [];
            $allowedFields = ['dawis_id', 'nama_lengkap', 'nomor_kk', 'alamat', 'no_telepon', 'status'];

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

            $db->update('warga', $updateData, 'id = :id', ['id' => $data['id']]);

            jsonResponse([
                'success' => true,
                'message' => 'Data warga berhasil diupdate'
            ]);
            break;

        case 'DELETE':
            // Delete warga
            $data = getPostData();

            if (!isset($data['id'])) {
                jsonResponse([
                    'success' => false,
                    'message' => 'ID warga tidak ditemukan'
                ], 400);
            }

            $db->delete('warga', 'id = :id', ['id' => $data['id']]);

            jsonResponse([
                'success' => true,
                'message' => 'Data warga berhasil dihapus'
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
