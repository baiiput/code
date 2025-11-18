<?php
require_once 'config.php';
requireRole(['super_admin']);
$pageTitle = 'Kelola Users';

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    if ($deleteId == $_SESSION['user_id']) {
        setFlash('error', 'Anda tidak dapat menghapus akun sendiri.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
        setFlash('success', 'User berhasil dihapus.');
    }
    redirect('users.php');
}

// Get users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

require_once 'includes/header.php';

$roleColors = [
    'super_admin' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    'admin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'viewer' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
];
?>

<div class="space-y-6">
    <!-- Action Button -->
    <div class="flex justify-end">
        <a href="user_form.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            + Tambah User
        </a>
    </div>

    <!-- Table -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dibuat</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= e($user['name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= e($user['email']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $roleColors[$user['role']] ?>">
                                <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= formatDate($user['created_at']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="user_form.php?id=<?= $user['id'] ?>" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 mr-3">Edit</a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="users.php?delete=<?= $user['id'] ?>" onclick="return confirm('Yakin ingin menghapus?')" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
