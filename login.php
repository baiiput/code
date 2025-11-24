<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role, cabang_id, warehouse_id, is_active FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['is_active'] == 0) {
                $error = 'Akun Anda tidak aktif. Hubungi administrator.';
            } elseif (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['cabang_id'] = $user['cabang_id'];
                $_SESSION['warehouse_id'] = $user['warehouse_id'];

                // Log activity
                logActivity('LOGIN', 'auth', 'User logged in successfully');

                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah';
            }
        } else {
            $error = 'Username atau password salah';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warehouse Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --danger-color: #ef4444;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: var(--bg-secondary);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 48px;
            width: 100%;
            max-width: 440px;
            backdrop-filter: blur(10px);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 36px;
        }
        
        .logo-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 16px;
        }
        
        .logo-section h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .logo-section p {
            color: var(--text-secondary);
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--bg-secondary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            border: 2px solid var(--danger-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .theme-toggle {
            position: fixed;
            top: 24px;
            right: 24px;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 50%;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            color: var(--text-primary);
            font-size: 20px;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1) rotate(15deg);
            border-color: var(--primary-color);
        }
        
        .demo-info {
            margin-top: 24px;
            padding: 20px;
            background: rgba(59, 130, 246, 0.05);
            border: 2px solid var(--primary-color);
            border-radius: 10px;
            font-size: 14px;
        }
        
        .demo-info strong {
            color: var(--primary-color);
            display: block;
            margin-bottom: 12px;
            font-size: 15px;
        }
        
        .demo-info div {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }
        
        .demo-label {
            color: var(--text-secondary);
        }
        
        .demo-value {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 32px 24px;
            }
            
            .logo-section h1 {
                font-size: 24px;
            }
            
            .theme-toggle {
                width: 44px;
                height: 44px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>
    
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-warehouse"></i>
            </div>
            <h1>Warehouse System</h1>
            <p>Sistem Manajemen Gudang & Keuangan</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required autofocus placeholder="Masukkan username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Masukkan password">
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <span>Masuk ke Dashboard</span>
            </button>
        </form>
        
        <div class="demo-info">
            <strong><i class="fas fa-info-circle"></i> Akun Demo</strong>
            <div>
                <span class="demo-label">Username:</span>
                <span class="demo-value">admin</span>
            </div>
            <div>
                <span class="demo-label">Password:</span>
                <span class="demo-value">admin123</span>
            </div>
        </div>
    </div>
    
    <script>
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            const icon = document.getElementById('theme-icon');
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        document.getElementById('theme-icon').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    </script>
</body>
</html>
