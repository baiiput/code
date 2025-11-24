<?php
require_once 'config.php';

// Log activity before destroying session
if (isLoggedIn()) {
    logActivity('LOGOUT', 'auth', 'User logged out');
}

session_unset();
session_destroy();
header('Location: login.php');
exit;
?>
