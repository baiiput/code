<?php
session_start();
require_once '../includes/functions.php';

if (isAdminLoggedIn()) {
    logActivity('admin', $_SESSION['admin_id'], 'Logout Admin', 'Admin logout');
}

session_destroy();
redirect('login.php');
