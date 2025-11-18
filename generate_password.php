<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Generator</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }
        h1 {
            margin: 0 0 10px 0;
            color: #1f2937;
        }
        p {
            color: #6b7280;
            margin: 0 0 30px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f3f4f6;
            border-radius: 8px;
            word-break: break-all;
        }
        .result strong {
            color: #1f2937;
        }
        .result code {
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            display: block;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            color: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Generator</h1>
        <p>Generate hashed password untuk user database</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Masukkan username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Masukkan password" required>
            </div>
            
            <button type="submit">Generate Hash</button>
        </form>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = htmlspecialchars($_POST['username']);
            $password = $_POST['password'];
            $hash = password_hash($password, PASSWORD_BCRYPT);
            
            echo '<div class="result">';
            echo '<strong>✅ Hash berhasil dibuat!</strong>';
            echo '<p style="margin-top: 15px; margin-bottom: 5px;">SQL Query untuk insert user:</p>';
            echo '<code>';
            echo "INSERT INTO users (username, password, nama, role, status) VALUES<br>";
            echo "('{$username}', '{$hash}', 'Nama User', 'karyawan', 'aktif');";
            echo '</code>';
            echo '<p style="margin-top: 15px; color: #6b7280; font-size: 14px;">Copy SQL di atas dan jalankan di database Anda</p>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb; text-align: center;">
            <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 600;">← Kembali ke Login</a>
        </div>
    </div>
</body>
</html>
