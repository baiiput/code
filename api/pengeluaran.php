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
            // Get all pengeluaran or filter by date
            $bulan = $_GET['bulan'] ?? null;
            $tahun = $_GET['tahun'] ?? null;

            $sql = "SELECT * FROM pengeluaran WHERE 1=1";
            $params = [];

            if ($bulan) {
                $sql .= " AND MONTH(tanggal_pengeluaran) = :bulan";
                $params['bulan'] = $bulan;
            }

            if ($tahun) {
                $sql .= " AND YEAR(tanggal_pengeluaran) = :tahun";
                $params['tahun'] = $tahun;
            }

            $sql .= " ORDER BY tanggal_pengeluaran DESC";

            $pengeluaran = $db->fetchAll($sql, $params);

            jsonResponse([
                'success' => true,
                'data' => $pengeluaran
            ]);
            break;

        case 'POST':
            // Create new pengeluaran
            $data = getPostData();

            $required = ['tanggal_pengeluaran', 'kategori', 'jumlah', 'keterangan'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    jsonResponse([
                        'success' => false,
                        'message' => "Field {$field} wajib diisi"
                    ], 400);
                }
            }

            $insertData = [
                'tanggal_pengeluaran' => $data['tanggal_pengeluaran'],
                'kategori' => $data['kategori'],
                'jumlah' => $data['jumlah'],
                'keterangan' => $data['keterangan'],
                'created_by' => $_SESSION['username'] ?? 'system'
            ];

            $id = $db->insert('pengeluaran', $insertData);

            jsonResponse([
                'success' => true,
                'message' => 'Pengeluaran berhasil ditambahkan',
                'data' => ['id' => $id]
            ], 201);
            break;

        case 'PUT':
            // Update pengeluaran
            $data = getPostData();

            if (!isset($data['id'])) {
                jsonResponse([
                    'success' => false,
                    'message' => 'ID pengeluaran tidak ditemukan'
                ], 400);
            }

            $updateData = [];
            $allowedFields = ['tanggal_pengeluaran', 'kategori', 'jumlah', 'keterangan'];

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

            $db->update('pengeluaran', $updateData, 'id = :id', ['id' => $data['id']]);

            jsonResponse([
                'success' => true,
                'message' => 'Pengeluaran berhasil diupdate'
            ]);
            break;

        case 'DELETE':
            // Delete pengeluaran
            $data = getPostData();

            if (!isset($data['id'])) {
                jsonResponse([
                    'success' => false,
                    'message' => 'ID pengeluaran tidak ditemukan'
                ], 400);
            }

            $db->delete('pengeluaran', 'id = :id', ['id' => $data['id']]);

            jsonResponse([
                'success' => true,
                'message' => 'Pengeluaran berhasil dihapus'
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
