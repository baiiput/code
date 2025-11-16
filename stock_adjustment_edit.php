<?php
require_once 'config.php';
requireRole('admin');

$conn = getDBConnection();
$user = getCurrentUser();

// Get transaction ID
$adjustment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$adjustment_id) {
    header("Location: stock_adjustment.php");
    exit();
}

// Get adjustment data
$query = "SELECT sa.*, u.full_name 
          FROM stock_adjustment sa
          LEFT JOIN users u ON sa.created_by = u.user_id 
          WHERE sa.adjustment_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $adjustment_id);
$stmt->execute();
$result = $stmt->get_result();
$adjustment = $result->fetch_assoc();

if (!$adjustment) {
    header("Location: stock_adjustment.php");
    exit();
}

// Get adjustment details (single item adjustment)
$adjustment['old_stock'] = $adjustment['old_stock'] ?? 0;
$adjustment['new_stock'] = $adjustment['new_stock'] ?? 0;
$adjustment['reason'] = $adjustment['reason'] ?? '';

// Get all items for dropdown
$items_query = "SELECT * FROM items ORDER BY item_name";
$items = $conn->query($items_query);

$page_title = 'Edit Stock Adjustment';
include 'includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check"></i> Edit Stock Adjustment</h1>
        <a href="stock_adjustment.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="card" style="background: white; border-radius: 10px; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div class="card-body" style="padding: 24px;">
            <form id="adjustmentForm" method="POST" action="stock_adjustment_update.php">
                <input type="hidden" name="adjustment_id" value="<?php echo $adjustment_id; ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label>Kode Adjustment:</label>
                        <input type="text" class="form-control" value="<?php echo $adjustment['transaction_code']; ?>" readonly 
                               style="background: #f5f5f5;">
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal: <span class="required">*</span></label>
                        <input type="datetime-local" name="adjustment_date" class="form-control" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($adjustment['adjustment_date'])); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-box"></i> Barang <span class="required">*</span></label>
                    <select name="item_id" id="itemSelect" class="form-control" required onchange="updateCurrentStock()">
                        <option value="">Pilih Barang</option>
                        <?php while ($item = $items->fetch_assoc()): ?>
                        <option value="<?php echo $item['item_id']; ?>" 
                                data-stock="<?php echo $item['current_stock']; ?>"
                                data-unit="<?php echo $item['unit']; ?>"
                                <?php echo ($item['item_id'] == $adjustment['item_id']) ? 'selected' : ''; ?>>
                            <?php echo $item['item_code'] . ' - ' . $item['item_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label><i class="fas fa-database"></i> Stok Lama (Sistem)</label>
                        <input type="number" name="old_stock" id="oldStock" class="form-control" 
                               value="<?php echo $adjustment['old_stock']; ?>" readonly
                               style="background: #f5f5f5; font-weight: 600;">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-boxes"></i> Stok Baru (Fisik) <span class="required">*</span></label>
                        <input type="number" name="new_stock" id="newStock" class="form-control"
                               value="<?php echo $adjustment['new_stock']; ?>" 
                               step="0.01" min="0" required onchange="calculateDifference()">
                    </div>
                </div>

                <div style="padding: 16px; background: var(--bg-primary, #f8f9fa); border-radius: 10px; margin: 16px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong>Selisih:</strong>
                        <strong id="difference" style="font-size: 20px; color: <?php echo ($adjustment['difference'] >= 0) ? 'green' : 'red'; ?>">
                            <?php echo ($adjustment['difference'] >= 0 ? '+' : '') . $adjustment['difference']; ?>
                        </strong>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Alasan Koreksi <span class="required">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required 
                              placeholder="Jelaskan alasan koreksi stok"><?php echo $adjustment['reason']; ?></textarea>
                </div>

                <div class="form-footer" style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="confirmCancel()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.section-divider {
    border-bottom: 2px solid #3498db;
    padding-bottom: 8px;
    margin-bottom: 16px;
}

.section-divider h4 {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
}

.form-footer {
    border-top: 1px solid #dee2e6;
    padding-top: 20px;
    margin-top: 20px;
}

.required {
    color: #e74c3c;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}
</style>

<script>
function updateCurrentStock() {
    const select = document.getElementById('itemSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const stock = parseFloat(option.dataset.stock) || 0;
        document.getElementById('oldStock').value = stock;
        calculateDifference();
    }
}

function calculateDifference() {
    const oldStock = parseFloat(document.getElementById('oldStock').value) || 0;
    const newStock = parseFloat(document.getElementById('newStock').value) || 0;
    const difference = newStock - oldStock;
    
    const diffElement = document.getElementById('difference');
    diffElement.textContent = (difference >= 0 ? '+' : '') + difference.toFixed(2);
    diffElement.style.color = difference >= 0 ? 'green' : 'red';
}

function confirmCancel() {
    if (confirm('Batalkan perubahan? Data yang sudah diinput akan hilang.')) {
        window.location.href = 'stock_adjustment.php';
    }
}

// Form validation
document.getElementById('adjustmentForm').addEventListener('submit', function(e) {
    if (!confirm('Update Stock Adjustment ini?')) {
        e.preventDefault();
        return false;
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCurrentStock();
});
</script>

<?php include 'includes/footer.php'; ?>
