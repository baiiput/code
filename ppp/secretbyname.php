<?php
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
  $secretname = $_GET['secret'];

  // Load secret details
  if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
    $getsecret = $API->comm("/ppp/secret/print", array("?name" => $secretname));
    $getprofiles = $API->comm("/ppp/profile/print");
    $API->disconnect();

    if (empty($getsecret)) {
      echo "<script>alert('Secret not found!'); window.location='./?ppp=secrets&session=$session';</script>";
      exit;
    }

    $secret = $getsecret[0];
  } else {
    echo "<script>alert('Failed to connect to MikroTik'); window.location='./?ppp=secrets&session=$session';</script>";
    exit;
  }

  // Process update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_secret'])) {
    $password = $_POST['password'];
    $service = $_POST['service'];
    $profile = $_POST['profile'];
    $local_address = $_POST['local_address'];
    $remote_address = $_POST['remote_address'];
    $comment = $_POST['comment'];

    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
      $params = array();

      if (!empty($password)) {
        $params['password'] = $password;
      }
      if (!empty($service)) {
        $params['service'] = $service;
      }
      if (!empty($profile)) {
        $params['profile'] = $profile;
      }
      if (!empty($local_address)) {
        $params['local-address'] = $local_address;
      }
      if (!empty($remote_address)) {
        $params['remote-address'] = $remote_address;
      }
      if (!empty($comment)) {
        $params['comment'] = $comment;
      }

      $API->comm("/ppp/secret/set", array_merge(array(".id" => $secret['.id']), $params));
      $API->disconnect();

      echo "<script>alert('PPP Secret updated successfully!'); window.location='./?secret=$secretname&session=$session';</script>";
      exit;
    } else {
      $error_msg = "Failed to connect to MikroTik";
    }
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

.badge {
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success { background: #27ae60; color: white; }
.badge-danger { background: #e74c3c; color: white; }

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
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}

.btn {
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
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

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}
</style>

<div class="row">
    <div class="col-lg-10 offset-lg-1">
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fa fa-link"></i>
                    PPP Secret: <?= htmlspecialchars($secretname) ?>
                </h3>
            </div>

            <div class="card-body">
                <!-- Secret Information -->
                <h4 style="margin-bottom: 15px;">Secret Information</h4>
                <div class="info-box">
                    <table>
                        <tr>
                            <td>Username</td>
                            <td><?= htmlspecialchars($secret['name']) ?></td>
                        </tr>
                        <tr>
                            <td>Service</td>
                            <td><?= htmlspecialchars($secret['service'] ?? 'any') ?></td>
                        </tr>
                        <tr>
                            <td>Profile</td>
                            <td><?= htmlspecialchars($secret['profile'] ?? 'default') ?></td>
                        </tr>
                        <tr>
                            <td>Local Address</td>
                            <td><?= htmlspecialchars($secret['local-address'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td>Remote Address</td>
                            <td><?= htmlspecialchars($secret['remote-address'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <?php if (isset($secret['disabled']) && ($secret['disabled'] === 'true' || $secret['disabled'] === true)): ?>
                                    <span class="badge badge-danger">Disabled</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Enabled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($secret['comment'])): ?>
                        <tr>
                            <td>Comment</td>
                            <td><?= htmlspecialchars($secret['comment']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Edit Form -->
                <h4 style="margin-top: 30px; margin-bottom: 15px;">Edit Secret</h4>
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="text" class="form-control" id="password" name="password" value="<?= htmlspecialchars($secret['password'] ?? '') ?>" placeholder="Enter new password">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="service">Service</label>
                                <select class="form-control" id="service" name="service">
                                    <option value="any" <?= ($secret['service'] ?? 'any') === 'any' ? 'selected' : '' ?>>Any</option>
                                    <option value="pppoe" <?= ($secret['service'] ?? '') === 'pppoe' ? 'selected' : '' ?>>PPPoE</option>
                                    <option value="pptp" <?= ($secret['service'] ?? '') === 'pptp' ? 'selected' : '' ?>>PPTP</option>
                                    <option value="l2tp" <?= ($secret['service'] ?? '') === 'l2tp' ? 'selected' : '' ?>>L2TP</option>
                                    <option value="sstp" <?= ($secret['service'] ?? '') === 'sstp' ? 'selected' : '' ?>>SSTP</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="profile">Profile</label>
                                <select class="form-control" id="profile" name="profile">
                                    <option value="">Default</option>
                                    <?php foreach($getprofiles as $prof): ?>
                                        <option value="<?= htmlspecialchars($prof['name']) ?>" <?= ($secret['profile'] ?? '') === $prof['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($prof['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="comment">Comment</label>
                                <input type="text" class="form-control" id="comment" name="comment" value="<?= htmlspecialchars($secret['comment'] ?? '') ?>" placeholder="Optional description">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="local_address">Local Address</label>
                                <input type="text" class="form-control" id="local_address" name="local_address" value="<?= htmlspecialchars($secret['local-address'] ?? '') ?>" placeholder="e.g., 10.10.10.1">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="remote_address">Remote Address</label>
                                <input type="text" class="form-control" id="remote_address" name="remote_address" value="<?= htmlspecialchars($secret['remote-address'] ?? '') ?>" placeholder="e.g., 10.10.10.100">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 30px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="window.location='./?ppp=secrets&session=<?= $session ?>'">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </button>
                        <button type="submit" name="update_secret" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Secret
                        </button>
                        <button type="button" class="btn btn-danger" onclick="deleteSecret()">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteSecret() {
    if (confirm('Delete PPP secret: <?= htmlspecialchars($secretname) ?>?\n\nThis action cannot be undone!')) {
        window.location = './?remove-pppsecret=<?= urlencode($secretname) ?>&session=<?= $session ?>';
    }
}
</script>
