<?php
/**
 * Authentication API
 * Handles login, logout, and user status
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
setSecurityHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetAuth($action);
            break;
        case 'POST':
            handlePostAuth($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetAuth(string $action): void {
    switch ($action) {
        case 'status':
            // Check login status
            $user = getCurrentUser();
            if ($user) {
                echo json_encode([
                    'logged_in' => true,
                    'user' => $user,
                    'can_add' => canAdd(),
                    'can_edit' => canEdit()
                ]);
            } else {
                echo json_encode([
                    'logged_in' => false,
                    'user' => null,
                    'can_add' => false,
                    'can_edit' => false
                ]);
            }
            break;

        case 'logout':
            // Logout user
            initSession();
            session_unset();
            session_destroy();
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
            break;

        case 'users':
            // Get all users (admin only)
            requireAdmin();
            $pdo = getDB();
            $stmt = $pdo->query("
                SELECT id, username, nama, role, is_active, last_login, created_at
                FROM users
                ORDER BY role, username
            ");
            echo json_encode($stmt->fetchAll());
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePostAuth(string $action): void {
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($action) {
        case 'login':
            handleLogin($input);
            break;

        case 'create-user':
            handleCreateUser($input);
            break;

        case 'update-user':
            handleUpdateUser($input);
            break;

        case 'change-password':
            handleChangePassword($input);
            break;

        case 'delete-user':
            handleDeleteUser($input);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handleLogin(?array $input): void {
    if (!$input || empty($input['username']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username dan password harus diisi']);
        return;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT id, username, password, nama, role, is_active
        FROM users
        WHERE username = ?
    ");
    $stmt->execute([trim($input['username'])]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Username atau password salah']);
        return;
    }

    if (!$user['is_active']) {
        http_response_code(401);
        echo json_encode(['error' => 'Akun tidak aktif. Hubungi administrator.']);
        return;
    }

    if (!password_verify($input['password'], $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Username atau password salah']);
        return;
    }

    // Update last login
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);

    // Set session
    initSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_nama'] = $user['nama'];
    $_SESSION['user_role'] = $user['role'];

    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'nama' => $user['nama'],
            'role' => $user['role']
        ],
        'can_add' => in_array($user['role'], ['admin', 'editor']),
        'can_edit' => $user['role'] === 'admin'
    ]);
}

function handleCreateUser(?array $input): void {
    requireAdmin();

    if (!$input || empty($input['username']) || empty($input['password']) || empty($input['nama'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username, password, dan nama harus diisi']);
        return;
    }

    $pdo = getDB();

    // Check if username exists
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([trim($input['username'])]);
    if ($check->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Username sudah digunakan']);
        return;
    }

    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
    $role = isset($input['role']) && in_array($input['role'], ['admin', 'editor', 'viewer']) ? $input['role'] : 'viewer';

    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, nama, role)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        trim($input['username']),
        $hashedPassword,
        trim($input['nama']),
        $role
    ]);

    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId(),
        'message' => 'User berhasil dibuat'
    ]);
}

function handleUpdateUser(?array $input): void {
    requireAdmin();

    if (!$input || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID user harus diisi']);
        return;
    }

    $pdo = getDB();

    // Check if username is being changed and if it's already taken
    if (!empty($input['username'])) {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([trim($input['username']), $input['id']]);
        if ($check->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Username sudah digunakan']);
            return;
        }
    }

    // Build update query
    $updates = [];
    $params = [];

    if (!empty($input['username'])) {
        $updates[] = "username = ?";
        $params[] = trim($input['username']);
    }

    if (!empty($input['nama'])) {
        $updates[] = "nama = ?";
        $params[] = trim($input['nama']);
    }

    if (!empty($input['role']) && in_array($input['role'], ['admin', 'editor', 'viewer'])) {
        $updates[] = "role = ?";
        $params[] = $input['role'];
    }

    if (isset($input['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = $input['is_active'] ? 1 : 0;
    }

    if (!empty($input['password'])) {
        $updates[] = "password = ?";
        $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tidak ada data untuk diupdate']);
        return;
    }

    $params[] = $input['id'];
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'User berhasil diupdate'
    ]);
}

function handleChangePassword(?array $input): void {
    requireAuth();

    if (!$input || empty($input['current_password']) || empty($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Password lama dan baru harus diisi']);
        return;
    }

    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($input['current_password'], $user['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Password lama salah']);
        return;
    }

    // Update password
    $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->execute([$hashedPassword, $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Password berhasil diubah'
    ]);
}

function handleDeleteUser(?array $input): void {
    requireAdmin();

    if (!$input || empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID user harus diisi']);
        return;
    }

    // Prevent deleting self
    if ($input['id'] == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Tidak dapat menghapus akun sendiri']);
        return;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$input['id']]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'User tidak ditemukan']);
        return;
    }

    echo json_encode([
        'success' => true,
        'message' => 'User berhasil dihapus'
    ]);
}
