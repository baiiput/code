<?php
/**
 * API: Get Counts for Badge
 * Returns counts for Jatuh Tempo, Proses, Segera, Observasi
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $counts = [];

    // Jatuh Tempo
    $query = "SELECT COUNT(*) as count FROM v_jatuh_tempo";
    $stmt = $db->query($query);
    $counts['jatuh_tempo'] = $stmt->fetch()['count'];

    // Proses
    $query = "SELECT COUNT(*) as count FROM v_proses";
    $stmt = $db->query($query);
    $counts['proses'] = $stmt->fetch()['count'];

    // Segera
    $query = "SELECT COUNT(*) as count FROM v_segera";
    $stmt = $db->query($query);
    $counts['segera'] = $stmt->fetch()['count'];

    // Observasi
    $query = "SELECT COUNT(*) as count FROM v_observasi";
    $stmt = $db->query($query);
    $counts['observasi'] = $stmt->fetch()['count'];

    echo json_encode([
        'success' => true,
        'counts' => $counts
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}
