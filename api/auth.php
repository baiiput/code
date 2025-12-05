<?php
// File: api/auth.php
// API untuk Authentication & User Management

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database config
$DB_HOST = 'localhost';
$DB_NAME = 'db_offer';
$DB_USER = 'db_offer';
$DB_PASS = 'db_offer';

function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'status' => 'success',
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function sendError($message, $status = 400) {
    http_response_code($status);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    sendError('Database connection failed: ' . $e->getMessage(), 500);
}

// Helper function to generate session token
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

// Helper function to get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Helper function to verify session token
function verifySession($pdo, $token) {
    $stmt = $pdo->prepare("
        SELECT us.*, u.username, u.email, u.full_name, u.role, u.is_active
        FROM user_sessions us
        JOIN users u ON us.user_id = u.id
        WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1
    ");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'login':
            // User login
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) sendError('Invalid JSON input');

            if (!isset($input['username']) || !isset($input['password'])) {
                sendError('Username and password are required');
            }

            $username = trim($input['username']);
            $password = $input['password'];

            // Get user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                sendError('Invalid username or password', 401);
            }

            // Create session
            $sessionToken = generateSessionToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
            $ipAddress = getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $stmt = $pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $sessionToken, $ipAddress, $userAgent, $expiresAt]);

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            sendResponse([
                'sessionToken' => $sessionToken,
                'expiresAt' => $expiresAt,
                'user' => [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'fullName' => $user['full_name'],
                    'role' => $user['role']
                ],
                'message' => 'Login successful'
            ]);
            break;

        case 'logout':
            // User logout
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                sendError('Authorization header required', 401);
            }

            $token = $matches[1];

            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$token]);

            sendResponse(['message' => 'Logout successful']);
            break;

        case 'verify':
            // Verify session
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['sessionToken'])) sendError('Session token required');

            $session = verifySession($pdo, $input['sessionToken']);

            if (!$session) sendError('Invalid or expired session', 401);

            sendResponse([
                'valid' => true,
                'user' => [
                    'id' => (int)$session['user_id'],
                    'username' => $session['username'],
                    'email' => $session['email'],
                    'fullName' => $session['full_name'],
                    'role' => $session['role']
                ]
            ]);
            break;

        case 'change_password':
            // Change password
            if ($method !== 'POST') sendError('Method not allowed', 405);

            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['sessionToken']) || !isset($input['currentPassword']) || !isset($input['newPassword'])) {
                sendError('Session token, current password, and new password are required');
            }

            $session = verifySession($pdo, $input['sessionToken']);
            if (!$session) sendError('Invalid or expired session', 401);

            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$session['user_id']]);
            $user = $stmt->fetch();

            if (!password_verify($input['currentPassword'], $user['password_hash'])) {
                sendError('Current password is incorrect', 401);
            }

            // Update password
            $newPasswordHash = password_hash($input['newPassword'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newPasswordHash, $session['user_id']]);

            sendResponse(['message' => 'Password berhasil diubah']);
            break;

        case 'list_users':
            // List all users (admin only)
            if ($method !== 'GET') sendError('Method not allowed', 405);

            // Get session token from header
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                sendError('Authorization header required', 401);
            }

            $session = verifySession($pdo, $matches[1]);
            if (!$session) sendError('Invalid or expired session', 401);
            if ($session['role'] !== 'admin') sendError('Access denied. Admin only.', 403);

            $stmt = $pdo->query("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();

            sendResponse(array_map(function($u) {
                return [
                    'id' => (int)$u['id'],
                    'username' => $u['username'],
                    'email' => $u['email'],
                    'fullName' => $u['full_name'],
                    'role' => $u['role'],
                    'isActive' => (bool)$u['is_active'],
                    'lastLogin' => $u['last_login'],
                    'createdAt' => $u['created_at']
                ];
            }, $users));
            break;

        case 'create_user':
            // Create new user (admin only)
            if ($method !== 'POST') sendError('Method not allowed', 405);

            // Verify admin session
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                sendError('Authorization header required', 401);
            }

            $session = verifySession($pdo, $matches[1]);
            if (!$session) sendError('Invalid or expired session', 401);
            if ($session['role'] !== 'admin') sendError('Access denied. Admin only.', 403);

            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['username']) || !isset($input['email']) || !isset($input['password']) || !isset($input['fullName'])) {
                sendError('Username, email, password, and fullName are required');
            }

            $username = trim($input['username']);
            $email = trim($input['email']);
            $password = $input['password'];
            $fullName = trim($input['fullName']);
            $role = $input['role'] ?? 'sales';
            $isActive = isset($input['isActive']) && $input['isActive'] ? 1 : 1;

            // Check duplicate username
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) sendError('Username already exists');

            // Check duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) sendError('Email already exists');

            // Create user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, full_name, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $email, $passwordHash, $fullName, $role, $isActive]);

            $userId = $pdo->lastInsertId();

            sendResponse([
                'id' => (int)$userId,
                'username' => $username,
                'message' => 'User berhasil dibuat'
            ]);
            break;

        case 'update_user':
            // Update user (admin only)
            if ($method !== 'POST') sendError('Method not allowed', 405);

            // Verify admin session
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                sendError('Authorization header required', 401);
            }

            $session = verifySession($pdo, $matches[1]);
            if (!$session) sendError('Invalid or expired session', 401);
            if ($session['role'] !== 'admin') sendError('Access denied. Admin only.', 403);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid user ID');

            $input = json_decode(file_get_contents('php://input'), true);

            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) sendError('User not found', 404);

            $updates = [];
            $params = [];

            if (isset($input['email'])) {
                // Check duplicate
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([trim($input['email']), $id]);
                if ($stmt->fetch()) sendError('Email already exists');

                $updates[] = "email = ?";
                $params[] = trim($input['email']);
            }

            if (isset($input['fullName'])) {
                $updates[] = "full_name = ?";
                $params[] = trim($input['fullName']);
            }

            if (isset($input['role'])) {
                $updates[] = "role = ?";
                $params[] = $input['role'];
            }

            if (isset($input['isActive'])) {
                $updates[] = "is_active = ?";
                $params[] = $input['isActive'] ? 1 : 0;
            }

            if (isset($input['password']) && !empty($input['password'])) {
                $updates[] = "password_hash = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            if (empty($updates)) sendError('Nothing to update');

            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);

            sendResponse([
                'id' => (int)$id,
                'message' => 'User berhasil diupdate'
            ]);
            break;

        case 'delete_user':
            // Delete user (admin only)
            if ($method !== 'POST') sendError('Method not allowed', 405);

            // Verify admin session
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                sendError('Authorization header required', 401);
            }

            $session = verifySession($pdo, $matches[1]);
            if (!$session) sendError('Invalid or expired session', 401);
            if ($session['role'] !== 'admin') sendError('Access denied. Admin only.', 403);

            $id = $_GET['id'] ?? '';
            if (!$id || !is_numeric($id)) sendError('Invalid user ID');

            // Cannot delete yourself
            if ($id == $session['user_id']) sendError('Cannot delete your own account');

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) sendError('User not found', 404);

            sendResponse([
                'id' => (int)$id,
                'message' => 'User berhasil dihapus'
            ]);
            break;

        default:
            sendError('Invalid action. Available: login, logout, verify, change_password, list_users, create_user, update_user, delete_user');
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
?>
