<?php
/**
 * Database Configuration
 * Koperasi Syariah Online
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Ganti dengan user database Anda
define('DB_PASS', '');      // Ganti dengan password database Anda
define('DB_NAME', 'koperasi_syariah');

// Create connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");

        return $conn;
    } catch (Exception $e) {
        die("Database Error: " . $e->getMessage());
    }
}

// Get database connection
$conn = getDBConnection();
?>
