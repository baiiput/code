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
}

.card-header a:hover {
    background: rgba(52, 152, 219, 0.3) !important;
    border-color: rgba(52, 152, 219, 0.5) !important;
    color: #ffffff !important;
}

/* ==================== FILTER BOX ==================== */
.filter-box {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px;
    border-radius: 15px;
    margin: 20px 0;
    border: 1px solid #dee2e6;
}

/* ==================== TABLE STYLING ==================== */
.table-responsive {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    color: #ffffff;
    font-weight: 600;
    border: none;
    padding: 15px 10px;
    text-align: center;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table tbody td {
    padding: 12px 10px;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-action {
    padding: 5px 10px;
    margin: 2px;
    border-radius: 5px;
    font-size: 12px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}

.btn-info { background: #3498db; color: white; border: 1px solid #2980b9; }
.btn-danger { background: #e74c3c; color: white; border: 1px solid #c0392b; }
.btn-warning { background: #f39c12; color: white; border: 1px solid #e67e22; }
.btn-success { background: #27ae60; color: white; border: 1px solid #229954; }

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Loading spinner */
.loading-spinner {
    text-align: center;
    padding: 40px;
    font-size: 18px;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.badge {
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success { background: #27ae60; color: white; }
.badge-danger { background: #e74c3c; color: white; }
.badge-warning { background: #f39c12; color: white; }
</style>

<div class="row">
    <div class="col-lg-12">
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
                <div class="filter-box">
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
                <div id="loadingIndicator" class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading PPP Secrets...</p>
                </div>

                <!-- Secrets Table -->
                <div id="secretsTableContainer" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="secretsTable">
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
                    <div style="padding: 15px; text-align: center;">
                        <strong>Total Secrets: <span id="totalSecrets">0</span></strong>
                    </div>
                </div>

                <!-- Error Container -->
                <div id="errorContainer" style="display: none; padding: 20px; text-align: center;">
                    <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #e74c3c;"></i>
                    <p id="errorMessage" style="margin-top: 15px; font-size: 16px;"></p>
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
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('secretsTableContainer').style.display = 'none';
    document.getElementById('errorContainer').style.display = 'none';

    // AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('GET', './dashboard/aload.php?load=pppsecrets&session=' + session + '&profile=' + filterProfile + '&service=' + filterService, true);
    xhr.timeout = 60000; // 60 second timeout

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
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;">No PPP secrets found</td></tr>';
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
