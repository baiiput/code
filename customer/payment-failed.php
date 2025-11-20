<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireCustomer();

$pageTitle = 'Pembayaran Gagal';

include '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 dark:bg-red-900 rounded-full mb-4">
                    <svg class="w-12 h-12 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Pembayaran Gagal</h1>
                <p class="text-gray-600 dark:text-gray-400">Pembayaran Anda tidak dapat diproses</p>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg mb-6">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Jangan khawatir, Anda dapat mencoba lagi dengan membuat pembayaran baru dari dashboard Anda.
                </p>
            </div>

            <div class="space-y-3">
                <a href="/customer/index.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
