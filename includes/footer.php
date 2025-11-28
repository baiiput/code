    </div>
    
    <!-- Main Footer -->
    <?php
    // Check if user is admin
    $isAdmin = false;
    if (isset($user) && isset($user['role'])) {
        $isAdmin = ($user['role'] === 'admin');
    }
    ?>
    
    <?php if ($isAdmin): ?>
    <!-- Admin Footer: Dokumentasi & Tools -->
    <footer class="main-footer" style="background: #2c3e50; color: white; padding: 30px 20px; margin-top: 50px;">
        <div class="footer-content" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            
            <div class="footer-section">
                <h3 style="color: white; margin-bottom: 15px; font-size: 18px;">📚 Dokumentasi</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin: 10px 0;">
                        <a href="dokumentasi-system.php" style="color: #ecf0f1; text-decoration: none;">📖 Panduan Lengkap System</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="dokumentasi-system.php#first-setup" style="color: #ecf0f1; text-decoration: none;">🚀 Setup Awal</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="dokumentasi-system.php#input-data" style="color: #ecf0f1; text-decoration: none;">📝 Input Data Real</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="dokumentasi-system.php#troubleshooting" style="color: #ecf0f1; text-decoration: none;">🔧 Troubleshooting</a>
                    </li>
                </ul>
            </div>

            <div class="footer-section">
                <h3 style="color: white; margin-bottom: 15px; font-size: 18px;">⚙️ Admin Tools</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin: 10px 0;">
                        <a href="admin-reset-data.php" style="color: #ecf0f1; text-decoration: none;">🔄 Reset Data System</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="dokumentasi-system.php#backup" style="color: #ecf0f1; text-decoration: none;">💾 Backup Database</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="dokumentasi-system.php#tips" style="color: #ecf0f1; text-decoration: none;">💡 Tips & Best Practice</a>
                    </li>
                </ul>
            </div>

            <div class="footer-section">
                <h3 style="color: white; margin-bottom: 15px; font-size: 18px;">📊 Menu Cepat</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin: 10px 0;">
                        <a href="index.php" style="color: #ecf0f1; text-decoration: none;">🏠 Dashboard</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="items.php" style="color: #ecf0f1; text-decoration: none;">📦 Data Produk</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="transactions.php" style="color: #ecf0f1; text-decoration: none;">💸 Transaksi</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="reports.php" style="color: #ecf0f1; text-decoration: none;">📊 Laporan</a>
                    </li>
                </ul>
            </div>

            <div class="footer-section">
                <h3 style="color: white; margin-bottom: 15px; font-size: 18px;">ℹ️ Informasi</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin: 10px 0;">
                        <a href="dokumentasi-system.php#overview" style="color: #ecf0f1; text-decoration: none;">📋 Tentang System</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <a href="dokumentasi-system.php" style="color: #ecf0f1; text-decoration: none;">❓ Butuh Bantuan?</a>
                    </li>
                    <li style="margin: 10px 0;">
                        <span style="color: #95a5a6; font-size: 14px;">v1.0 - Warehouse System</span>
                    </li>
                </ul>
            </div>
            
        </div>

        <div class="footer-bottom" style="text-align: center; padding-top: 20px; margin-top: 30px; border-top: 1px solid #34495e; color: #95a5a6;">
            <p>© 2024 Warehouse Management System | <a href="dokumentasi-system.php" style="color: #3498db; text-decoration: none;">Dokumentasi Lengkap</a></p>
        </div>
    </footer>

    <style>
        .main-footer a:hover {
            color: #3498db !important;
            padding-left: 5px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr !important;
            }
            .main-footer {
                padding: 20px 10px !important;
            }
        }
    </style>
    
    <?php else: ?>
    <!-- Simple Footer: Non-Admin Users -->
    <footer class="simple-footer" style="background: #2c3e50; color: white; padding: 20px; margin-top: 50px; text-align: center;">
        <p style="color: #95a5a6; margin: 0;">© 2024 Warehouse Management System</p>
        <?php if (isset($user) && isset($user['role'])): ?>
            <p style="color: #7f8c8d; font-size: 14px; margin-top: 10px;">Login sebagai: <?php echo ucfirst($user['role']); ?></p>
        <?php endif; ?>
    </footer>
    <?php endif; ?>
    
    <script>
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            const icon = document.getElementById('theme-icon');
            if (icon) {
                icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
        
        // Dropdown toggle for desktop and mobile
        function toggleDropdown(button) {
            const dropdown = button.parentElement;
            const isOpen = dropdown.classList.contains('open');
            
            // Close all other dropdowns first
            document.querySelectorAll('.dropdown.open').forEach(d => {
                if (d !== dropdown) {
                    d.classList.remove('open');
                }
            });
            
            // Toggle current dropdown
            if (isOpen) {
                dropdown.classList.remove('open');
            } else {
                dropdown.classList.add('open');
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown.open').forEach(d => {
                    d.classList.remove('open');
                });
            }
        });
        
        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('navbarMenu');
            if (menu) {
                if (menu.style.display === 'flex') {
                    menu.style.display = 'none';
                    // Close all dropdowns when closing menu
                    document.querySelectorAll('.dropdown.open').forEach(d => {
                        d.classList.remove('open');
                    });
                } else {
                    menu.style.display = 'flex';
                }
            }
        }
        
        // Close mobile menu when clicking a link (not dropdown button)
        document.addEventListener('DOMContentLoaded', function() {
            const menuLinks = document.querySelectorAll('.navbar-menu a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const menu = document.getElementById('navbarMenu');
                    if (window.innerWidth <= 1100 && menu) {
                        menu.style.display = 'none';
                        // Close all dropdowns
                        document.querySelectorAll('.dropdown.open').forEach(d => {
                            d.classList.remove('open');
                        });
                    }
                });
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('navbarMenu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (menu && toggle && window.innerWidth <= 1100) {
                if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                    menu.style.display = 'none';
                    // Close all dropdowns
                    document.querySelectorAll('.dropdown.open').forEach(d => {
                        d.classList.remove('open');
                    });
                }
            }
        });
        
        // Confirmation dialog
        function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
            return confirm(message);
        }
        
        // Show success message
        function showSuccess(message) {
            alert('✅ ' + message);
        }
        
        // Show error message
        function showError(message) {
            alert('❌ ' + message);
        }
        
        // Format number input
        function formatNumberInput(input) {
            let value = input.value.replace(/[^0-9.]/g, '');
            input.value = value;
        }
        
        // Calculate subtotal (for transaction forms)
        function calculateSubtotal(qtyInput, priceInput, subtotalInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const subtotal = qty * price;
            subtotalInput.value = subtotal.toFixed(2);
            if (typeof calculateTotal === 'function') {
                calculateTotal();
            }
        }
        
        // Print function
        function printPage() {
            window.print();
        }
        
        // Export to CSV
        function exportTableToCSV(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(row => {
                const cols = row.querySelectorAll('td, th');
                const csvRow = [];
                cols.forEach(col => {
                    csvRow.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
                });
                csv.push(csvRow.join(','));
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
