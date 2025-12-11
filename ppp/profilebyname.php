<?php
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

$profilename = isset($_GET['profile']) ? $_GET['profile'] : '';

// Load profile details
if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
    $getprofile = $API->comm("/ppp/profile/print", array("?name" => $profilename));
    $API->disconnect();

    if (empty($getprofile)) {
        echo "<script>alert('Profile not found!'); window.location='./?ppp=profiles&session=$session';</script>";
        exit;
    }

    $profile = $getprofile[0];
} else {
    echo "<script>alert('Failed to connect to MikroTik'); window.location='./?ppp=profiles&session=$session';</script>";
    exit;
}

// Process update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $local_address = $_POST['local_address'];
    $remote_address = $_POST['remote_address'];
    $rate_limit = $_POST['rate_limit'];
    $session_timeout = $_POST['session_timeout'];
    $idle_timeout = $_POST['idle_timeout'];

    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
        $params = array(".id" => $profile['.id']);

        if (!empty($local_address)) $params['local-address'] = $local_address;
        if (!empty($remote_address)) $params['remote-address'] = $remote_address;
        if (!empty($rate_limit)) $params['rate-limit'] = $rate_limit;
        if (!empty($session_timeout)) $params['session-timeout'] = $session_timeout;
        if (!empty($idle_timeout)) $params['idle-timeout'] = $idle_timeout;

        $API->comm("/ppp/profile/set", $params);
        $API->disconnect();

        echo "<script>alert('Profile updated successfully!'); window.location='./?ppp=edit-profile&profile=$profilename&session=$session';</script>";
        exit;
    } else {
        $error_msg = "Failed to connect to MikroTik";
    }
}
?>

<style>
.card-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    border-radius: 20px 20px 0 0 !important;
    padding: 20px 25px !important;
}

.card-header h3 {
    color: #ffffff !important;
    font-weight: 700 !important;
    margin: 0 !important;
}

.info-box {
    background: linear-gradient(135deg, #ecf0f1 0%, #bdc3c7 100%);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.info-box table {
    width: 100%;
    margin: 0;
}

.info-box td {
    padding: 8px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.info-box td:first-child {
    font-weight: 600;
    width: 30%;
    color: #2c3e50;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 10px 15px;
}

.btn {
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    border: none;
    margin: 5px;
}

.btn-primary {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.help-text {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 5px;
}
</style>

<div class="row">
    <div class="col-lg-10 offset-lg-1">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-cogs"></i> Edit Profile: <?= htmlspecialchars($profilename) ?></h3>
            </div>

            <div class="card-body">
                <h4 style="margin-bottom: 15px;">Profile Information</h4>
                <div class="info-box">
                    <table>
                        <tr>
                            <td>Name</td>
                            <td><?= htmlspecialchars($profile['name']) ?></td>
                        </tr>
                        <tr>
                            <td>Local Address</td>
                            <td><?= htmlspecialchars($profile['local-address'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td>Remote Address</td>
                            <td><?= htmlspecialchars($profile['remote-address'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td>Rate Limit</td>
                            <td><?= htmlspecialchars($profile['rate-limit'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td>Session Timeout</td>
                            <td><?= htmlspecialchars($profile['session-timeout'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td>Idle Timeout</td>
                            <td><?= htmlspecialchars($profile['idle-timeout'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>

                <h4 style="margin-top: 30px; margin-bottom: 15px;">Edit Profile Settings</h4>
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?= $error_msg ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Local Address</label>
                                <input type="text" class="form-control" name="local_address" value="<?= htmlspecialchars($profile['local-address'] ?? '') ?>" placeholder="e.g., 10.10.10.1">
                                <small class="help-text">Server IP pool or address</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Remote Address</label>
                                <input type="text" class="form-control" name="remote_address" value="<?= htmlspecialchars($profile['remote-address'] ?? '') ?>" placeholder="e.g., 10.10.10.2-10.10.10.254">
                                <small class="help-text">Client IP pool or address</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Rate Limit</label>
                        <input type="text" class="form-control" name="rate_limit" value="<?= htmlspecialchars($profile['rate-limit'] ?? '') ?>" placeholder="e.g., 10M/10M">
                        <small class="help-text">Format: upload/download (e.g., 5M/10M)</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Session Timeout</label>
                                <input type="text" class="form-control" name="session_timeout" value="<?= htmlspecialchars($profile['session-timeout'] ?? '') ?>" placeholder="e.g., 1h or 30m">
                                <small class="help-text">Maximum session duration</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Idle Timeout</label>
                                <input type="text" class="form-control" name="idle_timeout" value="<?= htmlspecialchars($profile['idle-timeout'] ?? '') ?>" placeholder="e.g., 15m">
                                <small class="help-text">Disconnect after idle time</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 30px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="window.location='./?ppp=profiles&session=<?= $session ?>'">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </button>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Profile
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteProfile()">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteProfile() {
    if (confirm('Delete profile: <?= htmlspecialchars($profilename) ?>?\n\nThis action cannot be undone!')) {
        window.location = './?remove-pprofile=<?= urlencode($profilename) ?>&session=<?= $session ?>';
    }
}
</script>
