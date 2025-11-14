<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
