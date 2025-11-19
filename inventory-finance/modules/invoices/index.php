<?php
require_once __DIR__ . '/../../config/database.php';

// Redirect to sales as invoices are generated from sales
header('Location: ' . BASE_URL . 'modules/stock-out/');
exit;
