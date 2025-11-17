<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] == '1' ? 'dark' : 'light' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? APP_NAME ?> | <?= APP_NAME ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Select2 Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

    <style>
        /* Custom Admin Layout */
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 56px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            width: 250px;
            transition: all 0.3s;
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 56px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            font-weight: 500;
            color: var(--bs-body-color);
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.125rem 0.5rem;
        }

        .sidebar .nav-link:hover {
            background-color: var(--bs-tertiary-bg);
        }

        .sidebar .nav-link.active {
            background-color: var(--bs-primary);
            color: white;
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
            padding: 1rem 1rem 0.25rem;
            font-weight: 600;
            color: var(--bs-secondary);
        }

        /* Main Content */
        main {
            margin-left: 250px;
            padding-top: 56px;
            transition: all 0.3s;
        }

        /* Navbar */
        .navbar {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
        }

        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            font-weight: bold;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            main {
                margin-left: 0;
            }

            .sidebar.show {
                margin-left: 0;
            }
        }

        /* Cards */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            margin-bottom: 1.5rem;
        }

        /* Small Box (Dashboard Cards) */
        .small-box {
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            margin-bottom: 1rem;
            overflow: hidden;
            position: relative;
        }

        .small-box .inner {
            padding: 1.5rem;
        }

        .small-box .inner h3 {
            font-size: 2.2rem;
            font-weight: bold;
            margin: 0 0 0.625rem;
        }

        .small-box .inner p {
            font-size: 1rem;
            margin: 0;
        }

        .small-box .icon {
            position: absolute;
            top: 0.625rem;
            right: 0.625rem;
            font-size: 5rem;
            opacity: 0.15;
        }

        .small-box-footer {
            background-color: rgba(0,0,0,.1);
            color: rgba(255,255,255,.8);
            display: block;
            padding: 0.5rem 0;
            text-align: center;
            text-decoration: none;
            transition: all .3s;
        }

        .small-box-footer:hover {
            background-color: rgba(0,0,0,.15);
            color: white;
        }

        /* Info Box */
        .info-box {
            display: flex;
            background: var(--bs-body-bg);
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            margin-bottom: 1rem;
            min-height: 80px;
        }

        .info-box-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 90px;
            font-size: 2.5rem;
            border-radius: 0.375rem 0 0 0.375rem;
        }

        .info-box-content {
            padding: 0.75rem 1rem;
            flex: 1;
        }

        .info-box-text {
            text-transform: uppercase;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .info-box-number {
            font-weight: bold;
            font-size: 1.5rem;
        }

        /* Badge positioning in sidebar */
        .sidebar .badge {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .sidebar .nav-link {
            position: relative;
        }

        /* Dark mode adjustments */
        [data-bs-theme="dark"] .sidebar {
            background-color: var(--bs-dark);
            box-shadow: inset -1px 0 0 rgba(255, 255, 255, .1);
        }

        [data-bs-theme="dark"] .navbar {
            background-color: var(--bs-dark) !important;
        }

        /* Status badges */
        .badge-jatuh-tempo {
            background-color: #0dcaf0;
        }

        .badge-proses {
            background-color: #ffc107;
            color: #000;
        }

        .badge-segera {
            background-color: #dc3545;
        }

        .badge-observasi {
            background-color: #6c757d;
        }

        /* Table responsive improvements */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .btn-sm {
                padding: 0.125rem 0.25rem;
                font-size: 0.75rem;
            }
        }
    </style>

    <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <a class="navbar-brand" href="<?= APP_URL ?>/index.php">
            <i class="fas fa-satellite-dish"></i> <strong>Starlink</strong> Manager
        </a>

        <div class="d-flex align-items-center ms-auto">
            <!-- Dark Mode Toggle -->
            <button class="btn btn-link text-white me-3" id="darkModeToggle" title="Toggle Dark Mode">
                <i class="fas fa-moon" id="darkModeIcon"></i>
            </button>

            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link text-white dropdown-toggle text-decoration-none" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle fa-lg"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header"><?= $_SESSION['nama_lengkap'] ?></h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><span class="dropdown-item-text"><small><?= getRoleName($_SESSION['user_role']) ?></small></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
