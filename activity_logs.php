<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user = getCurrentUser();

// Get filter parameters
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$action_filter = isset($_GET['action']) ? clean($_GET['action']) : '';
$module_filter = isset($_GET['module']) ? clean($_GET['module']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Build query based on role
$query = "
    SELECT al.*, u.username, u.full_name, u.role
    FROM activity_logs al
    JOIN users u ON al.user_id = u.user_id
    WHERE DATE(al.created_at) BETWEEN ? AND ?
";

$params = [$date_from, $date_to];
$types = "ss";

// Role-based filtering
if ($user['role'] === 'admin') {
    // Admin can see all logs
    if ($user_filter) {
        $query .= " AND al.user_id = ?";
        $params[] = $user_filter;
        $types .= "i";
    }
} elseif ($user['role'] === 'manager') {
    // Manager can see all logs EXCEPT admin logs
    $query .= " AND u.role != 'admin'";
    if ($user_filter) {
        $query .= " AND al.user_id = ?";
        $params[] = $user_filter;
        $types .= "i";
    }
} else {
    // Regular users can only see their own logs
    $query .= " AND al.user_id = ?";
    $params[] = $user['user_id'];
    $types .= "i";
}

// Additional filters
if ($action_filter) {
    $query .= " AND al.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if ($module_filter) {
    $query .= " AND al.module = ?";
    $params[] = $module_filter;
    $types .= "s";
}

$query .= " ORDER BY al.created_at DESC LIMIT 500";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

// Get unique actions and modules for filters
$actions = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC);
$modules = $conn->query("SELECT DISTINCT module FROM activity_logs ORDER BY module")->fetch_all(MYSQLI_ASSOC);

// Get users for filter (manager/admin only)
$users = [];
if (in_array($user['role'], ['admin', 'manager'])) {
    if ($user['role'] === 'admin') {
        $users_result = $conn->query("SELECT user_id, username, full_name, role FROM users ORDER BY username");
    } else {
        $users_result = $conn->query("SELECT user_id, username, full_name, role FROM users WHERE role != 'admin' ORDER BY username");
    }
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

$page_title = 'Log Aktivitas';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-history"></i> Log Aktivitas</h1>
    </div>

    <div class="filter-container" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">

            <?php if (in_array($user['role'], ['admin', 'manager'])): ?>
            <div class="form-group">
                <label><i class="fas fa-user"></i> User</label>
                <select name="user_id" class="form-control">
                    <option value="">Semua User</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['user_id']; ?>" <?php echo $user_filter == $u['user_id'] ? 'selected' : ''; ?>>
                        <?php echo $u['username']; ?> (<?php echo $u['full_name']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label><i class="fas fa-bolt"></i> Action</label>
                <select name="action" class="form-control">
                    <option value="">Semua Action</option>
                    <?php foreach ($actions as $a): ?>
                    <option value="<?php echo $a['action']; ?>" <?php echo $action_filter == $a['action'] ? 'selected' : ''; ?>>
                        <?php echo $a['action']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-cube"></i> Module</label>
                <select name="module" class="form-control">
                    <option value="">Semua Module</option>
                    <?php foreach ($modules as $m): ?>
                    <option value="<?php echo $m['module']; ?>" <?php echo $module_filter == $m['module'] ? 'selected' : ''; ?>>
                        <?php echo $m['module']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>

            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>

            <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="activity_logs.php" class="btn btn-secondary" style="flex: 1;">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="table-container">
        <div style="margin-bottom: 15px; color: var(--text-secondary);">
            <i class="fas fa-info-circle"></i> Menampilkan <?php echo count($logs); ?> log aktivitas
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="15%">Waktu</th>
                    <?php if (in_array($user['role'], ['admin', 'manager'])): ?>
                    <th width="12%">User</th>
                    <?php endif; ?>
                    <th width="10%">Action</th>
                    <th width="10%">Module</th>
                    <th>Deskripsi</th>
                    <th width="12%">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="<?php echo in_array($user['role'], ['admin', 'manager']) ? '6' : '5'; ?>" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                        Tidak ada log aktivitas
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
                        </td>
                        <?php if (in_array($user['role'], ['admin', 'manager'])): ?>
                        <td>
                            <div style="font-weight: 500;"><?php echo $log['username']; ?></div>
                            <div style="font-size: 11px; color: var(--text-secondary);"><?php echo $log['full_name']; ?></div>
                        </td>
                        <?php endif; ?>
                        <td>
                            <span class="badge badge-<?php
                                echo match($log['action']) {
                                    'LOGIN' => 'success',
                                    'LOGOUT' => 'secondary',
                                    'CREATE' => 'primary',
                                    'UPDATE' => 'info',
                                    'DELETE' => 'danger',
                                    default => 'dark'
                                };
                            ?>">
                                <?php echo $log['action']; ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 12px; font-weight: 500; color: var(--primary-color);">
                                <?php echo strtoupper($log['module']); ?>
                            </span>
                        </td>
                        <td style="font-size: 13px;"><?php echo $log['description'] ?: '-'; ?></td>
                        <td style="font-size: 12px; color: var(--text-secondary);"><?php echo $log['ip_address']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.filter-container .form-group {
    margin: 0;
}

.filter-container .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filter-container label {
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-primary);
}

.filter-container label i {
    margin-right: 5px;
    color: var(--primary-color);
}
</style>

<?php include 'includes/footer.php'; ?>
