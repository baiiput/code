<?php
/**
 * Authentication API
 */

require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance()->getConnection();

    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($username) || empty($password)) {
                throw new Exception('Username dan password harus diisi');
            }

            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Username atau password salah');
            }

            // Update last login
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            jsonResponse([
                'success' => true,
                'message' => 'Login berhasil',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ]);
            break;

        case 'logout':
            session_destroy();
            jsonResponse([
                'success' => true,
                'message' => 'Logout berhasil'
            ]);
            break;

        case 'check':
            if (isLoggedIn()) {
                jsonResponse([
                    'success' => true,
                    'logged_in' => true,
                    'user' => getCurrentUser()
                ]);
            } else {
                jsonResponse([
                    'success' => true,
                    'logged_in' => false
                ]);
            }
            break;

        case 'register':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $full_name = $data['full_name'] ?? '';
            $role = $data['role'] ?? 'staff';

            if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
                throw new Exception('Semua field harus diisi');
            }

            // Check if username already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception('Username atau email sudah terdaftar');
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $full_name, $role]);

            jsonResponse([
                'success' => true,
                'message' => 'Registrasi berhasil'
            ], 201);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage()
    ], 400);
}
