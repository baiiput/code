<?php
// validate_subdomain.php - Final Clean Version
// File ini akan memblokir akses jika subdomain tidak terdaftar atau expired

function getCurrentSubdomain() {
    $host = $_SERVER['HTTP_HOST'];
    $subdomain = str_replace('.octonet.my.id', '', $host);
    $subdomain = str_replace('www.', '', $subdomain);
    return strtolower(trim($subdomain));
}

function getPaymentDBConnection() {
    try {
        $payment_pdo = new PDO(
            "mysql:host=localhost;dbname=mikhmon_payment;charset=utf8mb4", 
            "mikhmon_payment", 
            "online2025",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $payment_pdo;
    } catch (PDOException $e) {
        error_log("Payment DB connection failed: " . $e->getMessage());
        return null;
    }
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Get current subdomain
$current_subdomain = getCurrentSubdomain();

// Skip validation for localhost, IP addresses, or main domain
$skip_validation = [
    'localhost', 
    '127.0.0.1', 
    'octonet.my.id',
    'www.octonet.my.id',
    'payment.octonet.my.id'
];

$is_ip = filter_var($current_subdomain, FILTER_VALIDATE_IP);
$should_skip = in_array($current_subdomain, $skip_validation) || $is_ip || empty($current_subdomain);

if (!$should_skip) {
    $payment_pdo = getPaymentDBConnection();
    
    if ($payment_pdo) {
        try {
            $stmt = $payment_pdo->prepare("
                SELECT id, nama, subdomain, email, no_wa, tanggal_expired, status,
                       (tanggal_expired > NOW()) as is_active
                FROM clients 
                WHERE subdomain = ?
                LIMIT 1
            ");
            $stmt->execute([$current_subdomain]);
            $client = $stmt->fetch();
            
            if (!$client) {
                // Subdomain tidak ditemukan
                showUnregisteredPopup($current_subdomain);
                exit();
                
            } elseif ($client['status'] !== 'active' || !$client['is_active']) {
                // Subdomain expired atau tidak aktif
                showExpiredPopup($current_subdomain, $client);
                exit();
                
            } else {
                // Subdomain valid dan aktif
                $GLOBALS['validated_client'] = $client;
            }
            
        } catch (Exception $e) {
            error_log("Subdomain validation error for {$current_subdomain}: " . $e->getMessage());
        }
    }
}

function showUnregisteredPopup($subdomain) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
        <title>Subdomain Tidak Ditemukan</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f172a 100%);
                height: 100vh;
                height: 100dvh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                overflow: hidden;
            }
            
            .card {
                background: linear-gradient(145deg, #1f2937, #111827);
                border: 1px solid #374151;
                border-radius: 16px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.4);
                width: 100%;
                max-width: 340px;
                overflow: hidden;
                animation: slideUp 0.6s ease-out;
            }
            
            .header {
                background: linear-gradient(135deg, #dc2626, #b91c1c);
                color: white;
                padding: 20px;
                text-align: center;
                position: relative;
            }
            
            .header::before {
                content: '⚠️';
                font-size: 2rem;
                display: block;
                margin-bottom: 8px;
                animation: pulse 2s infinite;
            }
            
            .header h1 {
                font-size: 1.1rem;
                font-weight: 600;
                margin-bottom: 4px;
            }
            
            .header p {
                font-size: 0.8rem;
                opacity: 0.9;
            }
            
            .content {
                padding: 20px;
                color: #e5e7eb;
            }
            
            .subdomain-box {
                background: #374151;
                border: 2px dashed #6b7280;
                border-radius: 12px;
                padding: 12px;
                text-align: center;
                margin-bottom: 16px;
            }
            
            .subdomain-text {
                font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
                font-size: 0.85rem;
                font-weight: 600;
                color: #f3f4f6;
                word-break: break-all;
            }
            
            .message {
                text-align: center;
                color: #d1d5db;
                font-size: 0.8rem;
                line-height: 1.4;
                margin-bottom: 16px;
            }
            
            .countdown {
                background: linear-gradient(45deg, #17a2b8, #20c997);
                color: white;
                border-radius: 20px;
                padding: 8px 16px;
                text-align: center;
                margin-bottom: 16px;
                font-size: 0.75rem;
                font-weight: 500;
            }
            
            .countdown-number {
                font-weight: 700;
                font-size: 0.9rem;
            }
            
            .actions {
                display: grid;
                gap: 8px;
            }
            
            .btn {
                padding: 10px 16px;
                border: none;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                text-decoration: none;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                transition: all 0.3s ease;
                cursor: pointer;
            }
            
            .btn-primary {
                background: linear-gradient(45deg, #28a745, #20c997);
                color: white;
            }
            
            .btn-secondary {
                background: linear-gradient(45deg, #007bff, #6f42c1);
                color: white;
            }
            
            .btn:active {
                transform: scale(0.98);
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            
            /* Compact mobile (most phones) */
            @media (max-height: 700px) {
                .card {
                    max-width: 320px;
                }
                
                .header {
                    padding: 16px;
                }
                
                .header::before {
                    font-size: 1.6rem;
                    margin-bottom: 6px;
                }
                
                .header h1 {
                    font-size: 1rem;
                }
                
                .header p {
                    font-size: 0.75rem;
                }
                
                .content {
                    padding: 16px;
                }
                
                .subdomain-box {
                    padding: 10px;
                    margin-bottom: 12px;
                }
                
                .subdomain-text {
                    font-size: 0.8rem;
                }
                
                .message {
                    font-size: 0.75rem;
                    margin-bottom: 12px;
                }
                
                .countdown {
                    padding: 6px 12px;
                    margin-bottom: 12px;
                    font-size: 0.7rem;
                }
                
                .btn {
                    padding: 8px 12px;
                    font-size: 0.75rem;
                }
            }
            
            /* Extra compact for smaller screens */
            @media (max-height: 600px) {
                body {
                    padding: 12px;
                }
                
                .card {
                    max-width: 300px;
                }
                
                .header {
                    padding: 12px;
                }
                
                .header::before {
                    font-size: 1.4rem;
                    margin-bottom: 4px;
                }
                
                .header h1 {
                    font-size: 0.9rem;
                    margin-bottom: 2px;
                }
                
                .header p {
                    font-size: 0.7rem;
                }
                
                .content {
                    padding: 12px;
                }
                
                .subdomain-box {
                    padding: 8px;
                    margin-bottom: 10px;
                }
                
                .subdomain-text {
                    font-size: 0.75rem;
                }
                
                .message {
                    font-size: 0.7rem;
                    margin-bottom: 10px;
                }
                
                .countdown {
                    padding: 5px 10px;
                    margin-bottom: 10px;
                    font-size: 0.65rem;
                }
                
                .actions {
                    gap: 6px;
                }
                
                .btn {
                    padding: 7px 10px;
                    font-size: 0.7rem;
                }
            }
            
            /* Landscape orientation */
            @media (orientation: landscape) and (max-height: 500px) {
                body {
                    padding: 8px;
                }
                
                .card {
                    max-width: 400px;
                    display: flex;
                    max-height: 90vh;
                }
                
                .header {
                    flex: 0 0 140px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    padding: 12px;
                }
                
                .header::before {
                    font-size: 1.2rem;
                    margin-bottom: 4px;
                }
                
                .header h1 {
                    font-size: 0.8rem;
                    margin-bottom: 2px;
                }
                
                .header p {
                    font-size: 0.65rem;
                }
                
                .content {
                    flex: 1;
                    padding: 12px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }
                
                .subdomain-box {
                    margin-bottom: 8px;
                    padding: 6px;
                }
                
                .subdomain-text {
                    font-size: 0.7rem;
                }
                
                .message {
                    font-size: 0.65rem;
                    margin-bottom: 8px;
                }
                
                .countdown {
                    padding: 4px 8px;
                    margin-bottom: 8px;
                    font-size: 0.6rem;
                }
                
                .actions {
                    gap: 4px;
                }
                
                .btn {
                    padding: 6px 8px;
                    font-size: 0.65rem;
                }
            }
            
            /* Very small screens (iPhone SE, etc) */
            @media (max-width: 350px) and (max-height: 600px) {
                .card {
                    max-width: 280px;
                }
                
                .header {
                    padding: 10px;
                }
                
                .content {
                    padding: 10px;
                }
                
                .subdomain-text {
                    font-size: 0.7rem;
                }
                
                .message {
                    font-size: 0.65rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="header">
                <h1>Subdomain Tidak Ditemukan</h1>
                <p>Belum terdaftar atau tidak aktif</p>
            </div>
            
            <div class="content">
                <div class="subdomain-box">
                    <div class="subdomain-text" id="subdomainName"><?= htmlspecialchars($subdomain) ?>.octonet.my.id</div>
                </div>
                
                <div class="message">
                    Silakan daftarkan subdomain ini untuk mengakses layanan Mikhmon
                </div>
                
                <div class="countdown">
                    🔄 Auto refresh dalam <span class="countdown-number" id="countdown">90</span> detik
                </div>
                
                <div class="actions">
                    <a href="https://payment.octonet.my.id" class="btn btn-primary" id="registerBtn" target="_blank">
                        <span>➕</span>
                        <span>Daftar Mikhmon Online</span>
                    </a>
                    <a href="https://wa.me/628567890902?text=Halo,%20saya%20ingin%20mendaftarkan%20subdomain%20<?= urlencode($subdomain) ?>.octonet.my.id" class="btn btn-secondary" id="whatsappBtn" target="_blank">
                        <span>💬</span>
                        <span>Hubungi Admin</span>
                    </a>
                </div>
            </div>
        </div>

        <script>
            // Countdown functionality
            let timeLeft = 90;
            const countdownElement = document.getElementById('countdown');
            
            const countdownInterval = setInterval(function() {
                timeLeft--;
                countdownElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    countdownElement.textContent = '...';
                    window.location.reload();
                }
            }, 1000);
            
            // Prevent viewport zoom on iOS
            document.addEventListener('touchstart', function() {}, {passive: true});
            
            // Handle viewport changes
            function setViewportHeight() {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', vh + 'px');
            }
            
            window.addEventListener('resize', setViewportHeight);
            window.addEventListener('orientationchange', function() {
                setTimeout(setViewportHeight, 100);
            });
            
            setViewportHeight();
            
            // Prevent right click and key shortcuts
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.keyCode === 116 || (e.ctrlKey && e.keyCode === 82) || e.keyCode === 27 || (e.altKey && e.keyCode === 115) || (e.ctrlKey && e.keyCode === 87)) {
                    e.preventDefault();
                    if (e.keyCode === 116 || (e.ctrlKey && e.keyCode === 82)) {
                        window.location.reload();
                    }
                    return false;
                }
            });
        </script>
    </body>
    </html>
    <?php
}

function showExpiredPopup($subdomain, $client_info) {
    $expired_time = time() - strtotime($client_info['tanggal_expired']);
    $expired_days = floor($expired_time / 86400);
    $expired_hours = floor(($expired_time % 86400) / 3600);
    
    // Sensor email dan nomor wa
    function censorEmail($email) {
        $parts = explode('@', $email);
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 3) {
            return $username . '@' . $domain;
        }
        
        $show = ceil(strlen($username) * 0.6); // Show 60% of username
        $censored = substr($username, 0, $show) . str_repeat('*', strlen($username) - $show) . '@' . $domain;
        return $censored;
    }
    
    function censorPhone($phone) {
        if (strlen($phone) <= 6) {
            return $phone;
        }
        
        $show = ceil(strlen($phone) * 0.5); // Show 50% of phone number
        return substr($phone, 0, $show) . str_repeat('*', strlen($phone) - $show);
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
        <title>Layanan Expired - <?= htmlspecialchars($subdomain) ?></title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
                min-height: 100vh;
                min-height: 100dvh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                overflow-y: auto;
            }
            
            .container {
                width: 100%;
                max-width: 420px;
                margin: auto;
            }
            
            .card {
                background: linear-gradient(145deg, #1f2937, #111827);
                border: 1px solid #374151;
                border-radius: 20px;
                box-shadow: 0 25px 50px rgba(0,0,0,0.4);
                overflow: hidden;
                animation: slideUp 0.6s ease-out;
                margin: 16px 0;
            }
            
            .header {
                background: linear-gradient(135deg, #dc2626, #b91c1c);
                color: white;
                padding: 24px 20px;
                text-align: center;
                position: relative;
            }
            
            .header::before {
                content: '⏰';
                font-size: 2.5rem;
                display: block;
                margin-bottom: 12px;
                animation: pulse 2s infinite;
            }
            
            .header h1 {
                font-size: 1.3rem;
                font-weight: 700;
                margin-bottom: 6px;
            }
            
            .header p {
                font-size: 0.9rem;
                opacity: 0.95;
            }
            
            .content {
                padding: 24px 20px;
                color: #e5e7eb;
            }
            
            .status-badge {
                background: linear-gradient(45deg, #dc2626, #b91c1c);
                color: white;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                text-align: center;
                margin-bottom: 20px;
                animation: blink 2s infinite;
            }
            
            .expired-info {
                background: linear-gradient(145deg, #374151, #1f2937);
                border: 2px solid #ef4444;
                border-radius: 16px;
                padding: 20px;
                margin-bottom: 20px;
                text-align: center;
            }
            
            .expired-info .icon {
                font-size: 1.5rem;
                margin-bottom: 8px;
                display: block;
            }
            
            .expired-info .title {
                font-size: 1rem;
                font-weight: 700;
                color: #fca5a5;
                margin-bottom: 8px;
            }
            
            .expired-info .duration {
                font-size: 0.9rem;
                color: #f87171;
                font-weight: 600;
            }
            
            .info-section {
                background: #374151;
                border-radius: 16px;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .info-section h3 {
                font-size: 1rem;
                font-weight: 600;
                color: #f3f4f6;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .info-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 8px 0;
                border-bottom: 1px solid #4b5563;
                font-size: 0.85rem;
            }
            
            .info-item:last-child {
                border-bottom: none;
            }
            
            .info-label {
                font-weight: 600;
                color: #d1d5db;
                flex: 0 0 auto;
                margin-right: 12px;
            }
            
            .info-value {
                color: #f3f4f6;
                font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
                font-size: 0.8rem;
                text-align: right;
                word-break: break-all;
                flex: 1;
            }
            
            .countdown {
                background: linear-gradient(45deg, #059669, #047857);
                color: white;
                border-radius: 16px;
                padding: 12px;
                text-align: center;
                margin-bottom: 20px;
                font-size: 0.8rem;
                font-weight: 600;
            }
            
            .countdown-number {
                font-weight: 700;
                font-size: 1rem;
            }
            
            .actions {
                display: grid;
                gap: 12px;
            }
            
            .btn {
                padding: 14px 20px;
                border: none;
                border-radius: 16px;
                font-size: 0.9rem;
                font-weight: 600;
                text-decoration: none;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all 0.3s ease;
                cursor: pointer;
                text-align: center;
            }
            
            .btn-primary {
                background: linear-gradient(45deg, #059669, #047857);
                color: white;
                box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
            }
            
            .btn-warning {
                background: linear-gradient(45deg, #d97706, #b45309);
                color: white;
                box-shadow: 0 4px 15px rgba(217, 119, 6, 0.3);
            }
            
            .btn-secondary {
                background: linear-gradient(45deg, #4f46e5, #3730a3);
                color: white;
                box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
            }
            
            .btn:hover, .btn:active {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            }
            
            .btn:active {
                transform: scale(0.98);
            }
            
            .footer-note {
                text-align: center;
                color: #9ca3af;
                font-size: 0.75rem;
                margin-top: 20px;
                line-height: 1.4;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            
            @keyframes blink {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            
            /* Compact mobile styles */
            @media (max-height: 700px) {
                body {
                    padding: 12px;
                }
                
                .container {
                    max-width: 380px;
                }
                
                .header {
                    padding: 20px 16px;
                }
                
                .header::before {
                    font-size: 2rem;
                    margin-bottom: 8px;
                }
                
                .header h1 {
                    font-size: 1.1rem;
                }
                
                .header p {
                    font-size: 0.8rem;
                }
                
                .content {
                    padding: 20px 16px;
                }
                
                .info-section {
                    padding: 16px;
                    margin-bottom: 16px;
                }
                
                .expired-info {
                    padding: 16px;
                    margin-bottom: 16px;
                }
                
                .actions {
                    gap: 10px;
                }
                
                .btn {
                    padding: 12px 16px;
                    font-size: 0.85rem;
                }
            }
            
            /* Extra compact for smaller screens */
            @media (max-height: 600px) {
                body {
                    padding: 8px;
                }
                
                .container {
                    max-width: 340px;
                }
                
                .header {
                    padding: 16px 12px;
                }
                
                .header::before {
                    font-size: 1.6rem;
                    margin-bottom: 6px;
                }
                
                .header h1 {
                    font-size: 1rem;
                    margin-bottom: 4px;
                }
                
                .header p {
                    font-size: 0.75rem;
                }
                
                .content {
                    padding: 16px 12px;
                }
                
                .info-section {
                    padding: 12px;
                    margin-bottom: 12px;
                }
                
                .expired-info {
                    padding: 12px;
                    margin-bottom: 12px;
                }
                
                .info-item {
                    padding: 6px 0;
                    font-size: 0.8rem;
                }
                
                .info-value {
                    font-size: 0.75rem;
                }
                
                .countdown {
                    padding: 8px;
                    margin-bottom: 12px;
                    font-size: 0.75rem;
                }
                
                .actions {
                    gap: 8px;
                }
                
                .btn {
                    padding: 10px 12px;
                    font-size: 0.8rem;
                }
                
                .footer-note {
                    font-size: 0.7rem;
                    margin-top: 12px;
                }
            }
            
            /* Landscape orientation */
            @media (orientation: landscape) and (max-height: 500px) {
                body {
                    padding: 8px;
                }
                
                .container {
                    max-width: 480px;
                    margin: 8px auto;
                }
                
                .card {
                    margin: 8px 0;
                }
                
                .header {
                    padding: 12px 16px;
                }
                
                .header::before {
                    font-size: 1.4rem;
                    margin-bottom: 4px;
                }
                
                .header h1 {
                    font-size: 0.9rem;
                    margin-bottom: 2px;
                }
                
                .header p {
                    font-size: 0.7rem;
                }
                
                .content {
                    padding: 12px 16px;
                }
                
                .info-section {
                    padding: 10px;
                    margin-bottom: 10px;
                }
                
                .expired-info {
                    padding: 10px;
                    margin-bottom: 10px;
                }
                
                .info-item {
                    padding: 4px 0;
                    font-size: 0.75rem;
                }
                
                .info-value {
                    font-size: 0.7rem;
                }
                
                .countdown {
                    padding: 6px;
                    margin-bottom: 10px;
                    font-size: 0.7rem;
                }
                
                .actions {
                    gap: 6px;
                }
                
                .btn {
                    padding: 8px 10px;
                    font-size: 0.75rem;
                }
                
                .footer-note {
                    font-size: 0.65rem;
                    margin-top: 8px;
                }
            }
            
            /* Very small screens */
            @media (max-width: 350px) {
                .container {
                    max-width: 300px;
                }
                
                .info-item {
                    flex-direction: column;
                    gap: 4px;
                    align-items: flex-start;
                }
                
                .info-value {
                    text-align: left;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <div class="header">
                    <h1>Layanan Expired</h1>
                    <p>Akses Mikhmon Ditangguhkan</p>
                </div>
                
                <div class="content">
                    <div class="status-badge">
                        ❌ Layanan Tidak Aktif
                    </div>
                    
                    <div class="expired-info">
                        <div class="icon">⚠️</div>
                        <div class="title">Layanan Telah Berakhir</div>
                        <div class="duration">
                            <?php if ($expired_days > 0): ?>
                                Expired sejak <?= $expired_days ?> hari <?= $expired_hours ?> jam lalu
                            <?php else: ?>
                                Expired sejak <?= $expired_hours ?> jam lalu
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h3>
                            <span>👤</span>
                            Informasi Akun
                        </h3>
                        <div class="info-item">
                            <span class="info-label">Nama:</span>
                            <span class="info-value"><?= htmlspecialchars($client_info['nama']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Domain:</span>
                            <span class="info-value"><?= htmlspecialchars($client_info['subdomain']) ?>.octonet.my.id</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?= censorEmail($client_info['email']) ?></span>
                        </div>
                        <?php if (!empty($client_info['no_wa'])): ?>
                        <div class="info-item">
                            <span class="info-label">No. WA:</span>
                            <span class="info-value"><?= censorPhone($client_info['no_wa']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Expired:</span>
                            <span class="info-value"><?= date('d M Y, H:i', strtotime($client_info['tanggal_expired'])) ?> WIB</span>
                        </div>
                    </div>
                    
                    <div class="countdown">
                        🔄 Auto refresh dalam <span class="countdown-number" id="countdown">90</span> detik
                    </div>
                    
                    <div class="actions">
                        <a href="https://payment.octonet.my.id/renewal.php?subdomain=<?= urlencode($subdomain) ?>&client_id=<?= $client_info['id'] ?>" class="btn btn-primary" target="_blank">
                            <span>💳</span>
                            <span>Perpanjang Layanan</span>
                        </a>
                        
                        <a href="#" onclick="window.location.reload(); return false;" class="btn btn-warning">
                            <span>🔍</span>
                            <span>Cek Status Pembayaran</span>
                        </a>
                        
                        <button onclick="window.location.reload()" class="btn btn-secondary">
                            <span>🔄</span>
                            <span>Refresh & Cek Ulang</span>
                        </button>
                    </div>
                    
                    <div class="footer-note">
                        <span>🔒</span> Halaman ini akan tetap muncul sampai layanan diperpanjang.
                        <br>Silakan lakukan perpanjangan untuk melanjutkan akses.
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Countdown functionality
            let timeLeft = 90;
            const countdownElement = document.getElementById('countdown');
            
            const countdownInterval = setInterval(function() {
                timeLeft--;
                countdownElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    countdownElement.textContent = '...';
                    window.location.reload();
                }
            }, 1000);
            
            // Prevent viewport zoom on iOS
            document.addEventListener('touchstart', function() {}, {passive: true});
            
            // Handle viewport changes
            function setViewportHeight() {
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', vh + 'px');
            }
            
            window.addEventListener('resize', setViewportHeight);
            window.addEventListener('orientationchange', function() {
                setTimeout(setViewportHeight, 100);
            });
            
            setViewportHeight();
            
            // Prevent right click and key shortcuts
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.keyCode === 116 || (e.ctrlKey && e.keyCode === 82) || e.keyCode === 27 || (e.altKey && e.keyCode === 115) || (e.ctrlKey && e.keyCode === 87)) {
                    e.preventDefault();
                    if (e.keyCode === 116 || (e.ctrlKey && e.keyCode === 82)) {
                        window.location.reload();
                    }
                    return false;
                }
            });
        </script>
    </body>
    </html>
    <?php
}
?>