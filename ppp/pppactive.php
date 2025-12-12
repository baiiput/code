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
}

.card-header a:hover {
    background: rgba(52, 152, 219, 0.3) !important;
    border-color: rgba(52, 152, 219, 0.5) !important;
    color: #ffffff !important;
}

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

.badge-success {
    background: linear-gradient(135deg, #27ae60, #229954);
    color: white;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
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

.btn-danger {
    background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
    color: #ffffff !important;
}

.btn-action:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
}

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
                <div id="loadingIndicator" class="loading-overlay">
                    <div class="loading-container">
                        <div class="loading-icon"></div>
                        <div class="loading-status">Loading Active Connections</div>
                        <div class="loading-message">Please wait...</div>
                    </div>
                </div>

                <div id="activeTableContainer" style="display: none;">
                    <div class="table-container">
                        <table id="dataTable">
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
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Total Active: <span id="totalActive">0</span>
                        </div>
                    </div>
                </div>

                <div id="errorContainer" style="display: none; padding: 60px; text-align: center;">
                    <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #e74c3c;"></i>
                    <p id="errorMessage" style="margin-top: 15px; font-size: 16px; color: #ffffff;"></p>
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
    document.getElementById('loadingIndicator').style.display = 'flex';
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
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #ffffff;">No active connections</td></tr>';
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
                <td style="text-align: center;"><span class="badge-success">Connected</span></td>
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
