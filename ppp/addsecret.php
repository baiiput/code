<?php
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {
  // Load profiles for dropdown
  if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
    $getprofile = $API->comm("/ppp/profile/print");
    $API->disconnect();
  } else {
    $getprofile = array();
  }

  // Process form submission
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_secret'])) {
    $name = $_POST['name'];
    $password = $_POST['password'];
    $service = $_POST['service'];
    $profile = $_POST['profile'];
    $local_address = $_POST['local_address'];
    $remote_address = $_POST['remote_address'];
    $comment = $_POST['comment'];

    if ($API->connect($iphost, $userhost, decrypt($passwdhost))) {
      $params = array(
        'name' => $name,
        'password' => $password,
      );

      if (!empty($service) && $service !== 'any') {
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

      $API->comm("/ppp/secret/add", $params);
      $API->disconnect();

      echo "<script>alert('PPP Secret added successfully!'); window.location='./?ppp=secrets&session=$session';</script>";
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

.card-header h3 i {
    font-size: 24px !important;
    color: #3498db !important;
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
}

.btn-primary {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #ffe6e6;
    border: 1px solid #e74c3c;
    color: #c0392b;
}

.card-body {
    padding: 30px;
}

.help-text {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 5px;
}
</style>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fa fa-plus-circle"></i>
                    Add PPP Secret
                </h3>
            </div>

            <div class="card-body">
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" onsubmit="return validateForm()">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Username <span style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Enter username">
                                <small class="help-text">Unique username for PPP connection</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password <span style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="password" name="password" required placeholder="Enter password">
                                <small class="help-text">Password for authentication</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="service">Service</label>
                                <select class="form-control" id="service" name="service">
                                    <option value="any">Any</option>
                                    <option value="pppoe">PPPoE</option>
                                    <option value="pptp">PPTP</option>
                                    <option value="l2tp">L2TP</option>
                                    <option value="sstp">SSTP</option>
                                </select>
                                <small class="help-text">Type of PPP service</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="profile">Profile</label>
                                <select class="form-control" id="profile" name="profile">
                                    <option value="">Default</option>
                                    <?php foreach($getprofile as $profile): ?>
                                        <option value="<?= htmlspecialchars($profile['name']) ?>">
                                            <?= htmlspecialchars($profile['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="help-text">Profile with bandwidth/limit settings</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="local_address">Local Address</label>
                                <input type="text" class="form-control" id="local_address" name="local_address" placeholder="e.g., 10.10.10.1">
                                <small class="help-text">Server IP address (optional)</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="remote_address">Remote Address</label>
                                <input type="text" class="form-control" id="remote_address" name="remote_address" placeholder="e.g., 10.10.10.100">
                                <small class="help-text">Client IP address (optional)</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comment">Comment</label>
                        <input type="text" class="form-control" id="comment" name="comment" placeholder="Optional description">
                        <small class="help-text">Description or note about this secret</small>
                    </div>

                    <div class="form-group" style="margin-top: 30px; text-align: right;">
                        <button type="button" class="btn btn-secondary" onclick="window.location='./?ppp=secrets&session=<?= $session ?>'">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="add_secret" class="btn btn-primary">
                            <i class="fa fa-save"></i> Save Secret
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function validateForm() {
    var name = document.getElementById('name').value.trim();
    var password = document.getElementById('password').value.trim();

    if (name === '') {
        alert('Username is required!');
        document.getElementById('name').focus();
        return false;
    }

    if (password === '') {
        alert('Password is required!');
        document.getElementById('password').focus();
        return false;
    }

    if (name.length < 3) {
        alert('Username must be at least 3 characters!');
        document.getElementById('name').focus();
        return false;
    }

    return true;
}
</script>
