<?php
/**
 * Logout
 * Starlink Customer Management System
 */

require_once 'config/config.php';

// Log activity before logout
if (isset($_SESSION['user_id'])) {
    logActivity($db, 'logout', 'users', $_SESSION['user_id'], 'User logout: ' . $_SESSION['username']);
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: ' . APP_URL . '/login.php');
exit();
