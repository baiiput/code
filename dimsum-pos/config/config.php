<?php
/**
 * Konfigurasi Utama - Dimsum Umami POS
 */

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dimsum_pos');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'Dimsum Umami POS');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/dimsum-pos/');

// Path Configuration
define('ROOT_PATH', dirname(__DIR__) . '/');
define('CONFIG_PATH', ROOT_PATH . 'config/');
define('CLASSES_PATH', ROOT_PATH . 'classes/');
define('MODELS_PATH', ROOT_PATH . 'models/');
define('PAGES_PATH', ROOT_PATH . 'pages/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('EXPORTS_PATH', ROOT_PATH . 'exports/');

// Upload Configuration
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [CLASSES_PATH, MODELS_PATH];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load core classes
require_once CLASSES_PATH . 'Database.php';
require_once CLASSES_PATH . 'Auth.php';
require_once CLASSES_PATH . 'Helper.php';

// Initialize database connection
$db = Database::getInstance();
