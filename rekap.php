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
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            height: 60vh;
            min-height: 300px;
            max-height: 500px;
            background: #000;
            border-radius: 8px;
            padding: 15px 50px;
        }

        .gallery-image {
            width: 100%;
            height: 100%;
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
                                <th>Aktivitas</th>
                                <th>Level</th>
                                <th>Bonus Harian</th>
                                <th>Detail</th>
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
                                    <a href="#" onclick="openDetail(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>); return false;" class="view-detail-btn">
                                        👁️ Lihat
                                    </a>
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
        let openedFromDetail = false;
        let currentDetailPhotos = [];
        let currentDetailTitle = '';

        function openGallery(photos, tanggal) {
            currentPhotos = photos;
            currentIndex = 0;

            document.getElementById('modalTitle').textContent = '📸 Foto - ' + tanggal;
            document.getElementById('photoModal').style.display = 'block';

            showImage(0);
            renderThumbnails();
        }

        function closeGallery() {
            document.getElementById('photoModal').style.display = 'none';
            if (openedFromDetail) {
                openedFromDetail = false;
                document.getElementById('detailModal').style.display = 'block';
            }
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
            const detailModal = document.getElementById('detailModal');
            if (event.target === modal) {
                closeGallery();
            }
            if (event.target === detailModal) {
                closeDetail();
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

        // Detail Modal
        function openDetail(item) {
            const modal = document.getElementById('detailModal');
            const content = document.getElementById('detailContent');

            const levelBadgeClass = getLevelBadgeClass(item.level);
            const levelColor = getLevelColor(item.level);

            let photosHtml = '<span style="color: #9ca3af;">Tidak ada foto</span>';
            if (item.foto_bukti) {
                const photos = item.foto_bukti.split(',');
                currentDetailPhotos = photos;
                currentDetailTitle = formatDate(item.tanggal);

                let thumbsHtml = '';
                photos.forEach(function(photo, index) {
                    thumbsHtml += '<img src="uploads/' + photo + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #475569;" onclick="openDetailPhoto(' + index + ')" alt="Foto ' + (index + 1) + '">';
                });

                photosHtml = '<div style="display: flex; flex-wrap: wrap; gap: 8px;">' + thumbsHtml + '</div><div style="margin-top: 8px; font-size: 11px; color: #9ca3af;">' + photos.length + ' foto - klik untuk perbesar</div>';
            }

            content.innerHTML = '<div style="display: grid; gap: 8px; font-size: 13px;">' +
                '<div style="display: grid; grid-template-columns: 100px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Tanggal</span>' +
                    '<span style="color: #f1f5f9;">' + formatDate(item.tanggal) + '</span>' +
                '</div>' +
                '<div style="display: grid; grid-template-columns: 100px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Jam Kerja</span>' +
                    '<span style="color: #f1f5f9;">' + (item.jam_mulai ? item.jam_mulai.substring(0, 5) + ' - ' + item.jam_selesai.substring(0, 5) : '-') + '</span>' +
                '</div>' +
                '<div style="display: grid; grid-template-columns: 100px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Durasi</span>' +
                    '<span style="color: #f1f5f9;">' + item.durasi_jam + ' jam</span>' +
                '</div>' +
                '<div style="display: grid; grid-template-columns: 100px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Level</span>' +
                    '<span><span class="badge badge-' + levelBadgeClass + '" style="background: ' + levelColor + ';">Level ' + item.level + '</span></span>' +
                '</div>' +
                '<div style="display: grid; grid-template-columns: 100px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Bonus</span>' +
                    '<span style="color: #10b981; font-weight: bold;">Rp ' + parseInt(item.bonus).toLocaleString('id-ID') + '</span>' +
                '</div>' +
                '<div style="padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<div style="font-weight: 600; margin-bottom: 6px; color: #e2e8f0;">Aktivitas</div>' +
                    '<div style="line-height: 1.5; color: #cbd5e1;">' + escapeHtml(item.aktivitas) + '</div>' +
                '</div>' +
                '<div style="padding: 8px 0;">' +
                    '<div style="font-weight: 600; margin-bottom: 8px; color: #e2e8f0;">Foto Bukti</div>' +
                    photosHtml +
                '</div>' +
            '</div>';

            modal.style.display = 'block';
        }

        function closeDetail() {
            document.getElementById('detailModal').style.display = 'none';
        }

        function openDetailPhoto(index) {
            openedFromDetail = true;
            document.getElementById('detailModal').style.display = 'none';
            openGallery(currentDetailPhotos, currentDetailTitle);
            setTimeout(function() {
                showImage(index);
            }, 100);
        }

        function getLevelBadgeClass(level) {
            const classes = { 'A': 'danger', 'B': 'warning', 'C': 'info', 'D': 'success' };
            return classes[level] || 'primary';
        }

        function getLevelColor(level) {
            const colors = { 'A': '#ef4444', 'B': '#f59e0b', 'C': '#3b82f6', 'D': '#10b981' };
            return colors[level] || '#6b7280';
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return day + '/' + month + '/' + year;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
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

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
        <span id="themeIcon">☀️</span>
    </button>

    <script>
        // Theme Toggle
        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('themeIcon');

            if (body.classList.contains('light-mode')) {
                body.classList.remove('light-mode');
                icon.textContent = '☀️';
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.add('light-mode');
                icon.textContent = '🌙';
                localStorage.setItem('theme', 'light');
            }
        }

        // Load saved theme
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const icon = document.getElementById('themeIcon');

            if (savedTheme === 'light') {
                document.body.classList.add('light-mode');
                if (icon) icon.textContent = '🌙';
            }
        })();
    </script>
</body>
</html>
