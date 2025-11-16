<?php
require_once 'config.php';
requireRole(['admin', 'staff_keuangan']);

$conn = getDBConnection();
$user = getCurrentUser();

// Get current balance
$result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
$current_balance = $result->fetch_assoc()['balance_amount'] ?? 0;

// Handle add capital
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_capital' && hasRole('admin')) {
        $amount = floatval($_POST['amount']);
        $description = clean($_POST['description']);
        $transaction_date = $_POST['transaction_date'] . ' ' . date('H:i:s');
        
        if ($amount > 0) {
            $conn->begin_transaction();
            try {
                // Update balance (increase)
                $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = balance_amount + ? WHERE balance_id = 1");
                $stmt->bind_param("d", $amount);
                $stmt->execute();
                $stmt->close();
                
                // Get new balance
                $result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
                $new_balance = $result->fetch_assoc()['balance_amount'];
                
                // Log transaction
                $reference = 'MODAL-' . date('YmdHis');
                $stmt = $conn->prepare("INSERT INTO financial_transactions (transaction_date, transaction_type, reference_code, description, debit, credit, balance_after, created_by) VALUES (?, 'modal', ?, ?, ?, 0, ?, ?)");
                $stmt->bind_param("sssddi", $transaction_date, $reference, $description, $amount, $new_balance, $user['user_id']);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                $_SESSION['success_message'] = "Modal berhasil ditambahkan: " . formatRupiah($amount);
                header('Location: finance.php');
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action === 'withdraw_capital' && hasRole('admin')) {
        $amount = floatval($_POST['amount']);
        $description = clean($_POST['description']);
        $transaction_date = $_POST['transaction_date'] . ' ' . date('H:i:s');
        
        if ($amount > 0) {
            // Check if balance is sufficient
            $result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
            $current = $result->fetch_assoc()['balance_amount'];
            
            if ($amount > $current) {
                $_SESSION['error_message'] = "Saldo tidak mencukupi! Saldo saat ini: " . formatRupiah($current);
                header('Location: finance.php');
                exit;
            }
            
            $conn->begin_transaction();
            try {
                // Update balance (decrease)
                $stmt = $conn->prepare("UPDATE warehouse_balance SET balance_amount = balance_amount - ? WHERE balance_id = 1");
                $stmt->bind_param("d", $amount);
                $stmt->execute();
                $stmt->close();
                
                // Get new balance
                $result = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1");
                $new_balance = $result->fetch_assoc()['balance_amount'];
                
                // Log transaction
                $reference = 'TARIK-' . date('YmdHis');
                $stmt = $conn->prepare("INSERT INTO financial_transactions (transaction_date, transaction_type, reference_code, description, debit, credit, balance_after, created_by) VALUES (?, 'penarikan', ?, ?, 0, ?, ?, ?)");
                $stmt->bind_param("sssddi", $transaction_date, $reference, $description, $amount, $new_balance, $user['user_id']);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                $_SESSION['success_message'] = "Penarikan modal berhasil: " . formatRupiah($amount);
                header('Location: finance.php');
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get financial transactions
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$type_filter = $_GET['type'] ?? '';

$query = "
    SELECT ft.*, u.full_name as created_by_name,
           si.supplier_id, s.supplier_name,
           so.branch_id, b.branch_name
    FROM financial_transactions ft
    LEFT JOIN users u ON ft.created_by = u.user_id
    LEFT JOIN stock_in si ON ft.reference_code = si.transaction_code AND ft.transaction_type = 'stock_in'
    LEFT JOIN suppliers s ON si.supplier_id = s.supplier_id
    LEFT JOIN stock_out so ON ft.reference_code = so.transaction_code AND ft.transaction_type = 'stock_out'
    LEFT JOIN branches b ON so.branch_id = b.branch_id
    WHERE DATE(ft.transaction_date) BETWEEN '$date_from' AND '$date_to'
";

if ($type_filter) {
    $query .= " AND ft.transaction_type = '$type_filter'";
}

$query .= " ORDER BY ft.transaction_date DESC, ft.transaction_id DESC LIMIT 100";

$transactions = [];
$total_debit = 0;
$total_credit = 0;

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    // Build detailed description
    if ($row['transaction_type'] === 'stock_in' && !empty($row['supplier_name'])) {
        $row['detail_description'] = "Pembelian dari supplier " . $row['supplier_name'];
    } elseif ($row['transaction_type'] === 'stock_out' && !empty($row['branch_name'])) {
        $row['detail_description'] = "Distribusi ke cabang " . $row['branch_name'];
    } else {
        $row['detail_description'] = $row['description'];
    }

    $transactions[] = $row;
    $total_debit += $row['debit'];
    $total_credit += $row['credit'];
}

$page_title = 'Keuangan Warehouse';
include 'includes/header.php';

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-wallet"></i> Keuangan Warehouse</h1>
        <?php if (hasRole('admin')): ?>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-success" onclick="showAddCapitalModal()">
                <i class="fas fa-plus-circle"></i> Tambah Modal
            </button>
            <button class="btn btn-danger" onclick="showWithdrawModal()">
                <i class="fas fa-minus-circle"></i> Tarik Modal
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Balance Card -->
    <div class="balance-card">
        <div class="balance-header">
            <div class="balance-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="balance-info">
                <div class="balance-label">Saldo Warehouse</div>
                <div class="balance-amount"><?php echo formatRupiah($current_balance); ?></div>
                <div class="balance-date">Per <?php echo date('d M Y, H:i'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card income">
            <div class="summary-icon"><i class="fas fa-arrow-up"></i></div>
            <div class="summary-info">
                <div class="summary-label">Pemasukan</div>
                <div class="summary-value"><?php echo formatRupiah($total_debit); ?></div>
            </div>
        </div>
        <div class="summary-card expense">
            <div class="summary-icon"><i class="fas fa-arrow-down"></i></div>
            <div class="summary-info">
                <div class="summary-label">Pengeluaran</div>
                <div class="summary-value"><?php echo formatRupiah($total_credit); ?></div>
            </div>
        </div>
        <div class="summary-card net">
            <div class="summary-icon"><i class="fas fa-chart-line"></i></div>
            <div class="summary-info">
                <div class="summary-label">Selisih (Net)</div>
                <div class="summary-value" style="color: <?php echo ($total_debit - $total_credit) >= 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
                    <?php echo formatRupiah($total_debit - $total_credit); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <input type="date" name="date_from" value="<?php echo $date_from; ?>" required>
            <span style="display: flex; align-items: center;">s/d</span>
            <input type="date" name="date_to" value="<?php echo $date_to; ?>" required>
            <select name="type">
                <option value="">Semua Tipe</option>
                <option value="modal" <?php echo $type_filter === 'modal' ? 'selected' : ''; ?>>Modal</option>
                <option value="penarikan" <?php echo $type_filter === 'penarikan' ? 'selected' : ''; ?>>Penarikan</option>
                <option value="stock_in" <?php echo $type_filter === 'stock_in' ? 'selected' : ''; ?>>Pembelian</option>
                <option value="stock_out" <?php echo $type_filter === 'stock_out' ? 'selected' : ''; ?>>Penjualan</option>
            </select>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="finance.php" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Reset
            </a>
            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </form>
    </div>
    
    <!-- Transactions Table -->
    <div class="table-container">
        <table class="data-table" id="financeTable">
            <thead>
                <tr>
                    <th width="13%">Tanggal</th>
                    <th width="10%">Tipe</th>
                    <th width="13%">Referensi</th>
                    <th>Deskripsi</th>
                    <th width="12%" class="text-right">Debit (+)</th>
                    <th width="12%" class="text-right">Kredit (-)</th>
                    <th width="13%" class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr><td colspan="7" class="text-center">Tidak ada transaksi</td></tr>
                <?php else: ?>
                <?php foreach ($transactions as $trans): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($trans['transaction_date'])); ?></td>
                    <td>
                        <span class="badge badge-<?php 
                            echo $trans['transaction_type'] === 'modal' ? 'info' : 
                                ($trans['transaction_type'] === 'penarikan' ? 'warning' :
                                ($trans['transaction_type'] === 'stock_in' ? 'danger' : 'success')); 
                        ?>">
                            <?php 
                            $types = [
                                'modal' => 'Modal',
                                'penarikan' => 'Penarikan',
                                'stock_in' => 'Pembelian',
                                'stock_out' => 'Penjualan',
                                'adjustment' => 'Adjustment'
                            ];
                            echo $types[$trans['transaction_type']] ?? $trans['transaction_type'];
                            ?>
                        </span>
                    </td>
                    <td><strong><?php echo $trans['reference_code']; ?></strong></td>
                    <td><?php echo $trans['detail_description']; ?></td>
                    <td class="text-right">
                        <?php if ($trans['debit'] > 0): ?>
                            <strong style="color: var(--success-color);"><?php echo formatRupiah($trans['debit']); ?></strong>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <?php if ($trans['credit'] > 0): ?>
                            <strong style="color: var(--danger-color);"><?php echo formatRupiah($trans['credit']); ?></strong>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><strong><?php echo formatRupiah($trans['balance_after']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Capital Modal -->
<?php if (hasRole('admin')): ?>
<div id="capitalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Tambah Modal Warehouse</h2>
            <span class="close" onclick="closeCapitalModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_capital">
            
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Tanggal Transaksi *</label>
                <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                <small style="color: var(--text-secondary);">Pilih tanggal sesuai transaksi sebenarnya</small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-money-bill-wave"></i> Jumlah Modal *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required placeholder="Masukkan jumlah modal">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Keterangan *</label>
                <textarea name="description" rows="3" required placeholder="Contoh: Modal awal, Tambahan modal dari investor, dll"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCapitalModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Tambah Modal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-minus-circle"></i> Penarikan Modal</h2>
            <span class="close" onclick="closeWithdrawModal()">&times;</span>
        </div>
        <form method="POST" onsubmit="return confirmWithdraw()">
            <input type="hidden" name="action" value="withdraw_capital">
            
            <div style="background: var(--warning-light); border: 1px solid var(--warning-color); padding: 16px; border-radius: 10px; margin-bottom: 20px;">
                <strong style="color: var(--warning-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Perhatian!
                </strong>
                <p style="margin-top: 8px; font-size: 14px;">
                    Saldo saat ini: <strong><?php echo formatRupiah($current_balance); ?></strong><br>
                    Pastikan jumlah penarikan tidak melebihi saldo yang tersedia.
                </p>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-calendar"></i> Tanggal Transaksi *</label>
                <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                <small style="color: var(--text-secondary);">Pilih tanggal sesuai transaksi sebenarnya</small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-money-bill-wave"></i> Jumlah Penarikan *</label>
                <input type="number" name="amount" id="withdrawAmount" step="0.01" min="0.01" max="<?php echo $current_balance; ?>" required placeholder="Masukkan jumlah yang akan ditarik">
                <small style="color: var(--text-secondary);">Maksimal: <?php echo formatRupiah($current_balance); ?></small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Keperluan/Keterangan *</label>
                <textarea name="description" rows="3" required placeholder="Contoh: Penarikan untuk ekspansi, Pembayaran dividen, dll"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeWithdrawModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-check"></i> Proses Penarikan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.balance-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-lg);
    color: white;
}

.balance-header {
    display: flex;
    align-items: center;
    gap: 16px;
}

.balance-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.balance-info {
    flex: 1;
}

.balance-label {
    font-size: 13px;
    opacity: 0.9;
    margin-bottom: 4px;
    font-weight: 500;
}

.balance-amount {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 2px;
    line-height: 1.2;
}

.balance-date {
    font-size: 12px;
    opacity: 0.8;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.summary-card {
    background: var(--bg-card);
    border-radius: 10px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.summary-card.income {
    border-left: 3px solid var(--success-color);
}

.summary-card.expense {
    border-left: 3px solid var(--danger-color);
}

.summary-card.net {
    border-left: 3px solid var(--info-color);
}

.summary-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.summary-card.income .summary-icon {
    background: var(--success-light);
    color: var(--success-color);
}

.summary-card.expense .summary-icon {
    background: var(--danger-light);
    color: var(--danger-color);
}

.summary-card.net .summary-icon {
    background: var(--info-light);
    color: var(--info-color);
}

.summary-info {
    flex: 1;
    min-width: 0;
}

.summary-label {
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 4px;
    font-weight: 600;
}

.summary-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

.summary-info small {
    font-size: 11px;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .balance-card {
        padding: 16px;
    }
    
    .balance-header {
        gap: 12px;
    }
    
    .balance-icon {
        width: 48px;
        height: 48px;
        font-size: 24px;
    }
    
    .balance-amount {
        font-size: 26px;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .summary-card {
        padding: 14px;
    }
    
    .summary-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .summary-value {
        font-size: 18px;
    }
}
</style>

<script>
function showAddCapitalModal() {
    document.getElementById('capitalModal').style.display = 'block';
}

function closeCapitalModal() {
    document.getElementById('capitalModal').style.display = 'none';
}

function showWithdrawModal() {
    document.getElementById('withdrawModal').style.display = 'block';
}

function closeWithdrawModal() {
    document.getElementById('withdrawModal').style.display = 'none';
}

function confirmWithdraw() {
    const amount = document.getElementById('withdrawAmount').value;
    return confirm(`Anda yakin ingin menarik modal sebesar Rp ${parseFloat(amount).toLocaleString('id-ID')}?`);
}

// Export to Excel (proper XLSX format with better formatting)
function exportToExcel() {
    const table = document.getElementById('financeTable');
    const wb = XLSX.utils.book_new();
    
    // Prepare data from table
    const data = [];
    
    // Add header row
    const headerRow = [];
    table.querySelectorAll('thead th').forEach(th => {
        headerRow.push(th.textContent.trim());
    });
    data.push(headerRow);
    
    // Add data rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            let text = td.textContent.trim();
            // Remove "Rp" and format numbers properly
            if (text.startsWith('Rp ')) {
                text = text.replace('Rp ', '').replace(/\./g, '').replace(',', '.');
                text = parseFloat(text) || text;
            }
            row.push(text);
        });
        if (row.length > 0) {
            data.push(row);
        }
    });
    
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(data);
    
    // Set column widths
    ws['!cols'] = [
        { wch: 15 }, // Tanggal
        { wch: 12 }, // Tipe
        { wch: 18 }, // Referensi
        { wch: 35 }, // Deskripsi
        { wch: 15 }, // Debit
        { wch: 15 }, // Kredit
        { wch: 15 }  // Saldo
    ];
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'Laporan Keuangan');
    
    // Generate filename with current date
    const filename = 'Laporan_Keuangan_' + new Date().toISOString().split('T')[0] + '.xlsx';
    
    // Save file
    XLSX.writeFile(wb, filename);
}

// Load SheetJS library if not already loaded
if (typeof XLSX === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js';
    document.head.appendChild(script);
}

window.onclick = function(event) {
    const modal1 = document.getElementById('capitalModal');
    const modal2 = document.getElementById('withdrawModal');
    if (event.target == modal1) {
        closeCapitalModal();
    } else if (event.target == modal2) {
        closeWithdrawModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
