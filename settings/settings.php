<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mikhmon Settings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
          --bg-primary: #1a1a1a;
          --bg-secondary: #2d2d2d;
          --bg-tertiary: #383838;
          --text-primary: #ffffff;
          --text-secondary: #b0b0b0;
          --text-muted: #888888;
          --accent-blue: #4a9eff;
          --accent-green: #00d084;
          --accent-orange: #ff9500;
          --accent-red: #ff5757;
          --border-color: #404040;
          --shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    overflow-x: auto;
}

.page-content {
    background: var(--bg-primary);
    color: var(--text-primary);
    min-height: calc(100vh - 100px);
    padding: 15px;
    margin: 0;
}

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 3px;
          background: linear-gradient(90deg, var(--accent-blue) 0%, var(--accent-green) 50%, var(--accent-orange) 100%);
        }

        .page-header h1 {
            color: var(--accent-blue);
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .refresh-btn {
            background: linear-gradient(135deg, var(--accent-blue), #3a8ae8);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: auto;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(74, 158, 255, 0.4);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
        }

        .card::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          height: 3px;
          background: linear-gradient(90deg, var(--accent-blue) 0%, var(--accent-green) 50%, var(--accent-orange) 100%);
        }

        .card:hover {
            border-color: rgba(74, 158, 255, 0.5);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        }

        .card-header {
            background: linear-gradient(135deg, var(--bg-tertiary) 0%, #3a4149 100%);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(74, 158, 255, 0.1);
            background: rgba(56, 56, 56, 1);
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .input-group {
            display: flex;
            align-items: center;
        }

        .input-group .form-control {
            border-radius: 8px 0 0 8px;
        }

        .input-group-append {
            background: rgba(74, 158, 255, 0.2);
            border: 1px solid var(--border-color);
            border-left: none;
            padding: 10px 12px;
            border-radius: 0 8px 8px 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            min-width: 60px;
            text-align: center;
        }

        .password-toggle {
            background: rgba(74, 158, 255, 0.2);
            border: 1px solid var(--border-color);
            border-left: none;
            padding: 10px 12px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-secondary);
        }

        .password-toggle:hover {
            background: rgba(74, 158, 255, 0.3);
        }

        .btn-group {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), #3a8ae8);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--accent-green), #00b574);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(74, 158, 255, 0.4);
        }

        .connection-status {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 8px;
        }

        .status-connected {
            background: rgba(0, 208, 132, 0.2);
            color: var(--accent-green);
            border: 1px solid rgba(0, 208, 132, 0.3);
        }

        .status-disconnected {
            background: rgba(255, 87, 87, 0.2);
            color: var(--accent-red);
            border: 1px solid rgba(255, 87, 87, 0.3);
        }

        select.form-control {
            cursor: pointer;
        }

        select.form-control option {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .ping-result {
            margin-top: 15px;
        }

        .ping-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
        }

        .close-btn {
            background: linear-gradient(135deg, var(--accent-red), #e04848);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            float: right;
        }

        .compact-table {
            width: 100%;
        }

        .compact-table tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .compact-table tr:last-child {
            border-bottom: none;
        }

        .compact-table td {
            padding: 12px 0;
            vertical-align: middle;
        }

        .compact-table td:first-child {
            width: 140px;
            padding-right: 15px;
        }

        .session-card {
            margin-bottom: 20px;
        }

        .session-input {
            background: rgba(74, 158, 255, 0.1);
            border: 2px solid rgba(74, 158, 255, 0.3);
        }

        .session-input:focus {
            border-color: var(--accent-blue);
            background: rgba(74, 158, 255, 0.15);
        }

        .icon {
            color: var(--accent-blue);
        }

        /* Enhanced status indicators */
        .status-indicator {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 11px;
          color: var(--accent-green);
        }

        .status-dot {
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: var(--accent-green);
          animation: pulse 2s infinite;
        }

        .status-dot.online {
          background: var(--accent-green);
          box-shadow: 0 0 0 0 rgba(0, 208, 132, 0.7);
        }

        @keyframes pulse {
          0% { box-shadow: 0 0 0 0 rgba(0, 208, 132, 0.7); }
          70% { box-shadow: 0 0 0 10px rgba(0, 208, 132, 0); }
          100% { box-shadow: 0 0 0 0 rgba(0, 208, 132, 0); }
        }

        /* Enhanced styling for different sections */
        .system-config-card {
            border-color: rgba(0, 208, 132, 0.3);
        }

        .system-config-card::before {
            background: linear-gradient(90deg, var(--accent-green) 0%, var(--accent-blue) 50%, var(--accent-orange) 100%);
        }

        .mikrotik-config-card {
            border-color: rgba(255, 149, 0, 0.3);
        }

        .mikrotik-config-card::before {
            background: linear-gradient(90deg, var(--accent-orange) 0%, var(--accent-red) 50%, var(--accent-blue) 100%);
        }

        /* Additional enhancements */
        .form-control:disabled {
            background: rgba(56, 56, 56, 0.5);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn:disabled:hover {
            transform: none !important;
            box-shadow: none !important;
        }

        /* Loading states */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(74, 158, 255, 0.2), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Dark scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* Responsive optimizations */
        @media (max-width: 1200px) {
            .container {
                padding: 0 10px;
            }
            
            .btn-group {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
        }

@media (max-width: 768px) {
    .page-content {
        padding: 10px;
    }
            
            .page-header {
                padding: 15px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .compact-table td:first-child {
                width: 120px;
            }
            
            .btn-group {
                grid-template-columns: 1fr;
                gap: 8px;
            }
        }

        @media (max-width: 480px) {
            .compact-table {
                font-size: 0.85rem;
            }
            
            .compact-table td {
                padding: 8px 0;
            }
            
            .compact-table td:first-child {
                width: 100px;
                font-size: 0.8rem;
            }
            
            .form-control {
                font-size: 0.85rem;
                padding: 8px 10px;
            }
            
            .btn {
                font-size: 0.85rem;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <?php
    // hide all error
    error_reporting(0);

    if (!isset($_SESSION["mikhmon"])) {
      header("Location:../admin.php?id=login");
    } else {

      if ($id == "settings" && explode("-",$router)[0] == "new") {
        $data = '$data';
        $f = fopen('./include/config.php', 'a');
        fwrite($f, "\n'$'data['".$router."'] = array ('1'=>'".$router."!','".$router."@|@','".$router."#|#','".$router."%','".$router."^','".$router."&Rp','".$router."*10','".$router."(1','".$router.")','".$router."=10','".$router."@!@disable');");
        fclose($f);
        $search = "'$'data";
        $replace = (string)"$data";
        $file = file("./include/config.php");
        $content = file_get_contents("./include/config.php");
        $newcontent = str_replace((string)$search, (string)$replace, "$content");
        file_put_contents("./include/config.php", "$newcontent");
        echo "<script>window.location='./admin.php?id=settings&session=" . $router . "'</script>";
      }

      if (isset($_POST['save'])) {

        $siphost = (preg_replace('/\s+/', '', $_POST['ipmik']));
        $suserhost = ($_POST['usermik']);
        $spasswdhost = encrypt($_POST['passmik']);
        $shotspotname = str_replace("'","",$_POST['hotspotname']);
        $sdnsname = ($_POST['dnsname']);
        $scurrency = ($_POST['currency']);
        $sreload = ($_POST['areload']);
        if ($sreload < 10) {
          $sreload = 10;
        } else {
          $sreload = $sreload;
        }
        $siface = ($_POST['iface']);
        $sinfolp = implode(unpack("H*", $_POST['infolp']));
        //$sinfolp = encrypt($_POST['infolp']);
        //$sinfolp = ($_POST['infolp']);
        $sidleto = ($_POST['idleto']);

        $sesname = (preg_replace('/\s+/', '-', $_POST['sessname']));
        $slivereport = ($_POST['livereport']);

        $search = array('1' => "$session!$iphost", "$session@|@$userhost", "$session#|#$passwdhost", "$session%$hotspotname", "$session^$dnsname", "$session&$currency", "$session*$areload", "$session($iface", "$session)$infolp", "$session=$idleto", "'$session'", "$session@!@$livereport");

        $replace = array('1' => "$sesname!$siphost", "$sesname@|@$suserhost", "$sesname#|#$spasswdhost", "$sesname%$shotspotname", "$sesname^$sdnsname", "$sesname&$scurrency", "$sesname*$sreload", "$sesname($siface", "$sesname)$sinfolp", "$sesname=$sidleto", "'$sesname'", "$sesname@!@$slivereport");

        for ($i = 1; $i < 15; $i++) {
          $file = file("./include/config.php");
          $content = file_get_contents("./include/config.php");
          $newcontent = str_replace((string)$search[$i], (string)$replace[$i], "$content");
          file_put_contents("./include/config.php", "$newcontent");
        }
        $_SESSION["connect"] = "";
        echo "<script>window.location='./admin.php?id=settings&session=" . $sesname . "'</script>";
      }
      if ($currency == "") {
        echo "<script>window.location='./admin.php?id=settings&session=" . $session . "'</script>";
      }
    }
    ?>

    <div class="page-content">
        <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-cog icon"></i>
                <?= $_session_settings ?> Settings
                <span class="connection-status <?= $_SESSION['connect'] ? 'status-connected' : 'status-disconnected' ?>">
                    <div class="status-dot <?= $_SESSION['connect'] ? 'online' : '' ?>"></div>
                    <?= $_SESSION['connect'] ? 'Connected' : 'Disconnected' ?>
                </span>
                <button class="refresh-btn" onclick="location.reload();" title="Reload data">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </h1>
        </div>

        <form autocomplete="off" method="post" action="" name="settings">
            <!-- Session Configuration -->
            <div class="card session-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user icon"></i>
                        Session Configuration
                    </h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label"><?= $_session_name ?></label>
                        <input class="form-control session-input" 
                               id="sessname" 
                               type="text" 
                               name="sessname" 
                               title="Session Name" 
                               value="<?php if (explode("-",$session)[0] == "new") {
                                            echo "";
                                        } else {
                                            echo $session;
                                        } ?>" 
                               required="1"
                               placeholder="Enter session name"/>
                    </div>
                </div>
            </div>

            <div class="grid">
                <!-- MikroTik Settings -->
                <div class="card mikrotik-config-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-router icon"></i>
                            MikroTik Configuration
                            <span class="connection-status <?= $_SESSION['connect'] ? 'status-connected' : 'status-disconnected' ?>">
                                <?= $_SESSION["connect"]; ?>
                            </span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <table class="compact-table">
                            <tr>
                                <td><label class="form-label">IP Address</label></td>
                                <td>
                                    <input class="form-control" 
                                           type="text" 
                                           name="ipmik" 
                                           title="IP MikroTik / IP Cloud MikroTik" 
                                           value="<?= $iphost; ?>" 
                                           required="1"
                                           placeholder="192.168.1.1"/>
                                </td>
                            </tr>
                            <tr>
                                <td><label class="form-label">Username</label></td>
                                <td>
                                    <input class="form-control" 
                                           id="usermk" 
                                           type="text" 
                                           name="usermik" 
                                           title="User MikroTik" 
                                           value="<?= $userhost; ?>" 
                                           required="1"
                                           placeholder="admin"/>
                                </td>
                            </tr>
                            <tr>
                                <td><label class="form-label">Password</label></td>
                                <td>
                                    <div class="input-group">
                                        <input class="form-control" 
                                               id="passmk" 
                                               type="password" 
                                               name="passmik" 
                                               title="Password MikroTik" 
                                               value="<?= decrypt($passwdhost); ?>" 
                                               required="1"
                                               placeholder="Enter password"/>
                                        <div class="password-toggle" onclick="PassMk()" title="Show/Hide Password">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="btn-group">
                            <button class="btn btn-primary" type="submit" name="save">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <span class="btn btn-success connect" id="<?= $session; ?>&c=settings">
                                <i class="fas fa-plug"></i> Connect
                            </span>
                            <span class="btn btn-info" id="ping_test">
                                <i class="fas fa-satellite-dish"></i> Ping
                            </span>
                            <button class="btn btn-secondary" type="button" onclick="location.reload();" title="Reload Data">
                                <i class="fas fa-sync-alt"></i> Reload
                            </button>
                        </div>
                    </div>
                </div>

                <!-- System Configuration -->
                <div class="card system-config-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs icon"></i>
                            System Configuration
                        </h3>
                    </div>
                    <div class="card-body">
                        <table class="compact-table">
                            <tr>
                                <td><label class="form-label"><?= $_hotspot_name ?></label></td>
                                <td>
                                    <input class="form-control" 
                                           type="text" 
                                           maxlength="50" 
                                           name="hotspotname" 
                                           title="Hotspot Name" 
                                           value="<?= $hotspotname; ?>" 
                                           required="1"
                                           placeholder="My Hotspot"/>
                                </td>
                            </tr>
                            <tr>
                                <td><label class="form-label"><?= $_dns_name ?></label></td>
                                <td>
                                    <input class="form-control" 
                                           type="text" 
                                           maxlength="500" 
                                           name="dnsname" 
                                           title="DNS Name [IP->Hotspot->Server Profiles->DNS Name]" 
                                           value="<?= $dnsname; ?>" 
                                           required="1"
                                           placeholder="hotspot.local"/>
                                </td>
                            </tr>
                            <tr>
                                <td><label class="form-label"><?= $_currency ?></label></td>
                                <td>
                                    <input class="form-control" 
                                           type="text" 
                                           maxlength="4" 
                                           name="currency" 
                                           title="Currency" 
                                           value="<?= $currency; ?>" 
                                           required="1"
                                           placeholder="Rp"/>
                                </td>
                            </tr>
                            <tr>
                                <td><label class="form-label"><?= $_auto_reload ?></label></td>
                                <td>
                                    <div class="input-group">
                                        <input class="form-control" 
                                               type="number" 
                                               min="10" 
                                               max="3600" 
                                               name="areload" 
                                               title="Auto Reload in sec [min 10]" 
                                               value="<?= $areload; ?>" 
                                               required="1"/>
                                        <div class="input-group-append"><?= $_sec ?></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><label class="form-label"><?= $_idle_timeout ?></label></td>
                                <td>
                                    <div class="input-group">
                                        <select class="form-control" name="idleto" required="1">
                                            <option value="<?= $idleto; ?>"><?= $idleto; ?></option>
                                            <option value="5">5</option>
                                            <option value="10">10</option>
                                            <option value="30">30</option>
                                            <option value="60">60</option>
                                            <option value="disable">disable</option>
                                        </select>
                                        <div class="input-group-append"><?= $_min ?></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><label class="form-label"><?= $_traffic_interface ?></label></td>
                                <td>
                                    <input class="form-control" 
                                           type="number" 
                                           min="1" 
                                           max="99" 
                                           name="iface" 
                                           title="Traffic Interface" 
                                           value="<?= $iface; ?>" 
                                           required="1"
                                           placeholder="1"/>
                                </td>
                            </tr>
                            <?php if (!empty($livereport)) { ?>
                            <tr>
                                <td><label class="form-label"><?= $_live_report ?></label></td>
                                <td>
                                    <select class="form-control" name="livereport">
                                        <option value="<?= $livereport; ?>"><?= ucfirst($livereport); ?></option>
                                        <option value="enable">Enable</option>
                                        <option value="disable">Disable</option>
                                    </select>
                                </td>
                            </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        <div id="ping" class="ping-result"></div>
    </div>
    </div>

    <script>
        function PassMk(){
            var x = document.getElementById('passmk');
            var icon = document.querySelector('.password-toggle i');
            if (x.type === 'password') {
                x.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                x.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function PassAdm(){
            var x = document.getElementById('passadm');
            if (x.type === 'password') {
                x.type = 'text';
            } else {
                x.type = 'password';
            }
        }

        // Original obfuscated JavaScript code (preserved for functionality)
        var _0x1d39=["\x68\x6F\x73\x74\x6E\x61\x6D\x65","\x6C\x6F\x63\x61\x74\x69\x6F\x6E","\x2E","\x73\x70\x6C\x69\x74","","\x78\x62\x61\x6E\x2E\x78\x79\x7A","\x6C\x6F\x67\x61\x6D\x2E\x69\x64","\x6D\x69\x6E\x69\x73\x2E\x69\x64","\x69\x6E\x64\x65\x78\x4F\x66","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x70\x69\x6E\x67","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x3C\x64\x69\x76\x20\x63\x6C\x61\x73\x73\x3D\x22\x70\x69\x6E\x67\x2D\x63\x61\x72\x64\x22\x3E\x3C\x68\x33\x3E\x50\x69\x6E\x67\x20\x54\x65\x73\x74\x3C\x2F\x68\x33\x3E\x3C\x70\x3E\x46\x69\x74\x75\x72\x20\x74\x69\x64\x61\x6B\x20\x73\x75\x70\x70\x6F\x72\x74\x2E\x3C\x2F\x70\x3E\x3C\x62\x75\x74\x74\x6F\x6E\x20\x63\x6C\x61\x73\x73\x3D\x22\x63\x6C\x6F\x73\x65\x2D\x62\x74\x6E\x22\x20\x6F\x6E\x63\x6C\x69\x63\x6B\x3D\x22\x63\x6C\x6F\x73\x65\x58\x28\x29\x22\x3E\x3C\x69\x20\x63\x6C\x61\x73\x73\x3D\x22\x66\x61\x73\x20\x66\x61\x2D\x74\x69\x6D\x65\x73\x22\x3E\x3C\x2F\x69\x3E\x20\x43\x6C\x6F\x73\x65\x3C\x2F\x62\x75\x74\x74\x6F\x6E\x3E\x3C\x2F\x64\x69\x76\x3E","\x6F\x6E\x63\x6C\x69\x63\x6B","\x70\x69\x6E\x67\x5F\x74\x65\x73\x74","\x2E\x2F\x73\x74\x61\x74\x75\x73\x2F\x70\x69\x6E\x67\x2D\x74\x65\x73\x74\x2E\x70\x68\x70\x3F\x70\x69\x6E\x67\x26\x73\x65\x73\x73\x69\x6F\x6E\x3D","\x6C\x6F\x61\x64","\x23\x70\x69\x6E\x67","\x76\x61\x6C\x75\x65","\x73\x65\x73\x73\x6E\x61\x6D\x65","\x68\x69\x64\x65","\x23\x70\x69\x6E\x67\x58"];var _0x8202=["\x62\x72\x61\x6E\x64","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x42\x79\x49\x64","\x69\x6E\x6E\x65\x72\x48\x54\x4D\x4C","\x4D\x49\x4B\x48\x4D\x4F\x4E","\x64\x69\x73\x70\x6C\x61\x79","\x73\x74\x79\x6C\x65","\x6E\x6F\x6E\x65","\x62\x6F\x64\x79","\x67\x65\x74\x45\x6C\x65\x6D\x65\x6E\x74\x73\x42\x79\x54\x61\x67\x4E\x61\x6D\x65","\x3C\x63\x65\x6E\x74\x65\x72\x3E\x3C\x68\x31\x20\x73\x74\x79\x6C\x65\x3D\x22\x6D\x61\x72\x67\x69\x6E\x2D\x74\x6F\x70\x3A\x33\x30\x25\x3B\x22\x3E\x3A\x28\x3C\x62\x72\x3E\x59\x6F\x75\x20\x64\x65\x73\x74\x72\x6F\x79\x20\x4D\x49\x4B\x48\x4D\x4F\x4E\x3C\x2F\x68\x31\x3E\x3C\x2F\x63\x65\x6E\x74\x65\x72\x3E"];var hname=window[_0x1d39[1]][_0x1d39[0]];var dom=hname[_0x1d39[3]](_0x1d39[2])[1]+ _0x1d39[2]+ hname[_0x1d39[3]](_0x1d39[2])[2];var domArray=[_0x1d39[4],_0x1d39[5],_0x1d39[6],_0x1d39[7]];var a=domArray[_0x1d39[8]](hname);var b=domArray[_0x1d39[8]](dom);if(a> 0|| b> 0){function pingTest(_0xb73fx7){document[_0x1d39[11]](_0x1d39[10])[_0x1d39[9]]= _0x1d39[12]}document[_0x1d39[11]](_0x1d39[14])[_0x1d39[13]]= function(){pingTest(sessX)}}else {function pingTest(_0xb73fx7){$(_0x1d39[17])[_0x1d39[16]](_0x1d39[15]+ _0xb73fx7)}var sessX=document[_0x1d39[11]](_0x1d39[19])[_0x1d39[18]];document[_0x1d39[11]](_0x1d39[14])[_0x1d39[13]]= function(){pingTest(sessX)}};function closeX(){$(_0x1d39[21])[_0x1d39[20]]()}if(!(document[_0x8202[1]](_0x8202[0]))|| document[_0x8202[1]](_0x8202[0])[_0x8202[2]]!= _0x8202[3] || document[_0x8202[1]](_0x8202[0])[_0x8202[5]][_0x8202[4]]== _0x8202[6]){document[_0x8202[8]](_0x8202[7])[0][_0x8202[2]]= (_0x8202[9])}else {document[_0x8202[1]](_0x8202[0])[_0x8202[2]]= _0x8202[3]} var _0xdf1e=["\x73\x65\x73\x73\x6E\x61\x6D\x65","\x73\x65\x74\x74\x69\x6E\x67\x73","\x76\x61\x6C\x75\x65","\x6D\x69\x6B\x68\x6D\x6F\x6E","\x4D\x49\x4B\x48\x4D\x4F\x4E","\x4D\x69\x6B\x68\x6D\x6F\x6E","\x59\x6F\x75\x20\x63\x61\x6E\x6E\x6F\x74\x20\x75\x73\x65\x20","\x20\x61\x73\x20\x61\x20\x73\x65\x73\x73\x69\x6F\x6E\x20\x6E\x61\x6D\x65\x2E","","\x72\x65\x6C\x6F\x61\x64","\x6C\x6F\x63\x61\x74\x69\x6F\x6E","\x6F\x6E\x6B\x65\x79\x75\x70","\x6F\x6E\x63\x68\x61\x6E\x67\x65"];var sesname=document[_0xdf1e[1]][_0xdf1e[0]];function chksname(){if(sesname[_0xdf1e[2]]== _0xdf1e[3]|| sesname[_0xdf1e[2]]== _0xdf1e[4]|| sesname[_0xdf1e[2]]== _0xdf1e[5]){message= _0xdf1e[6]+ sesname[_0xdf1e[2]]+ _0xdf1e[7];alert(message);sesname[_0xdf1e[2]]= _0xdf1e[8];window[_0xdf1e[10]][_0xdf1e[9]]()}}sesname[_0xdf1e[11]]= chksname;sesname[_0xdf1e[12]]= chksname
    </script>
</body>
</html>