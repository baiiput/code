<?php
session_start();
require_once 'includes/functions.php';

if (isLoggedIn()) {
    logActivity('user', $_SESSION['user_id'], 'Logout', 'User logout');
}

session_destroy();
redirect('index.php', 'Anda telah berhasil logout', 'success');
