<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? APP_NAME ?> | <?= APP_NAME ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">

    <style>
        /* Dark mode custom styles */
        .dark-mode .content-wrapper {
            background-color: #343a40;
            color: #fff;
        }
        .dark-mode .card {
            background-color: #454d55;
            color: #fff;
        }
        .dark-mode .card-header {
            background-color: #3d444b;
            border-color: #4b545c;
        }
        .dark-mode .table {
            color: #fff;
        }
        .dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255,255,255,.05);
        }
        .dark-mode .form-control {
            background-color: #3d444b;
            border-color: #4b545c;
            color: #fff;
        }
        .dark-mode .form-control:focus {
            background-color: #3d444b;
            color: #fff;
        }

        /* Badge colors */
        .badge-jatuh-tempo {
            background-color: #17a2b8;
            color: white;
        }
        .badge-proses {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-segera {
            background-color: #dc3545;
            color: white;
        }
        .badge-observasi {
            background-color: #6c757d;
            color: white;
        }

        /* Responsive table */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>

    <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed <?= isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] == '1' ? 'dark-mode' : '' ?>">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?= APP_URL ?>/index.php" class="nav-link">Home</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Dark Mode Toggle -->
            <li class="nav-item">
                <a class="nav-link" href="#" id="darkModeToggle" role="button">
                    <i class="fas fa-moon" id="darkModeIcon"></i>
                </a>
            </li>

            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user-circle fa-lg"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> <?= $_SESSION['nama_lengkap'] ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-id-badge mr-2"></i> <?= getRoleName($_SESSION['user_role']) ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= APP_URL ?>/logout.php" class="dropdown-item dropdown-footer text-danger">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->
