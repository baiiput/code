<?php
/**
 * Konfigurasi Sistem Voting RT
 * Sesuaikan dengan pengaturan server Anda
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'voting_rt');

// Konfigurasi Aplikasi
define('APP_NAME', 'Sistem Voting RT');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/voting-rt'); // Sesuaikan dengan domain Anda

// Konfigurasi Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Konfigurasi Keamanan
define('SESSION_LIFETIME', 3600); // 1 jam
define('CSRF_TOKEN_NAME', 'csrf_token');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (matikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set ke 1 jika menggunakan HTTPS
