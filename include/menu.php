<!-- Sidebar Navigation -->
<aside class="modern-sidebar" id="modernSidebar">
  <div class="sidebar-menu">

    <!-- Dashboard Section -->
    <div class="menu-section">
      <div class="menu-section-title">Main</div>
      <div class="menu-item">
        <a href="./?hotspot=dashboard&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'dashboard' || substr($_SERVER['REQUEST_URI'], -8) == '?session') ? 'active' : ''; ?>" data-tooltip="Dashboard">
          <span class="menu-icon"><i class="fa fa-dashboard"></i></span>
          <span class="menu-text">Dashboard</span>
        </a>
      </div>
    </div>

    <!-- Hotspot Section -->
    <div class="menu-section">
      <div class="menu-section-title">Hotspot</div>

      <!-- Users Menu with Submenu -->
      <div class="menu-item has-submenu <?= (in_array($hotspot, ['users', 'add-user', 'user-by-name'])) ? 'open' : ''; ?>">
        <a href="javascript:void(0)" class="menu-link <?= ($hotspot == 'users') ? 'active' : ''; ?>" data-tooltip="Hotspot Users">
          <span class="menu-icon"><i class="fa fa-users"></i></span>
          <span class="menu-text">Users</span>
          <i class="fa fa-chevron-down menu-arrow"></i>
        </a>
        <div class="submenu">
          <a href="./?hotspot=users&profile=all&session=<?= $session; ?>" class="submenu-link <?= ($hotspot == 'users') ? 'active' : ''; ?>">
            <i class="fa fa-circle"></i> All Users
          </a>
          <a href="./?hotspot=add-user&session=<?= $session; ?>" class="submenu-link <?= ($hotspot == 'add-user') ? 'active' : ''; ?>">
            <i class="fa fa-circle"></i> Add User
          </a>
        </div>
      </div>

      <!-- Active Users -->
      <div class="menu-item">
        <a href="./?hotspot=active&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'active') ? 'active' : ''; ?>" data-tooltip="Active Users">
          <span class="menu-icon"><i class="fa fa-signal"></i></span>
          <span class="menu-text">Active</span>
        </a>
      </div>

      <!-- Profiles -->
      <div class="menu-item has-submenu <?= (in_array($hotspot, ['user-profile', 'add-user-profile'])) ? 'open' : ''; ?>">
        <a href="javascript:void(0)" class="menu-link <?= ($hotspot == 'user-profile') ? 'active' : ''; ?>" data-tooltip="User Profiles">
          <span class="menu-icon"><i class="fa fa-id-card"></i></span>
          <span class="menu-text">Profiles</span>
          <i class="fa fa-chevron-down menu-arrow"></i>
        </a>
        <div class="submenu">
          <a href="./?hotspot=user-profile&session=<?= $session; ?>" class="submenu-link <?= ($hotspot == 'user-profile' && !isset($_GET['add'])) ? 'active' : ''; ?>">
            <i class="fa fa-circle"></i> All Profiles
          </a>
          <a href="./?hotspot=user-profile&add=true&session=<?= $session; ?>" class="submenu-link <?= (isset($_GET['add'])) ? 'active' : ''; ?>">
            <i class="fa fa-circle"></i> Add Profile
          </a>
        </div>
      </div>

      <!-- Hosts -->
      <div class="menu-item">
        <a href="./?hotspot=hosts&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'hosts') ? 'active' : ''; ?>" data-tooltip="Hotspot Hosts">
          <span class="menu-icon"><i class="fa fa-laptop"></i></span>
          <span class="menu-text">Hosts</span>
        </a>
      </div>

      <!-- IP Binding -->
      <div class="menu-item">
        <a href="./?hotspot=ipbinding&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'ipbinding') ? 'active' : ''; ?>" data-tooltip="IP Binding">
          <span class="menu-icon"><i class="fa fa-link"></i></span>
          <span class="menu-text">IP Binding</span>
        </a>
      </div>

      <!-- Cookies -->
      <div class="menu-item">
        <a href="./?hotspot=cookies&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'cookies') ? 'active' : ''; ?>" data-tooltip="Hotspot Cookies">
          <span class="menu-icon"><i class="fa fa-dot-circle-o"></i></span>
          <span class="menu-text">Cookies</span>
        </a>
      </div>

      <!-- DHCP Leases -->
      <div class="menu-item">
        <a href="./?hotspot=dhcp-leases&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'dhcp-leases') ? 'active' : ''; ?>" data-tooltip="DHCP Leases">
          <span class="menu-icon"><i class="fa fa-sitemap"></i></span>
          <span class="menu-text">DHCP Leases</span>
        </a>
      </div>

      <!-- Servers -->
      <div class="menu-item">
        <a href="./?hotspot=servers&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'servers') ? 'active' : ''; ?>" data-tooltip="Hotspot Servers">
          <span class="menu-icon"><i class="fa fa-server"></i></span>
          <span class="menu-text">Servers</span>
        </a>
      </div>

      <!-- Log -->
      <div class="menu-item">
        <a href="./?hotspot=log&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'log') ? 'active' : ''; ?>" data-tooltip="Hotspot Log">
          <span class="menu-icon"><i class="fa fa-list"></i></span>
          <span class="menu-text">Log</span>
        </a>
      </div>
    </div>

    <!-- PPPoE Section -->
    <div class="menu-section">
      <div class="menu-section-title">PPPoE</div>

      <!-- PPP Secrets -->
      <div class="menu-item has-submenu <?= (in_array($ppp, ['secrets', 'add-secret'])) ? 'open' : ''; ?>">
        <a href="javascript:void(0)" class="menu-link <?= ($ppp == 'secrets') ? 'active' : ''; ?>" data-tooltip="PPP Secrets">
          <span class="menu-icon"><i class="fa fa-key"></i></span>
          <span class="menu-text">Secrets</span>
          <i class="fa fa-chevron-down menu-arrow"></i>
        </a>
        <div class="submenu">
          <a href="./?ppp=secrets&session=<?= $session; ?>" class="submenu-link <?= ($ppp == 'secrets') ? 'active' : ''; ?>">
            <i class="fa fa-circle"></i> All Secrets
          </a>
          <a href="./?ppp=add-secret&session=<?= $session; ?>" class="submenu-link <?= ($ppp == 'add-secret') ? 'active' : ''; ?>">
            <i class="fa fa-circle"></i> Add Secret
          </a>
        </div>
      </div>

      <!-- PPP Profiles -->
      <div class="menu-item has-submenu <?= (in_array($ppp, ['profiles', 'add-profile'])) ? 'open' : ''; ?>">
        <a href="javascript:void(0)" class="menu-link <?= ($ppp == 'profiles') ? 'active' : ''; ?>" data-tooltip="PPP Profiles">
          <span class="menu-icon"><i class="fa fa-id-card-o"></i></span>
          <span class="menu-text">Profiles</span>
          <i class="fa fa-chevron-down menu-arrow"></i>
        </a>
        <div class="submenu">
          <a href="./?ppp=profiles&session=<?= $session; ?>" class="submenu-link <?= ($ppp == 'profiles') ? 'active' : ''; ?>">
            <i class="fa fa-circle"></i> All Profiles
          </a>
          <a href="./?ppp=add-profile&session=<?= $session; ?>" class="submenu-link <?= ($ppp == 'add-profile') ? 'active' : ''; ?>">
            <i class="fa fa-circle"></i> Add Profile
          </a>
        </div>
      </div>

      <!-- PPP Active -->
      <div class="menu-item">
        <a href="./?ppp=active&session=<?= $session; ?>" class="menu-link <?= ($ppp == 'active') ? 'active' : ''; ?>" data-tooltip="Active PPP">
          <span class="menu-icon"><i class="fa fa-plug"></i></span>
          <span class="menu-text">Active</span>
        </a>
      </div>
    </div>

    <!-- Reports Section -->
    <div class="menu-section">
      <div class="menu-section-title">Reports</div>

      <!-- User Log -->
      <div class="menu-item">
        <a href="./?report=userlog&session=<?= $session; ?>" class="menu-link <?= ($report == 'userlog') ? 'active' : ''; ?>" data-tooltip="User Log">
          <span class="menu-icon"><i class="fa fa-history"></i></span>
          <span class="menu-text">User Log</span>
        </a>
      </div>

      <!-- Selling Report -->
      <div class="menu-item">
        <a href="./?report=selling&session=<?= $session; ?>" class="menu-link <?= ($report == 'selling') ? 'active' : ''; ?>" data-tooltip="Selling Report">
          <span class="menu-icon"><i class="fa fa-money"></i></span>
          <span class="menu-text">Selling</span>
        </a>
      </div>

      <!-- Resume Report -->
      <div class="menu-item">
        <a href="./?report=resumereport&session=<?= $session; ?>" class="menu-link <?= ($report == 'resumereport') ? 'active' : ''; ?>" data-tooltip="Resume Report">
          <span class="menu-icon"><i class="fa fa-line-chart"></i></span>
          <span class="menu-text">Resume</span>
        </a>
      </div>

      <!-- Print Voucher -->
      <div class="menu-item">
        <a href="./report/print.php?session=<?= $session; ?>" class="menu-link" data-tooltip="Print Voucher" target="_blank">
          <span class="menu-icon"><i class="fa fa-print"></i></span>
          <span class="menu-text">Print</span>
        </a>
      </div>
    </div>

    <!-- System Section -->
    <div class="menu-section">
      <div class="menu-section-title">System</div>

      <!-- Settings -->
      <div class="menu-item">
        <a href="./?hotspot=settings&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'settings') ? 'active' : ''; ?>" data-tooltip="Settings">
          <span class="menu-icon"><i class="fa fa-cog"></i></span>
          <span class="menu-text">Settings</span>
        </a>
      </div>

      <!-- About -->
      <div class="menu-item">
        <a href="./?hotspot=about&session=<?= $session; ?>" class="menu-link <?= ($hotspot == 'about') ? 'active' : ''; ?>" data-tooltip="About">
          <span class="menu-icon"><i class="fa fa-info-circle"></i></span>
          <span class="menu-text">About</span>
        </a>
      </div>
    </div>

  </div>
</aside>

<!-- Main Content Wrapper -->
<div class="main-content">

<script>
// Submenu toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  const menuItems = document.querySelectorAll('.menu-item.has-submenu > .menu-link');

  menuItems.forEach(item => {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      const parent = this.parentElement;
      const sidebar = document.querySelector('.modern-sidebar');

      // Don't toggle if sidebar is collapsed
      if (sidebar.classList.contains('collapsed')) {
        return;
      }

      // Close other open submenus
      document.querySelectorAll('.menu-item.has-submenu.open').forEach(openItem => {
        if (openItem !== parent) {
          openItem.classList.remove('open');
        }
      });

      // Toggle current submenu
      parent.classList.toggle('open');
    });
  });

  // Set active menu based on current URL
  const currentUrl = window.location.href;
  document.querySelectorAll('.menu-link, .submenu-link').forEach(link => {
    if (link.href && currentUrl.includes(link.getAttribute('href'))) {
      link.classList.add('active');

      // Open parent submenu if it's a submenu link
      const parentSubmenu = link.closest('.has-submenu');
      if (parentSubmenu) {
        parentSubmenu.classList.add('open');
      }
    }
  });
});
</script>
