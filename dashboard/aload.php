<?php
session_start();
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
    exit;
}

$load = $_GET['load'];
$session = $_GET['session'];

// Include necessary files
include('../include/config.php');
include('../include/readcfg.php');
include_once('../lib/routeros_api.class.php');
include_once('../lib/formatbytesbites.php');
include_once('../include/cache.php');

$API = new RouterosAPI();
$API->debug = false;

// New endpoint: Dashboard stats with caching (OPTIMIZED)
if ($load == "dashstats") {
    header('Content-Type: application/json');

    // Connect to Mikrotik
    if ($API->connect($iphost, $userhost, $passwdhost)) {

        // Use caching to reduce Mikrotik API calls
        // Cache for 30 seconds (can be adjusted)
        $cacheTTL = 30;

        $response = array(
            'success' => true,
            'data' => array()
        );

        // Get system clock (cache: 30s)
        $getclock = getCachedApiData($API, "/system/clock/print", array(), $cacheTTL);
        if ($getclock && isset($getclock[0])) {
            $response['data']['clock'] = $getclock[0];

            // Update session timezone
            if (isset($getclock[0]['time-zone-name'])) {
                $_SESSION['timezone'] = $getclock[0]['time-zone-name'];
            }
        }

        // Get system resource (cache: 30s)
        $getresource = getCachedApiData($API, "/system/resource/print", array(), $cacheTTL);
        if ($getresource && isset($getresource[0])) {
            $response['data']['resource'] = $getresource[0];
        }

        // Get routerboard info (cache: 60s - rarely changes)
        $getrouterboard = getCachedApiData($API, "/system/routerboard/print", array(), 60);
        if ($getrouterboard && isset($getrouterboard[0])) {
            $response['data']['routerboard'] = $getrouterboard[0];
        }

        // Get hotspot users count (cache: 30s)
        $countallusers = getCachedApiData($API, "/ip/hotspot/user/print", array("count-only" => ""), $cacheTTL);
        $response['data']['countallusers'] = is_numeric($countallusers) ? intval($countallusers) : 0;

        // Get hotspot active count (cache: 15s - more dynamic)
        $counthotspotactive = getCachedApiData($API, "/ip/hotspot/active/print", array("count-only" => ""), 15);
        $response['data']['counthotspotactive'] = is_numeric($counthotspotactive) ? intval($counthotspotactive) : 0;

        $API->disconnect();

        echo json_encode($response);
    } else {
        echo json_encode(array(
            'success' => false,
            'error' => 'Failed to connect to Mikrotik'
        ));
    }
    exit;
}

// New endpoint: Selling report data with caching
if ($load == "sellingdata") {
    header('Content-Type: application/json');

    // Increase limits for large datasets
    set_time_limit(120); // 2 minutes max execution time
    ini_set('memory_limit', '256M');

    $idhr = isset($_GET['idhr']) ? $_GET['idhr'] : '';
    $idbl = isset($_GET['idbl']) ? $_GET['idbl'] : '';
    $prefix = isset($_GET['prefix']) ? $_GET['prefix'] : '';

    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {

        $cacheTTL = 60; // Cache for 60 seconds

        // Determine which data to fetch
        if (strlen($idhr) > 0) {
            $getData = getCachedApiData($API, "/system/script/print", array("?source" => "$idhr"), $cacheTTL);
        } elseif (strlen($idbl) > 0) {
            $getData = getCachedApiData($API, "/system/script/print", array("?owner" => "$idbl"), $cacheTTL);
        } else {
            $getData = getCachedApiData($API, "/system/script/print", array("?comment" => "mikhmon"), $cacheTTL);
        }

        $API->disconnect();

        // Process data
        $processedData = array();
        $totalPrice = 0;

        if ($getData && is_array($getData)) {
            foreach ($getData as $item) {
                $getname = explode("-|-", $item['name']);

                // Apply prefix filter if specified
                if ($prefix != "" && substr($getname[2], 0, strlen($prefix)) != $prefix) {
                    continue;
                }

                $row = array(
                    'date' => isset($getname[0]) ? $getname[0] : '',
                    'time' => isset($getname[1]) ? $getname[1] : '',
                    'username' => isset($getname[2]) ? $getname[2] : '',
                    'price' => isset($getname[3]) ? $getname[3] : '0',
                    'profile' => isset($getname[7]) ? $getname[7] : '',
                    'comment' => isset($getname[8]) ? $getname[8] : ''
                );

                $processedData[] = $row;
                $totalPrice += floatval($row['price']);
            }
        }

        echo json_encode(array(
            'success' => true,
            'data' => $processedData,
            'total' => count($processedData),
            'totalPrice' => $totalPrice
        ));

    } else {
        echo json_encode(array(
            'success' => false,
            'error' => 'Failed to connect to Mikrotik'
        ));
    }
    exit;
}

// New endpoint: Hotspot users data with caching
if ($load == "hotspotusers") {
    header('Content-Type: application/json');

    $prof = isset($_GET['prof']) ? $_GET['prof'] : 'all';
    $comm = isset($_GET['comm']) ? $_GET['comm'] : '';

    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {

        $cacheTTL = 30; // Cache for 30 seconds - user data is dynamic

        // Fetch users based on filter
        if ($prof == 'all') {
            $getuser = getCachedApiData($API, "/ip/hotspot/user/print", array(), $cacheTTL);
        } else {
            $getuser = getCachedApiData($API, "/ip/hotspot/user/print",
                      array("?profile" => $prof), $cacheTTL);
        }

        $API->disconnect();

        // Process user data with formatting
        $processedUsers = array();

        if ($getuser && is_array($getuser)) {
            foreach ($getuser as $user) {
                // Apply comment filter if specified
                if ($comm != "" && isset($user['comment']) && substr($user['comment'], 0, strlen($comm)) != $comm) {
                    continue;
                }

                // Format data using helper functions
                $server = isset($user['server']) && $user['server'] !== null ? $user['server'] : '';
                $macaddress = isset($user['mac-address']) && $user['mac-address'] !== null ? $user['mac-address'] : '';
                $comment = isset($user['comment']) && $user['comment'] !== null ? $user['comment'] : '';
                $uptime = isset($user['uptime']) && $user['uptime'] !== null ? formatDTM($user['uptime']) : '';
                $bytesin = isset($user['bytes-in']) && $user['bytes-in'] !== null ? formatBytes($user['bytes-in'], 2) : '0 B';
                $bytesout = isset($user['bytes-out']) && $user['bytes-out'] !== null ? formatBytes($user['bytes-out'], 2) : '0 B';
                $disabled = isset($user['disabled']) && $user['disabled'] !== null ? $user['disabled'] : 'false';
                $timelimit = isset($user['limit-uptime']) && $user['limit-uptime'] !== null ?
                            ($user['limit-uptime'] == '1s' ? ' expired' : ' ' . $user['limit-uptime']) : '';
                $datalimit = isset($user['limit-bytes-total']) && $user['limit-bytes-total'] !== null && $user['limit-bytes-total'] !== '' ?
                            ' ' . formatBytes($user['limit-bytes-total'], 2) : '';

                $row = array(
                    'id' => $user['.id'],
                    'server' => $server,
                    'name' => $user['name'],
                    'password' => isset($user['password']) ? $user['password'] : '',
                    'profile' => isset($user['profile']) ? $user['profile'] : '',
                    'macaddress' => $macaddress,
                    'uptime' => $uptime,
                    'bytesin' => $bytesin,
                    'bytesout' => $bytesout,
                    'comment' => $comment,
                    'disabled' => $disabled,
                    'timelimit' => $timelimit,
                    'datalimit' => $datalimit
                );

                $processedUsers[] = $row;
            }
        }

        echo json_encode(array(
            'success' => true,
            'data' => $processedUsers,
            'total' => count($processedUsers)
        ));

    } else {
        echo json_encode(array(
            'success' => false,
            'error' => 'Failed to connect to Mikrotik'
        ));
    }
    exit;
}

// New endpoint: Resume report data for charts
if ($load == "resumedata") {
    header('Content-Type: application/json');

    $idbl = isset($_GET['idbl']) ? $_GET['idbl'] : '';

    if (empty($idbl)) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Missing idbl parameter'
        ));
        exit;
    }

    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {

        $cacheTTL = 120; // Cache for 2 minutes - report data doesn't change often

        // Fetch selling data for the month
        $getData = getCachedApiData($API, "/system/script/print",
                  array("?owner" => $idbl), $cacheTTL);

        $API->disconnect();

        // Process data for resume
        $dataresume = "";
        $totalresume = 0;
        $totalvrc = 0;
        $sampleDates = array(); // For debugging

        if ($getData && is_array($getData)) {
            foreach ($getData as $item) {
                $getname = explode("-|-", $item['name']);
                if (count($getname) >= 4) {
                    $date = isset($getname[0]) ? $getname[0] : '';
                    $price = isset($getname[3]) ? $getname[3] : '0';

                    // Store sample dates for debugging (first 5 only)
                    if (count($sampleDates) < 5) {
                        $sampleDates[] = $date;
                    }

                    $dataresume .= $date . $price;
                    $totalresume += floatval($price);
                    $totalvrc++;
                }
            }
        }

        echo json_encode(array(
            'success' => true,
            'dataresume' => $dataresume,
            'totalresume' => $totalresume,
            'totalvrc' => $totalvrc,
            'idbl' => $idbl,
            'sampleDates' => $sampleDates, // For debugging date format
            'totalRecords' => count($getData)
        ));

    } else {
        echo json_encode(array(
            'success' => false,
            'error' => 'Failed to connect to Mikrotik'
        ));
    }
    exit;
}

if ($load == "logs") {
    // Enhanced Hotspot Logs with Modern Design
?>
<div id="r_3" class="compact-card logs-card-enhanced">
    <div class="compact-header logs-header">
        <div class="header-left">
            <a onclick="cancelPage()" href="./?hotspot=log&session=<?= $session; ?>" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <i class="fa fa-align-justify logs-icon"></i>
                <span>Hotspot Logs</span>
            </a>
        </div>
        <div class="logs-status">
            <div class="logs-indicator">
                <div class="activity-dot"></div>
                <span class="activity-text">Live</span>
            </div>
        </div>
    </div>
    <div class="compact-body logs-body">
        
        <!-- Enhanced CSS for Logs -->
        <style>
        .logs-card-enhanced {
            background: linear-gradient(135deg, #2d2d2d 0%, #383838 100%) !important;
            border: 1px solid #4a9eff !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .logs-card-enhanced::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 3px !important;
            background: linear-gradient(90deg, #4a9eff 0%, #00d084 50%, #ff9500 100%) !important;
        }
        
        .logs-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            background: linear-gradient(135deg, #1f3a4d 0%, #2d2d2d 100%) !important;
        }
        
        .header-left {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
        
        .logs-icon {
            font-size: 16px !important;
            color: #4a9eff !important;
            animation: logsPulse 3s ease-in-out infinite !important;
        }
        
        @keyframes logsPulse {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
        }
        
        .logs-status {
            display: flex !important;
            align-items: center !important;
        }
        
        .logs-indicator {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
        }
        
        .activity-dot {
            width: 8px !important;
            height: 8px !important;
            background: #00d084 !important;
            border-radius: 50% !important;
            animation: pulse 2s infinite !important;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 208, 132, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(0, 208, 132, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 208, 132, 0); }
        }
        
        .activity-text {
            font-size: 10px !important;
            color: #00d084 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            font-weight: 600 !important;
        }
        
        .logs-body {
            padding: 8px 12px !important;
        }
        
        .logs-container {
            max-height: 280px !important;
            overflow-y: auto !important;
            background: rgba(255,255,255,0.02) !important;
            border-radius: 8px !important;
            border: 1px solid #404040 !important;
            position: relative !important;
        }
        
        .logs-table-enhanced {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10px !important;
            margin: 0 !important;
        }
        
        .logs-table-enhanced thead {
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
        }
        
        .logs-table-enhanced th {
            background: linear-gradient(135deg, #383838 0%, #2d2d2d 100%) !important;
            color: #b0b0b0 !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 9px !important;
            padding: 10px 8px !important;
            border-bottom: 2px solid #4a9eff !important;
            text-align: left !important;
            letter-spacing: 0.5px !important;
        }
        
        .logs-table-enhanced th i {
            margin-right: 4px !important;
            opacity: 0.8 !important;
            font-size: 10px !important;
        }
        
        .logs-table-enhanced td {
            padding: 8px !important;
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
            color: #ffffff !important;
            font-size: 10px !important;
            vertical-align: top !important;
        }
        
        .logs-table-enhanced tbody tr {
            transition: all 0.2s ease !important;
        }
        
        .logs-table-enhanced tbody tr:hover {
            background: rgba(74, 158, 255, 0.1) !important;
            transform: translateX(2px) !important;
        }
        
        .logs-table-enhanced tbody tr:nth-child(even) {
            background: rgba(255,255,255,0.02) !important;
        }
        
        .logs-table-enhanced tbody tr:nth-child(even):hover {
            background: rgba(74, 158, 255, 0.15) !important;
        }
        
        /* Log entry types styling */
        .log-entry-time {
            color: #b0b0b0 !important;
            font-family: 'Courier New', monospace !important;
            font-size: 9px !important;
        }
        
        .log-entry-user {
            color: #4a9eff !important;
            font-weight: 600 !important;
            font-size: 10px !important;
        }
        
        .log-entry-message {
            color: #ffffff !important;
        }
        
        .log-entry-message.login {
            color: #00d084 !important;
        }
        
        .log-entry-message.logout {
            color: #ff9500 !important;
        }
        
        .log-entry-message.error {
            color: #ff5757 !important;
        }
        
        /* Loading state */
        .logs-loading {
            text-align: center !important;
            padding: 30px 20px !important;
            color: #888888 !important;
        }
        
        .logs-loading-content {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 12px !important;
        }
        
        .loading-dots {
            display: flex !important;
            gap: 4px !important;
        }
        
        .loading-dots .dot {
            width: 8px !important;
            height: 8px !important;
            background: #4a9eff !important;
            border-radius: 50% !important;
            animation: dotPulse 1.4s ease-in-out infinite both !important;
        }
        
        .loading-dots .dot:nth-child(1) { animation-delay: -0.32s !important; }
        .loading-dots .dot:nth-child(2) { animation-delay: -0.16s !important; }
        .loading-dots .dot:nth-child(3) { animation-delay: 0s !important; }
        
        @keyframes dotPulse {
            0%, 80%, 100% { transform: scale(0); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }
        
        .loading-text {
            font-size: 11px !important;
            color: #b0b0b0 !important;
        }
        
        /* Empty state */
        .logs-empty {
            text-align: center !important;
            padding: 40px 20px !important;
            color: #888888 !important;
        }
        
        .logs-empty-icon {
            font-size: 24px !important;
            color: #4a9eff !important;
            margin-bottom: 12px !important;
            opacity: 0.6 !important;
        }
        
        .logs-empty-title {
            font-size: 12px !important;
            font-weight: 600 !important;
            color: #b0b0b0 !important;
            margin-bottom: 6px !important;
        }
        
        .logs-empty-subtitle {
            font-size: 10px !important;
            color: #888888 !important;
            line-height: 1.4 !important;
        }
        
        /* Error state */
        .logs-error {
            text-align: center !important;
            padding: 30px 20px !important;
            color: #ff5757 !important;
        }
        
        .logs-error-icon {
            font-size: 20px !important;
            margin-bottom: 10px !important;
        }
        
        .logs-error-title {
            font-size: 12px !important;
            font-weight: 600 !important;
            margin-bottom: 6px !important;
        }
        
        .logs-error-subtitle {
            font-size: 10px !important;
            color: #b0b0b0 !important;
        }
        
        /* Custom scrollbar */
        .logs-container::-webkit-scrollbar {
            width: 6px !important;
        }
        
        .logs-container::-webkit-scrollbar-track {
            background: #2d2d2d !important;
            border-radius: 3px !important;
        }
        
        .logs-container::-webkit-scrollbar-thumb {
            background: #4a9eff !important;
            border-radius: 3px !important;
            transition: background 0.3s ease !important;
        }
        
        .logs-container::-webkit-scrollbar-thumb:hover {
            background: #3a8ae8 !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .logs-table-enhanced th,
            .logs-table-enhanced td {
                padding: 6px 4px !important;
                font-size: 9px !important;
            }
            
            .logs-container {
                max-height: 240px !important;
            }
        }
        </style>
        
        <div class="logs-container">
            <table class="logs-table-enhanced">
                <thead>
                    <tr>
                        <th><i class="fa fa-clock-o"></i> Time</th>
                        <th><i class="fa fa-user"></i> User (IP)</th>
                        <th><i class="fa fa-comment"></i> Activity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $API->connect($iphost, $userhost, decrypt($passwdhost));
                        
                        // Setup logging (from original working code)
                        $getlogging = $API->comm("/system/logging/print", array("?prefix" => "->"));
                        if (empty($getlogging) || $getlogging[0]['prefix'] != "->") {
                            $API->comm("/system/logging/add", array(
                                "action" => "disk", 
                                "prefix" => "->", 
                                "topics" => "hotspot,info,debug"
                            ));
                        }
                        
                        // Get logs (from original working code)
                        $getlog = $API->comm("/log/print", array("?topics" => "hotspot,info,debug"));
                        $log = array_reverse($getlog);
                        
                        if (!empty($log)) {
                            $displayed = 0;
                            $hasValidLogs = false;
                            
                            // Use original parsing logic (exactly from working file)
                            for ($i = 0; $i < count($log) && $displayed < 15; $i++) {
                                if (isset($log[$i]['message']) && isset($log[$i]['time'])) {
                                    $mess = explode(":", $log[$i]['message']);
                                    $time = $log[$i]['time'];
                                    
                                    // Only show logs with "->" prefix (from original logic)
                                    if (substr($log[$i]['message'], 0, 2) == "->") {
                                        $hasValidLogs = true;
                                        
                                        echo "<tr>";
                                        
                                        // Time column
                                        echo "<td><span class='log-entry-time'>" . htmlspecialchars($time) . "</span></td>";
                                        
                                        // User/IP column (exact original logic)
                                        echo "<td>";
                                        if (count($mess) > 6) {
                                            echo "<span class='log-entry-user'>" . htmlspecialchars($mess[1] . ":" . $mess[2] . ":" . $mess[3] . ":" . $mess[4] . ":" . $mess[5] . ":" . $mess[6]) . "</span>";
                                        } else {
                                            echo "<span class='log-entry-user'>" . htmlspecialchars($mess[1]) . "</span>";
                                        }
                                        echo "</td>";
                                        
                                        // Message column (exact original logic with enhanced styling)
                                        echo "<td>";
                                        if (count($mess) > 6) {
                                            $message = str_replace("trying to", "", $mess[7] . " " . $mess[8] . " " . $mess[9] . " " . $mess[10]);
                                        } else {
                                            $message = str_replace("trying to", "", $mess[2] . " " . $mess[3] . " " . $mess[4] . " " . $mess[5]);
                                        }
                                        
                                        // Add CSS class based on message content
                                        $messageClass = 'log-entry-message';
                                        if (stripos($message, 'login') !== false || stripos($message, 'logged in') !== false) {
                                            $messageClass .= ' login';
                                        } elseif (stripos($message, 'logout') !== false || stripos($message, 'logged out') !== false) {
                                            $messageClass .= ' logout';
                                        } elseif (stripos($message, 'error') !== false || stripos($message, 'failed') !== false) {
                                            $messageClass .= ' error';
                                        }
                                        
                                        echo "<span class='" . $messageClass . "'>" . htmlspecialchars(trim($message)) . "</span>";
                                        echo "</td>";
                                        echo "</tr>";
                                        
                                        $displayed++;
                                    }
                                }
                            }
                            
                            if (!$hasValidLogs) {
                                echo '<tr>
                                        <td colspan="3" class="logs-empty">
                                            <div class="logs-empty-icon">
                                                <i class="fa fa-info-circle"></i>
                                            </div>
                                            <div class="logs-empty-title">No Activity Logs Found</div>
                                            <div class="logs-empty-subtitle">
                                                No hotspot logs with prefix "->" detected.<br>
                                                Logging may need a few minutes to initialize.
                                            </div>
                                        </td>
                                      </tr>';
                            }
                            
                        } else {
                            echo '<tr>
                                    <td colspan="3" class="logs-empty">
                                        <div class="logs-empty-icon">
                                            <i class="fa fa-database"></i>
                                        </div>
                                        <div class="logs-empty-title">No Log Entries</div>
                                        <div class="logs-empty-subtitle">
                                            The system log is empty or logging is not yet configured.<br>
                                            Please wait for logging to start.
                                        </div>
                                    </td>
                                  </tr>';
                        }
                        
                    } catch (Exception $e) {
                        echo '<tr>
                                <td colspan="3" class="logs-error">
                                    <div class="logs-error-icon">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="logs-error-title">Error Loading Logs</div>
                                    <div class="logs-error-subtitle">' . htmlspecialchars($e->getMessage()) . '</div>
                                </td>
                              </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
} else {
    // Keep original sysresource and hotspot loads unchanged
    if ($load == "sysresource") {
        $API->connect($iphost, $userhost, decrypt($passwdhost));
        
        $getclock = $API->comm("/system/clock/print");
        $clock = $getclock[0];
        $timezone = $getclock[0]['time-zone-name'];
        date_default_timezone_set($timezone);

        $getresource = $API->comm("/system/resource/print");
        $resource = $getresource[0];

        $getrouterboard = $API->comm("/system/routerboard/print");
        $routerboard = $getrouterboard[0];
?>
<div id="r_1" class="compact-card system-status-card">
    <div class="compact-header system-header">
        <i class="fa fa-server"></i>
        System Status
        <div class="status-indicator">
            <div class="status-dot online"></div>
            <span>Online</span>
        </div>
    </div>
    <div class="compact-body">
        
        <!-- Primary Stats Row -->
        <div class="primary-stats">
            <div class="stat-item cpu-stat">
                <div class="stat-icon">
                    <i class="fa fa-microchip"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">CPU Load</div>
                    <div class="stat-value"><?= $resource['cpu-load'] ?>%</div>
                    <div class="stat-bar">
                        <div class="stat-progress" style="width: <?= $resource['cpu-load'] ?>%"></div>
                    </div>
                </div>
            </div>
            
            <div class="stat-item memory-stat">
                <div class="stat-icon">
                    <i class="fa fa-memory"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Free Memory</div>
                    <div class="stat-value"><?= formatBytes($resource['free-memory'], 1) ?></div>
                    <div class="memory-visual">
                        <div class="memory-bar"></div>
                    </div>
                </div>
            </div>
            
            <div class="stat-item uptime-stat">
                <div class="stat-icon">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Uptime</div>
                    <div class="stat-value"><?= formatDTM($resource['uptime']) ?></div>
                    <div class="uptime-pulse">
                        <div class="pulse-dot"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Secondary Info Grid -->
        <div class="secondary-info">
            <div class="info-pair">
                <div class="info-icon"><i class="fa fa-calendar"></i></div>
                <div class="info-text">
                    <div class="info-small">Date & Time</div>
                    <div class="info-main"><?= ucfirst($clock['date']) . " " . $clock['time'] ?></div>
                </div>
            </div>
            
            <div class="info-pair">
                <div class="info-icon"><i class="fa fa-info-circle"></i></div>
                <div class="info-text">
                    <div class="info-small">Board</div>
                    <div class="info-main"><?= $resource['board-name'] ?></div>
                </div>
            </div>
            
            <div class="info-pair">
                <div class="info-icon"><i class="fa fa-cog"></i></div>
                <div class="info-text">
                    <div class="info-small">RouterOS</div>
                    <div class="info-main"><?= $resource['version'] ?></div>
                </div>
            </div>
            
            <div class="info-pair">
                <div class="info-icon"><i class="fa fa-hdd-o"></i></div>
                <div class="info-text">
                    <div class="info-small">Free HDD</div>
                    <div class="info-main"><?= formatBytes($resource['free-hdd-space'], 1) ?></div>
                </div>
            </div>
        </div>
        
    </div>
</div>
<?php
        $_SESSION[$session.'sdate'] = $clock['date'];
        
    } elseif ($load == "hotspot") {
        $API->connect($iphost, $userhost, decrypt($passwdhost));
        
        $countallusers = $API->comm("/ip/hotspot/user/print", array("count-only" => ""));
        $counthotspotactive = $API->comm("/ip/hotspot/active/print", array("count-only" => ""));
?>

<!-- Inject CSS directly into the response -->
<style>
/* Hotspot Management Enhanced Styles - Inline */
.hotspot-management-card {
    background: linear-gradient(135deg, #2d2d2d 0%, #383838 100%) !important;
    border: 1px solid #4a9eff !important;
    position: relative !important;
    overflow: hidden !important;
}

.hotspot-management-card::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    height: 3px !important;
    background: linear-gradient(90deg, #4a9eff 0%, #00d084 50%, #ff9500 75%, #ff5757 100%) !important;
}

.hotspot-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    background: linear-gradient(135deg, #3a4149 0%, #2d2d2d 100%) !important;
}

.header-left {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.header-wifi-icon {
    font-size: 16px !important;
    color: #4a9eff !important;
    animation: wifiPulse 3s ease-in-out infinite !important;
}

.header-status {
    display: flex !important;
    align-items: center !important;
}

.wifi-indicator {
    display: flex !important;
    align-items: flex-end !important;
    gap: 2px !important;
    height: 16px !important;
}

.wifi-wave {
    width: 3px !important;
    background: #4a9eff !important;
    border-radius: 2px !important;
    animation: waveAnimation 2s ease-in-out infinite !important;
}

.wave-1 { height: 6px !important; animation-delay: 0s !important; }
.wave-2 { height: 10px !important; animation-delay: 0.2s !important; }
.wave-3 { height: 14px !important; animation-delay: 0.4s !important; }

.hotspot-grid-enhanced {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 12px !important;
    padding: 5px !important;
}

.hotspot-card {
    background: #383838 !important;
    border: 1px solid #404040 !important;
    border-radius: 12px !important;
    padding: 16px !important;
    text-decoration: none !important;
    color: inherit !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    position: relative !important;
    overflow: hidden !important;
    transition: all 0.3s ease !important;
    cursor: pointer !important;
}

.hotspot-card:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 25px rgba(0,0,0,0.4) !important;
    text-decoration: none !important;
    color: inherit !important;
}

.hotspot-card::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.03) 100%) !important;
    opacity: 0 !important;
    transition: opacity 0.3s ease !important;
}

.hotspot-card:hover::before {
    opacity: 1 !important;
}

.card-icon-container {
    position: relative !important;
    flex-shrink: 0 !important;
}

.card-icon {
    width: 40px !important;
    height: 40px !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 18px !important;
    position: relative !important;
    z-index: 2 !important;
    transition: all 0.3s ease !important;
}

.active-card .card-icon {
    background: linear-gradient(135deg, #4a9eff 0%, #3a8ae8 100%) !important;
    color: white !important;
}

.users-card .card-icon {
    background: linear-gradient(135deg, #00d084 0%, #00b574 100%) !important;
    color: white !important;
}

.add-card .card-icon {
    background: linear-gradient(135deg, #ff9500 0%, #e6850e 100%) !important;
    color: white !important;
}

.generate-card .card-icon {
    background: linear-gradient(135deg, #ff5757 0%, #e04848 100%) !important;
    color: white !important;
}

.status-ring {
    position: absolute !important;
    top: -3px !important;
    left: -3px !important;
    right: -3px !important;
    bottom: -3px !important;
    border-radius: 13px !important;
    z-index: 1 !important;
}

.active-ring {
    background: linear-gradient(45deg, #4a9eff, transparent, #4a9eff) !important;
    animation: ringRotate 3s linear infinite !important;
}

.users-ring {
    background: linear-gradient(45deg, #00d084, transparent, #00d084) !important;
    animation: ringRotate 4s linear infinite !important;
}

.add-icon-bg {
    position: absolute !important;
    top: -5px !important;
    left: -5px !important;
    right: -5px !important;
    bottom: -5px !important;
    background: radial-gradient(circle, rgba(255,149,0,0.2) 0%, transparent 70%) !important;
    border-radius: 15px !important;
    animation: addPulse 2s ease-in-out infinite !important;
}

.magic-sparkles {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
}

.sparkle {
    position: absolute !important;
    width: 4px !important;
    height: 4px !important;
    background: #ffd700 !important;
    border-radius: 50% !important;
    animation: sparkleAnimation 2s ease-in-out infinite !important;
}

.sparkle-1 { top: 5px !important; right: 5px !important; animation-delay: 0s !important; }
.sparkle-2 { bottom: 5px !important; left: 5px !important; animation-delay: 0.7s !important; }
.sparkle-3 { top: 50% !important; right: 2px !important; animation-delay: 1.4s !important; }

.card-content {
    flex: 1 !important;
    min-width: 0 !important;
}

.card-number {
    display: flex !important;
    align-items: baseline !important;
    gap: 4px !important;
    margin-bottom: 4px !important;
}

.number-display {
    font-size: 24px !important;
    font-weight: 700 !important;
    color: #ffffff !important;
    line-height: 1 !important;
}

.number-label {
    font-size: 10px !important;
    color: #888888 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

.add-symbol, .generate-symbol {
    font-size: 20px !important;
    font-weight: 700 !important;
    color: #ffffff !important;
}

.card-title {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #ffffff !important;
    margin-bottom: 2px !important;
    line-height: 1.2 !important;
}

.card-subtitle {
    font-size: 10px !important;
    color: #888888 !important;
    line-height: 1.2 !important;
}

.card-arrow {
    opacity: 0 !important;
    transform: translateX(-5px) !important;
    transition: all 0.3s ease !important;
    color: #888888 !important;
    font-size: 12px !important;
}

.hotspot-card:hover .card-arrow {
    opacity: 1 !important;
    transform: translateX(0) !important;
}

.hotspot-card:hover .card-icon {
    transform: scale(1.1) !important;
}

/* Animations */
@keyframes wifiPulse {
    0%, 100% { opacity: 0.7; }
    50% { opacity: 1; }
}

@keyframes waveAnimation {
    0%, 100% { opacity: 0.4; transform: scaleY(0.5); }
    50% { opacity: 1; transform: scaleY(1); }
}

@keyframes ringRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes addPulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.1); }
}

@keyframes sparkleAnimation {
    0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
    50% { opacity: 1; transform: scale(1) rotate(180deg); }
}
</style>

<div id="r_2" class="compact-card hotspot-management-card">
    <div class="compact-header hotspot-header">
        <div class="header-left">
            <i class="fa fa-wifi header-wifi-icon"></i>
            <span>Hotspot Management</span>
        </div>
        <div class="header-status">
            <div class="wifi-indicator">
                <div class="wifi-wave wave-1"></div>
                <div class="wifi-wave wave-2"></div>
                <div class="wifi-wave wave-3"></div>
            </div>
        </div>
    </div>
    <div class="compact-body">
        <div class="hotspot-grid-enhanced">
            
            <!-- Active Users -->
            <a class="hotspot-card active-card" onclick="cancelPage()" href="./?hotspot=active&session=<?= $session; ?>">
                <div class="card-icon-container">
                    <div class="card-icon">
                        <i class="fa fa-laptop"></i>
                    </div>
                    <div class="status-ring active-ring"></div>
                </div>
                <div class="card-content">
                    <div class="card-number" data-count="<?= $counthotspotactive; ?>">
                        <span class="number-display"><?= $counthotspotactive; ?></span>
                        <span class="number-label">active</span>
                    </div>
                    <div class="card-title">
                        Active Users
                    </div>
                    <div class="card-subtitle">
                        Currently online
                    </div>
                </div>
                <div class="card-arrow">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </a>
            
            <!-- Total Users -->
            <a class="hotspot-card users-card" onclick="cancelPage()" href="./?hotspot=users&profile=all&session=<?= $session; ?>">
                <div class="card-icon-container">
                    <div class="card-icon">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="status-ring users-ring"></div>
                </div>
                <div class="card-content">
                    <div class="card-number" data-count="<?= $countallusers; ?>">
                        <span class="number-display"><?= $countallusers; ?></span>
                        <span class="number-label">total</span>
                    </div>
                    <div class="card-title">
                        Total Users
                    </div>
                    <div class="card-subtitle">
                        All registered
                    </div>
                </div>
                <div class="card-arrow">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </a>
            
            <!-- Add User -->
            <a class="hotspot-card add-card" onclick="cancelPage()" href="./?hotspot-user=add&session=<?= $session; ?>">
                <div class="card-icon-container">
                    <div class="card-icon">
                        <i class="fa fa-user-plus"></i>
                    </div>
                    <div class="add-icon-bg"></div>
                </div>
                <div class="card-content">
                    <div class="card-number">
                        <span class="add-symbol">+</span>
                    </div>
                    <div class="card-title">
                        Add User
                    </div>
                    <div class="card-subtitle">
                        Create new user
                    </div>
                </div>
                <div class="card-arrow">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </a>
            
            <!-- Generate Users -->
            <a class="hotspot-card generate-card" onclick="cancelPage()" href="./?hotspot-user=generate&session=<?= $session; ?>">
                <div class="card-icon-container">
                    <div class="card-icon">
                        <i class="fa fa-magic"></i>
                    </div>
                    <div class="magic-sparkles">
                        <div class="sparkle sparkle-1"></div>
                        <div class="sparkle sparkle-2"></div>
                        <div class="sparkle sparkle-3"></div>
                    </div>
                </div>
                <div class="card-content">
                    <div class="card-number">
                        <span class="generate-symbol">⚡</span>
                    </div>
                    <div class="card-title">
                        Generate
                    </div>
                    <div class="card-subtitle">
                        Bulk user creation
                    </div>
                </div>
                <div class="card-arrow">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </a>
            
        </div>
    </div>
</div>
<?php
    }
}
?>