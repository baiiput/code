<?php
require_once 'config/config.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
