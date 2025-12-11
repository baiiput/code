<?php
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
}
?>

<style>
.card-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    border: none !important;
    border-radius: 20px 20px 0 0 !important;
    padding: 20px 25px !important;
}

.card-header h3 {
    color: #ffffff !important;
    font-weight: 700 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 15px !important;
}

.card-header a {
    color: #ecf0f1 !important;
    text-decoration: none !important;
    padding: 8px 15px !important;
    border-radius: 25px !important;
    background: rgba(255, 255, 255, 0.1) !important;
    font-weight: 600 !important;
}

.table thead th {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    color: #ffffff;
    font-weight: 600;
    padding: 15px 10px;
    text-align: center;
}

.table tbody td {
    padding: 12px 10px;
    vertical-align: middle;
}

.badge-success { background: #27ae60; color: white; padding: 5px 10px; border-radius: 12px; }

.btn-action {
    padding: 5px 10px;
    margin: 2px;
    border-radius: 5px;
    font-size: 12px;
    text-decoration: none;
    display: inline-block;
}

.btn-danger { background: #e74c3c; color: white; }

.loading-spinner {
    text-align: center;
    padding: 40px;
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
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fa fa-signal"></i>
                    PPP Active Connections
                    <span style="margin-left: auto;">
                        <a href="javascript:void(0)" onclick="loadActive()">
                            <i class="fa fa-refresh"></i> Refresh
                        </a>
                    </span>
                </h3>
            </div>

            <div class="card-body">
                <div id="loadingIndicator" class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading Active Connections...</p>
                </div>

                <div id="activeTableContainer" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 15%;">Name</th>
                                    <th style="width: 12%;">Service</th>
                                    <th style="width: 12%;">Caller ID</th>
                                    <th style="width: 12%;">Address</th>
                                    <th style="width: 12%;">Uptime</th>
                                    <th style="width: 10%;">Encoding</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 12%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="activeTableBody"></tbody>
                        </table>
                    </div>
                    <div style="padding: 15px; text-align: center;">
                        <strong>Total Active: <span id="totalActive">0</span></strong>
                    </div>
                </div>

                <div id="errorContainer" style="display: none; padding: 20px; text-align: center;">
                    <p id="errorMessage"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadActive();
    // Auto refresh every 10 seconds
    setInterval(loadActive, 10000);
});

function loadActive() {
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('activeTableContainer').style.display = 'none';
    document.getElementById('errorContainer').style.display = 'none';

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './dashboard/aload.php?load=pppactive&session=<?= $session ?>', true);
    xhr.timeout = 60000;

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    displayActive(response.data || []);
                } else {
                    showError(response.error || 'Failed to load active connections');
                }
            } catch (e) {
                showError('Invalid response from server');
            }
        } else {
            showError('Server error: ' + xhr.status);
        }
    };

    xhr.onerror = function() {
        showError('Network error');
    };

    xhr.send();
}

function displayActive(data) {
    var tbody = document.getElementById('activeTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;">No active connections</td></tr>';
    } else {
        data.forEach(function(conn, index) {
            var row = document.createElement('tr');
            row.innerHTML = `
                <td style="text-align: center;">${index + 1}</td>
                <td>${escapeHtml(conn.name)}</td>
                <td>${escapeHtml(conn.service || '-')}</td>
                <td>${escapeHtml(conn['caller-id'] || '-')}</td>
                <td>${escapeHtml(conn.address || '-')}</td>
                <td>${escapeHtml(conn.uptime || '-')}</td>
                <td>${escapeHtml(conn.encoding || '-')}</td>
                <td><span class="badge-success">Connected</span></td>
                <td style="text-align: center;">
                    <a href="javascript:void(0)" onclick="disconnectUser('${escapeHtml(conn['.id'])}')" class="btn-action btn-danger">
                        <i class="fa fa-times"></i> Disconnect
                    </a>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    document.getElementById('totalActive').textContent = data.length;
    document.getElementById('loadingIndicator').style.display = 'none';
    document.getElementById('activeTableContainer').style.display = 'block';
}

function showError(message) {
    document.getElementById('loadingIndicator').style.display = 'none';
    document.getElementById('errorContainer').style.display = 'block';
    document.getElementById('errorMessage').textContent = message;
}

function escapeHtml(text) {
    var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function disconnectUser(id) {
    if (confirm('Disconnect this PPP connection?')) {
        window.location = './?remove-pactive=' + encodeURIComponent(id) + '&session=<?= $session ?>';
    }
}
</script>
