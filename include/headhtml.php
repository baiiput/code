<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mikhmon - MikroTik Hotspot Monitor</title>

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

  <style>
  /* ==================== MODERN COMPACT NAVIGATION STYLES ==================== */
  :root {
    --navbar-height: 60px;
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 70px;
    --bg-dark: #1a1d29;
    --bg-darker: #14171f;
    --bg-light: #2d3142;
    --accent-blue: #4a9eff;
    --accent-green: #00d084;
    --text-primary: #ffffff;
    --text-muted: #8b8d98;
    --border-color: #2d3142;
    --transition-speed: 0.3s;
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #0f1117;
    color: var(--text-primary);
    overflow-x: hidden;
  }

  /* ==================== TOP NAVBAR ==================== */
  .modern-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--navbar-height);
    background: var(--bg-dark);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    padding: 0 20px;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  }

  .navbar-left {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .navbar-toggle-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: var(--bg-light);
    color: var(--text-primary);
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-speed);
    font-size: 18px;
  }

  .navbar-toggle-btn:hover {
    background: var(--accent-blue);
    transform: scale(1.05);
  }

  .navbar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    text-decoration: none;
  }

  .navbar-brand i {
    color: var(--accent-blue);
    font-size: 24px;
  }

  .navbar-brand span {
    background: linear-gradient(135deg, var(--accent-blue), var(--accent-green));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .navbar-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .navbar-router-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 15px;
    background: var(--bg-light);
    border-radius: 8px;
    font-size: 13px;
  }

  .navbar-router-info i {
    color: var(--accent-green);
  }

  .navbar-user {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 15px;
    background: var(--bg-light);
    border-radius: 8px;
    cursor: pointer;
    transition: all var(--transition-speed);
  }

  .navbar-user:hover {
    background: var(--accent-blue);
  }

  .navbar-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent-blue), var(--accent-green));
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
  }

  .navbar-logout {
    padding: 8px 15px;
    background: rgba(255, 87, 87, 0.1);
    color: #ff5757;
    border: 1px solid rgba(255, 87, 87, 0.3);
    border-radius: 8px;
    cursor: pointer;
    transition: all var(--transition-speed);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
  }

  .navbar-logout:hover {
    background: #ff5757;
    color: white;
    border-color: #ff5757;
  }

  /* ==================== SIDEBAR NAVIGATION ==================== */
  .modern-sidebar {
    position: fixed;
    top: var(--navbar-height);
    left: 0;
    width: var(--sidebar-width);
    height: calc(100vh - var(--navbar-height));
    background: var(--bg-dark);
    border-right: 1px solid var(--border-color);
    transition: all var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 999;
    overflow-y: auto;
    overflow-x: hidden;
  }

  .modern-sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
  }

  .modern-sidebar::-webkit-scrollbar {
    width: 6px;
  }

  .modern-sidebar::-webkit-scrollbar-track {
    background: var(--bg-darker);
  }

  .modern-sidebar::-webkit-scrollbar-thumb {
    background: var(--bg-light);
    border-radius: 3px;
  }

  .sidebar-menu {
    padding: 20px 0;
  }

  .menu-section {
    margin-bottom: 25px;
  }

  .menu-section-title {
    padding: 0 20px;
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-muted);
    font-weight: 600;
    letter-spacing: 1px;
    margin-bottom: 10px;
    transition: all var(--transition-speed);
  }

  .collapsed .menu-section-title {
    opacity: 0;
    height: 0;
    margin: 0;
    padding: 0;
    overflow: hidden;
  }

  .menu-item {
    position: relative;
    margin: 2px 10px;
  }

  .menu-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 8px;
    transition: all var(--transition-speed);
    position: relative;
    overflow: hidden;
  }

  .menu-link:hover {
    background: var(--bg-light);
    color: var(--text-primary);
  }

  .menu-link.active {
    background: linear-gradient(135deg, rgba(74, 158, 255, 0.15), rgba(0, 208, 132, 0.15));
    color: var(--accent-blue);
    font-weight: 600;
  }

  .menu-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(180deg, var(--accent-blue), var(--accent-green));
  }

  .menu-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 18px;
    flex-shrink: 0;
  }

  .menu-text {
    flex: 1;
    white-space: nowrap;
    transition: all var(--transition-speed);
    font-size: 14px;
    font-weight: 500;
  }

  .collapsed .menu-text {
    opacity: 0;
    width: 0;
    overflow: hidden;
  }

  .menu-arrow {
    font-size: 12px;
    transition: transform var(--transition-speed);
    margin-left: auto;
  }

  .collapsed .menu-arrow {
    opacity: 0;
  }

  .menu-item.has-submenu.open .menu-arrow {
    transform: rotate(180deg);
  }

  .submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height var(--transition-speed) ease-out;
    background: var(--bg-darker);
    margin: 5px 0;
    border-radius: 8px;
  }

  .menu-item.has-submenu.open .submenu {
    max-height: 500px;
    transition: max-height var(--transition-speed) ease-in;
  }

  .collapsed .submenu {
    display: none;
  }

  .submenu-link {
    display: flex;
    align-items: center;
    padding: 10px 15px 10px 50px;
    color: var(--text-muted);
    text-decoration: none;
    transition: all var(--transition-speed);
    font-size: 13px;
  }

  .submenu-link:hover {
    color: var(--text-primary);
    background: rgba(255, 255, 255, 0.05);
  }

  .submenu-link.active {
    color: var(--accent-blue);
    font-weight: 600;
  }

  .submenu-link i {
    width: 16px;
    margin-right: 10px;
    font-size: 10px;
  }

  /* ==================== MAIN CONTENT AREA ==================== */
  .main-content {
    margin-top: var(--navbar-height);
    margin-left: var(--sidebar-width);
    padding: 25px;
    min-height: calc(100vh - var(--navbar-height));
    transition: margin-left var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
    background: #0f1117;
  }

  .sidebar-collapsed .main-content {
    margin-left: var(--sidebar-collapsed-width);
  }

  /* ==================== RESPONSIVE ==================== */
  @media (max-width: 768px) {
    .modern-sidebar {
      transform: translateX(-100%);
    }

    .modern-sidebar.mobile-open {
      transform: translateX(0);
    }

    .main-content {
      margin-left: 0;
    }

    .navbar-router-info span {
      display: none;
    }

    .navbar-user span {
      display: none;
    }
  }

  /* ==================== TOOLTIP FOR COLLAPSED SIDEBAR ==================== */
  .collapsed .menu-item {
    position: relative;
  }

  .collapsed .menu-link::after {
    content: attr(data-tooltip);
    position: absolute;
    left: calc(100% + 15px);
    top: 50%;
    transform: translateY(-50%);
    background: var(--bg-darker);
    color: var(--text-primary);
    padding: 8px 12px;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--transition-speed);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    border: 1px solid var(--border-color);
    font-size: 13px;
    z-index: 1000;
  }

  .collapsed .menu-link:hover::after {
    opacity: 1;
  }
  </style>
</head>
<body>

<!-- Top Navbar -->
<nav class="modern-navbar">
  <div class="navbar-left">
    <button class="navbar-toggle-btn" id="sidebarToggle">
      <i class="fa fa-bars"></i>
    </button>
    <a href="./?session=<?= $session; ?>" class="navbar-brand">
      <i class="fa fa-wifi"></i>
      <span>Mikhmon</span>
    </a>
  </div>

  <div class="navbar-right">
    <div class="navbar-router-info">
      <i class="fa fa-server"></i>
      <span><?= isset($identity) ? $identity : 'Router'; ?></span>
    </div>

    <div class="navbar-user">
      <div class="navbar-user-avatar">
        <?= strtoupper(substr($_SESSION['user'], 0, 1)); ?>
      </div>
      <span><?= isset($_SESSION['user']) ? $_SESSION['user'] : 'Admin'; ?></span>
    </div>

    <a href="./?hotspot=logout&session=<?= $session; ?>" class="navbar-logout">
      <i class="fa fa-sign-out"></i>
      <span>Logout</span>
    </a>
  </div>
</nav>

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.querySelector('.modern-sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const body = document.body;

  // Load saved state
  const savedState = localStorage.getItem('sidebarCollapsed');
  if (savedState === 'true') {
    sidebar.classList.add('collapsed');
    body.classList.add('sidebar-collapsed');
  }

  // Toggle sidebar
  toggleBtn.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    body.classList.toggle('sidebar-collapsed');

    // Save state
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
  });

  // Mobile responsive
  if (window.innerWidth <= 768) {
    sidebar.classList.remove('collapsed');
    body.classList.remove('sidebar-collapsed');

    toggleBtn.addEventListener('click', function() {
      sidebar.classList.toggle('mobile-open');
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
      if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('mobile-open');
      }
    });
  }
});
</script>
