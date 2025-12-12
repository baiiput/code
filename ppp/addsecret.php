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

.form-control, select.form-control {
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

.form-control:focus, select.form-control:focus {
    outline: none !important;
    border-color: #3498db !important;
    background: rgba(52, 58, 70, 1) !important;
}

select.form-control {
    appearance: none !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffffff' viewBox='0 0 16 16'%3e%3cpath d='m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 12px center !important;
    background-size: 12px !important;
    cursor: pointer !important;
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
                                <label for="name">Username <span>*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Enter username">
                                <small class="help-text">Unique username for PPP connection</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password <span>*</span></label>
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
