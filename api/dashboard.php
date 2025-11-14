<?php
session_start();
require_once '../config/database.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$db = Database::getInstance();

try {
    // Get saldo total RT
    $saldoRT = $db->fetchOne("SELECT * FROM saldo_total_rt");

    // Get laporan per dawis
    $laporanDawis = $db->fetchAll("SELECT * FROM laporan_per_dawis ORDER BY dawis_id");

    // Get jumlah total warga
    $totalWarga = $db->fetchOne("SELECT COUNT(*) as total FROM warga WHERE status = 'aktif'");

    // Get transaksi terakhir
    $transaksiTerakhir = $db->fetchAll("
        SELECT t.*, w.nama_lengkap, d.nama_dawis
        FROM transaksi t
        JOIN warga w ON t.warga_id = w.id
        JOIN dawis d ON w.dawis_id = d.id
        ORDER BY t.created_at DESC
        LIMIT 10
    ");

    // Get pengeluaran bulan ini
    $pengeluaranBulanIni = $db->fetchAll("
        SELECT *
        FROM pengeluaran
        WHERE MONTH(tanggal_pengeluaran) = MONTH(CURRENT_DATE())
        AND YEAR(tanggal_pengeluaran) = YEAR(CURRENT_DATE())
        ORDER BY tanggal_pengeluaran DESC
    ");

    // Get statistik bulanan (6 bulan terakhir)
    $statistikBulanan = $db->fetchAll("
        SELECT
            DATE_FORMAT(tanggal_transaksi, '%Y-%m') as bulan,
            DATE_FORMAT(tanggal_transaksi, '%M %Y') as bulan_nama,
            SUM(CASE WHEN jenis_transaksi = 'setoran' THEN jumlah ELSE 0 END) as total_setoran,
            SUM(CASE WHEN jenis_transaksi = 'penarikan' THEN jumlah ELSE 0 END) as total_penarikan
        FROM transaksi
        WHERE tanggal_transaksi >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY bulan, bulan_nama
        ORDER BY bulan DESC
    ");

    $response = [
        'success' => true,
        'data' => [
            'saldo_rt' => $saldoRT,
            'laporan_dawis' => $laporanDawis,
            'total_warga' => $totalWarga['total'],
            'transaksi_terakhir' => $transaksiTerakhir,
            'pengeluaran_bulan_ini' => $pengeluaranBulanIni,
            'statistik_bulanan' => $statistikBulanan
        ]
    ];

    jsonResponse($response);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ], 500);
}
