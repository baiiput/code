<?php
require_once 'config.php';
requireKaryawan();

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month and year ranges
if ($month < 1 || $month > 12) {
    $month = intval(date('m'));
}
if ($year < 2020 || $year > 2099) {
    $year = intval(date('Y'));
}

$aktivitas = getAktivitasUser($_SESSION['user_id'], $month, $year);
$salary = getMonthlySalary($_SESSION['user_id'], $month, $year);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Aktivitas</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Modal Gallery Styles */
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
        
        .gallery-thumbnails {
            display: flex;
            gap: 10px;
            padding: 20px;
            overflow-x: auto;
            justify-content: center;
        }
        
        .gallery-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .gallery-thumbnail:hover {
            border-color: white;
            transform: scale(1.1);
        }
        
        .gallery-thumbnail.active {
            border-color: #3b82f6;
        }
        
        .photo-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: #3b82f6;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .photo-badge:hover {
            background: #2563eb;
            transform: scale(1.05);
        }
        
        .jam-kerja {
            color: #6b7280;
            font-size: 14px;
        }
        
        /* Badge colors for 4 levels */
        .badge-danger { background: #ef4444; }
        .badge-warning { background: #f59e0b; }
        .badge-info { background: #3b82f6; }
        .badge-success { background: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-menu">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="input.php">Input Aktivitas</a></li>
                <li><a href="rekap.php" class="active">Rekap</a></li>
            </ul>
        </div>

        <div class="dashboard">
            <div class="dashboard-header">
                <h1>📋 Rekap Aktivitas</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h3>Total Aktivitas</h3>
                    <div class="value"><?php echo count($aktivitas); ?></div>
                    <div class="label">Aktivitas</div>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3>Gaji Pokok</h3>
                    <div class="value"><?php echo formatRupiah($salary['gaji_pokok']); ?></div>
                    <div class="label">Per Bulan</div>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h3>Total Bonus</h3>
                    <div class="value"><?php echo formatRupiah($salary['total_bonus']); ?></div>
                    <div class="label">Bulan Ini</div>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <h3>Total Gaji</h3>
                    <div class="value"><?php echo formatRupiah($salary['total_gaji']); ?></div>
                    <div class="label">Bulan Ini</div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card">
                <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">Bulan</label>
                        <select name="month" class="form-control" style="padding: 12px; font-size: 15px;">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                        <?php echo $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">Tahun</label>
                        <select name="year" class="form-control" style="padding: 12px; font-size: 15px;">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px; height: fit-content;">
                        🔍 Filter
                    </button>
                </form>
            </div>

            <!-- Activities Table -->
            <div class="card">
                <div class="card-header">
                    <h2>📅 Daftar Aktivitas - <?php echo date('F Y', strtotime("$year-$month-01")); ?></h2>
                </div>
                
                <?php if (count($aktivitas) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jam Kerja</th>
                                <th>Durasi</th>
                                <th>Aktivitas</th>
                                <th>Level</th>
                                <th>Bonus Harian</th>
                                <th>Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Group activities by date to find last activity per day
                            $activities_by_date = [];
                            foreach ($aktivitas as $item) {
                                $activities_by_date[$item['tanggal']][] = $item['id'];
                            }
                            
                            foreach ($aktivitas as $item): 
                                // Check if this is the last activity of the day
                                $date_activities = $activities_by_date[$item['tanggal']];
                                $is_last_activity = ($item['id'] == end($date_activities));
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($item['tanggal'])); ?></td>
                                <td class="jam-kerja">
                                    <?php 
                                    if (!empty($item['jam_mulai']) && !empty($item['jam_selesai'])) {
                                        echo substr($item['jam_mulai'], 0, 5) . ' - ' . substr($item['jam_selesai'], 0, 5);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $item['durasi_jam']; ?> jam</td>
                                <td class="description-cell">
                                    <div class="description-text"><?php echo htmlspecialchars($item['aktivitas']); ?></div>
                                    <?php if (strlen($item['aktivitas']) > 50): ?>
                                    <span class="view-more-btn" onclick="showFullDescription(<?php echo htmlspecialchars(json_encode($item['aktivitas']), ENT_QUOTES, 'UTF-8'); ?>)">Lihat</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo getLevelBadgeClass($item['level']); ?>">
                                        Level <?php echo $item['level']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    // Only show bonus on last activity of the day
                                    if ($is_last_activity) {
                                        $daily = getDailyBonus($_SESSION['user_id'], $item['tanggal']);
                                        echo '<strong style="color: #10b981;">' . formatRupiah($daily['bonus']) . '</strong>'; 
                                    } else {
                                        echo '<span style="color: #9ca3af;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($item['foto_bukti'])):
                                        $photos = explode(',', $item['foto_bukti']);
                                        $photo_count = count($photos);
                                        $gallery_date = date('d/m/Y', strtotime($item['tanggal']));
                                    ?>
                                        <span class="photo-badge" onclick="openGallery(<?php echo htmlspecialchars(json_encode($photos), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($gallery_date), ENT_QUOTES, 'UTF-8'); ?>)">
                                            📸 <?php echo $photo_count; ?> foto
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #6b7280;">
                    <h3>📭 Belum ada aktivitas</h3>
                    <p>Silakan tambahkan aktivitas Anda</p>
                    <a href="input.php" class="btn btn-primary" style="margin-top: 15px;">Tambah Aktivitas</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Photo Gallery Modal -->
    <div id="photoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Foto Bukti</div>
                <span class="close" onclick="closeGallery()">&times;</span>
            </div>
            
            <div class="gallery-container">
                <button class="gallery-nav prev" onclick="changeImage(-1)">❮</button>
                <img id="galleryImage" class="gallery-image" src="" alt="Foto Bukti">
                <button class="gallery-nav next" onclick="changeImage(1)">❯</button>
            </div>
            
            <div class="gallery-thumbnails" id="thumbnailContainer"></div>
        </div>
    </div>

    <script>
        let currentPhotos = [];
        let currentIndex = 0;
        
        function openGallery(photos, tanggal) {
            currentPhotos = photos;
            currentIndex = 0;
            
            document.getElementById('modalTitle').textContent = 'Foto Bukti - ' + tanggal;
            document.getElementById('photoModal').style.display = 'block';
            
            showImage(0);
            renderThumbnails();
        }
        
        function closeGallery() {
            document.getElementById('photoModal').style.display = 'none';
        }
        
        function showImage(index) {
            if (index < 0) index = currentPhotos.length - 1;
            if (index >= currentPhotos.length) index = 0;
            
            currentIndex = index;
            document.getElementById('galleryImage').src = 'uploads/' + currentPhotos[index];
            
            // Update active thumbnail
            const thumbnails = document.querySelectorAll('.gallery-thumbnail');
            thumbnails.forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });
        }
        
        function changeImage(direction) {
            showImage(currentIndex + direction);
        }
        
        function renderThumbnails() {
            const container = document.getElementById('thumbnailContainer');
            container.innerHTML = '';
            
            currentPhotos.forEach((photo, index) => {
                const img = document.createElement('img');
                img.src = 'uploads/' + photo;
                img.className = 'gallery-thumbnail' + (index === 0 ? ' active' : '');
                img.onclick = () => showImage(index);
                container.appendChild(img);
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('photoModal');
            if (event.target === modal) {
                closeGallery();
            }
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('photoModal');
            const descModal = document.getElementById('descModal');
            if (modal.style.display === 'block') {
                if (e.key === 'ArrowLeft') changeImage(-1);
                if (e.key === 'ArrowRight') changeImage(1);
                if (e.key === 'Escape') closeGallery();
            }
            if (descModal && descModal.style.display === 'block') {
                if (e.key === 'Escape') closeDescModal();
            }
        });

        // Description Modal
        function showFullDescription(text) {
            document.getElementById('fullDescText').textContent = text;
            document.getElementById('descModal').style.display = 'block';
        }

        function closeDescModal() {
            document.getElementById('descModal').style.display = 'none';
        }
    </script>

    <!-- Description Modal -->
    <div id="descModal" class="modal">
        <div class="modal-content detail-modal" style="max-width: 600px;">
            <div class="modal-header">
                <div class="modal-title">📋 Deskripsi Aktivitas</div>
                <span class="close" onclick="closeDescModal()">&times;</span>
            </div>
            <div class="detail-content">
                <p id="fullDescText" style="white-space: pre-wrap; margin: 0;"></p>
            </div>
        </div>
    </div>
</body>
</html>
