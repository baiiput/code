<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Warehouse Management'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/common.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --primary-light: #dbeafe;
            --success-color: #10b981;
            --success-light: #d1fae5;
            --danger-color: #ef4444;
            --danger-light: #fee2e2;
            --warning-color: #f59e0b;
            --warning-light: #fef3c7;
            --info-color: #06b6d4;
            --info-light: #cffafe;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --primary-light: #1e3a8a;
            --success-light: #064e3b;
            --danger-light: #7f1d1d;
            --warning-light: #78350f;
            --info-light: #164e63;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* Navbar Styles */
        .navbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 700;
            font-size: 22px;
        }
        
        .navbar-brand i {
            font-size: 28px;
            color: var(--primary-color);
        }
        
        .navbar-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .navbar-menu > li {
            list-style: none;
            position: relative;
        }
        
        .navbar-menu > li > a,
        .navbar-menu > li > .menu-dropdown-btn {
            padding: 12px 20px;
            text-decoration: none;
            color: var(--text-secondary);
            border-radius: 10px;
            transition: all 0.2s;
            font-size: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            background: transparent;
            border: none;
        }
        
        .navbar-menu > li > a:hover,
        .navbar-menu > li > .menu-dropdown-btn:hover {
            background: var(--primary-light);
            color: var(--primary-color);
        }
        
        .navbar-menu > li > a.active {
            background: var(--primary-color);
            color: white;
        }
        
        .navbar-menu > li > a i,
        .menu-dropdown-btn i {
            font-size: 16px;
        }
        
        /* Dropdown Menu */
        .dropdown {
            position: relative;
        }
        
        .menu-dropdown-btn {
            width: 100%;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            background: var(--bg-secondary);
            min-width: 220px;
            box-shadow: var(--shadow-lg);
            border-radius: 12px;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid var(--border-color);
            z-index: 1000;
        }
        
        .dropdown.open .dropdown-content {
            display: block;
            animation: dropdownFade 0.2s ease-out;
        }
        
        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .dropdown-content a:hover {
            background: var(--primary-light);
            color: var(--primary-color);
        }
        
        .dropdown-content a.active {
            background: var(--primary-light);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .dropdown-content a i {
            font-size: 14px;
            width: 18px;
            text-align: center;
        }
        
        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            padding: 10px 18px;
            background: var(--bg-primary);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .user-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .user-role {
            padding: 5px 12px;
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .theme-toggle-btn {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
        }
        
        .theme-toggle-btn:hover {
            background: var(--primary-light);
            color: var(--primary-color);
            border-color: var(--primary-color);
            transform: scale(1.05);
        }
        
        .btn-logout {
            padding: 10px 20px;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-logout:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
        
        .mobile-menu-toggle {
            display: none;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            color: var(--text-primary);
            transition: all 0.2s;
        }
        
        .mobile-menu-toggle:active {
            transform: scale(0.95);
        }
        
        .main-content {
            min-height: calc(100vh - 70px);
        }
        
        /* Mobile Responsive */
        @media (max-width: 1100px) {
            .navbar-menu {
                position: fixed;
                top: 70px;
                left: 0;
                right: 0;
                background: var(--bg-secondary);
                border-bottom: 1px solid var(--border-color);
                flex-direction: column;
                padding: 20px;
                gap: 8px;
                max-height: calc(100vh - 70px);
                overflow-y: auto;
                box-shadow: var(--shadow-lg);
                display: none;
            }
            
            .navbar-menu.show {
                display: flex;
            }
            
            .navbar-menu > li {
                width: 100%;
            }
            
            .navbar-menu > li > a,
            .navbar-menu > li > .menu-dropdown-btn {
                width: 100%;
                padding: 14px 18px;
                justify-content: flex-start;
            }
            
            .dropdown-content {
                position: static;
                box-shadow: none;
                border: none;
                margin-top: 8px;
                margin-left: 20px;
                padding: 0;
                background: transparent;
            }
            
            .dropdown.open .dropdown-content {
                display: block;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .user-info {
                display: none;
            }
            
            .btn-logout span {
                display: none;
            }
            
            .navbar-container {
                padding: 0 20px;
            }
        }
        
        @media (max-width: 480px) {
            .navbar-brand span {
                display: none;
            }
            
            .navbar-brand i {
                font-size: 32px;
            }
            
            .navbar-container {
                height: 64px;
            }
            
            .main-content {
                min-height: calc(100vh - 64px);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-warehouse"></i>
                <span>Warehouse</span>
            </a>
            
            <ul class="navbar-menu" id="navbarMenu">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                $role = $user['role'];
                ?>
                
                <!-- Dashboard -->
                <li>
                    <a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <?php if (hasRole(['admin', 'staff_warehouse'])): ?>
                <!-- Master Data Dropdown -->
                <li class="dropdown">
                    <button class="menu-dropdown-btn" onclick="toggleDropdown(this)">
                        <i class="fas fa-database"></i>
                        <span>Data</span>
                        <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="items.php" class="<?php echo $current_page === 'items.php' ? 'active' : ''; ?>">
                            <i class="fas fa-box"></i>
                            <span>Data Barang</span>
                        </a>
                        <a href="categories.php" class="<?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                            <i class="fas fa-folder"></i>
                            <span>Kategori</span>
                        </a>
                        <a href="suppliers.php" class="<?php echo $current_page === 'suppliers.php' ? 'active' : ''; ?>">
                            <i class="fas fa-truck"></i>
                            <span>Supplier</span>
                        </a>
                        <a href="branches.php" class="<?php echo $current_page === 'branches.php' ? 'active' : ''; ?>">
                            <i class="fas fa-building"></i>
                            <span>Cabang</span>
                        </a>
                    </div>
                </li>
                
                <!-- Transaksi Dropdown -->
                <li class="dropdown">
                    <button class="menu-dropdown-btn" onclick="toggleDropdown(this)">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Transaksi</span>
                        <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="stock_in.php" class="<?php echo $current_page === 'stock_in.php' ? 'active' : ''; ?>">
                            <i class="fas fa-arrow-down"></i>
                            <span>Stok Masuk</span>
                        </a>
                        <a href="stock_out.php" class="<?php echo $current_page === 'stock_out.php' ? 'active' : ''; ?>">
                            <i class="fas fa-arrow-up"></i>
                            <span>Stok Keluar</span>
                        </a>
                        <a href="stock_adjustment.php" class="<?php echo $current_page === 'stock_adjustment.php' ? 'active' : ''; ?>">
                            <i class="fas fa-wrench"></i>
                            <span>Opname</span>
                        </a>
                    </div>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole(['admin', 'staff_keuangan'])): ?>
                <!-- Keuangan -->
                <li>
                    <a href="finance.php" class="<?php echo $current_page === 'finance.php' ? 'active' : ''; ?>">
                        <i class="fas fa-wallet"></i>
                        <span>Keuangan</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole(['admin', 'staff_warehouse', 'staff_keuangan'])): ?>
                <!-- Laporan -->
                <li>
                    <a href="reports.php" class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Laporan</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole('admin')): ?>
                <!-- Users -->
                <li>
                    <a href="users.php" class="<?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (hasRole('cabang')): ?>
                <!-- Distribusi untuk Cabang -->
                <li>
                    <a href="my_distributions.php" class="<?php echo $current_page === 'my_distributions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box-open"></i>
                        <span>Distribusi Saya</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="navbar-actions">
                <div class="user-info">
                    <span class="user-name"><?php echo $user['full_name']; ?></span>
                    <span class="user-role"><?php 
                        $role_names = [
                            'admin' => 'Admin',
                            'staff_warehouse' => 'Staff',
                            'staff_keuangan' => 'Keuangan',
                            'cabang' => 'Cabang'
                        ];
                        echo $role_names[$user['role']] ?? $user['role'];
                    ?></span>
                </div>
                
                <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme">
                    <i class="fas fa-moon" id="theme-icon"></i>
                </button>
                
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
                
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>
    
    <div class="main-content">
