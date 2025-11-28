<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">🔒</div>
        <h1>Akses Ditolak</h1>
        <p>Halaman ini hanya dapat diakses oleh <strong>Administrator</strong>.</p>
        <p>Role Anda saat ini: <strong>' . ucfirst($user['role']) . '</strong></p>
        <a href="index.php" class="btn">← Kembali ke Dashboard</a>
    </div>
</body>
</html>';
    exit;
}

// If admin, show the page
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Data System - Admin Only</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .admin-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.95);
            padding: 10px 20px;
            border-radius: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            font-size: 14px;
            color: #667eea;
            font-weight: bold;
        }

        .admin-badge::before {
            content: '👤 ';
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .warning-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .content {
            padding: 40px;
        }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .warning-box p {
            color: #856404;
            line-height: 1.6;
        }

        .info-box {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            color: #0c5460;
            margin-bottom: 15px;
        }

        .info-box ul {
            color: #0c5460;
            margin-left: 20px;
            line-height: 1.8;
        }

        .success-box {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .success-box h3 {
            color: #155724;
            margin-bottom: 10px;
        }

        .success-box p {
            color: #155724;
        }

        .reset-section {
            margin-bottom: 30px;
            padding: 25px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
        }

        .reset-section h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .reset-section p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .sql-code {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 15px 0;
        }

        .sql-code code {
            color: #e83e8c;
        }

        .step-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
        }

        .step-box strong {
            color: #1976D2;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .checklist {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .checklist label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.2s;
        }

        .checklist label:hover {
            background: #e9ecef;
        }

        .checklist input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .backup-reminder {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .backup-reminder h3 {
            color: #721c24;
            margin-bottom: 10px;
        }

        .backup-reminder p {
            color: #721c24;
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .admin-badge {
                top: 10px;
                right: 10px;
                font-size: 12px;
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-badge">Admin: <?php echo htmlspecialchars($user['full_name']); ?></div>
    
    <div class="container">
        <div class="header">
            <div class="warning-icon">⚠️</div>
            <h1>Reset Data System</h1>
            <p>Halaman Admin - Gunakan dengan Hati-hati</p>
        </div>

        <div class="content">
            <div class="warning-box">
                <h3>⚠️ PERINGATAN PENTING!</h3>
                <p><strong>Tindakan ini akan menghapus SEMUA data testing dan tidak dapat di-undo!</strong></p>
                <p>Pastikan Anda sudah melakukan backup database sebelum melanjutkan.</p>
            </div>
            
            <div class="success-box">
                <h3>✅ Metode: DELETE (Aman & Terbukti Berhasil)</h3>
                <p>Query menggunakan <code>DELETE</code> yang aman untuk semua hosting dan tidak memerlukan privilege khusus. Metode ini sudah ditest dan terbukti berhasil.</p>
                <p><strong>Bonus:</strong> AUTO_INCREMENT akan di-reset ke 1, jadi ID transaksi mulai dari awal lagi.</p>
            </div>

            <div class="backup-reminder">
                <h3>📦 Sudah Backup Database?</h3>
                <p>Jangan lupa export database dulu sebelum reset!</p>
            </div>

            <div class="info-box">
                <h3>📋 Data yang Akan Di-reset:</h3>
                <ul>
                    <li>Semua transaksi stock IN (pembelian dari supplier)</li>
                    <li>Semua transaksi stock OUT (distribusi ke cabang)</li>
                    <li>History penyesuaian stok</li>
                    <li>Data transaksi keuangan</li>
                    <li>Stok item (reset ke 0)</li>
                    <li>Saldo warehouse (reset ke 0)</li>
                </ul>
                <p style="margin-top: 15px;"><strong>Yang TIDAK dihapus:</strong> Data master items, suppliers, branches, categories, dan users</p>
            </div>

            <div class="reset-section">
                <h3>🔧 Cara Reset via phpMyAdmin</h3>
                
                <div class="step-box">
                    <strong>Langkah 1:</strong> Login ke phpMyAdmin
                </div>

                <div class="step-box">
                    <strong>Langkah 2:</strong> Pilih database warehouse Anda
                </div>

                <div class="step-box">
                    <strong>Langkah 3:</strong> Klik tab "SQL" di bagian atas
                </div>

                <div class="step-box">
                    <strong>Langkah 4:</strong> Copy-paste query SQL di bawah ini:
                </div>

                <div class="sql-code">
<code>-- Reset Semua Data Transaksi dan Keuangan
-- BACKUP DATABASE DULU SEBELUM JALANKAN INI!
-- Menggunakan DELETE (Terbukti Berhasil)

-- 1. Hapus semua transaksi stock OUT (distribusi ke cabang)
DELETE FROM stock_out_detail;
DELETE FROM stock_out;

-- 2. Hapus semua transaksi stock IN (pembelian dari supplier)
DELETE FROM stock_in_detail;
DELETE FROM stock_in;

-- 3. Hapus semua penyesuaian stok
DELETE FROM stock_adjustment;

-- 4. Hapus semua history transaksi keuangan
DELETE FROM financial_transactions;

-- 5. Reset AUTO_INCREMENT ke 1 (mulai dari awal lagi)
ALTER TABLE stock_out AUTO_INCREMENT = 1;
ALTER TABLE stock_out_detail AUTO_INCREMENT = 1;
ALTER TABLE stock_in AUTO_INCREMENT = 1;
ALTER TABLE stock_in_detail AUTO_INCREMENT = 1;
ALTER TABLE stock_adjustment AUTO_INCREMENT = 1;
ALTER TABLE financial_transactions AUTO_INCREMENT = 1;

-- 6. Reset stok semua item ke 0
UPDATE items SET current_stock = 0, average_cost = 0;

-- 7. Reset saldo warehouse ke 0
UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1;

-- Selesai! Database siap untuk data real</code>
                </div>

                <div class="step-box">
                    <strong>Langkah 5:</strong> Klik tombol "Go" atau "Jalankan" untuk eksekusi query
                </div>

                <div class="step-box">
                    <strong>Langkah 6:</strong> Refresh halaman sistem Anda dan cek hasilnya
                </div>
            </div>

            <div class="reset-section">
                <h3>🔧 Reset Selective (Per Bagian)</h3>
                <p>Jika hanya ingin reset bagian tertentu, gunakan query individual:</p>

                <h4 style="margin: 20px 0 10px 0; color: #555;">Reset Transaksi Saja:</h4>
                <div class="sql-code">
<code>DELETE FROM stock_out_detail;
DELETE FROM stock_out;
DELETE FROM stock_in_detail;
DELETE FROM stock_in;
DELETE FROM stock_adjustment;
ALTER TABLE stock_out AUTO_INCREMENT = 1;
ALTER TABLE stock_in AUTO_INCREMENT = 1;
UPDATE items SET current_stock = 0, average_cost = 0;</code>
                </div>

                <h4 style="margin: 20px 0 10px 0; color: #555;">Reset Keuangan Saja:</h4>
                <div class="sql-code">
<code>DELETE FROM financial_transactions;
ALTER TABLE financial_transactions AUTO_INCREMENT = 1;
UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1;</code>
                </div>

                <h4 style="margin: 20px 0 10px 0; color: #555;">Reset Master Data (Cabang & Supplier):</h4>
                <div class="sql-code">
<code>DELETE FROM branches;
DELETE FROM suppliers;
ALTER TABLE branches AUTO_INCREMENT = 1;
ALTER TABLE suppliers AUTO_INCREMENT = 1;</code>
                </div>
            </div>

            <div class="checklist">
                <h3 style="margin-bottom: 15px;">✅ Checklist Sebelum Reset:</h3>
                <label>
                    <input type="checkbox" id="check1">
                    <span>Sudah backup database (export .sql)</span>
                </label>
                <label>
                    <input type="checkbox" id="check2">
                    <span>Sudah screenshot/catat data penting</span>
                </label>
                <label>
                    <input type="checkbox" id="check3">
                    <span>Sudah informasikan ke team (jika ada)</span>
                </label>
                <label>
                    <input type="checkbox" id="check4">
                    <span>Yakin 100% mau reset data testing</span>
                </label>
                <label>
                    <input type="checkbox" id="check5">
                    <span>Sudah siap input data real yang benar</span>
                </label>
            </div>

            <div class="info-box" style="background: #d4edda; border-color: #c3e6cb;">
                <h3 style="color: #155724;">💡 Tips Setelah Reset:</h3>
                <ul style="color: #155724;">
                    <li>Mulai input data master dulu (produk, cabang, supplier)</li>
                    <li>Lalu input stok awal gudang</li>
                    <li>Baru input transaksi dari tanggal mulai operasional</li>
                    <li>Set saldo awal keuangan sesuai kondisi real</li>
                    <li>Test sistem dengan beberapa transaksi untuk memastikan</li>
                </ul>
            </div>

            <div class="button-group">
                <a href="dokumentasi-system.php" class="btn btn-info">📖 Lihat Dokumentasi Lengkap</a>
                <a href="index.php" class="btn btn-secondary">🏠 Kembali ke Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        // Validasi checklist sebelum bisa reset
        const checkboxes = document.querySelectorAll('.checklist input[type="checkbox"]');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                if (allChecked) {
                    alert('✅ Semua checklist sudah dipenuhi!\n\nSekarang Anda bisa jalankan query SQL di phpMyAdmin untuk reset data.');
                }
            });
        });
    </script>
</body>
</html>
