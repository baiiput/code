<nav class="navbar">
    <div class="navbar-brand">
        <button id="sidebarToggle" class="btn-icon">☰</button>
        <span class="brand-text"><?php echo APP_NAME; ?></span>
    </div>

    <div class="navbar-menu">
        <button id="themeToggle" class="btn-icon" title="Toggle Dark Mode">
            <span class="theme-icon-light">🌙</span>
            <span class="theme-icon-dark">☀️</span>
        </button>

        <div class="user-menu">
            <button class="user-menu-btn">
                <span class="user-avatar">👤</span>
                <span class="user-name"><?php echo $_SESSION['full_name']; ?></span>
                <span class="dropdown-arrow">▼</span>
            </button>
            <div class="user-dropdown">
                <a href="#" class="dropdown-item">
                    <span class="icon">👤</span> Profile
                </a>
                <a href="#" class="dropdown-item">
                    <span class="icon">⚙️</span> Pengaturan
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" id="logoutBtn" class="dropdown-item">
                    <span class="icon">🚪</span> Logout
                </a>
            </div>
        </div>
    </div>
</nav>
