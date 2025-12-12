<?php
session_start();
// hide all error
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
	header("Location:../admin.php?id=login");
} else {
// load session MikroTik
	$session = $_GET['session'];
	$serveractive = $_GET['server'];
// load config
	include('../include/config.php');
	include('../include/readcfg.php');
	
// lang
  include('../include/lang.php');
  include('../lang/'.$langid.'.php');
// routeros api
	include_once('../lib/routeros_api.class.php');
	include_once('../lib/formatbytesbites.php');
	$API = new RouterosAPI();
	$API->debug = false;
	$API->connect($iphost, $userhost, decrypt($passwdhost));
	if ($serveractive != "") {
		$gethotspotactive = $API->comm("/ip/hotspot/active/print", array("?server" => "" . $serveractive . ""));
		$TotalReg = count($gethotspotactive);
		$counthotspotactive = $API->comm("/ip/hotspot/active/print", array(
			"count-only" => "", "?server" => "" . $serveractive . ""
		));
	} else {
		$gethotspotactive = $API->comm("/ip/hotspot/active/print");
		$TotalReg = count($gethotspotactive);
		$counthotspotactive = $API->comm("/ip/hotspot/active/print", array(
			"count-only" => "",
		));
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikhMon - Active Hotspot Users</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
            background: #0d1117;
            color: #ffffff;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 100%;
            padding: 15px;
        }

        /* ==================== HEADER STYLING (sama seperti users.php & generateuser.php) ==================== */
        .card-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%) !important;
            border: none !important;
            border-radius: 18px 18px 0 0 !important;
            padding: 20px 25px !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
            position: relative !important;
            overflow: hidden !important;
            margin-bottom: 0 !important;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #3498db;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
            margin-bottom: 15px;
        }

        .header-title i {
            font-size: 22px !important;
            color: #3498db !important;
            text-shadow: 0 0 15px rgba(52, 152, 219, 0.5) !important;
        }

        .header-info {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .info-badge {
            background: rgba(52, 58, 70, 0.8) !important;
            padding: 8px 15px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-badge i {
            color: #3498db;
        }

        .show-all-link {
            color: #3498db !important;
            text-decoration: none !important;
            padding: 8px 15px;
            border-radius: 15px;
            border: 1px solid rgba(52, 152, 219, 0.5);
            transition: all 0.3s ease;
            background: rgba(52, 152, 219, 0.1);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .show-all-link:hover {
            background: linear-gradient(135deg, #3498db, #2980b9) !important;
            color: #ffffff !important;
        }

        .status-online {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #27ae60;
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #27ae60;
        }

        /* ==================== REFRESH CONTROLS ==================== */
        .refresh-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .refresh-btn {
            background: rgba(52, 58, 70, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
            padding: 10px !important;
            border-radius: 10px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 40px !important;
            height: 40px !important;
        }

        .refresh-btn:hover {
            background: linear-gradient(135deg, #3498db, #2980b9) !important;
            border-color: #3498db !important;
        }

        .refresh-btn.loading {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .auto-refresh-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .auto-refresh-toggle input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3498db;
        }

        /* ==================== SEARCH CONTAINER ==================== */
        .search-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 45px;
            background: rgba(52, 58, 70, 0.9) !important;
            border: 2px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 12px !important;
            color: #ffffff !important;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #3498db !important;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2) !important;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            pointer-events: none;
            font-size: 16px;
        }

        .clear-search {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }

        .clear-search.show {
            opacity: 1;
            visibility: visible;
        }

        .clear-search:hover {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .search-filters {
            display: flex;
            gap: 8px;
        }

        .filter-select {
            padding: 10px 12px;
            background: rgba(52, 58, 70, 0.9) !important;
            border: 2px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 10px !important;
            color: #ffffff !important;
            font-size: 0.875rem;
            outline: none;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 16 16'%3e%3cpath d='m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 10px center !important;
            background-size: 12px !important;
            padding-right: 35px !important;
        }

        .filter-select:focus {
            border-color: #3498db !important;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2) !important;
        }

        .filter-select option {
            background: #34495e !important;
            color: #ffffff !important;
            padding: 8px !important;
        }

        /* ==================== CARD STYLING ==================== */
        .card {
            background: rgba(40, 44, 52, 0.95) !important;
            border-radius: 18px !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
            overflow: hidden !important;
            transition: all 0.3s ease !important;
            margin-bottom: 20px;
        }

        .card:hover {
            border-color: rgba(52, 152, 219, 0.3) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
        }

        .data-card {
            background: rgba(40, 44, 52, 0.95) !important;
            border-radius: 0 0 18px 18px !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-top: none !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
            overflow: hidden !important;
        }

        /* ==================== TABLE STYLING ==================== */
        .table-container {
            overflow-x: auto;
            background: rgba(45, 52, 64, 0.9) !important;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
        }

        .modern-table th {
            background: linear-gradient(135deg, #0f4c75, #3282b8) !important;
            color: white !important;
            padding: 15px 12px !important;
            text-align: left;
            font-weight: 600 !important;
            font-size: 0.875rem;
            border: none !important;
            white-space: nowrap;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }

        .modern-table td {
            padding: 12px !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            vertical-align: middle;
            font-size: 0.875rem;
            border: none !important;
            color: #ffffff !important;
        }

        .modern-table tr {
            background: rgba(52, 58, 70, 0.8) !important;
            transition: all 0.3s ease !important;
        }

        .modern-table tr:nth-child(even) {
            background: rgba(45, 52, 64, 0.9) !important;
        }

        .modern-table tr:hover {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.15), rgba(41, 128, 185, 0.15)) !important;
        }

        .text-right {
            text-align: right;
        }

        /* ==================== MOBILE CARDS ==================== */
        .mobile-cards {
            display: none;
            gap: 15px;
            padding: 20px;
            background: rgba(45, 52, 64, 0.9) !important;
        }

        .user-card {
            background: rgba(52, 58, 70, 0.9) !important;
            border-radius: 12px !important;
            padding: 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            transition: all 0.3s ease !important;
        }

        .user-card:hover {
            border-color: rgba(52, 152, 219, 0.5) !important;
        }

        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-name {
            font-weight: 600;
            color: #3498db !important;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-details {
            display: grid;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-label {
            color: rgba(255, 255, 255, 0.7) !important;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-label i {
            color: #3498db;
            width: 16px;
        }

        .detail-value {
            color: #ffffff !important;
            font-weight: 600;
            text-align: right;
        }

        /* ==================== ACTION BUTTONS ==================== */
        .action-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            text-decoration: none !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
            border: 1px solid transparent !important;
            cursor: pointer !important;
        }

        .btn-remove {
            color: #e74c3c !important;
            border-color: rgba(231, 76, 60, 0.5) !important;
            background: rgba(231, 76, 60, 0.1) !important;
        }

        .btn-remove:hover {
            background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
            color: white !important;
        }

        .btn-server {
            color: #3498db !important;
            text-decoration: none !important;
            transition: all 0.3s ease !important;
        }

        .btn-server:hover {
            color: #ffffff !important;
            background: rgba(52, 152, 219, 0.2) !important;
            border-radius: 8px !important;
            padding: 4px 8px !important;
            transform: scale(1.05) !important;
        }

        .btn-user {
            color: #27ae60 !important;
            text-decoration: none !important;
            transition: all 0.3s ease !important;
        }

        .btn-user:hover {
            color: #ffffff !important;
            background: rgba(39, 174, 96, 0.2) !important;
            border-radius: 8px !important;
            padding: 4px 8px !important;
            transform: scale(1.05) !important;
        }

        /* ==================== NO RESULTS ==================== */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.6);
            background: rgba(45, 52, 64, 0.9) !important;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
            color: #3498db;
        }

        .no-results h4 {
            color: #ffffff;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .no-results p {
            font-size: 0.9rem;
        }

        /* ==================== SEARCH HIGHLIGHT ==================== */
        .search-highlight {
            background: rgba(255, 235, 59, 0.3) !important;
            color: #f39c12 !important;
            border-radius: 3px;
            padding: 0.1rem 0.2rem;
        }

        /* ==================== RESPONSIVE DESIGN ==================== */
        @media (max-width: 768px) {
            .table-container {
                display: none;
            }
            
            .mobile-cards {
                display: flex;
                flex-direction: column;
            }

            .header-title {
                font-size: 1.3rem;
            }

            .header-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .container {
                padding: 12px;
            }

            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: unset;
                max-width: unset;
            }

            .search-filters {
                justify-content: center;
            }

            .refresh-controls {
                flex-direction: row;
                justify-content: center;
                gap: 15px;
            }

            .card-header {
                padding: 15px 20px !important;
            }
        }

        @media (max-width: 480px) {
            .user-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .detail-value {
                text-align: left;
            }

            .header-info {
                gap: 8px;
            }

            .info-badge, .show-all-link {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
        }

        /* ==================== LOADING STATES ==================== */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* ==================== PERFORMANCE OPTIMIZATION ==================== */
        
        /* Reduce motion for users who prefer it */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Optimize animations with will-change */
        .modern-table tr {
            will-change: background-color;
        }

        .user-card {
            will-change: border-color;
        }

        .btn-remove:hover, .btn-server:hover, .btn-user:hover {
            will-change: background-color, color;
        }

        /* ==================== SCROLLBAR STYLING ==================== */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(45, 52, 64, 0.9);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #3498db;
        }

        /* ==================== ANIMATIONS ==================== */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ==================== CODE STYLING ==================== */
        code {
            background: rgba(52, 58, 70, 0.6);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: #e74c3c;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="reloadHotspotActive" class="fade-in">
            <!-- Header Card -->
            <div class="card">
                <div class="card-header">
                    <div class="header-title">
                        <i class="fas fa-wifi"></i>
                        <?= $_hotspot_active ?> 
                        <?php if ($serveractive != "") echo $serveractive; ?>
                    </div>
                    <div class="header-info">
                        <div class="info-badge">
                            <i class="fas fa-users"></i>
                            <span id="userCount">
                            <?php
                            if ($counthotspotactive < 2) {
                                echo "$counthotspotactive user";
                            } else {
                                echo "$counthotspotactive users";
                            }
                            ?>
                            </span>
                        </div>
                        <?php if ($serveractive != ""): ?>
                            <a href="./?hotspot=active&session=<?= $session ?>" class="show-all-link">
                                <i class="fas fa-search"></i> Show all servers
                            </a>
                        <?php endif; ?>
                        <div class="status-online">
                            <span class="status-dot"></span>
                            Live Data
                        </div>
                        <div class="refresh-controls">
                            <button type="button" id="manualRefresh" class="refresh-btn" title="Refresh data">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <label class="auto-refresh-toggle">
                                <input type="checkbox" id="autoRefreshToggle">
                                <span class="toggle-text">Auto Refresh</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Search Box -->
                    <div class="search-container">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchInput" placeholder="Search users, servers, IP, MAC address..." class="search-input">
                            <button type="button" id="clearSearch" class="clear-search" title="Clear search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="search-filters">
                            <select id="searchFilter" class="filter-select">
                                <option value="all">All Fields</option>
                                <option value="user">User</option>
                                <option value="server">Server</option>
                                <option value="address">IP Address</option>
                                <option value="mac">MAC Address</option>
                                <option value="loginby">Login By</option>
                                <option value="comment">Comment</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Data Card -->
                <div class="data-card">
                    <!-- No Results Message -->
                    <div id="noResults" class="no-results" style="display: none;">
                        <i class="fas fa-search"></i>
                        <div>
                            <h4>No users found</h4>
                            <p>Try adjusting your search criteria or clear the search to see all users.</p>
                        </div>
                    </div>
                    <!-- Desktop Table View -->
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><i class="fas fa-server"></i> Server</th>
                                    <th><i class="fas fa-user"></i> User</th>
                                    <th><i class="fas fa-map-marker-alt"></i> Address</th>
                                    <th><i class="fas fa-network-wired"></i> MAC Address</th>
                                    <th class="text-right"><i class="fas fa-clock"></i> Uptime</th>
                                    <th class="text-right"><i class="fas fa-download"></i> Bytes In</th>
                                    <th class="text-right"><i class="fas fa-upload"></i> Bytes Out</th>
                                    <th class="text-right"><i class="fas fa-hourglass-half"></i> Time Left</th>
                                    <th><i class="fas fa-sign-in-alt"></i> Login By</th>
                                    <th><i class="fas fa-comment"></i> <?= $_comment ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                for ($i = 0; $i < $TotalReg; $i++) {
                                    $hotspotactive = $gethotspotactive[$i];
                                    $id = $hotspotactive['.id'];
                                    $server = $hotspotactive['server'];
                                    $user = $hotspotactive['user'];
                                    $address = $hotspotactive['address'];
                                    $mac = $hotspotactive['mac-address'];
                                    $uptime = formatDTM($hotspotactive['uptime']);
                                    $usesstime = formatDTM($hotspotactive['session-time-left']);
                                    $bytesi = formatBytes($hotspotactive['bytes-in'], 2);
                                    $byteso = formatBytes($hotspotactive['bytes-out'], 2);
                                    $loginby = $hotspotactive['login-by'];
                                    $comment = $hotspotactive['comment'];
                                    $uriprocess = "'./?remove-user-active=" . $id . "&session=" . $session . "'";
                                    
                                    echo "<tr>";
                                    echo "<td><span class='action-btn btn-remove pointer' title='Remove " . $user . "' onclick='loadpage(".$uriprocess.")'><i class='fas fa-times'></i></span></td>";
                                    echo "<td><a class='btn-server' title='Filter " . $server . "' href='./?hotspot=active&server=" . $server . "&session=" . $session . "'><i class='fas fa-server'></i> " . $server . "</a></td>";
                                    echo "<td><a class='btn-user' title='Open User " . $user . "' href='./?hotspot-user=" . $user . "&session=" . $session . "'><i class='fas fa-user-edit'></i> " . $user . "</a></td>";
                                    echo "<td>" . $address . "</td>";
                                    echo "<td><code>" . $mac . "</code></td>";
                                    echo "<td class='text-right'>" . $uptime . "</td>";
                                    echo "<td class='text-right'>" . $bytesi . "</td>";
                                    echo "<td class='text-right'>" . $byteso . "</td>";
                                    echo "<td class='text-right'>" . $usesstime . "</td>";
                                    echo "<td>" . $loginby . "</td>";
                                    echo "<td>" . $comment . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards View -->
                    <div class="mobile-cards">
                        <?php
                        for ($i = 0; $i < $TotalReg; $i++) {
                            $hotspotactive = $gethotspotactive[$i];
                            $id = $hotspotactive['.id'];
                            $server = $hotspotactive['server'];
                            $user = $hotspotactive['user'];
                            $address = $hotspotactive['address'];
                            $mac = $hotspotactive['mac-address'];
                            $uptime = formatDTM($hotspotactive['uptime']);
                            $usesstime = formatDTM($hotspotactive['session-time-left']);
                            $bytesi = formatBytes($hotspotactive['bytes-in'], 2);
                            $byteso = formatBytes($hotspotactive['bytes-out'], 2);
                            $loginby = $hotspotactive['login-by'];
                            $comment = $hotspotactive['comment'];
                            $uriprocess = "'./?remove-user-active=" . $id . "&session=" . $session . "'";
                            ?>
                            <div class="user-card">
                                <div class="user-header">
                                    <div class="user-name">
                                        <i class="fas fa-user"></i> <?= $user ?>
                                    </div>
                                    <span class="action-btn btn-remove" title="Remove <?= $user ?>" onclick="loadpage(<?= $uriprocess ?>)">
                                        <i class="fas fa-times"></i>
                                    </span>
                                </div>
                                <div class="user-details">
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-server"></i> Server
                                        </span>
                                        <span class="detail-value">
                                            <a class="btn-server" href="./?hotspot=active&server=<?= $server ?>&session=<?= $session ?>"><?= $server ?></a>
                                        </span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-map-marker-alt"></i> IP Address
                                        </span>
                                        <span class="detail-value"><?= $address ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-network-wired"></i> MAC Address
                                        </span>
                                        <span class="detail-value"><code><?= $mac ?></code></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-clock"></i> Uptime
                                        </span>
                                        <span class="detail-value"><?= $uptime ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-download"></i> Downloaded
                                        </span>
                                        <span class="detail-value"><?= $bytesi ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-upload"></i> Uploaded
                                        </span>
                                        <span class="detail-value"><?= $byteso ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-hourglass-half"></i> Time Left
                                        </span>
                                        <span class="detail-value"><?= $usesstime ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-sign-in-alt"></i> Login By
                                        </span>
                                        <span class="detail-value"><?= $loginby ?></span>
                                    </div>
                                    <?php if (!empty($comment)): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-comment"></i> Comment
                                        </span>
                                        <span class="detail-value"><?= $comment ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        class HotspotSearch {
            constructor() {
                this.searchInput = document.getElementById('searchInput');
                this.searchFilter = document.getElementById('searchFilter');
                this.clearSearch = document.getElementById('clearSearch');
                this.userCount = document.getElementById('userCount');
                this.noResults = document.getElementById('noResults');
                this.tableRows = document.querySelectorAll('.modern-table tbody tr');
                this.mobileCards = document.querySelectorAll('.user-card');
                this.totalUsers = <?= $TotalReg ?>;
                
                this.init();
            }

            init() {
                // Event listeners
                this.searchInput.addEventListener('input', () => this.handleSearch());
                this.searchFilter.addEventListener('change', () => this.handleSearch());
                this.clearSearch.addEventListener('click', () => this.clearSearchInput());
                
                // Show/hide clear button
                this.searchInput.addEventListener('input', () => {
                    if (this.searchInput.value.length > 0) {
                        this.clearSearch.classList.add('show');
                    } else {
                        this.clearSearch.classList.remove('show');
                    }
                });
            }

            handleSearch() {
                const query = this.searchInput.value.toLowerCase().trim();
                const filter = this.searchFilter.value;
                let visibleCount = 0;

                if (query === '') {
                    this.showAllItems();
                    this.updateCounter(this.totalUsers);
                    this.hideNoResults();
                    return;
                }

                // Search in table rows (desktop)
                this.tableRows.forEach(row => {
                    if (this.matchesSearch(row, query, filter)) {
                        row.style.display = '';
                        this.highlightSearchTerms(row, query);
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Search in mobile cards
                this.mobileCards.forEach(card => {
                    if (this.matchesSearchCard(card, query, filter)) {
                        card.style.display = '';
                        this.highlightSearchTermsCard(card, query);
                        if (window.innerWidth <= 768) visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Update UI
                this.updateCounter(visibleCount);
                if (visibleCount === 0) {
                    this.showNoResults();
                } else {
                    this.hideNoResults();
                }
            }

            matchesSearch(row, query, filter) {
                const cells = row.children;
                
                switch (filter) {
                    case 'user':
                        return cells[2].textContent.toLowerCase().includes(query);
                    case 'server':
                        return cells[1].textContent.toLowerCase().includes(query);
                    case 'address':
                        return cells[3].textContent.toLowerCase().includes(query);
                    case 'mac':
                        return cells[4].textContent.toLowerCase().includes(query);
                    case 'loginby':
                        return cells[9].textContent.toLowerCase().includes(query);
                    case 'comment':
                        return cells[10].textContent.toLowerCase().includes(query);
                    default: // 'all'
                        return Array.from(cells).some(cell => 
                            cell.textContent.toLowerCase().includes(query)
                        );
                }
            }

            matchesSearchCard(card, query, filter) {
                const userElement = card.querySelector('.user-name');
                const detailRows = card.querySelectorAll('.detail-row');
                
                if (filter === 'user') {
                    return userElement.textContent.toLowerCase().includes(query);
                }
                
                if (filter === 'all') {
                    // Search in user name
                    if (userElement.textContent.toLowerCase().includes(query)) return true;
                    
                    // Search in all detail rows
                    return Array.from(detailRows).some(row => 
                        row.textContent.toLowerCase().includes(query)
                    );
                }
                
                // Search in specific fields
                const fieldMap = {
                    'server': 'Server',
                    'address': 'IP Address',
                    'mac': 'MAC Address',
                    'loginby': 'Login By',
                    'comment': 'Comment'
                };
                
                const targetField = fieldMap[filter];
                if (!targetField) return false;
                
                const targetRow = Array.from(detailRows).find(row => 
                    row.querySelector('.detail-label').textContent.includes(targetField)
                );
                
                return targetRow && targetRow.textContent.toLowerCase().includes(query);
            }

            highlightSearchTerms(row, query) {
                // Remove existing highlights
                this.removeHighlights(row);
                
                if (query.length < 2) return;
                
                const cells = row.children;
                Array.from(cells).forEach(cell => {
                    this.highlightInElement(cell, query);
                });
            }

            highlightSearchTermsCard(card, query) {
                // Remove existing highlights
                this.removeHighlights(card);
                
                if (query.length < 2) return;
                
                this.highlightInElement(card, query);
            }

            highlightInElement(element, query) {
                const walker = document.createTreeWalker(
                    element,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );

                const textNodes = [];
                let node;
                while (node = walker.nextNode()) {
                    textNodes.push(node);
                }

                textNodes.forEach(textNode => {
                    const text = textNode.textContent;
                    const regex = new RegExp(`(${query})`, 'gi');
                    if (regex.test(text)) {
                        const highlightedText = text.replace(regex, '<span class="search-highlight">$1</span>');
                        const wrapper = document.createElement('span');
                        wrapper.innerHTML = highlightedText;
                        textNode.parentNode.replaceChild(wrapper, textNode);
                    }
                });
            }

            removeHighlights(element) {
                const highlights = element.querySelectorAll('.search-highlight');
                highlights.forEach(highlight => {
                    const parent = highlight.parentNode;
                    parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                    parent.normalize();
                });
            }

            showAllItems() {
                this.tableRows.forEach(row => {
                    row.style.display = '';
                    this.removeHighlights(row);
                });
                
                this.mobileCards.forEach(card => {
                    card.style.display = '';
                    this.removeHighlights(card);
                });
            }

            updateCounter(count) {
                const text = count === 1 ? `${count} user` : `${count} users`;
                this.userCount.textContent = text;
            }

            showNoResults() {
                this.noResults.style.display = 'block';
            }

            hideNoResults() {
                this.noResults.style.display = 'none';
            }

            clearSearchInput() {
                this.searchInput.value = '';
                this.clearSearch.classList.remove('show');
                this.handleSearch();
                this.searchInput.focus();
            }
        }

        // Initialize search when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Stop any existing auto-refresh intervals
            if (window.autoRefreshInterval) {
                clearInterval(window.autoRefreshInterval);
            }
            
            // Clear any existing timeouts
            for (let i = 1; i < 99999; i++) {
                clearTimeout(i);
                clearInterval(i);
            }
            
            // Initialize search functionality
            new HotspotSearch();
            
            // Initialize refresh controls
            initRefreshControls();
            
            // Add smooth scrolling and enhanced interactions
            const actionButtons = document.querySelectorAll('.action-btn');
            actionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.style.opacity = '0.7';
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.opacity = '1';
                        this.style.transform = 'scale(1)';
                    }, 200);
                });
            });
        });

        // Refresh Controls
        function initRefreshControls() {
            const manualRefresh = document.getElementById('manualRefresh');
            const autoRefreshToggle = document.getElementById('autoRefreshToggle');
            let autoRefreshInterval = null;

            // Manual refresh button
            manualRefresh.addEventListener('click', function() {
                this.classList.add('loading');
                refreshPage();
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 1000);
            });

            // Auto refresh toggle
            autoRefreshToggle.addEventListener('change', function() {
                if (this.checked) {
                    // Start auto refresh every 30 seconds
                    autoRefreshInterval = setInterval(() => {
                        refreshPage();
                    }, 30000);
                    console.log('Auto refresh enabled (30s interval)');
                } else {
                    // Stop auto refresh
                    if (autoRefreshInterval) {
                        clearInterval(autoRefreshInterval);
                        autoRefreshInterval = null;
                    }
                    console.log('Auto refresh disabled');
                }
            });

            // Load saved preference (using session storage alternative)
            window.mikhmonAutoRefreshEnabled = window.mikhmonAutoRefreshEnabled || false;
            if (window.mikhmonAutoRefreshEnabled) {
                autoRefreshToggle.checked = true;
                autoRefreshToggle.dispatchEvent(new Event('change'));
            }

            // Save preference when changed
            autoRefreshToggle.addEventListener('change', function() {
                window.mikhmonAutoRefreshEnabled = this.checked;
            });
        }

        function refreshPage() {
            // Use existing MikhMon refresh function if available
            if (typeof loadpage === 'function') {
                loadpage('./?hotspot=active&session=<?= $session ?>');
            } else {
                // Fallback to page reload
                window.location.reload();
            }
        }

        // Enhanced loadpage function with simple loading state
        function loadpage(url) {
            // Simple loading state without transform
            const container = document.getElementById('reloadHotspotActive');
            container.style.opacity = '0.8';
            container.style.pointerEvents = 'none';
            
            // Navigate immediately
            window.location.href = url;
        }
    </script>
</body>
</html>