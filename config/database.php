<?php
/**
 * Database Configuration
 * Starlink Customer Management System
 */

// Database credentials - SESUAIKAN DENGAN HOSTING ANDA
define('DB_HOST', 'localhost');           // Hostname database (biasanya localhost)
define('DB_NAME', 'starlink_db');         // Nama database yang akan dibuat
define('DB_USER', 'root');                // Username database
define('DB_PASS', '');                    // Password database
define('DB_CHARSET', 'utf8mb4');

// Database connection
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            die();
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

// Create a global database connection
$database = new Database();
$db = $database->getConnection();
