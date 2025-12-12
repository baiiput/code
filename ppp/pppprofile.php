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

.card-header a {
    color: #ecf0f1 !important;
    text-decoration: none !important;
    padding: 8px 15px !important;
    border-radius: 25px !important;
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    transition: all 0.3s ease !important;
    font-weight: 600 !important;
}

.table-responsive {
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.table thead th {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    color: #ffffff;
    font-weight: 600;
    border: none;
    padding: 18px 15px;
    text-align: center;
    font-size: 15px;
}

.table tbody td {
    padding: 15px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
    font-size: 14px;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-action {
    padding: 8px 14px;
    margin: 2px;
    border-radius: 6px;
    font-size: 13px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-info { background: #3498db; color: white; border: 1px solid #2980b9; }
.btn-danger { background: #e74c3c; color: white; border: 1px solid #c0392b; }

.loading-spinner {
    text-align: center;
    padding: 60px;
    font-size: 18px;
}

.spinner {
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

.card-body {
    padding: 30px;
}

.total-info {
    padding: 20px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    background: #f8f9fa;
    border-top: 2px solid #dee2e6;
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
                    <i class="fa fa-cogs"></i>
                    PPP Profiles
                    <span style="margin-left: auto; display: flex; gap: 10px;">
                        <a href="./?ppp=add-profile&session=<?= $session ?>">
                            <i class="fa fa-plus"></i> Add Profile
                        </a>
                        <a href="javascript:void(0)" onclick="loadProfiles()">
                            <i class="fa fa-refresh"></i> Refresh
                        </a>
                    </span>
                </h3>
            </div>

            <div class="card-body">
                <div id="loadingIndicator" class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading PPP Profiles...</p>
                </div>

                <div id="profilesTableContainer" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 20%;">Name</th>
                                    <th style="width: 15%;">Local Address</th>
                                    <th style="width: 15%;">Remote Address</th>
                                    <th style="width: 12%;">Rate Limit</th>
                                    <th style="width: 18%;">Session Timeout</th>
                                    <th style="width: 15%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="profilesTableBody"></tbody>
                        </table>
                    </div>
                    <div class="total-info">
                        Total Profiles: <span id="totalProfiles">0</span>
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
    loadProfiles();
});

function loadProfiles() {
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('profilesTableContainer').style.display = 'none';
    document.getElementById('errorContainer').style.display = 'none';

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './dashboard/aload.php?load=pppprofiles&session=<?= $session ?>', true);
    xhr.timeout = 60000;

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    displayProfiles(response.data || []);
                } else {
                    showError(response.error || 'Failed to load profiles');
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

function displayProfiles(data) {
    var tbody = document.getElementById('profilesTableBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">No profiles found</td></tr>';
    } else {
        data.forEach(function(profile, index) {
            var row = document.createElement('tr');
            row.innerHTML = `
                <td style="text-align: center;">${index + 1}</td>
                <td>${escapeHtml(profile.name)}</td>
                <td>${escapeHtml(profile['local-address'] || '-')}</td>
                <td>${escapeHtml(profile['remote-address'] || '-')}</td>
                <td>${escapeHtml(profile['rate-limit'] || '-')}</td>
                <td>${escapeHtml(profile['session-timeout'] || '-')}</td>
                <td style="text-align: center;">
                    <a href="./?ppp=edit-profile&profile=${encodeURIComponent(profile.name)}&session=<?= $session ?>" class="btn-action btn-info">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <a href="javascript:void(0)" onclick="removeProfile('${escapeHtml(profile.name)}')" class="btn-action btn-danger">
                        <i class="fa fa-trash"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    document.getElementById('totalProfiles').textContent = data.length;
    document.getElementById('loadingIndicator').style.display = 'none';
    document.getElementById('profilesTableContainer').style.display = 'block';
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

function removeProfile(name) {
    if (confirm('Delete profile: ' + name + '?')) {
        window.location = './?remove-pprofile=' + encodeURIComponent(name) + '&session=<?= $session ?>';
    }
}
</script>
