<?php
require_once __DIR__ . '/config/database.php';

// Redirect to dashboard or login
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'modules/dashboard/');
} else {
    header('Location: ' . BASE_URL . 'login.php');
}
exit;
