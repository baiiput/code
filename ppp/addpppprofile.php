<?php
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
  exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_profile'])) {
    $name = $_POST['name'];
    $local_address = $_POST['local_address'];
    $remote_address = $_POST['remote_address'];
    $rate_limit = $_POST['rate_limit'];
    $session_timeout = $_POST['session_timeout'];
    $idle_timeout = $_POST['idle_timeout'];

    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
        $params = array('name' => $name);

        if (!empty($local_address)) $params['local-address'] = $local_address;
        if (!empty($remote_address)) $params['remote-address'] = $remote_address;
        if (!empty($rate_limit)) $params['rate-limit'] = $rate_limit;
        if (!empty($session_timeout)) $params['session-timeout'] = $session_timeout;
        if (!empty($idle_timeout)) $params['idle-timeout'] = $idle_timeout;

        $API->comm("/ppp/profile/add", $params);
        $API->disconnect();

        echo "<script>alert('PPP Profile added successfully!'); window.location='./?ppp=profiles&session=$session';</script>";
        exit;
    } else {
        $error_msg = "Failed to connect to MikroTik";
    }
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

.card {
    background: rgba(40, 44, 52, 0.95) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 20px !important;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
}

.card-body {
    background: rgba(45, 52, 64, 0.9) !important;
    padding: 30px !important;
    border-radius: 0 0 20px 20px !important;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #ffffff !important;
    margin-bottom: 8px;
    display: block;
    font-size: 14px;
}

.form-group label span {
    color: #e74c3c;
}

.form-control {
    background: rgba(52, 58, 70, 0.9) !important;
    border: 2px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 12px !important;
    color: #ffffff !important;
    padding: 12px 18px !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    transition: border-color 0.3s ease !important;
    width: 100%;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.5) !important;
}

.form-control:focus {
    outline: none !important;
    border-color: #3498db !important;
    background: rgba(52, 58, 70, 1) !important;
}

.btn {
    padding: 12px 25px !important;
    border-radius: 12px !important;
    font-weight: 600 !important;
    transition: all 0.3s !important;
    border: none !important;
    margin: 5px !important;
    font-size: 14px !important;
}

.btn-primary {
    background: linear-gradient(135deg, #3498db, #2980b9) !important;
    color: white !important;
}

.btn-secondary {
    background: linear-gradient(135deg, #95a5a6, #7f8c8d) !important;
    color: white !important;
}

.btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
}

.alert {
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid;
}

.alert-danger {
    background: rgba(231, 76, 60, 0.2);
    border-color: #e74c3c;
    color: #ffffff;
}

.help-text {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6) !important;
    margin-top: 5px;
}
</style>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-plus-circle"></i> Add PPP Profile</h3>
            </div>

            <div class="card-body">
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Profile Name <span>*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g., 10Mbps">
                        <small class="help-text">Unique name for this profile</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Local Address</label>
                                <input type="text" class="form-control" name="local_address" placeholder="e.g., 10.10.10.1">
                                <small class="help-text">Server IP pool or address</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Remote Address</label>
                                <input type="text" class="form-control" name="remote_address" placeholder="e.g., 10.10.10.2-10.10.10.254">
                                <small class="help-text">Client IP pool or address</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Rate Limit</label>
                        <input type="text" class="form-control" name="rate_limit" placeholder="e.g., 10M/10M">
                        <small class="help-text">Format: upload/download (e.g., 5M/10M)</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Session Timeout</label>
                                <input type="text" class="form-control" name="session_timeout" placeholder="e.g., 1h or 30m">
                                <small class="help-text">Maximum session duration</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Idle Timeout</label>
                                <input type="text" class="form-control" name="idle_timeout" placeholder="e.g., 15m">
                                <small class="help-text">Disconnect after idle time</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 30px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="window.location='./?ppp=profiles&session=<?= $session ?>'">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="add_profile" class="btn btn-primary">
                            <i class="fa fa-save"></i> Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
