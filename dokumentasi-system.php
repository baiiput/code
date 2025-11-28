<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">🔒</div>
        <h1>Akses Ditolak</h1>
        <p>Halaman dokumentasi ini hanya dapat diakses oleh <strong>Administrator</strong>.</p>
        <p>Role Anda saat ini: <strong>' . ucfirst($user['role']) . '</strong></p>
        <a href="index.php" class="btn">← Kembali ke Dashboard</a>
    </div>
</body>
</html>';
    exit;
}

// If admin, include the HTML documentation
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi System Warehouse - Admin Only</title>
    <style>
        .admin-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.95);
            padding: 10px 20px;
            border-radius: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            font-size: 14px;
            color: #667eea;
            font-weight: bold;
        }

        .admin-badge::before {
            content: '👤 ';
        }
    </style>
</head>
<body>
    <div class="admin-badge">Admin: <?php echo htmlspecialchars($user['full_name']); ?></div>
    <?php
    // Include the HTML documentation file
    $docFile = __DIR__ . '/dokumentasi-system.html';
    if (file_exists($docFile)) {
        $content = file_get_contents($docFile);
        // Remove doctype and html/head/body tags since we already have them
        $content = preg_replace('/<\!DOCTYPE[^>]*>/i', '', $content);
        $content = preg_replace('/<html[^>]*>/i', '', $content);
        $content = preg_replace('/<\/html>/i', '', $content);
        $content = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $content);
        $content = preg_replace('/<body[^>]*>/i', '', $content);
        $content = preg_replace('/<\/body>/i', '', $content);
        // Update links to use .php instead of .html
        $content = str_replace('admin-reset-data.html', 'admin-reset-data.php', $content);
        $content = str_replace('index.html', 'index.php', $content);
        echo $content;
    } else {
        echo '<div style="padding: 40px; text-align: center;">
            <h1>📚 Dokumentasi Belum Tersedia</h1>
            <p>File dokumentasi-system.html belum diupload. Silakan upload file tersebut ke server.</p>
            <a href="index.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">Kembali ke Dashboard</a>
        </div>';
    }
    ?>
</body>
</html>
