<?php
require_once 'config.php';

$id = $_GET['id'] ?? null;
if (!$id) redirect('customers.php');

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) redirect('customers.php');

$pageTitle = 'Detail Pelanggan';

// Get payment history
$stmt = $pdo->prepare("
    SELECT p.*, u.name as user_name
    FROM payments p
    JOIN users u ON p.user_id = u.id
    WHERE p.customer_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

require_once 'includes/header.php';

$statusColors = [
    'aktif' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    'nonaktif' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    'lunas' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    'belum_bayar' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <a href="customers.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">
            &larr; Kembali
        </a>
        <?php if (canManageCustomers()): ?>
        <a href="customer_form.php?id=<?= $customer['id'] ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            Edit
        </a>
        <?php endif; ?>
    </div>

    <!-- Customer Info -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= e($customer['nama']) ?></h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusColors[$customer['status_langganan']] ?>">
                    <?= ucfirst(str_replace('_', ' ', $customer['status_langganan'])) ?>
                </span>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Basic Info -->
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Informasi Dasar</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Client</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['email_client'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor CS</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['nomor_cs'] ?? '-') ?></dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Alamat</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['alamat'] ?? '-') ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Login Credentials -->
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Login Credentials</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Gmail</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?= e($customer['gmail_email'] ?? '-') ?>
                            <?php if ($customer['gmail_password']): ?>
                            <br><span class="text-gray-500">Pass: <?= e($customer['gmail_password']) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Starlink</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <?= e($customer['starlink_email'] ?? '-') ?>
                            <?php if ($customer['starlink_password']): ?>
                            <br><span class="text-gray-500">Pass: <?= e($customer['starlink_password']) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <?php if ($customer['login_alternatif']): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Login Alternatif</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-line"><?= e($customer['login_alternatif']) ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Starlink Info -->
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Informasi Starlink</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ACC No.</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['acc_no'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">KIT Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['kit_number'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Serial Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['serial_number'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kode</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['kode'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last 4 Digit</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['last_4_digit'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">No Aktivasi</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['no_aktivasi'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Koordinat</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['koordinat_lokasi'] ?? '-') ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Subscription -->
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Informasi Langganan</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Paket</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= e($customer['paket'] ?? '-') ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Jatuh Tempo</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white"><?= formatDate($customer['tanggal_jatuh_tempo']) ?></dd>
                    </div>
                </dl>
            </div>

            <?php if ($customer['catatan']): ?>
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Catatan</h4>
                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line"><?= e($customer['catatan']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment History -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">History Pembayaran</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php if (empty($payments)): ?>
            <div class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada history pembayaran</div>
            <?php else: ?>
            <?php foreach ($payments as $payment): ?>
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white"><?= e($payment['periode_bulan']) ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Diinput oleh: <?= e($payment['user_name']) ?></p>
                        <?php if ($payment['keterangan']): ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($payment['keterangan']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900 dark:text-white"><?= formatRupiah($payment['nominal']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= formatDate($payment['tanggal_bayar']) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
