<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            redirect('index.php');
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd',
                            400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8',
                            800: '#1e40af', 900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="h-12 w-12 rounded-xl bg-primary-600 flex items-center justify-center">
                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900 dark:text-white">Starlink Customer</h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">Management System</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow-xl sm:rounded-xl sm:px-10 border border-gray-200 dark:border-gray-700">
                <?php if ($error): ?>
                <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/50 p-4 text-sm text-red-800 dark:text-red-200">
                    <?= e($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
                        <input type="email" name="email" id="email" required value="<?= e($_POST['email'] ?? '') ?>" class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2.5 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Password</label>
                        <input type="password" name="password" id="password" required class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-2.5 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div class="flex items-center justify-between">
                        <div></div>
                        <button type="button" onclick="toggleDarkMode()" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                            Toggle Dark Mode
                        </button>
                    </div>

                    <button type="submit" class="w-full flex justify-center rounded-lg bg-primary-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-primary-500 focus:ring-2 focus:ring-primary-600">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
