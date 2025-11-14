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
            // Get all settings
            $settings = $db->fetchAll("SELECT * FROM settings ORDER BY setting_key");

            // Convert to associative array for easier access
            $settingsArray = [];
            foreach ($settings as $setting) {
                $settingsArray[$setting['setting_key']] = [
                    'value' => $setting['setting_value'],
                    'description' => $setting['description']
                ];
            }

            jsonResponse([
                'success' => true,
                'data' => $settingsArray
            ]);
            break;

        case 'POST':
        case 'PUT':
            // Update settings
            $data = getPostData();

            if (isset($data['target_tahunan'])) {
                $db->query(
                    "UPDATE settings SET setting_value = :value WHERE setting_key = 'target_tahunan'",
                    ['value' => $data['target_tahunan']]
                );
            }

            if (isset($data['tahun_berjalan'])) {
                $db->query(
                    "UPDATE settings SET setting_value = :value WHERE setting_key = 'tahun_berjalan'",
                    ['value' => $data['tahun_berjalan']]
                );
            }

            jsonResponse([
                'success' => true,
                'message' => 'Pengaturan berhasil disimpan'
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
