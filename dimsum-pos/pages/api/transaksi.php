<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check auth
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['items'])) {
        echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Calculate subtotal
        $subtotal = 0;
        foreach ($input['items'] as $item) {
            $subtotal += $item['harga'] * $item['qty'];
        }

        // Calculate discount
        $diskonNominal = 0;
        $diskonId = $input['diskon_id'] ?? null;
        if ($diskonId) {
            $diskon = $db->fetch("SELECT * FROM diskon WHERE id = ?", [$diskonId]);
            if ($diskon) {
                if ($diskon['tipe'] === 'persen' || $diskon['tipe'] === 'happy_hour' || $diskon['tipe'] === 'member') {
                    $diskonNominal = $subtotal * ($diskon['nilai'] / 100);
                } elseif ($diskon['tipe'] === 'nominal') {
                    $diskonNominal = $diskon['nilai'];
                }
            }
        }

        // Calculate tax
        $pajak = $db->fetch("SELECT * FROM pengaturan_pajak WHERE is_active = 1");
        $pajakPersen = $pajak ? $pajak['persentase'] : 0;
        $afterDiskon = $subtotal - $diskonNominal;
        $pajakNominal = $afterDiskon * ($pajakPersen / 100);

        // Calculate total
        $total = $afterDiskon + $pajakNominal;

        // Calculate change
        $uangDiterima = $input['uang_diterima'] ?? $total;
        $kembalian = max(0, $uangDiterima - $total);

        // Generate transaction number
        $noTransaksi = Helper::generateNoTransaksi($input['cabang_id']);

        // Insert transaksi
        $transaksiId = $db->insert('transaksi', [
            'cabang_id' => $input['cabang_id'],
            'user_id' => Auth::user('id'),
            'no_transaksi' => $noTransaksi,
            'tipe_order' => $input['tipe_order'],
            'subtotal' => $subtotal,
            'diskon_id' => $diskonId,
            'diskon_nominal' => $diskonNominal,
            'pajak_persen' => $pajakPersen,
            'pajak_nominal' => $pajakNominal,
            'total' => $total,
            'metode_pembayaran_id' => $input['metode_pembayaran_id'],
            'uang_diterima' => $uangDiterima,
            'kembalian' => $kembalian,
            'status' => 'completed'
        ]);

        // Insert transaksi detail
        foreach ($input['items'] as $item) {
            $db->insert('transaksi_detail', [
                'transaksi_id' => $transaksiId,
                'menu_variasi_id' => $item['variasi_id'],
                'nama_menu' => $item['nama'],
                'nama_variasi' => $item['variasi'],
                'harga' => $item['harga'],
                'qty' => $item['qty'],
                'subtotal' => $item['harga'] * $item['qty']
            ]);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'no_transaksi' => $noTransaksi,
            'total' => $total,
            'kembalian' => $kembalian
        ]);

    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
