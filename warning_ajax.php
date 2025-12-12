<?php
// warning_ajax.php - Gabungan Fix Bug "0 hari" + Return Expired Status
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Database config
$payment_host = 'localhost';
$payment_db = 'mikhmon_payment';
$payment_user = 'mikhmon_payment';
$payment_pass = 'online2025';

try {
    // Database connection
    $pdo = new PDO("mysql:host={$payment_host};dbname={$payment_db}", $payment_user, $payment_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set MySQL timezone
    $pdo->exec("SET time_zone = '+07:00'");
    
    // Get subdomain
    $current_subdomain = $_SERVER['HTTP_HOST'];
    $subdomain = str_replace('.octonet.my.id', '', $current_subdomain);
    
    // Query client
    $stmt = $pdo->prepare("SELECT nama, tanggal_expired, status FROM clients WHERE subdomain = ? LIMIT 1");
    $stmt->execute([$subdomain]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        $expiry_time = strtotime($client['tanggal_expired']);
        $current_time = time();
        $seconds_left = $expiry_time - $current_time;
        $days_left = floor($seconds_left / (24 * 60 * 60));
        $hours_left = floor(($seconds_left % (24 * 60 * 60)) / 3600);
        $minutes_left = floor(($seconds_left % 3600) / 60);
        
        // Check if expired - RETURN EXPIRED STATUS untuk redirect
        if ($seconds_left <= 0) {
            echo json_encode([
                'show_warning' => false,
                'reason' => 'already_expired',
                'expired_since' => abs($seconds_left)
            ]);
            exit;
        }
        
        // Check if need warning
        $show_warning = false;
        $level = '';
        $message = '';
        $time_display = '';
        
        if ($seconds_left <= 3600) {
            // Less than 1 hour - CRITICAL
            $show_warning = true;
            $level = 'critical';
            $message = "URGENT! Sisa {$minutes_left} menit lagi";
            $time_display = "{$minutes_left} menit";
            
        } elseif ($seconds_left <= 86400) {
            // Less than 24 hours - WARNING (FIX BUG: hilangkan "0 hari")
            $show_warning = true;
            $level = 'warning';
            $message = "Sisa {$hours_left} jam {$minutes_left} menit";
            $time_display = "{$hours_left}j {$minutes_left}m";
            
        } elseif ($days_left <= 7) {
            // 1-7 days - INFO
            $show_warning = true;
            $level = 'info';
            $message = "Sisa {$days_left} hari";
            $time_display = "{$days_left} hari";
        }
        
        if ($show_warning) {
            echo json_encode([
                'show_warning' => true,
                'level' => $level,
                'message' => $message,
                'time_display' => $time_display,
                'days' => max(0, $days_left),
                'hours' => max(0, $hours_left),
                'minutes' => max(0, $minutes_left),
                'date' => date('d F Y H:i', $expiry_time),
                'subdomain' => $subdomain,
                'nama' => $client['nama'],
                'renewal_url' => "https://payment.octonet.my.id/renewal.php?subdomain={$subdomain}"
            ]);
        } else {
            echo json_encode([
                'show_warning' => false,
                'reason' => 'not_in_warning_period'
            ]);
        }
        
    } else {
        echo json_encode([
            'show_warning' => false,
            'reason' => 'client_not_found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'show_warning' => false,
        'error' => true,
        'message' => 'Database connection failed'
    ]);
}
?>