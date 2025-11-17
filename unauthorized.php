<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak | Starlink Manager</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            max-width: 600px;
            width: 100%;
            padding: 15px;
        }
        .error-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 3rem;
            text-align: center;
        }
        .error-icon {
            font-size: 5rem;
            color: #ffc107;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h2 class="mb-3"><i class="fas fa-ban text-warning"></i> Akses Ditolak!</h2>

            <p class="text-muted mb-4">
                Anda tidak memiliki izin untuk mengakses halaman ini.<br>
                Silakan hubungi administrator jika Anda memerlukan akses.
            </p>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="<?= APP_URL ?? 'index.php' ?>" class="btn btn-primary px-4">
                    <i class="fas fa-home me-2"></i> Kembali ke Dashboard
                </a>
                <a href="<?= APP_URL ?? '' ?>/logout.php" class="btn btn-secondary px-4">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
