<?php
session_start();
// hide all error
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

// Include necessary files
include_once('./lib/routeros_api.class.php');

// Load config to get admin credentials and router data
// Clear any existing config data to ensure fresh load
unset($data);
include('./include/config.php');

// Extract admin credentials - IMPROVED VERSION WITH FALLBACK
$useradm = "admin"; // default fallback
$passadm = ""; // default fallback

if (isset($data['mikhmon'])) {
    // Method 1: Try original parsing
    if (isset($data['mikhmon'][1]) && strpos($data['mikhmon'][1], '<|<') !== false) {
        $useradm = explode('<|<', $data['mikhmon'][1])[1];
    }
    if (isset($data['mikhmon'][2]) && strpos($data['mikhmon'][2], '>|>') !== false) {
        $passadm = explode('>|>', $data['mikhmon'][2])[1];
    }
} else {
    // Fallback: scan all data for mikhmon entries
    foreach ($data as $key => $value) {
        if ($key == 'mikhmon' && is_array($value)) {
            if (isset($value[1]) && strpos($value[1], '<|<') !== false) {
                $useradm = explode('<|<', $value[1])[1];
            }
            if (isset($value[2]) && strpos($value[2], '>|>') !== false) {
                $passadm = explode('>|>', $value[2])[1];
            }
            break;
        }
    }
}

// Load language variables (fallback if files not found)
$_admin_settings = "Admin Settings";
$_router_list = "Router List";
$_hotspot_name = "Hotspot Name";
$_session_name = "Session Name";
$_open = "Open";
$_edit = "Edit";
$_delete = "Delete";
$_user_name = "Username";
$_password = "Password";
$_quick_print = "Quick Print";
$_save = "Save";
$_admin = "Admin";

// Try to load language files if they exist
if (file_exists('./include/lang.php')) {
  include('./include/lang.php');
}
if (isset($langid) && file_exists('./lang/'.$langid.'.php')) {
  include('./lang/'.$langid.'.php');
}

// Load quickbt setting
$qrbt = "enable";
if (file_exists('./include/quickbt.php')) {
  include('./include/quickbt.php');
}

// Array color for router boxes
$colorClasses = array('bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-yellow', 'bg-green', 'bg-teal', 'bg-cyan', 'bg-grey');

// IMPROVED FORM PROCESSING - EXACT SAME AS DEBUG VERSION
if (isset($_POST['save'])) {
    $suseradm = $_POST['useradm'];
    $spassadm = encrypt($_POST['passadm']);
    $qrbt = $_POST['qrbt'];
    
    // Method: Direct file content replacement (more reliable than array-based)
    $config_file = './include/config.php';
    $content = file_get_contents($config_file);
    
    if ($content !== false) {
        // Replace username pattern
        $old_user_pattern = "mikhmon<|<$useradm";
        $new_user_pattern = "mikhmon<|<$suseradm";
        $content = str_replace($old_user_pattern, $new_user_pattern, $content);
        
        // Replace password pattern  
        $old_pass_pattern = "mikhmon>|>$passadm";
        $new_pass_pattern = "mikhmon>|>$spassadm";
        $content = str_replace($old_pass_pattern, $new_pass_pattern, $content);
        
        // Write back to file
        $write_result = file_put_contents($config_file, $content);
        
        if ($write_result === false) {
            $error_msg = "Failed to write config file. Check permissions.";
        }
    } else {
        $error_msg = "Failed to read config file.";
    }
    
    // Save quickbt setting
    $gen = '<?php $qrbt="' . $qrbt . '";?>';
    $key = './include/quickbt.php';
    $handle = fopen($key, 'w');
    if ($handle) {
        fwrite($handle, $gen);
        fclose($handle);
    }
    
    // Clear opcode cache to ensure fresh data
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate('./include/config.php');
        opcache_invalidate('./include/quickbt.php');
    }
    
    if (!isset($error_msg)) {
        echo "<script>
        alert('Settings saved successfully!');
        window.location='./admin.php?id=sessions';
        </script>";
    } else {
        echo "<script>alert('Error: $error_msg');</script>";
    }
}

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikhMon - Sessions</title>
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
            line-height: 1.4;
            font-size: 14px;
        }

        /* Main content wrapper - avoid interfering with existing layout */
        .page-content {
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: calc(100vh - 100px);
            padding: 15px;
            margin: 0;
        }

        .container {
            max-width: 1400px;
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

        .page-header-title {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--accent-blue);
            text-align: center;
        }

        .page-header-title i {
            font-size: 1.4rem;
            color: var(--accent-blue);
            margin-right: 12px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            align-items: start;
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
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-title i {
            color: var(--accent-blue);
        }

        .card-body {
            padding: 20px;
        }

        .router-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }

        .router-box {
            border-radius: 10px;
            padding: 15px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        /* Default router box */
        .router-box {
            background: linear-gradient(135deg, var(--bg-tertiary) 0%, #3a4149 100%);
        }

        /* Different colored router boxes */
        .router-box.bg-blue { 
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            border-color: rgba(30, 64, 175, 0.5);
        }
        .router-box.bg-indigo { 
            background: linear-gradient(135deg, #3730a3 0%, #4338ca 100%);
            border-color: rgba(67, 56, 202, 0.5);
        }
        .router-box.bg-purple { 
            background: linear-gradient(135deg, #6b21a8 0%, #7c3aed 100%);
            border-color: rgba(124, 58, 237, 0.5);
        }
        .router-box.bg-pink { 
            background: linear-gradient(135deg, #be185d 0%, #db2777 100%);
            border-color: rgba(219, 39, 119, 0.5);
        }
        .router-box.bg-red { 
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
            border-color: rgba(220, 38, 38, 0.5);
        }
        .router-box.bg-yellow { 
            background: linear-gradient(135deg, #a16207 0%, #ca8a04 100%);
            border-color: rgba(202, 138, 4, 0.5);
        }
        .router-box.bg-green { 
            background: linear-gradient(135deg, #166534 0%, #16a34a 100%);
            border-color: rgba(22, 163, 74, 0.5);
        }
        .router-box.bg-teal { 
            background: linear-gradient(135deg, #115e59 0%, #0d9488 100%);
            border-color: rgba(13, 148, 136, 0.5);
        }
        .router-box.bg-cyan { 
            background: linear-gradient(135deg, #155e75 0%, #0891b2 100%);
            border-color: rgba(8, 145, 178, 0.5);
        }
        .router-box.bg-grey { 
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
            border-color: rgba(75, 85, 99, 0.5);
        }

        /* Top border indicators for different colors */
        .router-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .router-box.bg-blue::before { background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%); }
        .router-box.bg-indigo::before { background: linear-gradient(90deg, #6366f1 0%, #4f46e5 100%); }
        .router-box.bg-purple::before { background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%); }
        .router-box.bg-pink::before { background: linear-gradient(90deg, #ec4899 0%, #db2777 100%); }
        .router-box.bg-red::before { background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%); }
        .router-box.bg-yellow::before { background: linear-gradient(90deg, #eab308 0%, #ca8a04 100%); }
        .router-box.bg-green::before { background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%); }
        .router-box.bg-teal::before { background: linear-gradient(90deg, #14b8a6 0%, #0d9488 100%); }
        .router-box.bg-cyan::before { background: linear-gradient(90deg, #06b6d4 0%, #0891b2 100%); }
        .router-box.bg-grey::before { background: linear-gradient(90deg, #64748b 0%, #475569 100%); }

        /* Enhanced icon colors for different router types */
        .router-box.bg-blue .router-icon { 
            background: rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }
        .router-box.bg-indigo .router-icon { 
            background: rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
        }
        .router-box.bg-purple .router-icon { 
            background: rgba(139, 92, 246, 0.3);
            color: #c4b5fd;
        }
        .router-box.bg-pink .router-icon { 
            background: rgba(236, 72, 153, 0.3);
            color: #f9a8d4;
        }
        .router-box.bg-red .router-icon { 
            background: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .router-box.bg-yellow .router-icon { 
            background: rgba(234, 179, 8, 0.3);
            color: #fde047;
        }
        .router-box.bg-green .router-icon { 
            background: rgba(34, 197, 94, 0.3);
            color: #86efac;
        }
        .router-box.bg-teal .router-icon { 
            background: rgba(20, 184, 166, 0.3);
            color: #5eead4;
        }
        .router-box.bg-cyan .router-icon { 
            background: rgba(6, 182, 212, 0.3);
            color: #67e8f9;
        }
        .router-box.bg-grey .router-icon { 
            background: rgba(100, 116, 139, 0.3);
            color: #cbd5e1;
        }

        .router-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border-color: rgba(74, 158, 255, 0.5);
        }

        .router-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .router-icon {
            width: 45px;
            height: 45px;
            background: rgba(74, 158, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--accent-blue);
            flex-shrink: 0;
        }

        .router-info {
            flex: 1;
            min-width: 0;
        }

        .router-info div {
            margin-bottom: 8px;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #ffffff;
            background: rgba(0, 0, 0, 0.4);
            padding: 6px 10px;
            border-radius: 6px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
        }

        .router-info strong {
            color: #ffffff;
            font-weight: 600;
        }

        .router-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .router-actions a, .router-actions span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            background: rgba(74, 158, 255, 0.2);
            border-radius: 6px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .router-actions a:hover, .router-actions span:hover {
            background: rgba(74, 158, 255, 0.3);
            border-color: var(--accent-blue);
            transform: translateY(-1px);
        }

        .form-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-green) 0%, var(--accent-blue) 50%, var(--accent-orange) 100%);
        }

        .form-table {
            width: 100%;
        }

        .form-table td {
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }

        .form-table td:first-child {
            font-weight: 500;
            color: var(--accent-blue);
            width: 120px;
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

        .input-group {
            display: flex;
            border-radius: 8px;
            overflow: hidden;
        }

        .input-group input {
            border-radius: 0;
            border-right: none;
            flex: 1;
        }

        .input-group .show-password {
            background: rgba(74, 158, 255, 0.2);
            border: 1px solid var(--border-color);
            border-left: none;
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            color: var(--text-secondary);
        }

        .input-group .show-password:hover {
            background: rgba(74, 158, 255, 0.3);
        }

        .btn-group {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), #3a8ae8);
            color: white;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(74, 158, 255, 0.4);
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

        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .page-content {
                padding: 10px;
            }
            
            .page-header {
                padding: 15px;
            }
            
            .router-grid {
                grid-template-columns: 1fr;
            }
            
            .form-table td {
                padding: 10px 15px;
            }

            .card-body {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .form-table td:first-child {
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
    <div class="page-content">
        <div class="container">
            <div class="page-header">
                <div class="page-header-title">
                    <i class="fas fa-cogs"></i>
                    <span><?= $_admin_settings ?></span>
                </div>
            </div>

            <div class="main-grid">
                <!-- Router List Section -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-server"></i>
                            <span><?= $_router_list ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="router-grid">
                            <?php
                            foreach ($data as $sessionName => $sessionData) {
                                if ($sessionName == "" || $sessionName == "mikhmon") {
                                    continue;
                                }
                                
                                $hotspotName = "Unknown";
                                if (isset($sessionData[4])) {
                                    $hotspotData = $sessionData[4];
                                    $parts = explode('%', $hotspotData);
                                    if (count($parts) > 1) {
                                        $hotspotName = $parts[1];
                                    } else {
                                        $hotspotName = $sessionName;
                                    }
                                }
                            ?>
                            <div class="router-box <?= $colorClasses[array_rand($colorClasses)]; ?>">
                                <div class="router-content">
                                    <div class="router-icon">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <div class="router-info">
                                        <div><strong><?= $_hotspot_name ?> :</strong> <?= htmlspecialchars($hotspotName); ?></div>
                                        <div><strong><?= $_session_name ?> :</strong> <?= htmlspecialchars($sessionName); ?></div>
                                        <div class="router-actions">
                                            <span class="connect pointer" id="<?= htmlspecialchars($sessionName); ?>">
                                                <i class="fas fa-external-link-alt"></i> <?= $_open ?>
                                            </span>
                                            <a href="./admin.php?id=settings&session=<?= urlencode($sessionName); ?>">
                                                <i class="fas fa-edit"></i> <?= $_edit ?>
                                            </a>
                                            <a href="javascript:void(0)" 
                                               onclick="if(confirm('Delete <?= htmlspecialchars($sessionName); ?>?')){
                                                   window.location='./admin.php?id=remove-session&session=<?= urlencode($sessionName); ?>'
                                               }">
                                                <i class="fas fa-trash"></i> <?= $_delete ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- Admin Settings Form -->
                <div class="form-container">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-user-circle"></i>
                            <span><?= $_admin ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form autocomplete="off" method="post" action="">
                            <table class="form-table">
                                <tr>
                                    <td><?= $_user_name ?></td>
                                    <td>
                                        <input class="form-control" id="useradm" type="text" name="useradm" 
                                               title="User Admin" value="<?= htmlspecialchars($useradm); ?>" required />
                                    </td>
                                </tr>
                                <tr>
                                    <td><?= $_password ?></td>
                                    <td>
                                        <div class="input-group">
                                            <input class="form-control" id="passadm" type="password" name="passadm" 
                                                   title="Password Admin" value="<?= htmlspecialchars(decrypt($passadm)); ?>" required />
                                            <div class="show-password" onclick="Pass('passadm')" title="Show/Hide Password">
                                                <i class="fas fa-eye" id="passadm-eye"></i>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?= $_quick_print ?> QR</td>
                                    <td>
                                        <select class="form-control" name="qrbt">
                                            <option <?= ($qrbt == 'enable') ? 'selected' : '' ?>>enable</option>
                                            <option <?= ($qrbt == 'disable') ? 'selected' : '' ?>>disable</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <div class="btn-group">
                                            <input type="submit" class="btn btn-primary" name="save" value="<?= $_save ?>" />
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function Pass(id){
            var x = document.getElementById(id);
            var eye = document.getElementById(id + '-eye');
            if (x.type === 'password') {
                x.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                x.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>