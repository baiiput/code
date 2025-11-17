<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak | Starlink Manager</title>

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        .error-page {
            width: 600px;
            margin: 80px auto 0;
        }
    </style>
</head>
<body class="hold-transition">
    <section class="content">
        <div class="error-page">
            <h2 class="headline text-warning"><i class="fas fa-exclamation-triangle"></i></h2>

            <div>
                <h3><i class="fas fa-ban text-warning"></i> Akses Ditolak!</h3>

                <p>
                    Anda tidak memiliki izin untuk mengakses halaman ini.<br>
                    Silakan hubungi administrator jika Anda memerlukan akses.
                </p>

                <div class="mt-4">
                    <a href="<?= APP_URL ?? 'index.php' ?>" class="btn btn-primary">
                        <i class="fas fa-home"></i> Kembali ke Dashboard
                    </a>
                    <a href="<?= APP_URL ?? '' ?>/logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
