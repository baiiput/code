<?php
require_once 'config.php';
requireRole('admin'); // Only admin can reset

$conn = getDBConnection();
$user = getCurrentUser();

$page_title = 'Reset Data - Complete';

// Handle reset action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $confirm_code = $_POST['confirm_code'] ?? '';

    // Security: require confirmation code
    if ($confirm_code !== 'RESET123') {
        $_SESSION['error_message'] = 'Kode konfirmasi salah! Tidak dapat melakukan reset.';
        header('Location: admin_reset_complete.php');
        exit;
    }

    $conn->begin_transaction();
    try {
        if ($action === 'reset_transactions_only') {
            // Reset transaksi saja, KEEP warehouse_items
            $conn->query("DELETE FROM stock_in_detail");
            $conn->query("DELETE FROM stock_out_detail");
            $conn->query("DELETE FROM stock_transfer_detail");
            $conn->query("DELETE FROM stock_in");
            $conn->query("DELETE FROM stock_out");
            $conn->query("DELETE FROM stock_transfers");
            $conn->query("DELETE FROM stock_adjustment");
            $conn->query("DELETE FROM financial_transactions");
            $conn->query("UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1");

            // Reset auto increment
            $conn->query("ALTER TABLE stock_in AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE stock_out AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE stock_transfers AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE stock_adjustment AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE financial_transactions AUTO_INCREMENT = 1");

            logActivity('DELETE', 'system', 'Reset all transactions (keep warehouse_items)');
            $success_msg = 'Transaksi berhasil direset! Warehouse items tetap dipertahankan.';

        } elseif ($action === 'reset_all_complete') {
            // Reset SEMUA termasuk warehouse_items
            $conn->query("DELETE FROM stock_in_detail");
            $conn->query("DELETE FROM stock_out_detail");
            $conn->query("DELETE FROM stock_transfer_detail");
            $conn->query("DELETE FROM stock_in");
            $conn->query("DELETE FROM stock_out");
            $conn->query("DELETE FROM stock_transfers");
            $conn->query("DELETE FROM stock_adjustment");
            $conn->query("DELETE FROM financial_transactions");
            $conn->query("DELETE FROM warehouse_items"); // <-- INI YANG PENTING!
            $conn->query("UPDATE warehouse_balance SET balance_amount = 0 WHERE balance_id = 1");

            // Reset auto increment
            $conn->query("ALTER TABLE stock_in AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE stock_out AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE stock_transfers AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE stock_adjustment AUTO_INCREMENT = 1");
            $conn->query("ALTER TABLE financial_transactions AUTO_INCREMENT = 1");

            logActivity('DELETE', 'system', 'Complete reset - transactions AND warehouse_items');
            $success_msg = 'COMPLETE RESET berhasil! Semua transaksi dan warehouse items sudah bersih.';

        } elseif ($action === 'reset_warehouse_items_only') {
            // Reset warehouse_items saja
            $conn->query("DELETE FROM warehouse_items");

            logActivity('DELETE', 'system', 'Reset warehouse_items only');
            $success_msg = 'Warehouse items berhasil direset!';
        }

        $conn->commit();
        $_SESSION['success_message'] = $success_msg;
        header('Location: admin_reset_complete.php');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        header('Location: admin_reset_complete.php');
        exit;
    }
}

// Get statistics
$stats = [];
$stats['stock_in'] = $conn->query("SELECT COUNT(*) as count FROM stock_in")->fetch_assoc()['count'];
$stats['stock_out'] = $conn->query("SELECT COUNT(*) as count FROM stock_out")->fetch_assoc()['count'];
$stats['stock_transfer'] = $conn->query("SELECT COUNT(*) as count FROM stock_transfers")->fetch_assoc()['count'];
$stats['stock_adjustment'] = $conn->query("SELECT COUNT(*) as count FROM stock_adjustment")->fetch_assoc()['count'];
$stats['financial'] = $conn->query("SELECT COUNT(*) as count FROM financial_transactions")->fetch_assoc()['count'];
$stats['warehouse_items'] = $conn->query("SELECT COUNT(*) as count FROM warehouse_items")->fetch_assoc()['count'];
$stats['balance'] = $conn->query("SELECT balance_amount FROM warehouse_balance WHERE balance_id = 1")->fetch_assoc()['balance_amount'];

include 'includes/header.php';

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-exclamation-triangle"></i> Reset Data - Complete</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="alert alert-danger">
        <i class="fas fa-skull-crossbones"></i>
        <strong>BAHAYA! ZONA BERBAHAYA!</strong><br>
        Tool ini akan menghapus data secara PERMANEN dan TIDAK DAPAT dikembalikan!<br>
        <strong>WAJIB BACKUP DATABASE SEBELUM MELAKUKAN RESET!</strong>
    </div>

    <!-- Statistics -->
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px;">📊 Data Saat Ini</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px;">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Stock IN</div>
                <div style="font-size: 28px; font-weight: 700; color: #3b82f6;"><?php echo $stats['stock_in']; ?></div>
            </div>
            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px;">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Stock OUT</div>
                <div style="font-size: 28px; font-weight: 700; color: #f59e0b;"><?php echo $stats['stock_out']; ?></div>
            </div>
            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px;">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Transfer</div>
                <div style="font-size: 28px; font-weight: 700; color: #10b981;"><?php echo $stats['stock_transfer']; ?></div>
            </div>
            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px;">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Adjustment</div>
                <div style="font-size: 28px; font-weight: 700; color: #8b5cf6;"><?php echo $stats['stock_adjustment']; ?></div>
            </div>
            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px;">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Financial</div>
                <div style="font-size: 28px; font-weight: 700; color: #ec4899;"><?php echo $stats['financial']; ?></div>
            </div>
            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px;">
                <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">Warehouse Items</div>
                <div style="font-size: 28px; font-weight: 700; color: #ef4444;"><?php echo $stats['warehouse_items']; ?></div>
            </div>
        </div>
        <div style="margin-top: 20px; padding: 16px; background: var(--primary-light); border-radius: 8px;">
            <strong>Saldo Warehouse:</strong>
            <span style="font-size: 24px; font-weight: 700; color: var(--primary-color); margin-left: 10px;">
                <?php echo formatRupiah($stats['balance']); ?>
            </span>
        </div>
    </div>

    <!-- Reset Options -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">

        <!-- Option 1: Reset Transactions Only -->
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="color: #f59e0b; margin-bottom: 16px;">
                <i class="fas fa-sync"></i> Reset Transaksi Saja
            </h3>
            <p style="color: #64748b; margin-bottom: 16px;">
                Hapus semua transaksi (IN, OUT, Transfer, Adjustment) dan financial.<br>
                <strong>Warehouse items TETAP dipertahankan.</strong>
            </p>
            <div style="background: #fef3c7; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                <strong>Yang dihapus:</strong><br>
                ✅ Stock IN/OUT/Transfer<br>
                ✅ Adjustment<br>
                ✅ Financial transactions<br>
                ❌ Warehouse items (TIDAK dihapus)
            </div>
            <button class="btn btn-warning" onclick="showResetModal('reset_transactions_only')" style="width: 100%;">
                <i class="fas fa-sync"></i> Reset Transaksi
            </button>
        </div>

        <!-- Option 2: Reset Warehouse Items Only -->
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="color: #ef4444; margin-bottom: 16px;">
                <i class="fas fa-boxes"></i> Reset Warehouse Items Saja
            </h3>
            <p style="color: #64748b; margin-bottom: 16px;">
                Hapus semua data di warehouse_items.<br>
                <strong>Transaksi TETAP dipertahankan.</strong>
            </p>
            <div style="background: #fee2e2; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                <strong>Yang dihapus:</strong><br>
                ✅ Warehouse items<br>
                ❌ Transaksi (TIDAK dihapus)
            </div>
            <button class="btn btn-danger" onclick="showResetModal('reset_warehouse_items_only')" style="width: 100%;">
                <i class="fas fa-boxes"></i> Reset WH Items
            </button>
        </div>

        <!-- Option 3: Complete Reset -->
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border: 3px solid #ef4444;">
            <h3 style="color: #ef4444; margin-bottom: 16px;">
                <i class="fas fa-bomb"></i> COMPLETE RESET
            </h3>
            <p style="color: #64748b; margin-bottom: 16px;">
                Hapus <strong>SEMUA</strong> transaksi DAN warehouse items.<br>
                <strong style="color: #ef4444;">Mulai dari NOL lagi!</strong>
            </p>
            <div style="background: #fee2e2; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                <strong>Yang dihapus:</strong><br>
                ✅ Stock IN/OUT/Transfer<br>
                ✅ Adjustment<br>
                ✅ Financial transactions<br>
                ✅ Warehouse items<br>
                ✅ Saldo warehouse → 0
            </div>
            <button class="btn btn-danger" onclick="showResetModal('reset_all_complete')" style="width: 100%;">
                <i class="fas fa-bomb"></i> COMPLETE RESET
            </button>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="resetModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Konfirmasi Reset</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" id="resetAction">

            <div class="alert alert-danger">
                <i class="fas fa-skull-crossbones"></i>
                <strong>PERINGATAN KERAS!</strong><br>
                Data yang dihapus <strong>TIDAK DAPAT</strong> dikembalikan!<br>
                Pastikan Anda sudah <strong>BACKUP DATABASE!</strong>
            </div>

            <div style="background: var(--bg-primary); padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <strong>Anda akan melakukan:</strong>
                <div id="resetDescription" style="margin-top: 10px; font-size: 15px;"></div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-key"></i> Kode Konfirmasi *</label>
                <input type="text" name="confirm_code" placeholder="Ketik: RESET123" required style="font-family: monospace; font-size: 16px; text-align: center;">
                <small style="color: var(--text-secondary);">
                    Ketik <strong>RESET123</strong> untuk konfirmasi
                </small>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="confirmUnderstand" required>
                    <span>Saya mengerti konsekuensinya dan sudah backup database</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-bomb"></i> YA, RESET SEKARANG!
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const resetDescriptions = {
    'reset_transactions_only': 'Reset semua transaksi, KEEP warehouse items',
    'reset_warehouse_items_only': 'Reset warehouse items saja, KEEP transaksi',
    'reset_all_complete': 'COMPLETE RESET - Hapus SEMUA data!'
};

function showResetModal(action) {
    document.getElementById('resetAction').value = action;
    document.getElementById('resetDescription').textContent = resetDescriptions[action];
    document.getElementById('confirmUnderstand').checked = false;
    document.querySelector('input[name="confirm_code"]').value = '';
    document.getElementById('resetModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('resetModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('resetModal')) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
