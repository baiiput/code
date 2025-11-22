<?php
require_once 'config/base_path.php';
require_once 'includes/auth.php';

logoutUser();
redirectTo('login.php');
?>
