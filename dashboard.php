<?php
require_once 'config.php';
requireLogin();

$user = getUserInfo($_SESSION['user_id']);

if (isAdmin()) {
    $stats = getAdminStats();
} else {
    $aktivitas = getAktivitasUser($_SESSION['user_id'], date('m'), date('Y'));
    $salary = getMonthlySalary($_SESSION['user_id']);

    // Add daily bonus info to each activity
    foreach ($aktivitas as &$item) {
        $daily = getDailyBonus($_SESSION['user_id'], $item['tanggal']);
        $item['daily_level'] = $daily['level'];
        $item['daily_bonus'] = $daily['bonus'];
    }
    unset($item); // break reference
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="nav-menu">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="admin/users.php">👥 Kelola Karyawan</a></li>
                    <li><a href="admin/laporan.php">📈 Laporan</a></li>
                <?php else: ?>
                    <li><a href="input.php">Input Aktivitas</a></li>
                    <li><a href="rekap.php">Rekap</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h1>👋 Selamat Datang, <?php echo htmlspecialchars($user['nama']); ?></h1>
                    <p style="color: #6b7280; margin-top: 5px;">
                        <?php echo ucfirst($user['role']); ?> • <?php echo date('d F Y'); ?>
                    </p>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <?php if (isAdmin()): ?>
                <!-- Admin Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3>Total Karyawan Aktif</h3>
                        <div class="value"><?php echo $stats['total_karyawan']; ?></div>
                        <div class="label">Karyawan</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3>Aktivitas Bulan Ini</h3>
                        <div class="value"><?php echo $stats['total_aktivitas']; ?></div>
                        <div class="label">Aktivitas</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3>Total Bonus</h3>
                        <div class="value"><?php echo formatRupiah($stats['total_bonus']); ?></div>
                        <div class="label">Bulan Ini</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3>Total Gaji</h3>
                        <div class="value"><?php echo formatRupiah($stats['total_gaji']); ?></div>
                        <div class="label">Bulan Ini</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>📊 Menu Admin</h2>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="admin/users.php" class="btn btn-primary">👥 Kelola Karyawan</a>
                        <a href="admin/laporan.php" class="btn btn-secondary">📈 Lihat Laporan</a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Karyawan Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3>Aktivitas Bulan Ini</h3>
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

                <div class="card">
                    <div class="card-header">
                        <h2>🚀 Menu Karyawan</h2>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="input.php" class="btn btn-success">✏️ Input Aktivitas</a>
                        <a href="rekap.php" class="btn btn-primary">📋 Rekap Aktivitas</a>
                    </div>
                </div>

                <?php if (count($aktivitas) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>📅 Aktivitas Terbaru</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam Kerja</th>
                                    <th>Aktivitas</th>
                                    <th>Level</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($aktivitas, 0, 5) as $item): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($item['tanggal'])); ?></td>
                                    <td style="font-size: 13px; color: #6b7280;">
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
                                        <a href="#" onclick="openDetail(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>); return false;" class="view-detail-btn">
                                            👁️ Lihat
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="rekap.php" class="btn btn-secondary">Lihat Semua</a>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
        <span id="themeIcon">☀️</span>
    </button>

    <!-- Photo Gallery Modal -->
    <div id="photoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">📸 Foto Bukti</div>
                <span class="close" onclick="closeGallery()">&times;</span>
            </div>
            <div class="gallery-container">
                <button class="gallery-nav prev" onclick="changeImage(-1)">❮</button>
                <img id="galleryImage" class="gallery-image" src="" alt="Foto Bukti">
                <button class="gallery-nav next" onclick="changeImage(1)">❯</button>
            </div>
            <div class="gallery-counter" id="galleryCounter"></div>
            <div class="gallery-thumbnails" id="thumbnailContainer"></div>
        </div>
    </div>

    <!-- Description Modal -->
    <div id="descModal" class="modal">
        <div class="modal-content detail-modal">
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

    <script>
        // Photo Gallery
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

            renderThumbnails();
            showImage(0);
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
            document.getElementById('galleryCounter').textContent = (currentIndex + 1) + ' / ' + currentPhotos.length;

            // Hide nav if only one photo
            const prevBtn = document.querySelector('#photoModal .gallery-nav.prev');
            const nextBtn = document.querySelector('#photoModal .gallery-nav.next');
            if (currentPhotos.length <= 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'block';
                nextBtn.style.display = 'block';
            }

            // Update active thumbnail
            const thumbnails = document.querySelectorAll('#thumbnailContainer .gallery-thumbnail');
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

            // Use daily level and bonus (not per-activity)
            const dailyLevel = item.daily_level || item.level;
            const dailyBonus = item.daily_bonus || item.bonus;
            const levelBadgeClass = getLevelBadgeClass(dailyLevel);
            const levelColor = getLevelColor(dailyLevel);

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
                '<div style="display: grid; grid-template-columns: 120px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Tanggal</span>' +
                    '<span style="color: #f1f5f9;">' + formatDate(item.tanggal) + '</span>' +
                '</div>' +
                '<div style="display: grid; grid-template-columns: 120px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Jam Kerja</span>' +
                    '<span style="color: #f1f5f9;">' + (item.jam_mulai ? item.jam_mulai.substring(0, 5) + ' - ' + item.jam_selesai.substring(0, 5) : '-') + '</span>' +
                '</div>' +
                '<div style="display: grid; grid-template-columns: 120px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Durasi</span>' +
                    '<span style="color: #f1f5f9;">' + item.durasi_jam + ' jam</span>' +
                '</div>' +
                '<div style="display: grid; grid-template-columns: 120px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Level Harian</span>' +
                    '<span><span class="badge badge-' + levelBadgeClass + '" style="background: ' + levelColor + ';">Level ' + dailyLevel + '</span></span>' +
                '</div>' +
                '<div style="display: grid; grid-template-columns: 120px 1fr; padding: 8px 0; border-bottom: 1px solid #475569;">' +
                    '<span style="font-weight: 600; color: #e2e8f0;">Bonus Harian</span>' +
                    '<span style="color: #10b981; font-weight: bold;">Rp ' + parseInt(dailyBonus).toLocaleString('id-ID') + '</span>' +
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

        // Close modals
        window.onclick = function(event) {
            const photoModal = document.getElementById('photoModal');
            const descModal = document.getElementById('descModal');
            const detailModal = document.getElementById('detailModal');
            if (event.target === photoModal) {
                closeGallery();
            }
            if (event.target === descModal) {
                closeDescModal();
            }
            if (event.target === detailModal) {
                closeDetail();
            }
        }

        document.addEventListener('keydown', function(e) {
            const photoModal = document.getElementById('photoModal');
            if (photoModal.style.display === 'block') {
                if (e.key === 'ArrowLeft') changeImage(-1);
                if (e.key === 'ArrowRight') changeImage(1);
                if (e.key === 'Escape') closeGallery();
            }
            if (e.key === 'Escape') {
                closeDescModal();
            }
        });

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
                icon.textContent = '🌙';
            }
        })();
    </script>
</body>
</html>
