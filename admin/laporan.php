<?php
require_once '../config.php';
requireAdmin();

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Validate month and year ranges
if ($month < 1 || $month > 12) {
    $month = intval(date('m'));
}
if ($year < 2020 || $year > 2099) {
    $year = intval(date('Y'));
}

// Get all active karyawan
$karyawan_list = mysqli_query($conn, "SELECT id, nama FROM users WHERE role='karyawan' AND status='aktif' ORDER BY nama");

// Build query
$query = "SELECT a.*, u.nama as nama_karyawan 
          FROM aktivitas a 
          JOIN users u ON a.user_id = u.id 
          WHERE MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?";
$params = [$month, $year];
$types = "ii";

if ($user_filter > 0) {
    $query .= " AND a.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

$query .= " ORDER BY a.tanggal DESC, a.waktu_input DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$aktivitas = mysqli_stmt_get_result($stmt);

// Calculate summary by level
$summary_query = "SELECT 
    level,
    COUNT(*) as total_aktivitas,
    SUM(durasi_jam) as total_durasi,
    AVG(durasi_jam) as avg_durasi
FROM aktivitas 
WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?";

if ($user_filter > 0) {
    $summary_query .= " AND user_id = ?";
}

$summary_query .= " GROUP BY level ORDER BY level";

$summary_stmt = mysqli_prepare($conn, $summary_query);
if ($user_filter > 0) {
    mysqli_stmt_bind_param($summary_stmt, "iii", $month, $year, $user_filter);
} else {
    mysqli_stmt_bind_param($summary_stmt, "ii", $month, $year);
}
mysqli_stmt_execute($summary_stmt);
$summary = mysqli_stmt_get_result($summary_stmt);

// Get monthly stats
$stats_query = "SELECT 
    COUNT(DISTINCT user_id) as total_karyawan,
    COUNT(*) as total_aktivitas,
    SUM(durasi_jam) as total_durasi,
    COUNT(DISTINCT tanggal) as total_hari
FROM aktivitas 
WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?";

if ($user_filter > 0) {
    $stats_query .= " AND user_id = ?";
}

$stats_stmt = mysqli_prepare($conn, $stats_query);
if ($user_filter > 0) {
    mysqli_stmt_bind_param($stats_stmt, "iii", $month, $year, $user_filter);
} else {
    mysqli_stmt_bind_param($stats_stmt, "ii", $month, $year);
}
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Aktivitas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #6b7280;
        }
        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .level-a { border-left-color: #ef4444; }
        .level-b { border-left-color: #f59e0b; }
        .level-c { border-left-color: #3b82f6; }
        .level-d { border-left-color: #10b981; }
        
        .badge-danger { background: #ef4444; }
        .badge-warning { background: #f59e0b; }
        .badge-info { background: #3b82f6; }
        .badge-success { background: #10b981; }
        
        .export-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
        .export-btn:hover {
            background: #059669;
        }
        
        /* Photo Badge - Clickable */
        .photo-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: #3b82f6;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        .photo-badge:hover {
            background: #2563eb;
            transform: scale(1.05);
        }
        
        /* Modal for Photo Gallery */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .modal-content {
            position: relative;
            margin: auto;
            padding: 20px;
            width: 90%;
            max-width: 1200px;
            height: 90%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .modal-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 0 20px 0;
        }
        
        .modal-title {
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        
        .close {
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #f87171;
        }
        
        .gallery-container {
            width: 100%;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .gallery-image {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            font-size: 30px;
            padding: 15px 20px;
            cursor: pointer;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .gallery-nav:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .gallery-nav.prev {
            left: 20px;
        }
        
        .gallery-nav.next {
            right: 20px;
        }
        
        .gallery-counter {
            color: white;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        
        /* View Detail Button */
        .view-detail-btn {
            display: inline-block;
            padding: 4px 8px;
            background: #6366f1;
            color: white;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
        }
        .view-detail-btn:hover {
            background: #4f46e5;
        }
        
        /* Detail Modal */
        .detail-modal {
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .detail-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .detail-content h3 {
            margin-top: 0;
            color: #1f2937;
        }
        
        .detail-content p {
            line-height: 1.6;
            color: #4b5563;
        }
        
        @media print {
            .nav-menu, .dashboard-header a, .card-header button, .export-btn, form {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-menu">
            <ul>
                <li><a href="../dashboard.php">Dashboard</a></li>
                <li><a href="users.php">Kelola Karyawan</a></li>
                <li><a href="laporan.php" class="active">Laporan</a></li>
            </ul>
        </div>

        <div class="dashboard">
            <div class="dashboard-header">
                <h1>📈 Laporan Aktivitas</h1>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>

            <!-- Filter -->
            <div class="card">
                <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 150px; margin: 0;">
                        <label>Bulan</label>
                        <select name="month" class="form-control">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                        <?php echo $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 120px; margin: 0;">
                        <label>Tahun</label>
                        <select name="year" class="form-control">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 180px; margin: 0;">
                        <label>Karyawan</label>
                        <select name="user_id" class="form-control">
                            <option value="0">Semua Karyawan</option>
                            <?php mysqli_data_seek($karyawan_list, 0); ?>
                            <?php while ($k = mysqli_fetch_assoc($karyawan_list)): ?>
                                <option value="<?php echo $k['id']; ?>" <?php echo $user_filter == $k['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($k['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <button type="button" onclick="window.print()" class="btn btn-secondary">🖨️ Print</button>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="card">
                <h3 style="margin-bottom: 15px;">📊 Ringkasan - <?php echo date('F Y', strtotime("$year-$month-01")); ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div style="background: #f3f4f6; padding: 15px; border-radius: 8px;">
                        <div style="font-size: 12px; color: #6b7280;">Total Karyawan</div>
                        <div style="font-size: 24px; font-weight: bold; color: #1f2937;">
                            <?php echo $stats['total_karyawan']; ?>
                        </div>
                    </div>
                    <div style="background: #f3f4f6; padding: 15px; border-radius: 8px;">
                        <div style="font-size: 12px; color: #6b7280;">Total Aktivitas</div>
                        <div style="font-size: 24px; font-weight: bold; color: #1f2937;">
                            <?php echo $stats['total_aktivitas']; ?>
                        </div>
                    </div>
                    <div style="background: #f3f4f6; padding: 15px; border-radius: 8px;">
                        <div style="font-size: 12px; color: #6b7280;">Total Durasi</div>
                        <div style="font-size: 24px; font-weight: bold; color: #1f2937;">
                            <?php echo number_format($stats['total_durasi'], 1); ?> jam
                        </div>
                    </div>
                    <div style="background: #f3f4f6; padding: 15px; border-radius: 8px;">
                        <div style="font-size: 12px; color: #6b7280;">Total Hari Kerja</div>
                        <div style="font-size: 24px; font-weight: bold; color: #1f2937;">
                            <?php echo $stats['total_hari']; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Level Summary (4 Levels) -->
            <div class="card">
                <h3 style="margin-bottom: 15px;">🏅 Distribusi Level (4 Level System)</h3>
                <div class="stats-grid">
                    <?php mysqli_data_seek($summary, 0); ?>
                    <?php 
                    $level_data = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
                    while ($row = mysqli_fetch_assoc($summary)) {
                        $level_data[$row['level']] = $row;
                    }
                    
                    $level_info = [
                        'A' => ['name' => 'Level A', 'desc' => '< 1 jam', 'bonus' => 15000, 'class' => 'level-a', 'color' => '#ef4444'],
                        'B' => ['name' => 'Level B', 'desc' => '1-1.9 jam', 'bonus' => 25000, 'class' => 'level-b', 'color' => '#f59e0b'],
                        'C' => ['name' => 'Level C', 'desc' => '2-3.9 jam', 'bonus' => 45000, 'class' => 'level-c', 'color' => '#3b82f6'],
                        'D' => ['name' => 'Level D', 'desc' => '≥ 4 jam', 'bonus' => 80000, 'class' => 'level-d', 'color' => '#10b981']
                    ];
                    
                    foreach ($level_info as $level => $info):
                        $data = $level_data[$level];
                    ?>
                    <div class="stat-box <?php echo $info['class']; ?>">
                        <h3><?php echo $info['name']; ?> (<?php echo $info['desc']; ?>)</h3>
                        <div class="value" style="color: <?php echo $info['color']; ?>">
                            <?php echo $data ? $data['total_aktivitas'] : 0; ?> aktivitas
                        </div>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                            Total: <?php echo $data ? number_format($data['total_durasi'], 1) : 0; ?> jam<br>
                            Rata-rata: <?php echo $data ? number_format($data['avg_durasi'], 1) : 0; ?> jam<br>
                            Bonus: <?php echo formatRupiah($info['bonus']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Detail Table -->
            <div class="card">
                <div class="card-header">
                    <h2>📋 Detail Aktivitas</h2>
                </div>
                
                <?php if (mysqli_num_rows($aktivitas) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Karyawan</th>
                                <th>Jam Kerja</th>
                                <th>Durasi</th>
                                <th style="min-width: 250px;">Aktivitas</th>
                                <th>Level</th>
                                <th>Bukti</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($aktivitas, 0); ?>
                            <?php while ($item = mysqli_fetch_assoc($aktivitas)): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($item['tanggal'])); ?></td>
                                <td><?php echo htmlspecialchars($item['nama_karyawan']); ?></td>
                                <td style="font-size: 13px; color: #6b7280;">
                                    <?php 
                                    if (!empty($item['jam_mulai']) && !empty($item['jam_selesai'])) {
                                        echo substr($item['jam_mulai'], 0, 5) . ' - ' . substr($item['jam_selesai'], 0, 5);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $item['durasi_jam']; ?> jam</td>
                                <td style="max-width: 300px; white-space: normal; word-wrap: break-word;">
                                    <?php echo htmlspecialchars($item['aktivitas']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo getLevelBadgeClass($item['level']); ?>">
                                        Level <?php echo $item['level']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!empty($item['foto_bukti'])):
                                        $photos = explode(',', $item['foto_bukti']);
                                        $photo_count = count($photos);
                                        $gallery_title = $item['nama_karyawan'] . ' - ' . date('d/m/Y', strtotime($item['tanggal']));
                                    ?>
                                        <span class="photo-badge" onclick="openGallery(<?php echo json_encode($photos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>, <?php echo json_encode($gallery_title, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)">
                                            📸 <?php echo $photo_count; ?> foto
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="#" onclick="openDetail(<?php echo json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>); return false;" class="view-detail-btn">
                                        👁️ Lihat
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <h3>📭 Tidak ada data</h3>
                    <p>Belum ada aktivitas untuk periode ini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Photo Gallery Modal -->
    <div id="galleryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="galleryTitle">📸 Foto Bukti</div>
                <span class="close" onclick="closeGallery()">&times;</span>
            </div>
            <div class="gallery-container">
                <button class="gallery-nav prev" onclick="changePhoto(-1)">❮</button>
                <img id="galleryImage" class="gallery-image" src="" alt="Foto Bukti">
                <button class="gallery-nav next" onclick="changePhoto(1)">❯</button>
            </div>
            <div class="gallery-counter" id="galleryCounter"></div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content detail-modal">
            <div class="modal-header">
                <div class="modal-title">📋 Detail Aktivitas</div>
                <span class="close" onclick="closeDetail()">&times;</span>
            </div>
            <div class="detail-content" id="detailContent">
                <!-- Content will be inserted here -->
            </div>
        </div>
    </div>

    <script>
        // Photo Gallery
        let currentPhotos = [];
        let currentPhotoIndex = 0;
        let galleryTitle = '';

        function openGallery(photos, title) {
            currentPhotos = photos;
            currentPhotoIndex = 0;
            galleryTitle = title;
            
            document.getElementById('galleryModal').style.display = 'block';
            document.getElementById('galleryTitle').textContent = '📸 ' + title;
            showPhoto();
        }

        function closeGallery() {
            document.getElementById('galleryModal').style.display = 'none';
        }

        function changePhoto(direction) {
            currentPhotoIndex += direction;
            
            if (currentPhotoIndex < 0) {
                currentPhotoIndex = currentPhotos.length - 1;
            } else if (currentPhotoIndex >= currentPhotos.length) {
                currentPhotoIndex = 0;
            }
            
            showPhoto();
        }

        function showPhoto() {
            const img = document.getElementById('galleryImage');
            const counter = document.getElementById('galleryCounter');
            
            img.src = '../uploads/' + currentPhotos[currentPhotoIndex];
            counter.textContent = (currentPhotoIndex + 1) + ' / ' + currentPhotos.length;
            
            // Hide navigation if only one photo
            const prevBtn = document.querySelector('.gallery-nav.prev');
            const nextBtn = document.querySelector('.gallery-nav.next');
            
            if (currentPhotos.length <= 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'block';
                nextBtn.style.display = 'block';
            }
        }

        // Detail Modal
        function openDetail(item) {
            const modal = document.getElementById('detailModal');
            const content = document.getElementById('detailContent');
            
            const levelBadgeClass = getLevelBadgeClass(item.level);
            const levelColor = getLevelColor(item.level);
            
            let photosHtml = '-';
            if (item.foto_bukti) {
                const photos = item.foto_bukti.split(',');
                photosHtml = photos.length + ' foto - <a href="#" onclick="openGallery(' + JSON.stringify(photos) + ', \'' + item.nama_karyawan + ' - ' + formatDate(item.tanggal) + '\'); return false;" style="color: #3b82f6;">Lihat Galeri</a>';
            }
            
            content.innerHTML = `
                <h3>📋 Detail Aktivitas</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-weight: 600; width: 150px;">Karyawan</td>
                        <td style="padding: 12px;">${item.nama_karyawan}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-weight: 600;">Tanggal</td>
                        <td style="padding: 12px;">${formatDate(item.tanggal)}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-weight: 600;">Jam Kerja</td>
                        <td style="padding: 12px;">${item.jam_mulai ? item.jam_mulai.substring(0, 5) + ' - ' + item.jam_selesai.substring(0, 5) : '-'}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-weight: 600;">Durasi</td>
                        <td style="padding: 12px;">${item.durasi_jam} jam</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-weight: 600;">Level</td>
                        <td style="padding: 12px;">
                            <span class="badge badge-${levelBadgeClass}" style="background: ${levelColor};">
                                Level ${item.level}
                            </span>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-weight: 600;">Bonus</td>
                        <td style="padding: 12px; color: #10b981; font-weight: bold;">Rp ${parseInt(item.bonus).toLocaleString('id-ID')}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; font-weight: 600; vertical-align: top;">Aktivitas</td>
                        <td style="padding: 12px; line-height: 1.6;">${item.aktivitas}</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; font-weight: 600;">Foto Bukti</td>
                        <td style="padding: 12px;">${photosHtml}</td>
                    </tr>
                </table>
            `;
            
            modal.style.display = 'block';
        }

        function closeDetail() {
            document.getElementById('detailModal').style.display = 'none';
        }

        function getLevelBadgeClass(level) {
            const classes = {
                'A': 'danger',
                'B': 'warning',
                'C': 'info',
                'D': 'success'
            };
            return classes[level] || 'primary';
        }

        function getLevelColor(level) {
            const colors = {
                'A': '#ef4444',
                'B': '#f59e0b',
                'C': '#3b82f6',
                'D': '#10b981'
            };
            return colors[level] || '#6b7280';
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return day + '/' + month + '/' + year;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const galleryModal = document.getElementById('galleryModal');
            const detailModal = document.getElementById('detailModal');
            
            if (event.target == galleryModal) {
                closeGallery();
            }
            if (event.target == detailModal) {
                closeDetail();
            }
        }

        // Keyboard navigation for gallery
        document.addEventListener('keydown', function(event) {
            const galleryModal = document.getElementById('galleryModal');
            if (galleryModal.style.display === 'block') {
                if (event.key === 'ArrowLeft') {
                    changePhoto(-1);
                } else if (event.key === 'ArrowRight') {
                    changePhoto(1);
                } else if (event.key === 'Escape') {
                    closeGallery();
                }
            }
        });
    </script>
</body>
</html>
