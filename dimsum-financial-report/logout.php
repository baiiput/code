<?php
/**
 * Logout - Laporan Keuangan Dimsum
 */
require_once 'config.php';

logoutUser();

header('Location: index.php');
exit;
