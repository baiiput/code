<?php
error_reporting(0);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
  // Load profiles for filter dropdown
  if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
    $getprofile = $API->comm("/ppp/profile/print");
    $TotalProfiles = count($getprofile);
    $API->disconnect();
  } else {
    $getprofile = array();
    $TotalProfiles = 0;
  }
}
?>

<style>
/* ==================== HEADER STYLING ==================== */
.card-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    border: none !important;
    border-radius: 20px 20px 0 0 !important;
    padding: 20px 25px !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.card-header h3 {
    color: #ffffff !important;
    font-weight: 700 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 15px !important;
}

.card-header h3 i {
    font-size: 24px !important;
    color: #3498db !important;
}

.card-header a {
    color: #ecf0f1 !important;
    text-decoration: none !important;
    padding: 8px 15px !important;
    border-radius: 25px !important;
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    transition: all 0.3s ease !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin: 0 4px !important;
}

.card-header a:hover {
    background: rgba(52, 152, 219, 0.3) !important;
    border-color: rgba(52, 152, 219, 0.5) !important;
    color: #ffffff !important;
}

/* ==================== FILTER BOX ==================== */
.filter-container {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%) !important;
    border-radius: 18px !important;
    padding: 20px !important;
    margin: 20px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.filter-container label {
    color: #ffffff !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    margin-bottom: 8px !important;
    display: block;
}

.filter-container .form-control, .filter-container select {
    background: rgba(52, 58, 70, 0.9) !important;
    border: 2px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 12px !important;
    color: #ffffff !important;
    padding: 12px 18px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    transition: border-color 0.3s ease !important;
    width: 100% !important;
}

.filter-container .form-control::placeholder {
    color: rgba(255, 255, 255, 0.6) !important;
}

.filter-container .form-control:focus, .filter-container select:focus {
    outline: none !important;
    border-color: #3498db !important;
}

.filter-container select {
    appearance: none !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 16 16'%3e%3cpath d='m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    background-size: 12px !important;
    cursor: pointer !important;
}

/* ==================== TABLE STYLING ==================== */
.table-container {
    background: rgba(40, 44, 52, 0.95) !important;
    border-radius: 20px !important;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    margin: 20px !important;
    overflow: hidden;
}

#dataTable {
    margin: 0 !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    background: rgba(40, 44, 52, 0.95) !important;
    width: 100%;
}

#dataTable thead th {
    background: linear-gradient(135deg, #0f4c75, #3282b8) !important;
    color: white !important;
    border: none !important;
    padding: 15px !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    font-size: 12px !important;
    position: sticky;
    top: 0;
    z-index: 10;
    text-align: center;
}

#dataTable tbody tr {
    background: rgba(52, 58, 70, 0.8) !important;
    color: #ffffff !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    transition: background-color 0.2s ease !important;
}

#dataTable tbody tr:nth-child(even) {
    background: rgba(45, 52, 64, 0.9) !important;
}

#dataTable tbody tr:hover {
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(41, 128, 185, 0.2)) !important;
}

#dataTable tbody td {
    padding: 12px 15px !important;
    border: none !important;
    vertical-align: middle !important;
    color: #ffffff !important;
}

.btn-action {
    border: none !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    transition: all 0.3s ease !important;
    text-decoration: none !important;
    display: inline-block !important;
    margin: 2px !important;
}

.btn-info {
    background: linear-gradient(135deg, #3498db, #2980b9) !important;
    color: #ffffff !important;
}

.btn-danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
    color: #ffffff !important;
}

.btn-warning {
    background: linear-gradient(135deg, #f39c12, #e67e22) !important;
    color: #ffffff !important;
}

.btn-success {
    background: linear-gradient(135deg, #27ae60, #229954) !important;
    color: #ffffff !important;
}

.btn-action:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
}

/* ==================== LOADING OVERLAY ==================== */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(13, 17, 23, 0.95), rgba(22, 27, 34, 0.98));
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-container {
    background: linear-gradient(145deg, #1a1d23, #2d3339);
    border: 1px solid rgba(79, 172, 254, 0.2);
    border-radius: 24px;
    padding: 50px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    min-width: 380px;
}

.loading-icon {
    width: 60px;
    height: 60px;
    border: 3px solid rgba(79, 172, 254, 0.2);
    border-top: 3px solid #4facfe;
    border-radius: 50%;
    animation: spin 1.2s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-status {
    color: #ffffff;
    font-size: 18px;
    font-weight: 600;
}

.loading-message {
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    margin-top: 10px;
}

.badge {
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: linear-gradient(135deg, #27ae60, #229954);
    color: white;
}

.badge-danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: rgba(45, 52, 64, 0.9);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0 0 20px 20px;
}

.pagination-info {
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
}

.card-body {
    padding: 0 !important;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fa fa-link"></i>
                    PPP Secrets
                    <span style="margin-left: auto; display: flex; gap: 10px;">
                        <a href="./?ppp=addsecret&session=<?= $session ?>">
                            <i class="fa fa-plus"></i> Add Secret
                        </a>
                        <a href="javascript:void(0)" onclick="loadSecrets()">
                            <i class="fa fa-refresh"></i> Refresh
                        </a>
                    </span>
                </h3>
            </div>

            <div class="card-body">
                <!-- Filter Box -->
                <div class="filter-container">
                    <div class="row">
                        <div class="col-md-4">
                            <label>Filter by Profile:</label>
                            <select id="filterProfile" class="form-control" onchange="loadSecrets()">
                                <option value="all">All Profiles</option>
                                <?php foreach($getprofile as $profile): ?>
                                    <option value="<?= $profile['name'] ?>"><?= $profile['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Filter by Service:</label>
                            <select id="filterService" class="form-control" onchange="loadSecrets()">
                                <option value="all">All Services</option>
                                <option value="pppoe">PPPoE</option>
                                <option value="pptp">PPTP</option>
                                <option value="l2tp">L2TP</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Search:</label>
                            <input type="text" id="searchBox" class="form-control" placeholder="Search by name..." onkeyup="filterTable()">
                        </div>
                    </div>
                </div>

                <!-- Loading Indicator -->
                <div id="loadingIndicator" class="loading-overlay">
                    <div class="loading-container">
                        <div class="loading-icon"></div>
                        <div class="loading-status">Loading PPP Secrets</div>
                        <div class="loading-message">Please wait...</div>
                    </div>
                </div>

                <!-- Secrets Table -->
                <div id="secretsTableContainer" style="display: none;">
                    <div class="table-container">
                        <table id="dataTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 15%;">Name</th>
                                    <th style="width: 12%;">Password</th>
                                    <th style="width: 12%;">Service</th>
                                    <th style="width: 12%;">Profile</th>
                                    <th style="width: 10%;">Local Address</th>
                                    <th style="width: 10%;">Remote Address</th>
                                    <th style="width: 8%;">Status</th>
                                    <th style="width: 16%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="secretsTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Total Secrets: <span id="totalSecrets">0</span>
                        </div>
                    </div>
                </div>

                <!-- Error Container -->
                <div id="errorContainer" style="display: none; padding: 60px; text-align: center;">
                    <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #e74c3c;"></i>
                    <p id="errorMessage" style="margin-top: 15px; font-size: 16px; color: #ffffff;"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var secretsData = [];

// Load secrets on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSecrets();
});

function loadSecrets() {
    var session = "<?= $session ?>";
    var filterProfile = document.getElementById('filterProfile').value;
    var filterService = document.getElementById('filterService').value;

    // Show loading
    document.getElementById('loadingIndicator').style.display = 'flex';
    document.getElementById('secretsTableContainer').style.display = 'none';
    document.getElementById('errorContainer').style.display = 'none';

    // AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('GET', './dashboard/aload.php?load=pppsecrets&session=' + session + '&profile=' + filterProfile + '&service=' + filterService, true);
    xhr.timeout = 60000;

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);

                if (response.success) {
                    secretsData = response.data || [];
                    displaySecrets(secretsData);
                } else {
                    showError(response.error || 'Failed to load PPP secrets');
                }
            } catch (e) {
                showError('Invalid response from server');
                console.error('Parse error:', e);
            }
        } else {
            showError('Server error: ' + xhr.status);
        }
    };

    xhr.onerror = function() {
        showError('Network error - please check your connection');
    };

    xhr.ontimeout = function() {
        showError('Request timeout - please try again');
    };

    xhr.send();
}

function displaySecrets(data) {
    var tbody = document.getElementById('secretsTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #ffffff;">No PPP secrets found</td></tr>';
    } else {
        data.forEach(function(secret, index) {
            var row = document.createElement('tr');

            var disabled = secret.disabled === 'true' || secret.disabled === true;
            var statusBadge = disabled
                ? '<span class="badge badge-danger">Disabled</span>'
                : '<span class="badge badge-success">Enabled</span>';

            var service = secret.service || 'any';
            var profile = secret.profile || 'default';
            var localAddr = secret['local-address'] || '-';
            var remoteAddr = secret['remote-address'] || '-';

            row.innerHTML = `
                <td style="text-align: center;">${index + 1}</td>
                <td>${escapeHtml(secret.name)}</td>
                <td>${escapeHtml(secret.password || '***')}</td>
                <td>${escapeHtml(service)}</td>
                <td>${escapeHtml(profile)}</td>
                <td>${escapeHtml(localAddr)}</td>
                <td>${escapeHtml(remoteAddr)}</td>
                <td style="text-align: center;">${statusBadge}</td>
                <td style="text-align: center;">
                    <a href="./?secret=${encodeURIComponent(secret.name)}&session=<?= $session ?>" class="btn-action btn-info" title="Details">
                        <i class="fa fa-eye"></i>
                    </a>
                    ${disabled
                        ? `<a href="javascript:void(0)" onclick="enableSecret('${escapeHtml(secret.name)}')" class="btn-action btn-success" title="Enable"><i class="fa fa-check"></i></a>`
                        : `<a href="javascript:void(0)" onclick="disableSecret('${escapeHtml(secret.name)}')" class="btn-action btn-warning" title="Disable"><i class="fa fa-ban"></i></a>`
                    }
                    <a href="javascript:void(0)" onclick="removeSecret('${escapeHtml(secret.name)}')" class="btn-action btn-danger" title="Delete">
                        <i class="fa fa-trash"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    document.getElementById('totalSecrets').textContent = data.length;
    document.getElementById('loadingIndicator').style.display = 'none';
    document.getElementById('secretsTableContainer').style.display = 'block';
}

function filterTable() {
    var searchValue = document.getElementById('searchBox').value.toLowerCase();
    var filteredData = secretsData.filter(function(secret) {
        return secret.name.toLowerCase().includes(searchValue);
    });
    displaySecrets(filteredData);
}

function showError(message) {
    document.getElementById('loadingIndicator').style.display = 'none';
    document.getElementById('secretsTableContainer').style.display = 'none';
    document.getElementById('errorContainer').style.display = 'block';
    document.getElementById('errorMessage').textContent = message;
}

function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function enableSecret(name) {
    if (confirm('Enable PPP secret: ' + name + '?')) {
        window.location = './?enable-pppsecret=' + encodeURIComponent(name) + '&session=<?= $session ?>';
    }
}

function disableSecret(name) {
    if (confirm('Disable PPP secret: ' + name + '?')) {
        window.location = './?disable-pppsecret=' + encodeURIComponent(name) + '&session=<?= $session ?>';
    }
}

function removeSecret(name) {
    if (confirm('Delete PPP secret: ' + name + '?\n\nThis action cannot be undone!')) {
        window.location = './?remove-pppsecret=' + encodeURIComponent(name) + '&session=<?= $session ?>';
    }
}
</script>
