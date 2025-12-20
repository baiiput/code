<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Koperasi Syariah';
}
$currentUser = getCurrentUser();
$isAdminPage = isAdmin();
?>
<!DOCTYPE html>
<html lang="id" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Koperasi Syariah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-200">

    <!-- Navbar -->
    <nav class="bg-white dark:bg-gray-800 shadow-lg" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <!-- Mobile menu button -->
                    <?php if ($isAdminPage): ?>
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none mr-2">
                        <svg class="h-6 w-6" :class="{ 'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="h-6 w-6" :class="{ 'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <?php endif; ?>

                    <a href="<?php echo baseUrl($isAdminPage ? 'admin/index.php' : 'customer/index.php'); ?>" class="flex items-center">
                        <span class="text-xl font-bold text-blue-600 dark:text-blue-400">Koperasi Syariah</span>
                    </a>

                    <?php if ($isAdminPage): ?>
                    <!-- Admin Menu -->
                    <div class="hidden md:ml-6 md:flex md:space-x-4">
                        <a href="<?php echo baseUrl('admin/index.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Dashboard
                        </a>
                        <a href="<?php echo baseUrl('admin/customers.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Pelanggan
                        </a>
                        <a href="<?php echo baseUrl('admin/products.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Barang
                        </a>
                        <a href="<?php echo baseUrl('admin/transactions.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Transaksi
                        </a>
                        <a href="<?php echo baseUrl('admin/payments.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Pembayaran
                        </a>
                        <a href="<?php echo baseUrl('admin/verify_payments.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Verifikasi
                        </a>
                        <?php if (hasPermission(USER_LEVEL_MANAGER)): ?>
                        <a href="<?php echo baseUrl('admin/investors.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Investor
                        </a>
                        <a href="<?php echo baseUrl('admin/kas_transactions.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Kas
                        </a>
                        <?php endif; ?>
                        <?php if (isSuperAdmin()): ?>
                        <a href="<?php echo baseUrl('admin/users.php'); ?>" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Users
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button id="themeToggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg id="sunIcon" class="w-5 h-5 text-gray-700 dark:text-gray-300 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 text-gray-700 dark:text-gray-300 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </button>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                            <div class="text-right hidden md:block">
                                <div class="text-sm font-medium"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo $currentUser['level_name']; ?></div>
                            </div>
                            <span class="text-sm font-medium md:hidden"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50">
                            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">@<?php echo htmlspecialchars($currentUser['username']); ?></div>
                                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1"><?php echo $currentUser['level_name']; ?></div>
                            </div>
                            <a href="<?php echo baseUrl('logout.php'); ?>" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <?php if ($isAdminPage): ?>
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="md:hidden border-t border-gray-200 dark:border-gray-700"
             @click.away="mobileMenuOpen = false">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="<?php echo baseUrl('admin/index.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    📊 Dashboard
                </a>
                <a href="<?php echo baseUrl('admin/customers.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    👥 Pelanggan
                </a>
                <a href="<?php echo baseUrl('admin/products.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    📦 Barang
                </a>
                <a href="<?php echo baseUrl('admin/transactions.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    💳 Transaksi
                </a>
                <a href="<?php echo baseUrl('admin/payments.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    💰 Pembayaran
                </a>
                <a href="<?php echo baseUrl('admin/verify_payments.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    ✅ Verifikasi
                </a>
                <?php if (hasPermission(USER_LEVEL_MANAGER)): ?>
                <a href="<?php echo baseUrl('admin/investors.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    💼 Investor
                </a>
                <a href="<?php echo baseUrl('admin/kas_transactions.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    🏦 Kas
                </a>
                <?php endif; ?>
                <?php if (isSuperAdmin()): ?>
                <a href="<?php echo baseUrl('admin/users.php'); ?>" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    👤 Users
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </nav>

    <!-- Flash Message -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
        $bgColor = $flash['type'] === 'success' ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200';
    ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="<?php echo $bgColor; ?> p-4 rounded-lg" x-data="{ show: true }" x-show="show">
            <div class="flex justify-between items-center">
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
                <button @click="show = false" class="ml-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const sunIcon = document.getElementById('sunIcon');
        const moonIcon = document.getElementById('moonIcon');
        const html = document.documentElement;

        function updateThemeIcons() {
            if (html.classList.contains('dark')) {
                sunIcon.classList.remove('hidden');
                moonIcon.classList.add('hidden');
            } else {
                sunIcon.classList.add('hidden');
                moonIcon.classList.remove('hidden');
            }
        }

        // Load theme from localStorage
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
        updateThemeIcons();

        themeToggle.addEventListener('click', () => {
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                html.classList.add('dark');
                localStorage.theme = 'dark';
            }
            updateThemeIcons();
        });
    </script>
