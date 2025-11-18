<?php
require_once 'config.php';
requireKaryawan();

$success = '';
$error = '';

// Get user info for bonus calculation
$current_user = getUserInfo($_SESSION['user_id']);
$user_level = $current_user['level_karyawan'] ?? '3';
$bonus_rates = getBonusRates();
$user_bonus_rates = $bonus_rates[$user_level];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aktivitas = sanitize($_POST['aktivitas']);
    $tanggal = sanitize($_POST['tanggal']);
    $jam_mulai = sanitize($_POST['jam_mulai']);
    $jam_selesai = sanitize($_POST['jam_selesai']);
    
    // Validate inputs
    if (empty($aktivitas) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai)) {
        $error = 'Semua field harus diisi';
    } elseif (!validateDate($tanggal)) {
        $error = 'Format tanggal tidak valid';
    } else {
        // Get employee level
        $user_info = getUserInfo($_SESSION['user_id']);
        $level_karyawan = $user_info['level_karyawan'] ?? '3';
        
        // Calculate duration
        $durasi_jam = calculateDuration($jam_mulai, $jam_selesai);
        
        if ($durasi_jam <= 0 || $durasi_jam > 24) {
            $error = 'Durasi jam tidak valid (0-24 jam)';
        } else {
            // Check existing duration
            $check_query = "SELECT SUM(durasi_jam) as total_durasi 
                            FROM aktivitas 
                            WHERE user_id = ? AND tanggal = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "is", $_SESSION['user_id'], $tanggal);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $row = mysqli_fetch_assoc($check_result);
            $total_durasi_existing = $row['total_durasi'] ?? 0;
            
            // Calculate new total
            $total_durasi_baru = $total_durasi_existing + $durasi_jam;
            
            // Calculate level and bonus with employee level
            $levelBonus = calculateLevelBonus($total_durasi_baru, $level_karyawan);
            $level = strtoupper(trim($levelBonus['level']));
            $bonus_baru = $levelBonus['bonus'];
            
            $levelBonusOld = calculateLevelBonus($total_durasi_existing, $level_karyawan);
            $bonus_lama = $levelBonusOld['bonus'];
            $bonus_increment = $bonus_baru - $bonus_lama;
            
            // Handle multiple file uploads
            $foto_bukti = null;
            if (isset($_FILES['foto_bukti']) && !empty($_FILES['foto_bukti']['name'][0])) {
                $upload_result = uploadMultipleFiles($_FILES['foto_bukti']);
                
                if ($upload_result['success']) {
                    $foto_bukti = implode(',', $upload_result['files']);
                } else {
                    $error = $upload_result['message'];
                }
            }
            
            if (empty($error)) {
                // Insert aktivitas
                $query = "INSERT INTO aktivitas (user_id, tanggal, aktivitas, durasi_jam, jam_mulai, jam_selesai, level, bonus, foto_bukti) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "issdsssss", 
                    $_SESSION['user_id'],
                    $tanggal,
                    $aktivitas,
                    $durasi_jam,
                    $jam_mulai,
                    $jam_selesai,
                    $level,
                    $bonus_increment,
                    $foto_bukti
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    $foto_count = $foto_bukti ? count(explode(',', $foto_bukti)) : 0;
                    $success = "✅ Aktivitas berhasil ditambahkan!<br>";
                    $success .= "📅 Tanggal: " . date('d/m/Y', strtotime($tanggal)) . "<br>";
                    $success .= "⏰ Jam Kerja: $jam_mulai - $jam_selesai<br>";
                    $success .= "⌛ Durasi: " . $durasi_jam . " jam<br>";
                    $success .= "📊 Total durasi hari ini: " . $total_durasi_baru . " jam<br>";
                    $success .= "🏅 Level: $level | Bonus hari ini: " . formatRupiah($bonus_baru) . "<br>";
                    if ($foto_count > 0) {
                        $success .= "📸 Foto: $foto_count file";
                    }
                    
                    $_POST = [];
                } else {
                    $error = 'Gagal menyimpan: ' . mysqli_stmt_error($stmt);
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Aktivitas</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .time-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .photo-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .photo-preview img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
        }
        .level-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
        }
        .level-box {
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            color: white;
            font-size: 13px;
        }
        .level-a { background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); }
        .level-b { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
        .level-c { background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); }
        .level-d { background: linear-gradient(135deg, #34d399 0%, #10b981 100%); }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-menu">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="input.php" class="active">Input Aktivitas</a></li>
                <li><a href="rekap.php">Rekap</a></li>
            </ul>
        </div>

        <div class="dashboard">
            <div class="dashboard-header">
                <h1>✏️ Input Aktivitas</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 20px;">
                <h3 style="margin-bottom: 15px;">ℹ️ Informasi Bonus - Level Karyawan <?php echo $user_level; ?></h3>
                <div class="level-info">
                    <div class="level-box level-a">
                        <strong>Level A</strong><br>
                        &lt; 1 jam<br>
                        <strong><?php echo formatRupiah($user_bonus_rates['A']); ?></strong>
                    </div>
                    <div class="level-box level-b">
                        <strong>Level B</strong><br>
                        1 - 1,9 jam<br>
                        <strong><?php echo formatRupiah($user_bonus_rates['B']); ?></strong>
                    </div>
                    <div class="level-box level-c">
                        <strong>Level C</strong><br>
                        2 - 3,9 jam<br>
                        <strong><?php echo formatRupiah($user_bonus_rates['C']); ?></strong>
                    </div>
                    <div class="level-box level-d">
                        <strong>Level D</strong><br>
                        ≥ 4 jam<br>
                        <strong><?php echo formatRupiah($user_bonus_rates['D']); ?></strong>
                    </div>
                </div>
                <p style="margin-top: 15px; font-size: 13px; opacity: 0.9;">
                    * Bonus dihitung berdasarkan total durasi per hari
                </p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>📝 Form Input</h2>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="tanggal">Tanggal *</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control" 
                               value="<?php echo isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d'); ?>"
                               max="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="time-inputs">
                        <div class="form-group">
                            <label for="jam_mulai">Jam Mulai *</label>
                            <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" 
                                   value="<?php echo $_POST['jam_mulai'] ?? '09:00'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="jam_selesai">Jam Selesai *</label>
                            <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" 
                                   value="<?php echo $_POST['jam_selesai'] ?? '17:00'; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="aktivitas">Deskripsi Aktivitas *</label>
                        <textarea name="aktivitas" id="aktivitas" class="form-control" rows="4"
                                  placeholder="Contoh: Meeting dengan client, update dokumentasi..."
                                  required><?php echo $_POST['aktivitas'] ?? ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="foto_bukti">Upload Foto (1-5 foto, opsional)</label>
                        <input type="file" name="foto_bukti[]" id="foto_bukti" class="form-control" 
                               accept="image/jpeg,image/jpg,image/png" multiple>
                        <small style="color: #6b7280;">Max 5 foto, masing-masing max 5MB (JPG, PNG)</small>
                        <div class="photo-preview" id="photoPreview"></div>
                    </div>

                    <div id="durationPreview" style="padding: 15px; background: #f3f4f6; border-radius: 8px; margin-bottom: 20px; text-align: center; color: #6b7280;">
                        Pilih jam kerja untuk melihat durasi
                    </div>

                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        💾 Simpan Aktivitas
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Photo preview
        document.getElementById('foto_bukti').addEventListener('change', function(e) {
            const files = e.target.files;
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '';
            
            if (files.length > 5) {
                alert('Maksimal 5 foto!');
                e.target.value = '';
                return;
            }
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(file);
            }
        });

        // Duration preview dengan 4 level
        const userBonusRates = {
            'A': <?php echo $user_bonus_rates['A']; ?>,
            'B': <?php echo $user_bonus_rates['B']; ?>,
            'C': <?php echo $user_bonus_rates['C']; ?>,
            'D': <?php echo $user_bonus_rates['D']; ?>
        };

        function updateDuration() {
            const jamMulai = document.getElementById('jam_mulai').value;
            const jamSelesai = document.getElementById('jam_selesai').value;
            const preview = document.getElementById('durationPreview');

            if (!jamMulai || !jamSelesai) {
                preview.innerHTML = 'Pilih jam kerja untuk melihat durasi';
                preview.style.background = '#f3f4f6';
                preview.style.color = '#6b7280';
                return;
            }

            const start = new Date('2000-01-01 ' + jamMulai);
            let end = new Date('2000-01-01 ' + jamSelesai);

            if (end < start) {
                end = new Date('2000-01-02 ' + jamSelesai);
            }

            const diff = (end - start) / (1000 * 60 * 60);
            const durasi = Math.round(diff * 100) / 100;

            let level, badgeClass, bgColor;

            if (durasi < 1) {
                level = 'A'; badgeClass = 'danger'; bgColor = '#fee2e2';
            } else if (durasi >= 1 && durasi < 2) {
                level = 'B'; badgeClass = 'warning'; bgColor = '#fef3c7';
            } else if (durasi >= 2 && durasi < 4) {
                level = 'C'; badgeClass = 'info'; bgColor = '#dbeafe';
            } else {
                level = 'D'; badgeClass = 'success'; bgColor = '#d1fae5';
            }

            const bonus = userBonusRates[level];

            preview.innerHTML = `
                <strong style="font-size: 16px;">⌛ Durasi: ${durasi} jam</strong><br>
                <span class="badge badge-${badgeClass}" style="font-size: 14px; margin: 10px 0; display: inline-block;">Level ${level}</span><br>
                <strong style="color: #10b981; font-size: 18px;">${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 0}).format(bonus)}</strong>
            `;
            preview.style.background = bgColor;
            preview.style.color = '#1f2937';
        }
        
        document.getElementById('jam_mulai').addEventListener('change', updateDuration);
        document.getElementById('jam_selesai').addEventListener('change', updateDuration);
        
        window.addEventListener('load', updateDuration);
    </script>
</body>
</html>
